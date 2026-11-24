<?php

namespace Tests\Modules\Inventory;

use App\Modules\Inventory\Contracts\InsufficientStockException;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menguji SKIP LOCKED dengan dua koneksi database sungguhan, seperti dua
 * pembeli yang checkout di saat bersamaan.
 *
 * Data uji di-commit lewat koneksi kedua (agar terlihat oleh keduanya) lalu
 * dihapus lagi setelah transaksi test di-rollback. Tabel lain tidak disentuh.
 */
class ReservationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private const OTHER = 'pgsql_concurrent_buyer';

    private ConnectionInterface $other;

    private int $packageId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::OTHER => config('database.connections.pgsql')]);
        $this->other = DB::connection(self::OTHER);

        $this->packageId = $this->other->table('voucher_packages')->insertGetId([
            'name' => 'Paket Konkurensi', 'slug' => 'paket-konkurensi-'.uniqid(), 'price' => 1000,
            'duration_minutes' => 60, 'is_active' => true, 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['A1', 'A2', 'A3', 'A4'] as $code) {
            $this->other->table('vouchers')->insert([
                'package_id' => $this->packageId, 'code' => "KONKUREN{$code}", 'status' => 'available',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Dijalankan setelah rollback transaksi test (didaftarkan setelah RefreshDatabase).
        $this->beforeApplicationDestroyed(function () {
            if ($this->other->transactionLevel() > 0) {
                $this->other->rollBack();
            }
            $this->other->table('vouchers')->where('package_id', $this->packageId)->delete();
            $this->other->table('voucher_packages')->where('id', $this->packageId)->delete();
            DB::purge(self::OTHER);
        });
    }

    private function reserve(int $quantity, string $reference): array
    {
        return app(VoucherReservations::class)->reserve($this->packageId, $quantity, $reference, now()->addMinutes(15));
    }

    public function test_concurrent_checkouts_never_get_the_same_voucher(): void
    {
        // Pembeli pertama sedang checkout dan mengunci 2 voucher pertama (belum commit).
        $this->other->beginTransaction();
        $lockedByOther = array_column($this->other->select(
            "select id from vouchers where package_id = ? and status = 'available' order by id limit 2 for update skip locked",
            [$this->packageId],
        ), 'id');

        // Pembeli kedua checkout bersamaan: tidak menunggu, dan mendapat voucher lain.
        $reserved = $this->reserve(2, 'INV-KEDUA');

        $this->assertCount(2, $lockedByOther);
        $this->assertCount(2, $reserved);
        $this->assertSame([], array_intersect($lockedByOther, $reserved));

        // Pembeli ketiga: semua voucher sedang dikunci/dipesan, jadi stok dianggap habis.
        try {
            $this->reserve(1, 'INV-KETIGA');
            $this->fail('Seharusnya stok dianggap habis selama dikunci transaksi lain.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame(0, $exception->available);
        }

        // Pembeli pertama batal (rollback): voucher yang dikuncinya bisa dipesan lagi.
        $this->other->rollBack();

        $again = $this->reserve(2, 'INV-KETIGA');
        $this->assertEqualsCanonicalizing($lockedByOther, $again);
        $this->assertSame(4, Voucher::query()->where('package_id', $this->packageId)->where('status', VoucherStatus::Reserved)->count());
    }
}
