<?php

namespace App\Modules\Catalog\Data;

/**
 * Data paket voucher yang boleh dipakai modul lain.
 */
final readonly class PackageData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $price,
        public int $durationMinutes,
        public ?int $validityDays,
        public ?int $quotaMb,
        public ?string $speedLimit,
        public bool $isActive,
        public int $sortOrder,
    ) {}

    /**
     * Label untuk pilihan dropdown, contoh: "Voucher 1 Hari (nonaktif)".
     */
    public function optionLabel(): string
    {
        return $this->name.($this->isActive ? '' : ' (nonaktif)');
    }
}
