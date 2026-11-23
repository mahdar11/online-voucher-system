<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Data\PackageData;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function toData(): PackageData
    {
        return new PackageData(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            price: $this->price,
            durationMinutes: $this->duration_minutes,
            quotaMb: $this->quota_mb,
            speedLimit: $this->speed_limit,
            isActive: $this->is_active,
            sortOrder: $this->sort_order,
        );
    }
}
