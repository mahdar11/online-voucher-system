<?php

namespace Tests\Modules\Payment;

use App\Modules\Ordering\Models\Order;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\ModuleTestCase;

/**
 * Dasar test pembayaran: Xendit selalu disimulasikan (Http::fake) dan
 * permintaan HTTP yang tidak disimulasikan langsung membuat test gagal.
 */
abstract class PaymentTestCase extends ModuleTestCase
{
    protected const TOKEN = 'test-callback-token';

    /** @var list<array<string, mixed>> body permintaan create invoice yang diterima "Xendit" */
    protected array $createdInvoices = [];

    /** Status yang dikembalikan saat invoice dicek (GET). */
    protected string $remoteStatus = 'PENDING';

    protected int $remotePaidAmount = 0;

    /** Simulasikan Xendit sedang bermasalah (semua permintaan dijawab error). */
    protected bool $gatewayDown = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xendit.secret_key' => 'xnd_development_fake',
            'services.xendit.callback_token' => self::TOKEN,
            'services.xendit.base_url' => 'https://api.xendit.co',
        ]);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($this->gatewayDown) {
                return Http::response(['error_code' => 'SERVER_ERROR', 'message' => 'down'], 500);
            }

            if ($request->method() === 'POST' && $request->url() === 'https://api.xendit.co/v2/invoices') {
                $this->createdInvoices[] = $request->data();
                $n = count($this->createdInvoices);

                return Http::response($this->invoiceJson("inv_{$n}", $request->data()['external_id'], 'PENDING', $request->data()['amount']));
            }

            if ($request->method() === 'GET' && str_starts_with($request->url(), 'https://api.xendit.co/v2/invoices/')) {
                $id = basename(parse_url($request->url(), PHP_URL_PATH));
                $n = (int) str_replace('inv_', '', $id);
                $data = $this->createdInvoices[$n - 1];

                return Http::response($this->invoiceJson($id, $data['external_id'], $this->remoteStatus, $data['amount'], $this->remotePaidAmount ?: $data['amount']));
            }

            return Http::response(['error_code' => 'NOT_FOUND'], 404);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function invoiceJson(string $id, string $externalId, string $status, int $amount, ?int $paidAmount = null): array
    {
        return array_filter([
            'id' => $id,
            'external_id' => $externalId,
            'status' => $status,
            'amount' => $amount,
            'paid_amount' => in_array($status, ['PAID', 'SETTLED'], true) ? ($paidAmount ?? $amount) : null,
            'payment_method' => in_array($status, ['PAID', 'SETTLED'], true) ? 'QR_CODE' : null,
            'payment_channel' => in_array($status, ['PAID', 'SETTLED'], true) ? 'QRIS' : null,
            'paid_at' => in_array($status, ['PAID', 'SETTLED'], true) ? now()->toIso8601String() : null,
            'invoice_url' => "https://checkout-staging.xendit.co/web/{$id}",
            'expiry_date' => now()->addMinutes(15)->toIso8601String(),
        ], fn ($v) => $v !== null);
    }

    /**
     * Guest membuat pesanan lewat checkout (session browser ini boleh melihatnya).
     */
    protected function placeOrder(int $quantity = 2, int $stock = 5, int $price = 5000): Order
    {
        $package = $this->package(['slug' => 'paket-'.uniqid(), 'name' => 'Voucher Harian', 'price' => $price]);
        $this->stock($package, $stock);

        $this->post("/beli/{$package->slug}", [
            'quantity' => $quantity,
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'phone' => '081234567890',
        ])->assertSessionHasNoErrors();

        return Order::query()->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function webhook(string $externalId, string $status, int $amount, array $overrides = [], ?string $token = self::TOKEN)
    {
        $headers = $token === null ? [] : ['x-callback-token' => $token];

        return $this->postJson('/webhooks/xendit/invoice', [
            ...$this->invoiceJson('inv_1', $externalId, $status, $amount),
            ...$overrides,
        ], $headers);
    }
}
