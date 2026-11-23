<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Ordering\Models\OrderItem;

class PackageHasOrders implements PackageUsageCheck
{
    public function usageReason(int $packageId): ?string
    {
        return OrderItem::query()->where('package_id', $packageId)->exists()
            ? 'sudah pernah dipesan'
            : null;
    }
}
