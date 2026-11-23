<?php

namespace App\Modules\Ordering\Actions;

use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Exceptions\OrderNotCancellableException;
use App\Modules\Ordering\Models\Order;

/**
 * Pembeli membatalkan pesanan yang belum dibayar.
 */
class CancelOrder
{
    public function __construct(private readonly CloseOrder $closeOrder) {}

    /**
     * @throws OrderNotCancellableException
     */
    public function __invoke(Order $order): void
    {
        if (! ($this->closeOrder)($order, OrderStatus::Cancelled, 'order.cancelled')) {
            throw OrderNotCancellableException::for($order);
        }
    }
}
