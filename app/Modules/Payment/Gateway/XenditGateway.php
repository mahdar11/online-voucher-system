<?php

namespace App\Modules\Payment\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Xendit Invoice API (https://developers.xendit.co/api-reference/#create-invoice),
 * dipanggil langsung lewat HTTP client Laravel.
 */
class XenditGateway implements PaymentGateway
{
    /**
     * @param  array{base_url: string, secret_key: ?string, callback_token: ?string, timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['secret_key'] ?? null);
    }

    public function createInvoice(InvoiceRequest $request): GatewayInvoice
    {
        $payload = array_filter([
            'external_id' => $request->externalId,
            'amount' => $request->amount,
            'currency' => 'IDR',
            'description' => $request->description,
            'invoice_duration' => $request->durationSeconds,
            'payer_email' => $request->payerEmail,
            'customer' => array_filter([
                'given_names' => $request->payerName,
                'email' => $request->payerEmail,
                'mobile_number' => $this->internationalPhone($request->payerPhone),
            ]),
            'items' => $request->items,
            'success_redirect_url' => $request->successUrl,
            'failure_redirect_url' => $request->failureUrl,
        ], fn ($value) => $value !== null && $value !== []);

        return $this->send(fn (PendingRequest $http) => $http->post('/v2/invoices', $payload));
    }

    public function getInvoice(string $invoiceId): GatewayInvoice
    {
        return $this->send(fn (PendingRequest $http) => $http->get('/v2/invoices/'.rawurlencode($invoiceId)));
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): GatewayInvoice
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException('Xendit belum dikonfigurasi (XENDIT_SECRET_KEY kosong).');
        }

        $http = Http::baseUrl($this->config['base_url'])
            ->withBasicAuth((string) $this->config['secret_key'], '')
            ->acceptJson()
            ->timeout($this->config['timeout'] ?? 15);

        try {
            $response = $call($http);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('Tidak bisa terhubung ke Xendit.', previous: $exception);
        }

        if ($response->failed()) {
            $error = $response->json('error_code') ?? 'HTTP_'.$response->status();
            throw new PaymentGatewayException("Xendit menolak permintaan ({$error}): ".$response->json('message', ''));
        }

        return GatewayInvoice::fromXendit((array) $response->json());
    }

    /**
     * 081234567890 -> +6281234567890 (format yang diminta Xendit).
     */
    private function internationalPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        return '+62'.preg_replace('/^(\+62|62|0)/', '', $phone);
    }
}
