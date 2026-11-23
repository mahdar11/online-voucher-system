<?php

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Data\PackageData;

/**
 * Pintu baca data paket untuk modul lain.
 */
interface PackageCatalog
{
    public function find(int $id): ?PackageData;

    /**
     * Semua paket, terurut sesuai urutan tampil.
     *
     * @return list<PackageData>
     */
    public function all(): array;

    /**
     * @param  list<int>  $ids
     * @return array<int, string> [package_id => nama]
     */
    public function namesFor(array $ids): array;

    /**
     * Pilihan untuk dropdown di form.
     *
     * @return list<array{value: string, label: string}>
     */
    public function options(): array;
}
