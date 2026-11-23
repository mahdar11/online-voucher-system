<?php

namespace App\Modules\Ordering\Events;

/**
 * Pembayaran masuk setelah stok habis; dana pembeli perlu dikembalikan.
 */
final readonly class OrderRefundRequired
{
    public function __construct(public string $invoiceNumber) {}
}
