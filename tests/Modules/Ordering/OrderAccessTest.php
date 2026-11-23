<?php

namespace Tests\Modules\Ordering;

use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class OrderAccessTest extends ModuleTestCase
{
    private function guestOrder(): Order
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 3);

        $this->post('/beli/harian', ['quantity' => 1, 'name' => 'Tamu', 'email' => 'tamu@example.test']);

        return Order::query()->sole();
    }

    public function test_guest_can_open_own_order_in_same_browser(): void
    {
        $order = $this->guestOrder();

        $this->get("/pesanan/{$order->invoice_number}")->assertOk();
    }

    public function test_other_visitors_get_404(): void
    {
        $order = $this->guestOrder();

        $this->flushSession(); // browser lain
        $this->get("/pesanan/{$order->invoice_number}")->assertNotFound();
        $this->post("/pesanan/{$order->invoice_number}/batal")->assertNotFound();

        $this->actingAs($this->customer())->get("/pesanan/{$order->invoice_number}")->assertNotFound();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_owner_can_open_order_from_any_browser_after_login(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 3);
        $owner = $this->customer();

        $this->actingAs($owner)->post('/beli/harian', ['quantity' => 1, 'name' => 'Pemilik', 'email' => $owner->email]);
        $order = Order::query()->sole();

        $this->flushSession();
        $this->actingAs($owner)->get("/pesanan/{$order->invoice_number}")->assertOk();
    }

    public function test_my_orders_lists_only_own_orders(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 5);
        $me = $this->customer();
        $other = $this->customer();

        $this->actingAs($me)->post('/beli/harian', ['quantity' => 1, 'name' => 'Saya', 'email' => $me->email]);
        $this->actingAs($other)->post('/beli/harian', ['quantity' => 1, 'name' => 'Lain', 'email' => $other->email]);
        $mine = Order::query()->where('user_id', $me->id)->sole();

        $this->actingAs($me)
            ->get('/akun/pesanan')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ordering/Orders/Index')
                ->where('orders.total', 1)
                ->where('orders.data.0.invoice_number', $mine->invoice_number));

        $this->app['auth']->forgetGuards(); // logout
        $this->get('/akun/pesanan')->assertRedirect('/login');
    }
}
