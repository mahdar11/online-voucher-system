<?php

namespace App\Modules\Inventory\Contracts;

use RuntimeException;

/**
 * Bagian dari kontrak VoucherReservations: stok tidak cukup untuk jumlah yang diminta.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly int $requested, public readonly int $available)
    {
        parent::__construct(
            $available === 0
                ? 'Maaf, stok voucher ini sedang habis.'
                : "Stok tidak cukup, tersisa {$available} voucher."
        );
    }
}
