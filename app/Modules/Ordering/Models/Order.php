<?php

namespace App\Modules\Ordering\Models;

use App\Modules\Ordering\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pesanan voucher. Relasi ke user, voucher, dan pembayaran tidak dibuat di sini
 * karena dimiliki modul lain; cukup lewat kolom id (user_id) dan kontrak publik.
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

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }
}
