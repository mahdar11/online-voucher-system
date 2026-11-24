<?php

namespace App\Modules\Ordering\Contracts;

use App\Modules\Ordering\Data\SalesPeriod;

/**
 * Angka penjualan untuk laporan dan dashboard. Penjualan = pesanan Lunas/Selesai,
 * dihitung berdasarkan waktu bayar (paid_at) dalam zona waktu bisnis.
 */
interface SalesReport
{
    /**
     * @return array{revenue: int, orders: int, vouchers: int, average: int}
     */
    public function summary(SalesPeriod $period): array;

    /**
     * Satu baris per hari (termasuk hari tanpa penjualan).
     *
     * @return list<array{date: string, revenue: int, orders: int}>
     */
    public function daily(SalesPeriod $period): array;

    /**
     * @return list<array{package_id: int, package_name: string, vouchers: int, revenue: int}>
     */
    public function byPackage(SalesPeriod $period): array;

    /**
     * Semua pesanan yang dibuat dalam rentang (semua status), untuk export CSV.
     *
     * @return iterable<array<string, mixed>>
     */
    public function exportRows(SalesPeriod $period): iterable;

    /**
     * @return array{pending: int, refund_required: int, unsent_vouchers: int}
     */
    public function attentionCounts(): array;
}
