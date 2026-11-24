<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Enums\BatchSource;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Inventory\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan satu batch beserta kode-kodenya dalam satu transaksi.
 * Dipakai bersama oleh GenerateVouchers dan ImportVouchers.
 */
class VoucherWriter
{
    private const INSERT_CHUNK = 500;

    /**
     * @param  array<string, ?string>  $codes  [kode => password|null]
     */
    public function writeBatch(
        int $packageId,
        BatchSource $source,
        array $codes,
        ?int $createdBy,
        ?string $note = null,
        ?string $fileName = null,
    ): VoucherBatch {
        return DB::transaction(function () use ($packageId, $source, $codes, $createdBy, $note, $fileName) {
            $batch = VoucherBatch::query()->create([
                'package_id' => $packageId,
                'created_by' => $createdBy,
                'source' => $source,
                'file_name' => $fileName,
                'quantity' => count($codes),
                'note' => $note,
            ]);

            $now = now();
            $records = [];
            foreach ($codes as $code => $password) {
                $records[] = [
                    'package_id' => $packageId,
                    'batch_id' => $batch->id,
                    'code' => (string) $code,
                    'password' => $password,
                    'status' => VoucherStatus::Available->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($records, self::INSERT_CHUNK) as $chunk) {
                Voucher::query()->insert($chunk);
            }

            return $batch;
        });
    }
}
