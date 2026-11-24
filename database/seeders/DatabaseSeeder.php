<?php

namespace Database\Seeders;

use App\Modules\Identity\Database\Seeders\DemoUserSeeder;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(DemoUserSeeder::class);
        }
    }
}
