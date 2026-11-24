<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'package_id', 'batch_id', 'code', 'password', 'status',
    'reservation_ref', 'reserved_until', 'sold_at', 'expires_at',
])]
#[Hidden(['code', 'password'])] // kode hanya ditampilkan secara eksplisit ke pihak yang berhak
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

    /** @return BelongsTo<VoucherBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function isDeletable(): bool
    {
        return $this->status === VoucherStatus::Available;
    }

    public function isWithdrawable(): bool
    {
        return $this->status === VoucherStatus::Available;
    }
}
