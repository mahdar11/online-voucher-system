<?php

namespace App\Modules\Payment\Models;

use App\Modules\Payment\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * Pembayaran Xendit untuk sebuah pesanan (order_id milik modul Ordering).
 */
#[Fillable([
    'order_id', 'external_id', 'xendit_invoice_id', 'invoice_url', 'payment_method',
    'payment_channel', 'amount', 'status', 'payload', 'paid_at',
])]
#[Hidden(['payload'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
