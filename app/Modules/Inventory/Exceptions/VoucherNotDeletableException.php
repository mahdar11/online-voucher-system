<?php

namespace App\Modules\Inventory\Exceptions;

use App\Modules\Inventory\Models\Voucher;
use RuntimeException;

class VoucherNotDeletableException extends RuntimeException
{
    public static function for(Voucher $voucher): self
    {
        return new self("Voucher {$voucher->code} berstatus {$voucher->status->label()}, jadi tidak bisa dihapus.");
    }
}
