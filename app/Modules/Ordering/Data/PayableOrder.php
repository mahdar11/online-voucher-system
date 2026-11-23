<?php

namespace App\Modules\Ordering\Data;

use Carbon\CarbonImmutable;

/**
 * Data pesanan yang dibutuhkan modul Payment untuk membuat tagihan.
 */
final readonly class PayableOrder
{
    /**
     * @param  list<array{name: string, quantity: int, price: int}>  $items
     */
    public function __construct(
        public int $orderId,
        public string $invoiceNumber,
        public int $total,
        public string $customerName,
        public string $customerEmail,
        public ?string $customerPhone,
        public array $items,
        public ?CarbonImmutable $expiresAt,
        public bool $isPayable,
    ) {}

    public function description(): string
    {
        $lines = array_map(fn (array $item) => "{$item['name']} × {$item['quantity']}", $this->items);

        return 'Voucher internet: '.implode(', ', $lines);
    }

    public function secondsUntilExpiry(): int
    {
        return $this->expiresAt ? max(0, (int) now()->diffInSeconds($this->expiresAt, false)) : 0;
    }
}
