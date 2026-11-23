<?php

namespace App\Modules\Ordering\Actions;

use App\Modules\Inventory\Contracts\InsufficientStockException;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Enums\PaidOrderOutcome;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Models\OrderItem;
use App\Modules\Shared\Services\ActivityLogger;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menandai pesanan lunas dan menjual voucher yang dikuncinya.
 *
 * Pembayaran yang masuk setelah pesanan kedaluwarsa/dibatalkan tetap dihormati:
 * voucher dikunci ulang bila stok masih ada; bila habis, pesanan ditandai
 * "Perlu Refund" agar admin mengembalikan dana.
 */
class MarkOrderPaid
{
    public function __construct(
        private readonly VoucherReservations $reservations,
        private readonly ActivityLogger $logger,
    ) {}

    public function __invoke(string $invoiceNumber, DateTimeInterface $paidAt): PaidOrderOutcome
    {
        [$outcome, $order] = DB::transaction(function () use ($invoiceNumber, $paidAt) {
            /** @var Order|null $order */
            $order = Order::query()->with('items')->where('invoice_number', $invoiceNumber)->lockForUpdate()->first();

            if (! $order) {
                return [PaidOrderOutcome::NotFound, null];
            }

            if ($order->status->hasVouchers()) {
                return [PaidOrderOutcome::AlreadyPaid, $order];
            }

            if ($order->status === OrderStatus::RefundRequired) {
                return [PaidOrderOutcome::RefundRequired, $order];
            }

            $wasPending = $order->status === OrderStatus::Pending;
            $missing = $this->quantity($order) - $this->reservations->markSold($invoiceNumber, $paidAt);

            if ($missing > 0 && ! $this->reserveAndSell($order, $missing, $paidAt)) {
                $order->update(['status' => OrderStatus::RefundRequired, 'paid_at' => $paidAt]);

                return [PaidOrderOutcome::RefundRequired, $order];
            }

            $order->update(['status' => OrderStatus::Paid, 'paid_at' => $paidAt]);

            return [$wasPending && $missing <= 0 ? PaidOrderOutcome::Paid : PaidOrderOutcome::PaidLate, $order];
        });

        $this->record($outcome, $order);

        return $outcome;
    }

    /**
     * Pesanan yang vouchernya sudah dilepas (kedaluwarsa/dibatalkan) dikunci ulang.
     */
    private function reserveAndSell(Order $order, int $quantity, DateTimeInterface $paidAt): bool
    {
        try {
            $this->reservations->reserve($this->packageId($order), $quantity, $order->invoice_number, $paidAt);
        } catch (InsufficientStockException) {
            return false;
        }

        $this->reservations->markSold($order->invoice_number, $paidAt);

        return true;
    }

    private function quantity(Order $order): int
    {
        return (int) $order->items->sum('quantity');
    }

    private function packageId(Order $order): int
    {
        /** @var OrderItem $item */
        $item = $order->items->firstOrFail();

        return $item->package_id;
    }

    private function record(PaidOrderOutcome $outcome, ?Order $order): void
    {
        if (! $order || $outcome === PaidOrderOutcome::AlreadyPaid) {
            return;
        }

        $this->logger->log('order.'.$outcome->value, $order, ['invoice' => $order->invoice_number]);

        if ($outcome === PaidOrderOutcome::RefundRequired) {
            Log::warning('Pembayaran diterima tetapi stok habis; pesanan perlu refund.', ['invoice' => $order->invoice_number]);
        }
    }
}
