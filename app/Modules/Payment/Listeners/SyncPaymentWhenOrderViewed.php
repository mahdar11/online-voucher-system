<?php

namespace App\Modules\Payment\Listeners;

use App\Modules\Ordering\Events\OrderViewed;
use App\Modules\Payment\Actions\SyncPayment;

class SyncPaymentWhenOrderViewed
{
    public function __construct(private readonly SyncPayment $syncPayment) {}

    public function handle(OrderViewed $event): void
    {
        ($this->syncPayment)($event->invoiceNumber);
    }
}
