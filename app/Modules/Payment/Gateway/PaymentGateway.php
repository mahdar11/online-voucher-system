<?php

namespace App\Modules\Payment\Gateway;

/**
 * Abstraksi penyedia pembayaran (saat ini Xendit Invoice).
 * Memudahkan test: implementasi bisa diganti tanpa memanggil API sungguhan.
 */
interface PaymentGateway
{
    public function isConfigured(): bool;

    /**
     * @throws PaymentGatewayException
     */
    public function createInvoice(InvoiceRequest $request): GatewayInvoice;

    /**
     * @throws PaymentGatewayException
     */
    public function getInvoice(string $invoiceId): GatewayInvoice;
}
