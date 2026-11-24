<?php

namespace App\Modules\Ordering\Data;

use Carbon\CarbonImmutable;

/**
 * Satu percobaan pembayaran untuk sebuah pesanan (diisi oleh modul Payment).
 */
final readonly class PaymentAttempt
{
    public function __construct(
        public string $reference,
        public string $status,
        public string $statusLabel,
        public int $amount,
        public ?string $method,
        public ?string $channel,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $paidAt,
        public ?CarbonImmutable $expiresAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'amount' => $this->amount,
            'method' => $this->method,
            'channel' => $this->channel,
            'created_at' => $this->createdAt?->toIso8601String(),
            'paid_at' => $this->paidAt?->toIso8601String(),
            'expires_at' => $this->expiresAt?->toIso8601String(),
        ];
    }
}
