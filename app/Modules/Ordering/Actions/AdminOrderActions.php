<?php

namespace App\Modules\Ordering\Actions;

use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Events\OrderRefunded;
use App\Modules\Ordering\Exceptions\OrderActionException;
use App\Modules\Ordering\Mail\OrderMailData;
use App\Modules\Ordering\Mail\VoucherDeliveredMail;
use App\Modules\Ordering\Models\Order;
use App\Modules\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Tindakan admin atas pesanan (dicatat di log aktivitas).
 */
class AdminOrderActions
{
    public function __construct(
        private readonly OrderMailData $mailData,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Mengirim ulang email voucher. Pesanan "Lunas" yang emailnya gagal menjadi "Selesai".
     *
     * @throws OrderActionException
     */
    public function resendVouchers(Order $order): void
    {
        if (! $order->status->hasVouchers()) {
            throw OrderActionException::noVouchers($order);
        }

        Mail::to($order->customer_email)->send(new VoucherDeliveredMail($this->mailData->for($order, withCodes: true)));

        if ($order->status === OrderStatus::Paid) {
            $order->update(['status' => OrderStatus::Completed]);
        }

        $this->logger->log('order.vouchers_resent', $order, ['invoice' => $order->invoice_number]);
    }

    /**
     * Dana sudah dikembalikan di luar aplikasi (dashboard Xendit / transfer).
     *
     * @throws OrderActionException
     */
    public function markRefunded(Order $order, string $note): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::RefundRequired) {
                throw OrderActionException::notRefundable($locked);
            }

            $locked->update(['status' => OrderStatus::Refunded]);
        });

        $order->refresh();
        $this->logger->log('order.refunded', $order, ['invoice' => $order->invoice_number, 'note' => $note]);

        event(new OrderRefunded($order->invoice_number));
    }
}
