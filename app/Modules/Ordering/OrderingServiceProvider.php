<?php

namespace App\Modules\Ordering;

use App\Modules\Catalog\Contracts\PackageUsageCheck;
use App\Modules\Ordering\Console\ExpireOrdersCommand;
use App\Modules\Ordering\Contracts\OrderPayments;
use App\Modules\Identity\Events\EmailOwnershipConfirmed;
use App\Modules\Ordering\Listeners\ClaimGuestOrders;
use App\Modules\Ordering\Listeners\SendOrderEmails;
use App\Modules\Ordering\Services\OrderPaymentsService;
use App\Modules\Ordering\Services\PackageHasOrders;
use App\Modules\Shared\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
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
        $this->app->bind(OrderPayments::class, OrderPaymentsService::class);
    }

    protected function bootModule(): void
    {
        // Membatasi penimbunan stok oleh bot: maksimal 5 checkout per menit per IP.
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(self::CHECKOUTS_PER_MINUTE)
            ->by($request->ip())
            ->response(fn () => back()->with('error', 'Terlalu banyak percobaan checkout. Coba lagi dalam satu menit.')));

        // Mencegah menebak-nebak nomor invoice di "Cek Pesanan".
        RateLimiter::for('order-lookup', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => back()->withErrors(['invoice_number' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.'])));

        if ($this->app->runningInConsole()) {
            $this->commands([ExpireOrdersCommand::class]);
        }

        Event::subscribe(SendOrderEmails::class);
        Event::listen(EmailOwnershipConfirmed::class, ClaimGuestOrders::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('orders:expire')->everyMinute()->withoutOverlapping();
        });
    }
}
