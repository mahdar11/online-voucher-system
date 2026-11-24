<?php

namespace App\Modules\Payment\Listeners;

use App\Modules\Ordering\Events\OrderRefunded;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;

/**
 * Pembayaran untuk pesanan yang direfund tidak lagi dihitung sebagai uang masuk.
 */
class MarkPaymentRefunded
{
    public function handle(OrderRefunded $event): void
    {
        Payment::query()
            ->forInvoice($event->invoiceNumber)
            ->where('status', PaymentStatus::Paid)
            ->update(['status' => PaymentStatus::Refunded]);
    }
}
