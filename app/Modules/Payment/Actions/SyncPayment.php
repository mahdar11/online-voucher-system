<?php

namespace App\Modules\Payment\Actions;

use App\Modules\Payment\Gateway\PaymentGateway;
use App\Modules\Payment\Gateway\PaymentGatewayException;
use App\Modules\Payment\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cadangan bila webhook belum/tidak sampai (mis. saat pengembangan di localhost):
 * menanyakan status tagihan yang masih pending langsung ke Xendit.
 */
class SyncPayment
{
    public const THROTTLE_SECONDS = 10;

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ApplyInvoiceStatus $applyInvoiceStatus,
    ) {}

    public function __invoke(string $invoiceNumber): void
    {
        if (! $this->gateway->isConfigured()) {
            return;
        }

        $payment = Payment::query()->forInvoice($invoiceNumber)->pending()->whereNotNull('xendit_invoice_id')->latest('id')->first();

        // Maksimal sekali per 10 detik per tagihan, agar tidak membanjiri API Xendit.
        if (! $payment || ! Cache::add("payment-sync:{$payment->id}", true, self::THROTTLE_SECONDS)) {
            return;
        }

        try {
            ($this->applyInvoiceStatus)($this->gateway->getInvoice($payment->xendit_invoice_id));
        } catch (PaymentGatewayException $exception) {
            Log::warning('Gagal mengecek status invoice Xendit.', ['external_id' => $payment->external_id, 'error' => $exception->getMessage()]);
        }
    }
}
