<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Catalog\Data\PackageData;
use App\Modules\Inventory\Enums\BatchSource;
use App\Modules\Inventory\Enums\PasswordMode;
use App\Modules\Inventory\Models\VoucherBatch;
use App\Modules\Inventory\Services\VoucherCodeGenerator;
use App\Modules\Inventory\Services\VoucherWriter;
use App\Modules\Shared\Services\ActivityLogger;

/**
 * Membuat sejumlah voucher baru dengan kode acak untuk sebuah paket.
 */
class GenerateVouchers
{
    public const MAX_QUANTITY = 1000;

    public function __construct(
        private readonly VoucherCodeGenerator $generator,
        private readonly VoucherWriter $writer,
        private readonly ActivityLogger $logger,
    ) {}

    public function __invoke(
        PackageData $package,
        int $quantity,
        int $length,
        string $prefix,
        PasswordMode $passwordMode,
        ?int $createdBy,
        ?string $note = null,
    ): VoucherBatch {
        $codes = [];
        foreach ($this->generator->uniqueCodes($package->id, $quantity, $length, $prefix) as $code) {
            $codes[$code] = match ($passwordMode) {
                PasswordMode::None => null,
                PasswordMode::SameAsCode => $code,
                PasswordMode::Random => $this->generator->password(),
            };
        }

        $batch = $this->writer->writeBatch($package->id, BatchSource::Generate, $codes, $createdBy, $note);

        $this->logger->log('vouchers.generated', $batch, [
            'package_id' => $package->id,
            'quantity' => $batch->quantity,
        ]);

        return $batch;
    }
}
