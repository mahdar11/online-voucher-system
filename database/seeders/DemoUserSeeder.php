<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Membuat akun demo admin dan customer. Hanya berjalan saat APP_ENV=local
     * supaya akun demo tidak pernah terbuat di server produksi.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->warn('DemoUserSeeder dilewati: hanya berjalan saat APP_ENV=local.');

            return;
        }

        $accounts = [
            'admin' => Role::Admin,
            'customer' => Role::Customer,
        ];

        foreach ($accounts as $key => $role) {
            $config = config("voucher.demo_users.{$key}");

            if (blank($config['email']) || blank($config['password'])) {
                $this->command?->warn("Akun demo {$key} dilewati: email/password belum diisi di .env.");

                continue;
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

            $user->syncRoles([$role->value]);

            $this->command?->info("Akun demo {$role->value}: {$user->email}");
        }
    }
}
