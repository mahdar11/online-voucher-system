<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Data\PackageData;
use App\Modules\Catalog\Data\PackageStock;
use App\Modules\Ordering\Contracts\SalesReport;
use App\Modules\Ordering\Data\SalesPeriod;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard admin menggabungkan data dari beberapa modul, jadi tinggal di
 * lapisan aplikasi (shell) dan hanya memakai kontrak publik modul.
 */
class DashboardController
{
    public function __invoke(PackageCatalog $catalog, PackageStockReader $stockReader, SalesReport $sales): Response
    {
        $packages = $catalog->all();
        $stock = $stockReader->stockFor(array_map(fn (PackageData $p) => $p->id, $packages));

        $rows = array_map(fn (PackageData $package) => [
            'id' => $package->id,
            'name' => $package->name,
            'price' => $package->price,
            'is_active' => $package->isActive,
            ...($stock[$package->id] ?? new PackageStock)->toArray(),
        ], $packages);

        $sum = fn (string $key) => array_sum(array_column($rows, $key));
        $today = now((string) config('voucher.business_timezone'))->toDateString();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'active_packages' => count(array_filter($packages, fn (PackageData $p) => $p->isActive)),
                'available' => $sum('available_count'),
                'reserved' => $sum('reserved_count'),
                'sold' => $sum('sold_count'),
            ],
            'today' => $sales->summary(SalesPeriod::days($today, $today)),
            'attention' => $sales->attentionCounts(),
            'packages' => $rows,
        ]);
    }
}
