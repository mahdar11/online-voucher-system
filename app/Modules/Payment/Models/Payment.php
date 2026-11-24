<?php

namespace App\Modules\Payment\Models;

use App\Modules\Payment\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tagihan Xendit untuk sebuah pesanan (order_id/invoice_number milik modul Ordering).
 */
#[Fillable([
    'order_id', 'invoice_number', 'external_id', 'xendit_invoice_id', 'invoice_url', 'payment_method',
    'payment_channel', 'amount', 'status', 'payload', 'paid_at', 'expires_at',
])]
#[Hidden(['payload'])]
class Payment extends Model
{
    /** Invoice yang tersisa kurang dari ini dianggap terlalu mepet untuk dipakai ulang. */
    public const REUSE_MARGIN_SECONDS = 60;

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @param Builder<self> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', PaymentStatus::Pending);
    }

    /** @param Builder<self> $query */
    public function scopeForInvoice(Builder $query, string $invoiceNumber): void
    {
        $query->where('invoice_number', $invoiceNumber);
    }

    public function isReusable(): bool
    {
        return $this->status === PaymentStatus::Pending
            && filled($this->invoice_url)
            && $this->expires_at !== null
            && $this->expires_at->isAfter(now()->addSeconds(self::REUSE_MARGIN_SECONDS));
    }
}
