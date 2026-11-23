<?php

namespace App\Modules\Payment;

use App\Modules\Ordering\Contracts\PaymentHistoryReader;
use App\Modules\Ordering\Events\OrderRefunded;
use App\Modules\Ordering\Events\OrderViewed;
use App\Modules\Payment\Listeners\MarkPaymentRefunded;
use App\Modules\Payment\Contracts\PaymentStats;
use App\Modules\Payment\Services\EloquentPaymentStats;
use App\Modules\Payment\Services\PaymentHistory;
use App\Modules\Payment\Gateway\PaymentGateway;
use App\Modules\Payment\Gateway\XenditGateway;
use App\Modules\Payment\Listeners\SyncPaymentWhenOrderViewed;
use App\Modules\Shared\ModuleServiceProvider;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Alias tetap untuk kolom polymorphic (bukan nama class), agar data tidak
        // rusak bila class dipindah. Lihat migration normalize_morph_types.
        Relation::enforceMorphMap([
            'payment' => Payment::class,
        ]);
        $this->app->bind(PaymentGateway::class, fn () => new XenditGateway(config('services.xendit')));
        $this->app->bind(PaymentHistoryReader::class, PaymentHistory::class);
        $this->app->bind(PaymentStats::class, EloquentPaymentStats::class);
    }

    protected function bootModule(): void
    {
        Event::listen(OrderViewed::class, SyncPaymentWhenOrderViewed::class);
        Event::listen(OrderRefunded::class, MarkPaymentRefunded::class);
    }
}
