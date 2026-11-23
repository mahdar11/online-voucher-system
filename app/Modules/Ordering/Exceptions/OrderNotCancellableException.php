<?php

namespace App\Modules\Ordering\Exceptions;

use App\Modules\Ordering\Models\Order;
use RuntimeException;

class OrderNotCancellableException extends RuntimeException
{
    public static function for(Order $order): self
    {
        return new self("Pesanan {$order->invoice_number} berstatus {$order->status->label()}, jadi tidak bisa dibatalkan.");
    }
}
