<?php

namespace Tests\Modules\Ordering;

use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class CheckoutTest extends ModuleTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'quantity' => 2,
            'name' => 'Budi',
            'email' => 'Budi@Example.test',
            'phone' => '0812-3456-7890',
            ...$overrides,
        ];
    }

    public function test_checkout_page_shows_package_and_purchase_limit(): void
    {
        $package = $this->package(['slug' => 'harian', 'validity_days' => 7]);
        $this->stock($package, 3);

        $this->get('/beli/harian')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ordering/Checkout/Create')
                ->where('package.slug', 'harian')
                ->where('package.validity_days', 7)
                ->where('maxQuantity', 3)
                ->where('reservationMinutes', 15)
                ->where('customer.email', ''));
    }

    public function test_logged_in_customer_gets_prefilled_form(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 10);
        $user = $this->customer();
        $user->update(['phone' => '081200001111']);

        $this->actingAs($user)
            ->get('/beli/harian')
            ->assertInertia(fn (Assert $page) => $page
                ->where('maxQuantity', 5)
                ->where('customer.name', $user->name)
                ->where('customer.email', $user->email)
                ->where('customer.phone', '081200001111'));
    }

    public function test_guest_checkout_creates_order_and_reserves_exact_vouchers(): void
    {
        $package = $this->package(['slug' => 'harian', 'name' => 'Voucher Harian', 'price' => 5000]);
        $this->stock($package, 5);

        $response = $this->post('/beli/harian', $this->payload());

        $order = Order::query()->with('items')->sole();
        $response->assertRedirect("/pesanan/{$order->invoice_number}")->assertSessionHas('success');

        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{6}$/', $order->invoice_number);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNull($order->user_id);
        $this->assertSame('budi@example.test', $order->customer_email);
        $this->assertSame('081234567890', $order->customer_phone);
        $this->assertSame(10000, $order->total);
        $this->assertEqualsWithDelta(now()->addMinutes(15)->timestamp, $order->expires_at->timestamp, 5);

        $item = $order->items->sole();
        $this->assertSame('Voucher Harian', $item->package_name);
        $this->assertSame(5000, $item->unit_price);
        $this->assertSame(2, $item->quantity);

        $reserved = Voucher::query()->where('status', VoucherStatus::Reserved)->get();
        $this->assertCount(2, $reserved);
        $this->assertTrue($reserved->every(fn (Voucher $v) => $v->reservation_ref === $order->invoice_number));
        $this->assertSame(3, Voucher::query()->where('status', VoucherStatus::Available)->count());
    }

    public function test_logged_in_checkout_links_order_to_user(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 2);
        $user = $this->customer();

        $this->actingAs($user)->post('/beli/harian', $this->payload(['quantity' => 1]));

        $this->assertSame($user->id, Order::query()->sole()->user_id);
    }

    public function test_insufficient_stock_creates_nothing(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 1);

        $this->post('/beli/harian', $this->payload(['quantity' => 2]))
            ->assertSessionHasErrors(['quantity' => 'Stok tidak cukup, tersisa 1 voucher.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, Voucher::query()->where('status', VoucherStatus::Available)->count());
    }

    public function test_quantity_and_customer_validation(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 10);

        $this->post('/beli/harian', $this->payload(['quantity' => 6, 'name' => '', 'email' => 'bukan-email', 'phone' => '123']))
            ->assertSessionHasErrors(['quantity', 'name', 'email', 'phone']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_or_unknown_package_is_not_for_sale(): void
    {
        $package = $this->package(['slug' => 'lama', 'is_active' => false]);
        $this->stock($package, 3);

        $this->get('/beli/lama')->assertNotFound();
        $this->post('/beli/lama', $this->payload())->assertNotFound();
        $this->get('/beli/tidak-ada')->assertNotFound();
    }

    public function test_email_is_limited_to_three_pending_orders(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 10);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/beli/harian', $this->payload(['quantity' => 1]))->assertSessionHasNoErrors();
        }

        $this->post('/beli/harian', $this->payload(['quantity' => 1]))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('orders', 3);
    }

    public function test_checkout_is_rate_limited_per_ip(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 20);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/beli/harian', $this->payload(['quantity' => 1, 'email' => "pembeli{$i}@example.test"]));
        }

        $this->post('/beli/harian', $this->payload(['quantity' => 1, 'email' => 'keenam@example.test']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 5);
    }
}
