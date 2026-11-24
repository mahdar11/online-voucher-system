<?php

namespace App\Modules\Identity\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Customer => 'Customer',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staff(), true);
    }

    /**
     * Role yang boleh masuk area admin.
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::SuperAdmin, self::Admin];
    }

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::staff());
    }

    /**
     * Permission yang dimiliki tiap role. Super admin mendapat semua permission.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::Admin => [
                Permission::ManagePackages,
                Permission::ManageVouchers,
                Permission::ManageOrders,
                Permission::ViewReports,
            ],
            self::Customer => [],
        };
    }
}
