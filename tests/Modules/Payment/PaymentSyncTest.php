<?php

namespace Tests\Modules\Payment;

use App\Modules\Ordering\Enums\OrderStatus;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Cadangan bila webhook tidak sampai (mis. localhost): status dicek ke Xendit
 * saat pembeli membuka halaman pesanannya.
 */
class PaymentSyncTest extends PaymentTestCase
{
    private function countStatusChecks(): int
    {
        return count(array_filter(
            Http::recorded()->all(),
            fn (array $pair) => $pair[0]->method() === 'GET',
        ));
    }

    public function test_returning_buyer_sees_paid_order_without_webhook(): void
    {
        $order = $this->placeOrder();
        $this->post("/pesanan/{$order->invoice_number}/bayar");
        $this->remoteStatus = 'PAID';

        $this->get("/pesanan/{$order->invoice_number}?bayar=selesai")
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.status', 'completed')
                ->has('order.vouchers', 2));

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
    }

    public function test_status_checks_are_throttled(): void
    {
        $order = $this->placeOrder();
        $this->post("/pesanan/{$order->invoice_number}/bayar");

        $this->get("/pesanan/{$order->invoice_number}");
        $this->get("/pesanan/{$order->invoice_number}");
        $this->assertSame(1, $this->countStatusChecks());

        $this->travel(11)->seconds();
        $this->get("/pesanan/{$order->invoice_number}");
        $this->assertSame(2, $this->countStatusChecks());
    }

    public function test_no_status_check_without_open_payment(): void
    {
        $order = $this->placeOrder();

        $this->get("/pesanan/{$order->invoice_number}")->assertOk();

        $this->assertSame(0, $this->countStatusChecks());
    }

    public function test_gateway_errors_during_check_do_not_break_the_page(): void
    {
        $order = $this->placeOrder();
        $this->post("/pesanan/{$order->invoice_number}/bayar");

        $this->gatewayDown = true;

        $this->get("/pesanan/{$order->invoice_number}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.status', 'pending'));
    }
}
