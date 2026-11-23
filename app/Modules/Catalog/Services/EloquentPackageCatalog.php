<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Catalog\Data\PackageData;
use App\Modules\Catalog\Models\VoucherPackage;

class EloquentPackageCatalog implements PackageCatalog
{
    public function find(int $id): ?PackageData
    {
        return VoucherPackage::query()->find($id)?->toData();
    }

    public function findActiveBySlug(string $slug): ?PackageData
    {
        return VoucherPackage::query()->active()->where('slug', $slug)->first()?->toData();
    }

    public function all(): array
    {
        return VoucherPackage::query()
            ->ordered()
            ->get()
            ->map(fn (VoucherPackage $package) => $package->toData())
            ->all();
    }

    public function namesFor(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        return VoucherPackage::query()->whereKey($ids)->pluck('name', 'id')->all();
    }

    public function options(): array
    {
        return array_map(fn (PackageData $package) => [
            'value' => (string) $package->id,
            'label' => $package->optionLabel(),
        ], $this->all());
    }
}
