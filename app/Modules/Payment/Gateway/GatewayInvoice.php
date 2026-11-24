<?php

namespace App\Modules\Payment\Gateway;

use Carbon\CarbonImmutable;

/**
 * Invoice dari sisi penyedia pembayaran, baik dari respons API maupun webhook.
 */
final readonly class GatewayInvoice
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $externalId,
        public string $status,          // PENDING | PAID | SETTLED | EXPIRED
        public int $amount,
        public ?int $paidAmount,
        public ?string $invoiceUrl,
        public ?string $paymentMethod,
        public ?string $paymentChannel,
        public ?CarbonImmutable $paidAt,
        public ?CarbonImmutable $expiresAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data  respons Invoice API / body webhook Xendit
     */
    public static function fromXendit(array $data): self
    {
        $date = fn (?string $value) => $value ? CarbonImmutable::parse($value) : null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            externalId: (string) ($data['external_id'] ?? ''),
            status: strtoupper((string) ($data['status'] ?? '')),
            amount: (int) round((float) ($data['amount'] ?? 0)),
            paidAmount: isset($data['paid_amount']) ? (int) round((float) $data['paid_amount']) : null,
            invoiceUrl: $data['invoice_url'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            paymentChannel: $data['payment_channel'] ?? null,
            paidAt: $date($data['paid_at'] ?? null),
            expiresAt: $date($data['expiry_date'] ?? null),
            raw: $data,
        );
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['PAID', 'SETTLED'], true);
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED';
    }
}
