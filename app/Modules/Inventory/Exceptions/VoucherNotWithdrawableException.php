<?php

namespace App\Modules\Inventory\Exceptions;

use App\Modules\Inventory\Models\Voucher;
use RuntimeException;

class VoucherNotWithdrawableException extends RuntimeException
{
    public static function for(Voucher $voucher): self
    {
        return new self("Voucher {$voucher->code} berstatus {$voucher->status->label()}, jadi tidak bisa ditarik.");
    }
}
