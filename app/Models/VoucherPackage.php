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

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'package_id');
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Menambahkan available_count, reserved_count, dan sold_count (termasuk terpakai).
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithStockCounts(Builder $query): void
    {
        $query->withCount([
            'vouchers as available_count' => fn (Builder $q) => $q->where('status', VoucherStatus::Available),
            'vouchers as reserved_count' => fn (Builder $q) => $q->where('status', VoucherStatus::Reserved),
            'vouchers as sold_count' => fn (Builder $q) => $q->whereIn('status', [VoucherStatus::Sold, VoucherStatus::Used]),
        ]);
    }

    /**
     * Paket hanya boleh dihapus jika belum punya voucher dan belum pernah dipesan.
     */
    public function isDeletable(): bool
    {
        return ! $this->vouchers()->exists() && ! $this->orderItems()->exists();
    }
}
