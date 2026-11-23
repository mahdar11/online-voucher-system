<?php

namespace App\Modules\Ordering\Models;

use App\Modules\Ordering\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pesanan voucher. Relasi ke user, voucher, dan pembayaran tidak dibuat di sini
 * karena dimiliki modul lain; cukup lewat kolom id (user_id) dan kontrak publik.
 * Voucher yang dikunci untuk pesanan ini ditandai dengan invoice_number.
 */
#[Fillable([
    'invoice_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
    'subtotal', 'fee', 'total', 'status', 'paid_at', 'expires_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'fee' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @param Builder<self> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    /**
     * Pesanan pending yang batas bayarnya sudah lewat.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->pending()->where('expires_at', '<=', now());
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Sisa waktu bayar dalam detik (dihitung di server agar tidak terpengaruh jam perangkat).
     */
    public function secondsUntilExpiry(): int
    {
        if (! $this->isPending() || $this->expires_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
