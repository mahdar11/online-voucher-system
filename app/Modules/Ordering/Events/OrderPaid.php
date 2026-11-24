<?php

namespace App\Modules\Ordering\Events;

/**
 * Pesanan lunas dan vouchernya sudah terjual ke pembeli. Dikirim sekali per pesanan.
 */
final readonly class OrderPaid
{
    public function __construct(public string $invoiceNumber) {}
}
