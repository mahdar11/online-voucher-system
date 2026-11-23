<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('voucher.super_admin');

        if (blank($config['email']) || blank($config['password'])) {
            $this->command?->warn('SUPER_ADMIN_EMAIL / SUPER_ADMIN_PASSWORD belum diisi di .env, super admin dilewati.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $config['email']],
            [
                'name' => $config['name'],
                'password' => $config['password'],
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([Role::SuperAdmin->value]);
    }
}
