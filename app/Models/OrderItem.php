<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'package_id', 'quantity', 'unit_price', 'total'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'total' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<VoucherPackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(VoucherPackage::class, 'package_id');
    }

    /** @return HasMany<Voucher, $this> */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
