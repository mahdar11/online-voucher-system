<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Inventory\Models\Voucher;

class PackageHasVouchers implements PackageUsageCheck
{
    public function usageReason(int $packageId): ?string
    {
        return Voucher::query()->where('package_id', $packageId)->exists()
            ? 'sudah punya voucher'
            : null;
    }
}
