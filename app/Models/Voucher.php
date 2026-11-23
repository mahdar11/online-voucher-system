<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'package_id', 'batch_id', 'code', 'password', 'status',
    'order_item_id', 'reserved_until', 'sold_at', 'expires_at',
])]
#[Hidden(['code', 'password'])] // kode hanya ditampilkan secara eksplisit ke pemilik pesanan
class Voucher extends Model
{
    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'reserved_until' => 'datetime',
            'sold_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<VoucherPackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(VoucherPackage::class, 'package_id');
    }

    /** @return BelongsTo<VoucherBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
