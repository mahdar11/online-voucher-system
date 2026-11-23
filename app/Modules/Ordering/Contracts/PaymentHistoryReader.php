<?php

namespace App\Modules\Ordering\Contracts;

use App\Modules\Ordering\Data\PaymentAttempt;

/**
 * Port: halaman admin pesanan butuh riwayat pembayaran, tetapi data pembayaran
 * dimiliki modul Payment. Payment yang menyediakan implementasinya.
 */
interface PaymentHistoryReader
{
    /**
     * @return list<PaymentAttempt> terbaru lebih dulu
     */
    public function attemptsFor(string $invoiceNumber): array;
}
