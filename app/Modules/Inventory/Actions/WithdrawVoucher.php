<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Exceptions\VoucherNotWithdrawableException;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Shared\Services\ActivityLogger;

/**
 * Menarik voucher dari penjualan online tanpa menghapus jejaknya.
 * Dipakai bila kode yang sudah diimport ternyata terjual lewat kartu fisik.
 */
class WithdrawVoucher
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @throws VoucherNotWithdrawableException
     */
    public function __invoke(Voucher $voucher): void
    {
        if (! $voucher->isWithdrawable()) {
            throw VoucherNotWithdrawableException::for($voucher);
        }

        $voucher->update(['status' => VoucherStatus::Withdrawn]);

        $this->logger->log('voucher.withdrawn', $voucher, [
            'code' => $voucher->code,
            'package_id' => $voucher->package_id,
        ]);
    }
}
