<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Ordering\Contracts\SalesReport;
use App\Modules\Ordering\Data\SalesPeriod;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentSalesReport implements SalesReport
{
    /** Pesanan "Lunas" lebih lama dari ini dianggap emailnya belum terkirim. */
    private const UNSENT_GRACE_MINUTES = 10;

    public function summary(SalesPeriod $period): array
    {
        $row = $this->paid($period)
            ->selectRaw('count(*) as orders, coalesce(sum(total), 0) as revenue')
            ->toBase()
            ->first();

        $vouchers = (int) DB::table('order_items')
            ->whereIn('order_id', $this->paid($period)->select('id'))
            ->sum('quantity');

        $orders = (int) $row->orders;
        $revenue = (int) $row->revenue;

        return [
            'revenue' => $revenue,
            'orders' => $orders,
            'vouchers' => $vouchers,
            'average' => $orders > 0 ? intdiv($revenue, $orders) : 0,
        ];
    }

    public function daily(SalesPeriod $period): array
    {
        $day = $this->localDate('paid_at', $period->timezone);

        $rows = $this->paid($period)
            ->selectRaw("{$day} as day, count(*) as orders, coalesce(sum(total), 0) as revenue")
            ->groupByRaw($day)
            ->toBase()
            ->get()
            ->keyBy('day');

        return array_map(fn (string $date) => [
            'date' => $date,
            'revenue' => (int) ($rows[$date]->revenue ?? 0),
            'orders' => (int) ($rows[$date]->orders ?? 0),
        ], $period->dates());
    }

    public function byPackage(SalesPeriod $period): array
    {
        return DB::table('order_items')
            ->whereIn('order_id', $this->paid($period)->select('id'))
            ->selectRaw('package_id, max(package_name) as package_name, sum(quantity) as vouchers, sum(total) as revenue')
            ->groupBy('package_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'package_id' => (int) $row->package_id,
                'package_name' => (string) $row->package_name,
                'vouchers' => (int) $row->vouchers,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    public function exportRows(SalesPeriod $period): iterable
    {
        $orders = Order::query()
            ->with('items')
            ->whereBetween('created_at', [$period->fromUtc(), $period->untilUtc()])
            ->orderBy('id')
            ->lazy(500);

        foreach ($orders as $order) {
            $item = $order->items->first();

            yield [
                'invoice' => $order->invoice_number,
                'dibuat' => $order->created_at?->timezone($period->timezone)->format('Y-m-d H:i'),
                'dibayar' => $order->paid_at?->timezone($period->timezone)->format('Y-m-d H:i'),
                'status' => $order->status->label(),
                'nama' => $order->customer_name,
                'email' => $order->customer_email,
                'hp' => $order->customer_phone,
                'paket' => $item?->package_name,
                'jumlah' => $item?->quantity,
                'total' => $order->total,
            ];
        }
    }

    public function attentionCounts(): array
    {
        return [
            'pending' => Order::query()->pending()->where('expires_at', '>', now())->count(),
            'refund_required' => Order::query()->where('status', OrderStatus::RefundRequired)->count(),
            'unsent_vouchers' => Order::query()
                ->where('status', OrderStatus::Paid)
                ->where('paid_at', '<', now()->subMinutes(self::UNSENT_GRACE_MINUTES))
                ->count(),
        ];
    }

    /**
     * @return Builder<Order>
     */
    private function paid(SalesPeriod $period): Builder
    {
        return Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
            ->whereBetween('paid_at', [$period->fromUtc(), $period->untilUtc()]);
    }

    /**
     * Tanggal lokal dari kolom timestamp UTC (tanpa zona) di PostgreSQL.
     */
    private function localDate(string $column, string $timezone): string
    {
        $tz = DB::getPdo()->quote($timezone);

        return "to_char(({$column} at time zone 'UTC') at time zone {$tz}, 'YYYY-MM-DD')";
    }
}
