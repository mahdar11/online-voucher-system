<?php

namespace App\Modules\Ordering\Actions;

use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;

/**
 * Menandai pesanan pending yang lewat batas bayar sebagai kedaluwarsa
 * dan mengembalikan vouchernya ke stok.
 */
class ExpireOrders
{
    public function __construct(private readonly CloseOrder $closeOrder) {}

    /**
     * Satu pesanan; aman dipanggil berulang (mis. saat halaman pesanan dibuka).
     */
    public function expireIfOverdue(Order $order): bool
    {
        if (! $order->isOverdue()) {
            return false;
        }

        return ($this->closeOrder)($order, OrderStatus::Expired, 'order.expired', fn (Order $locked) => $locked->isOverdue());
    }

    /**
     * Semua pesanan yang lewat batas (dipanggil scheduler setiap menit).
     *
     * @return int jumlah pesanan yang dikedaluwarsakan
     */
    public function expireAllOverdue(): int
    {
        $expired = 0;

        Order::query()->overdue()->orderBy('id')->each(function (Order $order) use (&$expired) {
            $expired += (int) $this->expireIfOverdue($order);
        });

        return $expired;
    }
}
