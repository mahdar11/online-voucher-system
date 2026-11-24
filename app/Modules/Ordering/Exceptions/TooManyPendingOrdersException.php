<?php

namespace App\Modules\Ordering\Exceptions;

use RuntimeException;

class TooManyPendingOrdersException extends RuntimeException
{
    public static function forLimit(int $limit): self
    {
        return new self("Email ini masih punya {$limit} pesanan yang belum dibayar. Selesaikan atau batalkan dulu salah satunya.");
    }
}
