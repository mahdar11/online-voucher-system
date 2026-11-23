<?php

namespace Tests\Modules\Inventory;

use Tests\Support\AdminTestCase;
use App\Modules\Inventory\Enums\BatchSource;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Inventory\Models\VoucherBatch;
use App\Modules\Inventory\Services\VoucherCodeGenerator;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

class VoucherStockTest extends AdminTestCase
{
    public function test_generate_creates_unique_codes_with_prefix_and_length(): void
    {
        $package = $this->package();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/voucher/generate', [
                'package_id' => $package->id,
                'quantity' => 200,
                'length' => 8,
                'prefix' => 'vc-',
                'password_mode' => 'none',
            ])
            ->assertRedirect("/admin/voucher?package={$package->id}")
            ->assertSessionHas('success');

        $codes = Voucher::query()->where('package_id', $package->id)->pluck('code');

        $this->assertCount(200, $codes);
        $this->assertCount(200, $codes->unique());

        $pattern = '/^VC-['.preg_quote(VoucherCodeGenerator::ALPHABET, '/').']{8}$/';
        $codes->each(fn (string $code) => $this->assertMatchesRegularExpression($pattern, $code));

        $batch = VoucherBatch::query()->sole();
        $this->assertSame(BatchSource::Generate, $batch->source);
        $this->assertSame(200, $batch->quantity);
        $this->assertSame($admin->id, $batch->created_by);
        $this->assertSame(0, Voucher::query()->whereNotNull('password')->count());
    }

    public function test_generate_password_modes(): void
    {
        $package = $this->package();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/voucher/generate', [
            'package_id' => $package->id, 'quantity' => 5, 'length' => 6, 'password_mode' => 'same',
        ]);
        Voucher::query()->get()->each(fn (Voucher $v) => $this->assertSame($v->code, $v->password));

        Voucher::query()->delete();

        $this->actingAs($admin)->post('/admin/voucher/generate', [
            'package_id' => $package->id, 'quantity' => 5, 'length' => 6, 'password_mode' => 'random',
        ]);
        Voucher::query()->get()->each(fn (Voucher $v) => $this->assertSame(6, strlen($v->password)));
    }

    public function test_generate_validation_limits(): void
    {
        $package = $this->package();

        $this->actingAs($this->admin())
            ->post('/admin/voucher/generate', [
                'package_id' => $package->id,
                'quantity' => 1001,
                'length' => 5,
                'prefix' => 'A B',
                'password_mode' => 'x',
            ])
            ->assertSessionHasErrors(['quantity', 'length', 'prefix', 'password_mode']);

        $this->assertDatabaseCount('vouchers', 0);
    }

    public function test_generator_skips_codes_already_in_package(): void
    {
        $package = $this->package();
        Voucher::query()->create(['package_id' => $package->id, 'code' => '222222']);

        $codes = app(VoucherCodeGenerator::class)->uniqueCodes($package->id, 50, 6);

        $this->assertCount(50, array_unique($codes));
        $this->assertNotContains('222222', $codes);
        $this->assertContainsOnly('string', $codes);
    }

    public function test_import_inserts_valid_rows_and_skips_duplicates_and_invalid(): void
    {
        $package = $this->package();
        Voucher::query()->create(['package_id' => $package->id, 'code' => 'EXIST1']);

        $csv = "\xEF\xBB\xBFcode,password\n"
            ."NEW001,pass1\n"
            ."NEW002,\n"
            ."123456,\n"     // kode berupa angka saja
            ."NEW001,lagi\n" // duplikat di file
            ."EXIST1,\n"     // sudah ada di database
            ."ab,\n"         // terlalu pendek
            ."BAD CODE,\n"   // ada spasi
            ."\n";

        $this->actingAs($this->admin())
            ->post('/admin/voucher/import', [
                'package_id' => $package->id,
                'file' => UploadedFile::fake()->createWithContent('stok.csv', $csv),
                'note' => 'supplier',
            ])
            ->assertRedirect('/admin/batch')
            ->assertSessionHas('success', 'Import selesai: 3 voucher masuk, 2 duplikat dilewati, 2 baris tidak valid.')
            ->assertSessionHas('details');

        $this->assertEqualsCanonicalizing(
            ['EXIST1', 'NEW001', 'NEW002', '123456'],
            Voucher::query()->where('package_id', $package->id)->pluck('code')->all(),
        );
        $this->assertSame('pass1', Voucher::query()->where('code', 'NEW001')->value('password'));

        $batch = VoucherBatch::query()->sole();
        $this->assertSame(BatchSource::Import, $batch->source);
        $this->assertSame('stok.csv', $batch->file_name);
        $this->assertSame(3, $batch->quantity);
    }

    public function test_import_supports_semicolon_without_header(): void
    {
        $package = $this->package();

        $this->actingAs($this->admin())->post('/admin/voucher/import', [
            'package_id' => $package->id,
            'file' => UploadedFile::fake()->createWithContent('excel.csv', "AAA111;p1\r\nBBB222;p2\r\n"),
        ])->assertSessionHasNoErrors();

        $this->assertSame('p2', Voucher::query()->where('code', 'BBB222')->value('password'));
    }

    public function test_import_rejects_file_without_new_codes(): void
    {
        $package = $this->package();

        $this->actingAs($this->admin())
            ->post('/admin/voucher/import', [
                'package_id' => $package->id,
                'file' => UploadedFile::fake()->createWithContent('kosong.csv', "code,password\nab,\n"),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('voucher_batches', 0);
    }

    public function test_import_rejects_wrong_file_type(): void
    {
        $package = $this->package();

        $this->actingAs($this->admin())
            ->post('/admin/voucher/import', [
                'package_id' => $package->id,
                'file' => UploadedFile::fake()->create('foto.png', 10, 'image/png'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_available_voucher_can_be_deleted_but_sold_cannot(): void
    {
        $package = $this->package();
        $available = Voucher::query()->create(['package_id' => $package->id, 'code' => 'FREE01']);
        $sold = Voucher::query()->create(['package_id' => $package->id, 'code' => 'SOLD01', 'status' => VoucherStatus::Sold]);
        $admin = $this->admin();

        $this->actingAs($admin)->delete("/admin/voucher/{$available->id}")->assertSessionHas('success');
        $this->actingAs($admin)->delete("/admin/voucher/{$sold->id}")->assertSessionHas('error');

        $this->assertModelMissing($available);
        $this->assertModelExists($sold);
    }

    public function test_voucher_index_filters(): void
    {
        $a = $this->package();
        $b = $this->package();
        Voucher::query()->create(['package_id' => $a->id, 'code' => 'ALPHA1']);
        Voucher::query()->create(['package_id' => $a->id, 'code' => 'BRAVO1', 'status' => VoucherStatus::Sold]);
        Voucher::query()->create(['package_id' => $b->id, 'code' => 'ALPHA2']);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get("/admin/voucher?package={$a->id}&status=available")
            ->assertInertia(fn (Assert $page) => $page
                ->where('vouchers.total', 1)
                ->where('vouchers.data.0.code', 'ALPHA1'));

        $this->actingAs($admin)
            ->get('/admin/voucher?search=alpha')
            ->assertInertia(fn (Assert $page) => $page->where('vouchers.total', 2));
    }
}
