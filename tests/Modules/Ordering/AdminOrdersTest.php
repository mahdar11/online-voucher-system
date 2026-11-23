<?php

namespace Tests\Modules\Ordering;

use App\Modules\Identity\Enums\Role;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Ordering\Contracts\OrderPayments;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Mail\VoucherDeliveredMail;
use App\Modules\Ordering\Models\Order;
use App\Modules\Payment\Models\Payment;
use App\Modules\Shared\Models\ActivityLog;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class AdminOrdersTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function placeOrder(string $email = 'rina@example.test', int $quantity = 1, int $stock = 3): Order
    {
        $package = $this->package(['slug' => 'paket-'.uniqid(), 'name' => 'Voucher Harian']);
        $this->stock($package, $stock);

        $this->post("/beli/{$package->slug}", ['quantity' => $quantity, 'name' => 'Rina', 'email' => $email])
            ->assertSessionHasNoErrors();
        $this->flushSession();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_only_order_managers_can_open_admin_orders(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->customer())->get('/admin/pesanan')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/pesanan')->assertOk();
        $this->actingAs($this->admin())->get("/admin/pesanan/{$order->invoice_number}")->assertOk();
    }

    public function test_filters_search_and_needs_action_tab(): void
    {
        $pending = $this->placeOrder('a@example.test');
        $refund = $this->placeOrder('b@example.test');
        $refund->update(['status' => OrderStatus::RefundRequired]);
        $stuck = $this->placeOrder('c@example.test');
        $stuck->update(['status' => OrderStatus::Paid, 'paid_at' => now()->subHour()]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/pesanan?status=pending')
            ->assertInertia(fn (Assert $page) => $page->where('orders.total', 1)->where('orders.data.0.invoice_number', $pending->invoice_number));

        $this->actingAs($admin)->get('/admin/pesanan?status=perlu-tindakan')
            ->assertInertia(fn (Assert $page) => $page->where('orders.total', 2));

        $this->actingAs($admin)->get('/admin/pesanan?search=B@EXAMPLE')
            ->assertInertia(fn (Assert $page) => $page->where('orders.total', 1)->where('orders.data.0.invoice_number', $refund->invoice_number));

        $this->actingAs($admin)->get('/admin/pesanan?search='.substr($stuck->invoice_number, -6))
            ->assertInertia(fn (Assert $page) => $page->where('orders.data.0.invoice_number', $stuck->invoice_number));
    }

    public function test_detail_shows_codes_payments_and_history(): void
    {
        $order = $this->placeOrder();
        Payment::query()->create([
            'order_id' => $order->id, 'invoice_number' => $order->invoice_number, 'external_id' => "{$order->invoice_number}-1",
            'amount' => $order->total, 'status' => 'paid', 'payment_channel' => 'QRIS', 'paid_at' => now(),
        ]);
        app(OrderPayments::class)->markPaid($order->invoice_number, now());

        $this->actingAs($this->admin())->get("/admin/pesanan/{$order->invoice_number}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ordering/Admin/Orders/Show')
                ->has('vouchers', 1)
                ->where('payments.0.channel', 'QRIS')
                ->where('payments.0.status', 'paid')
                ->where('activity', fn ($activity) => collect($activity)->pluck('action')->contains('order.paid'))
                ->where('order.can.resend', true)
                ->where('order.can.refund', false));
    }

    public function test_resend_voucher_email_completes_a_stuck_paid_order(): void
    {
        $order = $this->placeOrder();
        app(OrderPayments::class)->markPaid($order->invoice_number, now());
        $order->update(['status' => OrderStatus::Paid]); // anggap email pertama gagal
        Mail::fake();

        $this->actingAs($this->admin())->post("/admin/pesanan/{$order->invoice_number}/kirim-ulang")
            ->assertSessionHas('success');

        Mail::assertSent(VoucherDeliveredMail::class, fn ($mail) => $mail->hasTo('rina@example.test'));
        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
        $this->assertTrue(ActivityLog::query()->where('action', 'order.vouchers_resent')->exists());
    }

    public function test_resend_is_rejected_for_unpaid_orders(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->admin())->post("/admin/pesanan/{$order->invoice_number}/kirim-ulang")->assertSessionHas('error');
        Mail::assertNotSent(VoucherDeliveredMail::class);
    }

    public function test_mark_refunded_requires_note_and_refund_required_status(): void
    {
        $order = $this->placeOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/pesanan/{$order->invoice_number}/refund", ['note' => 'TRF-1'])->assertSessionHas('error');

        $order->update(['status' => OrderStatus::RefundRequired]);
        $payment = Payment::query()->create([
            'order_id' => $order->id, 'invoice_number' => $order->invoice_number, 'external_id' => "{$order->invoice_number}-1",
            'amount' => $order->total, 'status' => 'paid', 'paid_at' => now(),
        ]);
        $this->actingAs($admin)->post("/admin/pesanan/{$order->invoice_number}/refund", ['note' => ''])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post("/admin/pesanan/{$order->invoice_number}/refund", ['note' => 'Refund via Xendit #RF-123'])
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Refunded, $order->refresh()->status);
        $this->assertSame('refunded', $payment->refresh()->status->value); // tidak lagi dihitung uang masuk
        $log = ActivityLog::query()->where('action', 'order.refunded')->sole();
        $this->assertSame('Refund via Xendit #RF-123', $log->properties['note']);
        $this->assertSame($admin->id, $log->user_id);
        // Waktu log harus UTC dari aplikasi, bukan dari zona waktu server database.
        $this->assertEqualsWithDelta(now()->timestamp, $log->created_at->timestamp, 5);
    }

    public function test_admin_can_cancel_pending_order(): void
    {
        $order = $this->placeOrder(quantity: 2);

        $this->actingAs($this->admin())->post("/admin/pesanan/{$order->invoice_number}/batal")->assertSessionHas('success');

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(0, Voucher::query()->where('status', VoucherStatus::Reserved)->count());
    }

    public function test_super_admin_also_manages_orders(): void
    {
        $this->actingAs($this->userWithRole(Role::SuperAdmin))->get('/admin/pesanan')->assertOk();
    }
}
