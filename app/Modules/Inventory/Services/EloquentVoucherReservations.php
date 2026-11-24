<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Contracts\InsufficientStockException;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Inventory\Data\VoucherCode;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use DateTimeInterface;

class EloquentVoucherReservations implements VoucherReservations
{
    public function reserve(int $packageId, int $quantity, string $reference, DateTimeInterface $until): array
    {
        // Baris yang sedang dikunci checkout lain dilewati, bukan ditunggu,
        // sehingga dua pembeli tidak pernah mendapat voucher yang sama.
        $ids = Voucher::query()
            ->where('package_id', $packageId)
            ->where('status', VoucherStatus::Available)
            ->orderBy('id')
            ->limit($quantity)
            ->lock('for update skip locked')
            ->pluck('id')
            ->all();

        if (count($ids) < $quantity) {
            throw new InsufficientStockException($quantity, count($ids));
        }

        Voucher::query()->whereKey($ids)->update([
            'status' => VoucherStatus::Reserved,
            'reservation_ref' => $reference,
            'reserved_until' => $until,
        ]);

        return $ids;
    }

    public function release(string $reference): int
    {
        return Voucher::query()
            ->where('reservation_ref', $reference)
            ->where('status', VoucherStatus::Reserved)
            ->update([
                'status' => VoucherStatus::Available,
                'reservation_ref' => null,
                'reserved_until' => null,
            ]);
    }

    public function markSold(string $reference, DateTimeInterface $soldAt): int
    {
        return Voucher::query()
            ->where('reservation_ref', $reference)
            ->where('status', VoucherStatus::Reserved)
            ->update([
                'status' => VoucherStatus::Sold,
                'reserved_until' => null,
                'sold_at' => $soldAt,
            ]);
    }

    public function codesFor(string $reference): array
    {
        return Voucher::query()
            ->where('reservation_ref', $reference)
            ->whereIn('status', [VoucherStatus::Sold, VoucherStatus::Used])
            ->orderBy('id')
            ->get(['code', 'password'])
            ->map(fn (Voucher $voucher) => new VoucherCode($voucher->code, $voucher->password))
            ->all();
    }
}
