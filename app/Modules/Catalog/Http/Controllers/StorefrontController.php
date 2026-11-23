<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Data\PackageData;
use App\Modules\Catalog\Data\PackageStock;
use App\Modules\Catalog\Data\StockAvailability;
use App\Modules\Catalog\Data\StorefrontPackage;
use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Shared\Settings\SiteSettings;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman publik toko voucher: katalog paket yang sedang dijual.
 */
class StorefrontController
{
    public function home(PackageStockReader $stockReader, SiteSettings $settings): Response
    {
        $packages = VoucherPackage::query()->active()->ordered()->get()
            ->map(fn (VoucherPackage $package) => $package->toData());

        $stock = $stockReader->stockFor($packages->map(fn (PackageData $p) => $p->id)->all());

        return Inertia::render('Catalog/Home', [
            'announcement' => $settings->announcement(),
            'packages' => $packages->map(fn (PackageData $package) => StorefrontPackage::make(
                $package,
                StockAvailability::from($stock[$package->id] ?? new PackageStock),
            ))->values(),
        ]);
    }
}
