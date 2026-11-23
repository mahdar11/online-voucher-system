<?php

namespace App\Modules\Payment\Contracts;

use App\Modules\Ordering\Data\SalesPeriod;

/**
 * Statistik pembayaran lunas untuk laporan.
 */
interface PaymentStats
{
    /**
     * Per channel pembayaran (QRIS, BCA, OVO, ...), terbesar lebih dulu.
     *
     * @return list<array{channel: string, method: ?string, payments: int, amount: int}>
     */
    public function byChannel(SalesPeriod $period): array;
}
