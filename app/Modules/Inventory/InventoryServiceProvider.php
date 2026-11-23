<?php

namespace App\Modules\Inventory;

use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Inventory\Services\PackageHasVouchers;
use App\Modules\Inventory\Services\StockCounter;
use App\Modules\Shared\ModuleServiceProvider;

class InventoryServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        // Inventory menyediakan implementasi untuk port milik Catalog.
        $this->app->bind(PackageStockReader::class, StockCounter::class);
        $this->app->tag([PackageHasVouchers::class], PackageUsageCheck::TAG);
    }
}
