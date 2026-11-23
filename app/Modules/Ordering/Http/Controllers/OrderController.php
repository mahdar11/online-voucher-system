<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Ordering\Actions\CancelOrder;
use App\Modules\Ordering\Actions\ExpireOrders;
use App\Modules\Ordering\Events\OrderViewed;
use App\Modules\Ordering\Exceptions\OrderNotCancellableException;
use App\Modules\Ordering\Http\Resources\OrderResource;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Services\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController
{
    public function __construct(private readonly OrderAccess $access) {}

    public function show(Request $request, Order $order, ExpireOrders $expireOrders, VoucherReservations $reservations): Response
    {
        $this->authorizeView($request, $order);

        // Beri kesempatan modul lain (Payment) memperbarui status pembayaran dulu,
        // baru cek kedaluwarsa: pembayaran yang sudah masuk tidak boleh hangus.
        event(new OrderViewed($order->invoice_number));
        $order->refresh();
        $expireOrders->expireIfOverdue($order);

        $codes = $order->status->hasVouchers() ? $reservations->codesFor($order->invoice_number) : [];

        return Inertia::render('Ordering/Orders/Show', [
            'order' => (new OrderResource($order->load('items')))->withVoucherCodes($codes)->resolve(),
        ]);
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        $this->authorizeView($request, $order);

        try {
            $cancelOrder($order);
        } catch (OrderNotCancellableException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan dibatalkan dan voucher dikembalikan ke stok.');
    }

    /**
     * Pesanan orang lain dijawab 404 (bukan 403) agar keberadaannya tidak bocor.
     */
    private function authorizeView(Request $request, Order $order): void
    {
        abort_unless($this->access->canView($request, $order), 404);
    }
}
