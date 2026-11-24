<?php

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Enums\Permission as PermissionEnum;
use App\Modules\Identity\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // DatabaseSeeder memakai WithoutModelEvents, jadi cache Spatie tidak
        // otomatis di-reset setelah permission dibuat.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');

            $role->syncPermissions(array_map(
                fn (PermissionEnum $permission) => $permission->value,
                $roleEnum->permissions(),
            ));
        }
    }
}
