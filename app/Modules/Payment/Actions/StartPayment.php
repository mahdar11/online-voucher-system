<?php

namespace App\Modules\Payment\Actions;

use App\Modules\Ordering\Data\PayableOrder;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Exceptions\PaymentUnavailableException;
use App\Modules\Payment\Gateway\InvoiceRequest;
use App\Modules\Payment\Gateway\PaymentGateway;
use App\Modules\Payment\Gateway\PaymentGatewayException;
use App\Modules\Payment\Models\Payment;
use App\Modules\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\Log;

/**
 * Membuat (atau memakai ulang) tagihan Xendit untuk pesanan.
 * Batas waktu tagihan = sisa waktu reservasi voucher, sehingga Xendit
 * tidak menerima pembayaran setelah voucher dilepas.
 */
class StartPayment
{
    public const MIN_SECONDS_LEFT = 60;

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @throws PaymentUnavailableException
     */
    public function __invoke(PayableOrder $order): Payment
    {
        if (! $this->gateway->isConfigured()) {
            throw PaymentUnavailableException::notConfigured();
        }

        if (! $order->isPayable) {
            throw PaymentUnavailableException::notPayable();
        }

        $existing = Payment::query()->forInvoice($order->invoiceNumber)->pending()->latest('id')->first();
        if ($existing?->isReusable()) {
            return $existing;
        }

        $secondsLeft = $order->secondsUntilExpiry();
        if ($secondsLeft < self::MIN_SECONDS_LEFT) {
            throw PaymentUnavailableException::tooLate();
        }

        $externalId = $order->invoiceNumber.'-'.(Payment::query()->forInvoice($order->invoiceNumber)->count() + 1);

        try {
            $invoice = $this->gateway->createInvoice(new InvoiceRequest(
                externalId: $externalId,
                amount: $order->total,
                description: $order->description(),
                durationSeconds: $secondsLeft,
                payerName: $order->customerName,
                payerEmail: $order->customerEmail,
                payerPhone: $order->customerPhone,
                items: $order->items,
                successUrl: route('orders.show', ['order' => $order->invoiceNumber, 'bayar' => 'selesai']),
                failureUrl: route('orders.show', ['order' => $order->invoiceNumber, 'bayar' => 'gagal']),
            ));
        } catch (PaymentGatewayException $exception) {
            Log::error('Gagal membuat invoice Xendit.', ['invoice' => $order->invoiceNumber, 'error' => $exception->getMessage()]);

            throw PaymentUnavailableException::gatewayError();
        }

        $payment = Payment::query()->create([
            'order_id' => $order->orderId,
            'invoice_number' => $order->invoiceNumber,
            'external_id' => $externalId,
            'xendit_invoice_id' => $invoice->id,
            'invoice_url' => $invoice->invoiceUrl,
            'amount' => $order->total,
            'status' => PaymentStatus::Pending,
            'expires_at' => $invoice->expiresAt ?? $order->expiresAt,
        ]);

        $this->logger->log('payment.started', $payment, ['invoice' => $order->invoiceNumber, 'external_id' => $externalId]);

        return $payment;
    }
}
