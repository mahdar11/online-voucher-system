<?php

namespace Tests\Support;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ModuleTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Role di-seed per test (ikut di-rollback), supaya tidak bergantung pada
     * urutan test atau pada seed saat database pertama kali dimigrasi.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    protected function admin(): User
    {
        return $this->userWithRole(Role::Admin);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function package(array $attributes = []): VoucherPackage
    {
        static $counter = 0;
        $counter++;

        return VoucherPackage::query()->create([
            'name' => "Paket {$counter}",
            'slug' => "paket-{$counter}",
            'price' => 5000,
            'duration_minutes' => 60,
            ...$attributes,
        ]);
    }

    /**
     * Membuat sejumlah voucher untuk sebuah paket.
     *
     * @return list<int> id voucher
     */
    protected function stock(VoucherPackage $package, int $count, VoucherStatus $status = VoucherStatus::Available): array
    {
        static $sequence = 0;

        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $sequence++;
            $ids[] = Voucher::query()->create([
                'package_id' => $package->id,
                'code' => sprintf('TEST%06d', $sequence),
                'status' => $status,
            ])->id;
        }

        return $ids;
    }

    protected function customer(): User
    {
        return $this->userWithRole(Role::Customer);
    }
}
