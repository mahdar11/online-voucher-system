<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Ordering\Contracts\PaymentHistoryReader;

/**
 * Implementasi bawaan bila tidak ada modul pembayaran yang terpasang.
 */
class NoPaymentHistory implements PaymentHistoryReader
{
    public function attemptsFor(string $invoiceNumber): array
    {
        return [];
    }
}
