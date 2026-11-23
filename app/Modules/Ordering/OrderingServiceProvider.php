<?php

namespace App\Modules\Ordering;

use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Ordering\Services\PackageHasOrders;
use App\Modules\Shared\ModuleServiceProvider;

/**
 * Checkout dan pesanan. Fiturnya dibangun di tahap 3.
 */
class OrderingServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        $this->app->tag([PackageHasOrders::class], PackageUsageCheck::TAG);
    }
}
