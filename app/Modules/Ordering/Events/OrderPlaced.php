<?php

namespace App\Modules\Ordering\Events;

/**
 * Pesanan baru dibuat (sudah tersimpan) dan menunggu pembayaran.
 */
final readonly class OrderPlaced
{
    public function __construct(public string $invoiceNumber) {}
}
