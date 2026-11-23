<?php

namespace Tests\Modules\Payment;

use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Models\Payment;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class StartPaymentTest extends PaymentTestCase
{
    public function test_starting_payment_creates_xendit_invoice_and_redirects_to_it(): void
    {
        $order = $this->placeOrder(quantity: 2, price: 5000);

        $this->post("/pesanan/{$order->invoice_number}/bayar")
            ->assertRedirect('https://checkout-staging.xendit.co/web/inv_1');

        $this->assertCount(1, $this->createdInvoices);
        $sent = $this->createdInvoices[0];
        $this->assertSame("{$order->invoice_number}-1", $sent['external_id']);
        $this->assertSame(10000, $sent['amount']);
        $this->assertSame('IDR', $sent['currency']);
        $this->assertSame('rina@example.test', $sent['payer_email']);
        $this->assertSame('+6281234567890', $sent['customer']['mobile_number']);
        $this->assertSame([['name' => 'Voucher Harian', 'quantity' => 2, 'price' => 5000]], $sent['items']);
        $this->assertGreaterThan(890, $sent['invoice_duration']);
        $this->assertLessThanOrEqual(900, $sent['invoice_duration']);
        $this->assertStringContainsString("/pesanan/{$order->invoice_number}?bayar=selesai", $sent['success_redirect_url']);

        $payment = Payment::query()->sole();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame($order->invoice_number, $payment->invoice_number);
        $this->assertSame('inv_1', $payment->xendit_invoice_id);
        $this->assertSame(10000, $payment->amount);

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('xnd_development_fake:')));
    }

    public function test_paying_again_reuses_the_open_invoice(): void
    {
        $order = $this->placeOrder();

        $this->post("/pesanan/{$order->invoice_number}/bayar")->assertRedirect('https://checkout-staging.xendit.co/web/inv_1');
        $this->post("/pesanan/{$order->invoice_number}/bayar")->assertRedirect('https://checkout-staging.xendit.co/web/inv_1');

        $this->assertCount(1, $this->createdInvoices);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_is_unavailable_without_secret_key(): void
    {
        config(['services.xendit.secret_key' => null]);
        $order = $this->placeOrder();

        $this->post("/pesanan/{$order->invoice_number}/bayar")->assertSessionHas('error');

        $this->assertSame([], $this->createdInvoices);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_other_visitors_cannot_start_payment(): void
    {
        $order = $this->placeOrder();
        $this->flushSession();

        $this->post("/pesanan/{$order->invoice_number}/bayar")->assertNotFound();
        $this->post('/pesanan/INV-TIDAK-ADA/bayar')->assertNotFound();

        $this->assertSame([], $this->createdInvoices);
    }

    public function test_expired_or_nearly_expired_orders_cannot_be_paid(): void
    {
        $order = $this->placeOrder();
        $this->travel(14 * 60 + 30)->seconds(); // sisa 30 detik

        $this->post("/pesanan/{$order->invoice_number}/bayar")
            ->assertSessionHas('error', 'Waktu pembayaran hampir habis. Silakan buat pesanan baru.');

        $this->travel(1)->minutes();
        $this->post("/pesanan/{$order->invoice_number}/bayar")
            ->assertSessionHas('error', 'Pesanan ini tidak bisa dibayar lagi.');

        $this->assertSame([], $this->createdInvoices);
    }

    public function test_gateway_failure_shows_friendly_error(): void
    {
        $order = $this->placeOrder();
        $this->gatewayDown = true;

        $this->post("/pesanan/{$order->invoice_number}/bayar")
            ->assertSessionHas('error', 'Gagal membuat tagihan pembayaran. Silakan coba lagi sebentar lagi.');

        $this->assertDatabaseCount('payments', 0);
    }
}
