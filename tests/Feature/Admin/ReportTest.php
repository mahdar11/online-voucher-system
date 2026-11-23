<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\VoucherPackage;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use App\Modules\Payment\Models\Payment;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class ReportTest extends ModuleTestCase
{
    private VoucherPackage $harian;

    private VoucherPackage $mingguan;

    protected function setUp(): void
    {
        parent::setUp();

        // "Sekarang" = 24 Nov 2026 10.00 WIB.
        $this->travelTo(CarbonImmutable::parse('2026-11-24 10:00', 'Asia/Jakarta'));
        $this->harian = $this->package(['name' => 'Harian', 'price' => 5000]);
        $this->mingguan = $this->package(['name' => 'Mingguan', 'price' => 20000]);
        $this->stock($this->harian, 2);
    }

    private function order(VoucherPackage $package, int $quantity, OrderStatus $status, ?string $paidAtWib, ?string $channel = 'QRIS'): Order
    {
        static $n = 0;
        $n++;
        $paidAt = $paidAtWib ? CarbonImmutable::parse($paidAtWib, 'Asia/Jakarta')->utc() : null;
        $total = $package->price * $quantity;

        $order = Order::query()->create([
            'invoice_number' => "INV-TEST-{$n}", 'customer_name' => 'X', 'customer_email' => "x{$n}@example.test",
            'subtotal' => $total, 'total' => $total, 'status' => $status, 'paid_at' => $paidAt,
            'expires_at' => now()->addMinutes(15),
        ]);
        $order->items()->create([
            'package_id' => $package->id, 'package_name' => $package->name,
            'quantity' => $quantity, 'unit_price' => $package->price, 'total' => $total,
        ]);

        if ($paidAt) {
            Payment::query()->create([
                'order_id' => $order->id, 'invoice_number' => $order->invoice_number, 'external_id' => "{$order->invoice_number}-1",
                'amount' => $total, 'status' => $status === OrderStatus::Refunded ? 'refunded' : 'paid', 'payment_channel' => $channel, 'paid_at' => $paidAt,
            ]);
        }

        return $order;
    }

    public function test_report_counts_only_paid_orders_with_wib_day_boundaries(): void
    {
        $this->order($this->harian, 2, OrderStatus::Completed, '2026-11-23 23:30');          // 16.30 UTC, tetap 23 Nov WIB
        $this->order($this->mingguan, 1, OrderStatus::Paid, '2026-11-24 00:30', 'BCA');      // 17.30 UTC tgl 23, tapi 24 Nov WIB
        $this->order($this->harian, 1, OrderStatus::Pending, null);
        $this->order($this->harian, 1, OrderStatus::Expired, null);
        $this->order($this->harian, 3, OrderStatus::Refunded, '2026-11-23 12:00');
        $this->order($this->harian, 1, OrderStatus::Completed, '2026-10-31 23:59');          // bulan lalu

        $this->actingAs($this->admin())
            ->get('/admin/laporan?preset=custom&from=2026-11-23&until=2026-11-24')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reports')
                ->where('summary', ['revenue' => 30000, 'orders' => 2, 'vouchers' => 3, 'average' => 15000])
                ->where('daily', [
                    ['date' => '2026-11-23', 'revenue' => 10000, 'orders' => 1],
                    ['date' => '2026-11-24', 'revenue' => 20000, 'orders' => 1],
                ])
                ->where('packages.0.package_name', 'Mingguan')
                ->where('packages.1.package_name', 'Harian')
                ->where('packages.1.vouchers', 2)
                ->where('packages.1.available', 2)
                ->where('channels.0.channel', 'BCA')
                ->where('channels.1', ['channel' => 'QRIS', 'method' => null, 'payments' => 1, 'amount' => 10000]));
    }

    public function test_presets_and_empty_days(): void
    {
        $this->order($this->harian, 1, OrderStatus::Completed, '2026-11-20 08:00');

        $this->actingAs($this->admin())->get('/admin/laporan?preset=7d')
            ->assertInertia(fn (Assert $page) => $page
                ->where('period.from', '2026-11-18')
                ->where('period.until', '2026-11-24')
                ->has('daily', 7)
                ->where('daily.2.revenue', 5000)
                ->where('daily.3.revenue', 0));

        $this->actingAs($this->admin())->get('/admin/laporan')
            ->assertInertia(fn (Assert $page) => $page->where('period.preset', 'month')->where('period.from', '2026-11-01'));

        $this->actingAs($this->admin())->get('/admin/laporan?preset=last_month')
            ->assertInertia(fn (Assert $page) => $page->where('period.from', '2026-10-01')->where('period.until', '2026-10-31'));
    }

    public function test_csv_export_lists_orders_in_range(): void
    {
        $paid = $this->order($this->harian, 2, OrderStatus::Completed, '2026-11-24 09:00');
        $pending = $this->order($this->harian, 1, OrderStatus::Pending, null);

        $response = $this->actingAs($this->admin())->get('/admin/laporan/export?preset=today');

        $response->assertOk()->assertDownload('pesanan-2026-11-24-sd-2026-11-24.csv');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('invoice;dibuat;dibayar;status', $csv);
        $this->assertStringContainsString("{$paid->invoice_number};", $csv);
        $this->assertStringContainsString('Selesai', $csv);
        $this->assertStringContainsString($pending->invoice_number, $csv);
    }

    public function test_dashboard_shows_today_and_attention(): void
    {
        $this->order($this->harian, 1, OrderStatus::Completed, '2026-11-24 09:00');
        $this->order($this->harian, 1, OrderStatus::RefundRequired, '2026-11-24 08:00');
        $this->order($this->harian, 1, OrderStatus::Pending, null);

        $this->actingAs($this->admin())->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('today.revenue', 5000)
                ->where('today.orders', 1)
                ->where('attention.refund_required', 1)
                ->where('attention.pending', 1));
    }

    public function test_reports_require_permission(): void
    {
        $this->actingAs($this->customer())->get('/admin/laporan')->assertForbidden();
        $this->actingAs($this->customer())->get('/admin/laporan/export')->assertForbidden();
    }
}
