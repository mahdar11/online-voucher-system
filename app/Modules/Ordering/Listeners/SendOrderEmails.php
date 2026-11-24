<?php

namespace App\Modules\Ordering\Listeners;

use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Events\OrderPaid;
use App\Modules\Ordering\Events\OrderPlaced;
use App\Modules\Ordering\Events\OrderRefundRequired;
use App\Modules\Ordering\Mail\OrderMailData;
use App\Modules\Ordering\Mail\OrderPlacedMail;
use App\Modules\Ordering\Mail\RefundRequiredMail;
use App\Modules\Ordering\Mail\VoucherDeliveredMail;
use App\Modules\Ordering\Models\Order;
use App\Modules\Shared\Services\ActivityLogger;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Mail;

/**
 * Mengirim email pesanan lewat queue, setelah transaksi database selesai.
 * Gagal kirim dicoba ulang dengan jeda.
 */
class SendOrderEmails implements ShouldQueueAfterCommit
{
    public int $tries = 3;

    /** @var list<int> detik */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly OrderMailData $mailData,
        private readonly ActivityLogger $logger,
    ) {}

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $order = $this->order($event->invoiceNumber);

        if ($order?->isPending()) {
            Mail::to($order->customer_email)->send(new OrderPlacedMail($this->mailData->for($order)));
        }
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $order = $this->order($event->invoiceNumber);

        // Hanya pesanan "Lunas" yang belum dikirimi email; "Selesai" berarti sudah terkirim.
        if ($order?->status !== OrderStatus::Paid) {
            return;
        }

        Mail::to($order->customer_email)->send(new VoucherDeliveredMail($this->mailData->for($order, withCodes: true)));

        $order->update(['status' => OrderStatus::Completed]);
        $this->logger->log('order.vouchers_emailed', $order, ['invoice' => $order->invoice_number]);
    }

    public function onOrderRefundRequired(OrderRefundRequired $event): void
    {
        $order = $this->order($event->invoiceNumber);

        if ($order?->status === OrderStatus::RefundRequired) {
            Mail::to($order->customer_email)->send(new RefundRequiredMail($this->mailData->for($order)));
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderPlaced::class => 'onOrderPlaced',
            OrderPaid::class => 'onOrderPaid',
            OrderRefundRequired::class => 'onOrderRefundRequired',
        ];
    }

    private function order(string $invoiceNumber): ?Order
    {
        return Order::query()->with('items')->where('invoice_number', $invoiceNumber)->first();
    }
}
