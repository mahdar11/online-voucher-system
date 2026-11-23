<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Shared\Services\ActivityLogger;
use Illuminate\Support\Str;

/**
 * Menambah paket baru atau memperbarui paket yang ada.
 * Slug dibuat sekali saat paket dibuat dan tidak berubah saat nama diganti,
 * supaya URL katalog tetap stabil.
 */
class SavePackage
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $attributes  data yang sudah divalidasi
     */
    public function create(array $attributes): VoucherPackage
    {
        $package = VoucherPackage::query()->create([
            ...$attributes,
            'slug' => $this->uniqueSlug($attributes['name']),
        ]);

        $this->logger->log('package.created', $package, ['name' => $package->name]);

        return $package;
    }

    /**
     * @param  array<string, mixed>  $attributes  data yang sudah divalidasi
     */
    public function update(VoucherPackage $package, array $attributes): VoucherPackage
    {
        $package->fill($attributes);
        $changes = array_keys($package->getDirty());
        $package->save();

        $this->logger->log('package.updated', $package, ['changes' => $changes]);

        return $package;
    }

    public function toggleActive(VoucherPackage $package): VoucherPackage
    {
        $package->update(['is_active' => ! $package->is_active]);

        $this->logger->log($package->is_active ? 'package.activated' : 'package.deactivated', $package);

        return $package;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'paket';
        $slug = $base;

        for ($i = 2; VoucherPackage::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
