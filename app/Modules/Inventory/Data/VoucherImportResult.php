<?php

namespace App\Modules\Inventory\Data;

use App\Modules\Inventory\Models\VoucherBatch;

final readonly class VoucherImportResult
{
    /**
     * @param  list<string>  $problems  contoh masalah per baris (maksimal beberapa saja)
     */
    public function __construct(
        public VoucherBatch $batch,
        public int $inserted,
        public int $duplicates,
        public int $invalid,
        public array $problems,
    ) {}

    public function summary(): string
    {
        return sprintf(
            'Import selesai: %s voucher masuk, %s duplikat dilewati, %s baris tidak valid.',
            number_format($this->inserted, 0, ',', '.'),
            number_format($this->duplicates, 0, ',', '.'),
            number_format($this->invalid, 0, ',', '.'),
        );
    }
}
