<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Port: modul lain memberi tahu apakah sebuah paket sedang dipakai
 * (punya voucher, pernah dipesan, dst.), sehingga tidak boleh dihapus.
 *
 * Implementasi didaftarkan dengan tag TAG di service provider modul masing-masing.
 */
interface PackageUsageCheck
{
    public const TAG = 'catalog.package-usage-checks';

    /**
     * Alasan paket tidak boleh dihapus, atau null jika tidak dipakai.
     */
    public function usageReason(int $packageId): ?string;
}
