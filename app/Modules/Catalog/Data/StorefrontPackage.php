<?php

namespace App\Modules\Catalog\Data;

/**
 * Data paket untuk halaman publik (katalog dan checkout): tanpa angka stok
 * persis dan tanpa data khusus admin.
 */
final class StorefrontPackage
{
    /**
     * @return array<string, mixed>
     */
    public static function make(PackageData $package, StockAvailability $availability): array
    {
        return [
            'id' => $package->id,
            'slug' => $package->slug,
            'name' => $package->name,
            'description' => $package->description,
            'price' => $package->price,
            'duration_minutes' => $package->durationMinutes,
            'validity_days' => $package->validityDays,
            'quota_mb' => $package->quotaMb,
            'speed_limit' => $package->speedLimit,
            'availability' => $availability->toArray(),
        ];
    }
}
