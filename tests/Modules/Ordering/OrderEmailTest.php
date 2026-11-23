<?php

namespace Tests\Modules\Ordering;

use App\Modules\Ordering\Contracts\OrderPayments;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Events\OrderPaid;
use App\Modules\Ordering\Mail\OrderPlacedMail;
use App\Modules\Ordering\Mail\RefundRequiredMail;
use App\Modules\Ordering\Mail\VoucherDeliveredMail;
use App\Modules\Ordering\Models\Order;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Models\Voucher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ModuleTestCase;

class OrderEmailTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function placeOrder(int $quantity = 2, int $stock = 5): Order
    {
        $package = $this->package(['slug' => 'harian', 'name' => 'Voucher Harian', 'duration_minutes' => 180, 'validity_days' => 7]);
        $this->stock($package, $stock);

        $this->post('/beli/harian', ['quantity' => $quantity, 'name' => 'Rina', 'email' => 'Rina@Example.test'])
            ->assertSessionHasNoErrors();

        return Order::query()->sole();
    }

    private function pay(Order $order): void
    {
        app(OrderPayments::class)->markPaid($order->invoice_number, now());
    }

    public function test_order_placed_email_contains_signed_payment_link(): void
    {
        $order = $this->placeOrder();

        Mail::assertSent(OrderPlacedMail::class, function (OrderPlacedMail $mail) use ($order) {
            $html = $mail->render();

            return $mail->hasTo('rina@example.test')
                && str_contains($html, $order->invoice_number)
                && str_contains($html, 'Lanjutkan Pembayaran')
                && str_contains($mail->data['orderUrl'], 'signature=');
        });
        Mail::assertNotSent(VoucherDeliveredMail::class);
    }

    public function test_paid_order_emails_voucher_codes_and_becomes_completed(): void
    {
        $order = $this->placeOrder();
        $this->pay($order);

        $codes = Voucher::query()->where('reservation_ref', $order->invoice_number)->pluck('code')->all();

        Mail::assertSent(VoucherDeliveredMail::class, function (VoucherDeliveredMail $mail) use ($codes) {
            $html = $mail->render();

            return $mail->hasTo('rina@example.test')
                && collect($codes)->every(fn ($code) => str_contains($html, $code))
                && str_contains($html, '3 jam')
                && str_contains($html, '7 hari sejak login pertama');
        });

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
    }

    public function test_voucher_email_is_sent_once_even_with_duplicate_payments(): void
    {
        $order = $this->placeOrder();

        $this->pay($order);
        $this->pay($order);
        event(new OrderPaid($order->invoice_number)); // event dobel pun aman

        Mail::assertSent(VoucherDeliveredMail::class, 1);
    }

    public function test_failed_checkout_sends_no_email(): void
    {
        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 1);

        $this->post('/beli/harian', ['quantity' => 2, 'name' => 'Rina', 'email' => 'rina@example.test'])
            ->assertSessionHasErrors('quantity');

        Mail::assertNothingSent();
    }

    public function test_refund_notice_is_emailed_once(): void
    {
        $order = $this->placeOrder(quantity: 2, stock: 2);
        $this->travel(16)->minutes();
        $this->artisan('orders:expire');
        Voucher::query()->update(['status' => VoucherStatus::Sold, 'reservation_ref' => 'INV-ORANG-LAIN']);

        $this->pay($order);
        $this->pay($order);

        $this->assertSame(OrderStatus::RefundRequired, $order->refresh()->status);
        Mail::assertSent(RefundRequiredMail::class, 1);
        Mail::assertNotSent(VoucherDeliveredMail::class);
    }

    public function test_emails_are_queued_after_commit(): void
    {
        Event::fake([OrderPaid::class]);
        $order = $this->placeOrder();
        $this->pay($order);

        Event::assertDispatchedTimes(OrderPaid::class, 1);
        $this->assertTrue(is_subclass_of(
            \App\Modules\Ordering\Listeners\SendOrderEmails::class,
            \Illuminate\Contracts\Queue\ShouldQueueAfterCommit::class,
        ));
    }
}
