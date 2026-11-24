<?php

namespace App\Modules\Ordering\Contracts;

use App\Modules\Ordering\Data\PayableOrder;
use App\Modules\Ordering\Enums\PaidOrderOutcome;
use DateTimeInterface;
use Illuminate\Http\Request;

/**
 * Pintu bagi modul Payment: membaca pesanan untuk ditagih dan
 * menandainya lunas, tanpa menyentuh tabel pesanan secara langsung.
 */
interface OrderPayments
{
    public function find(string $invoiceNumber): ?PayableOrder;

    /**
     * Apakah pengunjung ini boleh melihat/membayar pesanan tersebut.
     */
    public function canAccess(Request $request, string $invoiceNumber): bool;

    /**
     * Idempoten: aman dipanggil berkali-kali untuk pembayaran yang sama.
     */
    public function markPaid(string $invoiceNumber, DateTimeInterface $paidAt): PaidOrderOutcome;
}
