<?php

namespace App\Modules\Payment\Services;

use App\Modules\Ordering\Contracts\PaymentHistoryReader;
use App\Modules\Ordering\Data\PaymentAttempt;
use App\Modules\Payment\Models\Payment;

class PaymentHistory implements PaymentHistoryReader
{
    public function attemptsFor(string $invoiceNumber): array
    {
        return Payment::query()
            ->forInvoice($invoiceNumber)
            ->latest('id')
            ->get()
            ->map(fn (Payment $payment) => new PaymentAttempt(
                reference: $payment->external_id,
                status: $payment->status->value,
                statusLabel: $payment->status->label(),
                amount: $payment->amount,
                method: $payment->payment_method,
                channel: $payment->payment_channel,
                createdAt: $payment->created_at?->toImmutable(),
                paidAt: $payment->paid_at?->toImmutable(),
                expiresAt: $payment->expires_at?->toImmutable(),
            ))
            ->all();
    }
}
