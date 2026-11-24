<?php

namespace App\Modules\Payment\Actions;

use App\Modules\Ordering\Contracts\OrderPayments;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Gateway\GatewayInvoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menerapkan status invoice dari Xendit (webhook atau hasil cek status).
 * Idempoten: status yang sama boleh datang berkali-kali.
 */
class ApplyInvoiceStatus
{
    public const UNKNOWN = 'unknown';

    public const IGNORED = 'ignored';

    public const AMOUNT_MISMATCH = 'amount_mismatch';

    public const PAID = 'paid';

    public const EXPIRED = 'expired';

    public function __construct(
        private readonly OrderPayments $orders,
        private readonly ActivityLogger $logger,
    ) {}

    public function __invoke(GatewayInvoice $invoice): string
    {
        return DB::transaction(function () use ($invoice) {
            /** @var Payment|null $payment */
            $payment = Payment::query()->where('external_id', $invoice->externalId)->lockForUpdate()->first();

            if (! $payment) {
                Log::info('Status Xendit untuk external_id yang tidak dikenal diabaikan.', ['external_id' => $invoice->externalId]);

                return self::UNKNOWN;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                return self::IGNORED;
            }

            if ($invoice->isPaid()) {
                return $this->applyPaid($payment, $invoice);
            }

            if ($invoice->isExpired()) {
                $payment->update(['status' => PaymentStatus::Expired, 'payload' => $invoice->raw]);
                $this->logger->log('payment.expired', $payment, ['invoice' => $payment->invoice_number]);

                return self::EXPIRED;
            }

            return self::IGNORED;
        });
    }

    private function applyPaid(Payment $payment, GatewayInvoice $invoice): string
    {
        $paidAmount = $invoice->paidAmount ?? $invoice->amount;

        if ($paidAmount !== $payment->amount) {
            Log::warning('Jumlah dibayar tidak sama dengan tagihan; pembayaran tidak diproses.', [
                'external_id' => $payment->external_id,
                'expected' => $payment->amount,
                'paid' => $paidAmount,
            ]);

            return self::AMOUNT_MISMATCH;
        }

        $paidAt = $invoice->paidAt ?? now()->toImmutable();

        $payment->update([
            'status' => PaymentStatus::Paid,
            'payment_method' => $invoice->paymentMethod,
            'payment_channel' => $invoice->paymentChannel,
            'paid_at' => $paidAt,
            'payload' => $invoice->raw,
        ]);

        $outcome = $this->orders->markPaid($payment->invoice_number, $paidAt);

        $this->logger->log('payment.paid', $payment, [
            'invoice' => $payment->invoice_number,
            'channel' => $invoice->paymentChannel,
            'order_outcome' => $outcome->value,
        ]);

        return self::PAID;
    }
}
