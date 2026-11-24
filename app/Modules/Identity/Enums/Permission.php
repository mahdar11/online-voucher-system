<?php

namespace App\Modules\Identity\Enums;

enum Permission: string
{
    case ManageUsers = 'manage users';
    case ManageSettings = 'manage settings';
    case ManagePackages = 'manage packages';
    case ManageVouchers = 'manage vouchers';
    case ManageOrders = 'manage orders';
    case ViewReports = 'view reports';

    /**
     * Nama untuk frontend, contoh: ManagePackages -> "managePackages".
     */
    public function key(): string
    {
        return lcfirst($this->name);
    }

    /**
     * Middleware route, contoh: "permission:manage packages".
     */
    public function middleware(): string
    {
        return 'permission:'.$this->value;
    }
}
