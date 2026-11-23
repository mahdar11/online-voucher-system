<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Exceptions\VoucherNotDeletableException;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Shared\Services\ActivityLogger;

/**
 * Menghapus voucher yang belum pernah dipesan atau dijual.
 */
class DeleteVoucher
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @throws VoucherNotDeletableException
     */
    public function __invoke(Voucher $voucher): void
    {
        if (! $voucher->isDeletable()) {
            throw VoucherNotDeletableException::for($voucher);
        }

        $this->logger->log('voucher.deleted', null, [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'package_id' => $voucher->package_id,
        ]);

        $voucher->delete();
    }
}
