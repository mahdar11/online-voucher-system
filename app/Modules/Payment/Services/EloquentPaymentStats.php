<?php

namespace App\Modules\Payment\Services;

use App\Modules\Ordering\Data\SalesPeriod;
use App\Modules\Payment\Contracts\PaymentStats;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;

class EloquentPaymentStats implements PaymentStats
{
    public function byChannel(SalesPeriod $period): array
    {
        return Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$period->fromUtc(), $period->untilUtc()])
            ->selectRaw("coalesce(payment_channel, 'Lainnya') as channel, max(payment_method) as method, count(*) as payments, sum(amount) as amount")
            ->groupByRaw("coalesce(payment_channel, 'Lainnya')")
            ->orderByDesc('amount')
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'channel' => (string) $row->channel,
                'method' => $row->method,
                'payments' => (int) $row->payments,
                'amount' => (int) $row->amount,
            ])
            ->all();
    }
}
