<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Customer = 'customer';

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
