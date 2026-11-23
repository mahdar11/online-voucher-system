<?php

namespace App\Modules\Ordering\Actions;

use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use App\Modules\Shared\Services\ActivityLogger;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Menutup pesanan pending (batal atau kedaluwarsa) dan mengembalikan vouchernya ke stok.
 * Baris pesanan dikunci dulu supaya tidak bentrok dengan pembayaran yang masuk bersamaan.
 */
class CloseOrder
{
    public function __construct(
        private readonly VoucherReservations $reservations,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  Closure(Order): bool  $mayClose  syarat tambahan, dicek setelah baris dikunci
     * @return bool true jika pesanan benar-benar ditutup
     */
    public function __invoke(Order $order, OrderStatus $finalStatus, string $action, ?Closure $mayClose = null): bool
    {
        $closed = DB::transaction(function () use ($order, $finalStatus, $mayClose) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending() || ($mayClose && ! $mayClose($locked))) {
                return false;
            }

            $locked->update(['status' => $finalStatus]);
            $this->reservations->release($locked->invoice_number);

            return true;
        });

        $order->refresh();

        if ($closed) {
            $this->logger->log($action, $order, ['invoice' => $order->invoice_number]);
        }

        return $closed;
    }
}
