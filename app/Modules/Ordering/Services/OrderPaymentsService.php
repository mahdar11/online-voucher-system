<?php

namespace App\Modules\Ordering\Services;

use App\Modules\Ordering\Actions\MarkOrderPaid;
use App\Modules\Ordering\Contracts\OrderPayments;
use App\Modules\Ordering\Data\PayableOrder;
use App\Modules\Ordering\Enums\PaidOrderOutcome;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Models\OrderItem;
use DateTimeInterface;
use Illuminate\Http\Request;

class OrderPaymentsService implements OrderPayments
{
    public function __construct(
        private readonly OrderAccess $access,
        private readonly MarkOrderPaid $markOrderPaid,
    ) {}

    public function find(string $invoiceNumber): ?PayableOrder
    {
        $order = $this->order($invoiceNumber);

        if (! $order) {
            return null;
        }

        return new PayableOrder(
            orderId: $order->id,
            invoiceNumber: $order->invoice_number,
            total: $order->total,
            customerName: $order->customer_name,
            customerEmail: $order->customer_email,
            customerPhone: $order->customer_phone,
            items: $order->items->map(fn (OrderItem $item) => [
                'name' => $item->package_name,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
            ])->values()->all(),
            expiresAt: $order->expires_at?->toImmutable(),
            isPayable: $order->isPending() && ! $order->isOverdue(),
        );
    }

    public function canAccess(Request $request, string $invoiceNumber): bool
    {
        $order = $this->order($invoiceNumber);

        return $order !== null && $this->access->canView($request, $order);
    }

    public function markPaid(string $invoiceNumber, DateTimeInterface $paidAt): PaidOrderOutcome
    {
        return ($this->markOrderPaid)($invoiceNumber, $paidAt);
    }

    private function order(string $invoiceNumber): ?Order
    {
        return Order::query()->with('items')->where('invoice_number', $invoiceNumber)->first();
    }
}
