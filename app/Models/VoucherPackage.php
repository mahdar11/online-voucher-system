<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'description', 'price', 'duration_minutes',
    'quota_mb', 'speed_limit', 'is_active', 'sort_order',
])]
class VoucherPackage extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_minutes' => 'integer',
            'quota_mb' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Voucher, $this> */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'package_id');
    }

    /** @return HasMany<VoucherBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(VoucherBatch::class, 'package_id');
    }

    /** @return HasMany<Voucher, $this> */
    public function availableVouchers(): HasMany
    {
        return $this->vouchers()->where('status', VoucherStatus::Available);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
