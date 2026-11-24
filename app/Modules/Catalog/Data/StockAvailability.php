<?php

namespace App\Modules\Catalog\Data;

/**
 * Status stok yang ditampilkan ke publik. Angka stok persis hanya
 * diperlihatkan saat tinggal sedikit.
 */
final readonly class StockAvailability
{
    public const LOW_STOCK_THRESHOLD = 10;

    private function __construct(
        public string $state,      // available | low | sold_out
        public string $label,
        public int $purchasable,   // jumlah maksimal yang bisa dibeli saat ini
    ) {}

    public static function from(PackageStock $stock): self
    {
        return match (true) {
            $stock->available <= 0 => new self('sold_out', 'Habis', 0),
            $stock->available <= self::LOW_STOCK_THRESHOLD => new self('low', "Sisa {$stock->available}", $stock->available),
            default => new self('available', 'Tersedia', $stock->available),
        };
    }

    public function isSoldOut(): bool
    {
        return $this->state === 'sold_out';
    }

    /**
     * @return array{state: string, label: string}
     */
    public function toArray(): array
    {
        return ['state' => $this->state, 'label' => $this->label];
    }
}
