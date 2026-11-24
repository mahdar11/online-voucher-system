<?php

namespace App\Modules\Catalog\Data;

/**
 * Ringkasan stok satu paket. Angkanya disediakan oleh modul Inventory
 * melalui kontrak PackageStockReader.
 */
final readonly class PackageStock
{
    public function __construct(
        public int $available = 0,
        public int $reserved = 0,
        public int $sold = 0,
    ) {}

    /**
     * @return array{available_count: int, reserved_count: int, sold_count: int}
     */
    public function toArray(): array
    {
        return [
            'available_count' => $this->available,
            'reserved_count' => $this->reserved,
            'sold_count' => $this->sold,
        ];
    }
}
