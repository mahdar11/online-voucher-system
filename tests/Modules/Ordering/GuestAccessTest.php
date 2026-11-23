<?php

namespace Tests\Modules\Ordering;

use App\Modules\Ordering\Mail\OrderHistoryLinkMail;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Services\OrderLinks;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class GuestAccessTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function guestOrder(string $email = 'tamu@example.test', ?string $phone = '0812-3456-7890'): Order
    {
        $package = $this->package(['slug' => 'paket-'.uniqid()]);
        $this->stock($package, 2);

        $this->post("/beli/{$package->slug}", array_filter([
            'quantity' => 1, 'name' => 'Tamu', 'email' => $email, 'phone' => $phone,
        ]))->assertSessionHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->flushSession(); // anggap pembeli membuka dari browser lain

        return $order;
    }

    // --- Link bertanda tangan dari email ---

    public function test_signed_email_link_grants_access_and_redirects_to_clean_url(): void
    {
        $order = $this->guestOrder();
        $this->get("/pesanan/{$order->invoice_number}")->assertNotFound();

        $this->get(app(OrderLinks::class)->signed($order->invoice_number))
            ->assertRedirect("/pesanan/{$order->invoice_number}");

        $this->get("/pesanan/{$order->invoice_number}")->assertOk();
    }

    public function test_tampered_expired_or_foreign_signed_links_are_rejected(): void
    {
        $order = $this->guestOrder('a@example.test');
        $other = $this->guestOrder('b@example.test');
        $link = app(OrderLinks::class)->signed($order->invoice_number);

        $this->get($link.'x')->assertNotFound();
        $this->get(str_replace($order->invoice_number, $other->invoice_number, $link))->assertNotFound();

        $this->travel(OrderLinks::VALID_DAYS + 1)->days();
        $this->get($link)->assertNotFound();

        $this->get("/pesanan/{$order->invoice_number}")->assertNotFound();
    }

    // --- Cek Pesanan ---

    public function test_lookup_with_invoice_and_email_or_phone(): void
    {
        $order = $this->guestOrder();

        $this->post('/cek-pesanan', ['invoice_number' => strtolower($order->invoice_number), 'contact' => 'TAMU@example.test'])
            ->assertRedirect("/pesanan/{$order->invoice_number}");

        $this->flushSession();
        $this->post('/cek-pesanan', ['invoice_number' => $order->invoice_number, 'contact' => '+62 812 3456 7890'])
            ->assertRedirect("/pesanan/{$order->invoice_number}");
        $this->get("/pesanan/{$order->invoice_number}")->assertOk();
    }

    public function test_lookup_failures_share_one_message(): void
    {
        $order = $this->guestOrder();
        $message = 'Nomor pesanan dan email/nomor HP tidak cocok.';

        $this->post('/cek-pesanan', ['invoice_number' => $order->invoice_number, 'contact' => 'lain@example.test'])
            ->assertSessionHasErrors(['invoice_number' => $message]);
        $this->post('/cek-pesanan', ['invoice_number' => 'INV-20990101-ZZZZZZ', 'contact' => 'tamu@example.test'])
            ->assertSessionHasErrors(['invoice_number' => $message]);

        $this->get("/pesanan/{$order->invoice_number}")->assertNotFound();
    }

    public function test_lookup_is_rate_limited(): void
    {
        $order = $this->guestOrder();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/cek-pesanan', ['invoice_number' => $order->invoice_number, 'contact' => "salah{$i}@example.test"]);
        }

        $this->post('/cek-pesanan', ['invoice_number' => $order->invoice_number, 'contact' => 'tamu@example.test'])
            ->assertSessionHasErrors(['invoice_number' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
    }

    // --- Riwayat lewat magic link ---

    public function test_history_magic_link_lists_only_that_emails_orders(): void
    {
        $mine = $this->guestOrder('tamu@example.test');
        $this->guestOrder('orang-lain@example.test');

        $this->post('/riwayat', ['email' => 'Tamu@Example.test'])->assertSessionHas('status');

        $url = null;
        Mail::assertQueued(OrderHistoryLinkMail::class, function (OrderHistoryLinkMail $mail) use (&$url) {
            $url = $mail->url;

            return $mail->hasTo('tamu@example.test');
        });
        $this->assertStringNotContainsString('tamu', $url); // email tidak pernah ada di URL

        $this->get($url)->assertRedirect('/riwayat/pesanan');
        $this->get('/riwayat/pesanan')->assertInertia(fn (Assert $page) => $page
            ->component('Ordering/History/Index')
            ->where('orders.total', 1)
            ->where('orders.data.0.invoice_number', $mine->invoice_number));

        $this->get("/pesanan/{$mine->invoice_number}")->assertOk();
    }

    public function test_history_request_does_not_reveal_unknown_emails(): void
    {
        $this->guestOrder('tamu@example.test');

        $known = $this->post('/riwayat', ['email' => 'tamu@example.test']);
        $unknown = $this->post('/riwayat', ['email' => 'tidak-ada@example.test']);

        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Mail::assertQueued(OrderHistoryLinkMail::class, 1);
    }

    public function test_history_link_expires(): void
    {
        $order = $this->guestOrder();
        $this->post('/riwayat', ['email' => 'tamu@example.test']);
        $url = Mail::queued(OrderHistoryLinkMail::class)->first()->url;

        $this->travel(31)->minutes();

        $this->get($url)->assertRedirect('/riwayat')->assertSessionHas('error');
        $this->get('/riwayat/pesanan')->assertRedirect('/riwayat');
        $this->get("/pesanan/{$order->invoice_number}")->assertNotFound();
    }

    public function test_history_access_ends_after_thirty_minutes(): void
    {
        $order = $this->guestOrder();
        $this->post('/riwayat', ['email' => 'tamu@example.test']);
        $this->get(Mail::queued(OrderHistoryLinkMail::class)->first()->url);

        $this->get("/pesanan/{$order->invoice_number}")->assertOk();
        $this->travel(31)->minutes();
        $this->get("/pesanan/{$order->invoice_number}")->assertNotFound();
    }

    public function test_history_requests_are_rate_limited(): void
    {
        $this->guestOrder();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/riwayat', ['email' => 'tamu@example.test'])->assertSessionHasNoErrors();
        }

        $this->post('/riwayat', ['email' => 'tamu@example.test'])->assertSessionHasErrors('email');
        Mail::assertQueued(OrderHistoryLinkMail::class, 3);
    }
}
