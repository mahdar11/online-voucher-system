<?php

namespace App\Modules\Ordering;

use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Ordering\Console\ExpireOrdersCommand;
use App\Modules\Ordering\Services\PackageHasOrders;
use App\Modules\Shared\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Checkout, pesanan, pembatalan, dan kedaluwarsa pesanan.
 */
class OrderingServiceProvider extends ModuleServiceProvider
{
    public const CHECKOUTS_PER_MINUTE = 5;

    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        $this->app->tag([PackageHasOrders::class], PackageUsageCheck::TAG);
    }

    protected function bootModule(): void
    {
        // Membatasi penimbunan stok oleh bot: maksimal 5 checkout per menit per IP.
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(self::CHECKOUTS_PER_MINUTE)
            ->by($request->ip())
            ->response(fn () => back()->with('error', 'Terlalu banyak percobaan checkout. Coba lagi dalam satu menit.')));

        if ($this->app->runningInConsole()) {
            $this->commands([ExpireOrdersCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('orders:expire')->everyMinute()->withoutOverlapping();
        });
    }
}
