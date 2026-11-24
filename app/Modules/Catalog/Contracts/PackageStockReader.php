<?php

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Data\PackageStock;

/**
 * Port: Catalog butuh angka stok, tapi stok dimiliki modul Inventory.
 * Inventory yang menyediakan implementasinya (dependency inversion).
 */
interface PackageStockReader
{
    /**
     * @param  list<int>  $packageIds
     * @return array<int, PackageStock> [package_id => stok]; paket tanpa voucher boleh tidak ada
     */
    public function stockFor(array $packageIds): array;
}
