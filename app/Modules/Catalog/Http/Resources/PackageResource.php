<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Data\PackageStock;
use App\Modules\Catalog\Models\VoucherPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VoucherPackage
 */
class PackageResource extends JsonResource
{
    private ?PackageStock $stock = null;

    public function withStock(?PackageStock $stock): self
    {
        $this->stock = $stock ?? new PackageStock;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'validity_days' => $this->validity_days,
            'quota_mb' => $this->quota_mb,
            'speed_limit' => $this->speed_limit,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            ...($this->stock?->toArray() ?? []),
        ];
    }
}
