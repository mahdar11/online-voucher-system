<?php

namespace App\Modules\Payment\Gateway;

final readonly class InvoiceRequest
{
    /**
     * @param  list<array{name: string, quantity: int, price: int}>  $items
     */
    public function __construct(
        public string $externalId,
        public int $amount,
        public string $description,
        public int $durationSeconds,
        public string $payerName,
        public string $payerEmail,
        public ?string $payerPhone,
        public array $items,
        public string $successUrl,
        public string $failureUrl,
    ) {}
}
