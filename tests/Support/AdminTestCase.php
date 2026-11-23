<?php

namespace Tests\Support;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

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
}
