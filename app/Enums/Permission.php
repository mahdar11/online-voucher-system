<?php

namespace App\Enums;

enum Permission: string
{
    case ManageUsers = 'manage users';
    case ManageSettings = 'manage settings';
    case ManagePackages = 'manage packages';
    case ManageVouchers = 'manage vouchers';
    case ManageOrders = 'manage orders';
    case ViewReports = 'view reports';
}
