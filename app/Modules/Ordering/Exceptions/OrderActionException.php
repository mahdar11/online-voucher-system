<?php

namespace App\Modules\Ordering\Exceptions;

use App\Modules\Ordering\Models\Order;
use RuntimeException;

class OrderActionException extends RuntimeException
{
    public static function noVouchers(Order $order): self
    {
        return new self("Pesanan {$order->invoice_number} berstatus {$order->status->label()}, belum ada voucher untuk dikirim.");
    }

    public static function notRefundable(Order $order): self
    {
        return new self("Pesanan {$order->invoice_number} berstatus {$order->status->label()}, bukan \"Perlu Refund\".");
    }
}
