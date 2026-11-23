<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Data\PackageStock;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;

/**
 * Satu-satunya tempat menghitung stok voucher per paket.
 */
class StockCounter implements PackageStockReader
{
    public function stockFor(array $packageIds): array
    {
        if ($packageIds === []) {
            return [];
        }

        $rows = Voucher::query()
            ->selectRaw('package_id, status, count(*) as total')
            ->whereIn('package_id', $packageIds)
            ->groupBy('package_id', 'status')
            ->toBase()
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $status = VoucherStatus::from($row->status);
            $key = match ($status) {
                VoucherStatus::Available => 'available',
                VoucherStatus::Reserved => 'reserved',
                VoucherStatus::Sold, VoucherStatus::Used => 'sold',
                VoucherStatus::Expired => null,
            };

            if ($key !== null) {
                $counts[$row->package_id][$key] = ($counts[$row->package_id][$key] ?? 0) + (int) $row->total;
            }
        }

        return array_map(fn (array $c) => new PackageStock(
            available: $c['available'] ?? 0,
            reserved: $c['reserved'] ?? 0,
            sold: $c['sold'] ?? 0,
        ), $counts);
    }
}
