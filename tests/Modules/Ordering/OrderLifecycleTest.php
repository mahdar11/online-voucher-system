<?php

namespace Tests\Modules\Ordering;

use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class OrderLifecycleTest extends ModuleTestCase
{
    private function placeOrder(int $quantity = 2, array $overrides = []): Order
    {
        $package = $this->package(['slug' => 'paket-'.uniqid()]);
        $this->stock($package, 5);

        $this->post("/beli/{$package->slug}", [
            'quantity' => $quantity,
            'name' => 'Sari',
            'email' => 'sari@example.test',
            ...$overrides,
        ])->assertSessionHasNoErrors();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function reservedCount(Order $order): int
    {
        return Voucher::query()->where('reservation_ref', $order->invoice_number)->where('status', VoucherStatus::Reserved)->count();
    }

    public function test_order_page_shows_order_without_voucher_codes(): void
    {
        $order = $this->placeOrder();

        $this->get("/pesanan/{$order->invoice_number}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ordering/Orders/Show')
                ->where('order.invoice_number', $order->invoice_number)
                ->where('order.status', 'pending')
                ->where('order.can_cancel', true)
                ->where('order.can_pay', false)
                ->where('order.expires_in_seconds', fn ($s) => $s > 890 && $s <= 900)
                ->missing('order.vouchers')
                ->missing('order.items.0.codes'));
    }

    public function test_customer_can_cancel_and_vouchers_return_to_stock(): void
    {
        $order = $this->placeOrder();
        $this->assertSame(2, $this->reservedCount($order));

        $this->post("/pesanan/{$order->invoice_number}/batal")->assertSessionHas('success');

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(0, $this->reservedCount($order));
        $this->assertSame(5, Voucher::query()->where('status', VoucherStatus::Available)->whereNull('reservation_ref')->count());

        // Membatalkan lagi ditolak.
        $this->post("/pesanan/{$order->invoice_number}/batal")->assertSessionHas('error');
    }

    public function test_expire_command_expires_only_overdue_pending_orders(): void
    {
        $overdue = $this->placeOrder(1, ['email' => 'a@example.test']);
        $this->travel(10)->minutes();
        $fresh = $this->placeOrder(1, ['email' => 'b@example.test']);
        $this->travel(6)->minutes(); // overdue sudah 16 menit, fresh baru 6 menit

        $this->artisan('orders:expire')->expectsOutput('1 pesanan dikedaluwarsakan.')->assertSuccessful();

        $this->assertSame(OrderStatus::Expired, $overdue->refresh()->status);
        $this->assertSame(0, $this->reservedCount($overdue));
        $this->assertSame(OrderStatus::Pending, $fresh->refresh()->status);
        $this->assertSame(1, $this->reservedCount($fresh));
    }

    public function test_paid_orders_are_never_expired_or_cancelled(): void
    {
        $order = $this->placeOrder();
        $order->update(['status' => OrderStatus::Paid]);
        $this->travel(30)->minutes();

        $this->artisan('orders:expire')->expectsOutput('0 pesanan dikedaluwarsakan.');
        $this->post("/pesanan/{$order->invoice_number}/batal")->assertSessionHas('error');

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
        $this->assertSame(2, $this->reservedCount($order));
    }

    public function test_opening_an_overdue_order_expires_it_even_without_scheduler(): void
    {
        $order = $this->placeOrder();
        $this->travel(16)->minutes();

        $this->get("/pesanan/{$order->invoice_number}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.status', 'expired')
                ->where('order.can_cancel', false)
                ->where('order.expires_in_seconds', 0));

        $this->assertSame(0, $this->reservedCount($order));
    }
}
