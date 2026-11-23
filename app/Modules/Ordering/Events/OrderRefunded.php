<?php

namespace App\Modules\Ordering\Events;

/**
 * Admin menandai dana pesanan sudah dikembalikan ke pembeli.
 */
final readonly class OrderRefunded
{
    public function __construct(public string $invoiceNumber) {}
}
