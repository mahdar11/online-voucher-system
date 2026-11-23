<?php

namespace Tests\Modules\Payment;

use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use Inertia\Testing\AssertableInertia as Assert;

class XenditWebhookTest extends PaymentTestCase
{
    private function startedOrder(int $quantity = 2, int $stock = 5): Order
    {
        $order = $this->placeOrder(quantity: $quantity, stock: $stock);
        $this->post("/pesanan/{$order->invoice_number}/bayar");

        return $order;
    }

    private function soldCount(Order $order): int
    {
        return Voucher::query()->where('reservation_ref', $order->invoice_number)->where('status', VoucherStatus::Sold)->count();
    }

    public function test_webhook_requires_valid_callback_token(): void
    {
        $order = $this->startedOrder();

        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000, token: 'salah')->assertUnauthorized();
        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000, token: null)->assertUnauthorized();

        config(['services.xendit.callback_token' => '']);
        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000, token: '')->assertUnauthorized();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_paid_webhook_completes_order_and_reveals_voucher_codes(): void
    {
        $order = $this->startedOrder();

        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000)
            ->assertOk()
            ->assertJson(['received' => true, 'result' => 'paid']);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(2, $this->soldCount($order));

        $payment = Payment::query()->sole();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('QRIS', $payment->payment_channel);

        $this->get("/pesanan/{$order->invoice_number}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.status', 'paid')
                ->where('order.status_label', 'Lunas')
                ->has('order.vouchers', 2)
                ->where('order.vouchers.0.code', fn ($code) => str_starts_with($code, 'TEST'))
                ->where('order.can_pay', false));
    }

    public function test_duplicate_webhooks_are_processed_once(): void
    {
        $order = $this->startedOrder();

        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000)->assertJson(['result' => 'paid']);
        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000)->assertJson(['result' => 'ignored']);
        $this->webhook("{$order->invoice_number}-1", 'SETTLED', 10000)->assertJson(['result' => 'ignored']);

        $this->assertSame(2, $this->soldCount($order));
        $this->assertSame(3, Voucher::query()->where('status', VoucherStatus::Available)->count());
    }

    public function test_amount_mismatch_is_not_processed(): void
    {
        $order = $this->startedOrder();

        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000, ['paid_amount' => 1000])
            ->assertOk()
            ->assertJson(['result' => 'amount_mismatch']);

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
        $this->assertSame(PaymentStatus::Pending, Payment::query()->sole()->status);
        $this->assertSame(0, $this->soldCount($order));
    }

    public function test_expired_webhook_marks_payment_expired_only(): void
    {
        $order = $this->startedOrder();

        $this->webhook("{$order->invoice_number}-1", 'EXPIRED', 10000)->assertJson(['result' => 'expired']);

        $this->assertSame(PaymentStatus::Expired, Payment::query()->sole()->status);
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status); // kedaluwarsa pesanan diurus Ordering
    }

    public function test_unknown_invoice_is_acknowledged(): void
    {
        $this->webhook('INV-ASING-1', 'PAID', 5000)->assertOk()->assertJson(['result' => 'unknown']);
    }

    public function test_late_payment_is_fulfilled_when_stock_is_still_available(): void
    {
        $order = $this->startedOrder(quantity: 2, stock: 5);
        $this->travel(16)->minutes();
        $this->artisan('orders:expire');
        $this->assertSame(OrderStatus::Expired, $order->refresh()->status);

        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000)->assertJson(['result' => 'paid']);

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
        $this->assertSame(2, $this->soldCount($order));
    }

    public function test_late_payment_without_stock_requires_refund(): void
    {
        $order = $this->startedOrder(quantity: 2, stock: 2);
        $this->travel(16)->minutes();
        $this->artisan('orders:expire');

        // Stok yang dilepas sudah habis dibeli orang lain.
        Voucher::query()->update(['status' => VoucherStatus::Sold, 'reservation_ref' => 'INV-ORANG-LAIN']);

        $this->webhook("{$order->invoice_number}-1", 'PAID', 10000)->assertJson(['result' => 'paid']);

        $order->refresh();
        $this->assertSame(OrderStatus::RefundRequired, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(PaymentStatus::Paid, Payment::query()->sole()->status);

        $this->get("/pesanan/{$order->invoice_number}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.status', 'refund_required')
                ->missing('order.vouchers'));
    }

    public function test_voucher_codes_never_appear_before_payment(): void
    {
        $order = $this->startedOrder();

        $this->get("/pesanan/{$order->invoice_number}")
            ->assertInertia(fn (Assert $page) => $page->missing('order.vouchers'));
    }
}
