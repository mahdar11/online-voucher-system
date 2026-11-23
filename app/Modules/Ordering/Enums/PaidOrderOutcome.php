<?php

namespace App\Modules\Ordering\Enums;

/**
 * Hasil menandai pesanan sebagai lunas (lihat OrderPayments::markPaid).
 */
enum PaidOrderOutcome: string
{
    case Paid = 'paid';
    case AlreadyPaid = 'already_paid';
    /** Dibayar terlambat tetapi stok masih ada, jadi tetap terpenuhi. */
    case PaidLate = 'paid_late';
    case RefundRequired = 'refund_required';
    case NotFound = 'not_found';
}
