<?php

namespace App\Modules\Payment;

use App\Modules\Ordering\Events\OrderViewed;
use App\Modules\Payment\Gateway\PaymentGateway;
use App\Modules\Payment\Gateway\XenditGateway;
use App\Modules\Payment\Listeners\SyncPaymentWhenOrderViewed;
use App\Modules\Shared\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * Pembayaran lewat Xendit Invoice: memulai tagihan, webhook, dan cek status.
 */
class PaymentServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => new XenditGateway(config('services.xendit')));
    }

    protected function bootModule(): void
    {
        Event::listen(OrderViewed::class, SyncPaymentWhenOrderViewed::class);
    }
}
