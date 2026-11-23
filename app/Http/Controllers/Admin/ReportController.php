<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Ordering\Contracts\SalesReport;
use App\Modules\Ordering\Data\SalesPeriod;
use App\Modules\Payment\Contracts\PaymentStats;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan penjualan: menggabungkan Ordering (penjualan), Payment (metode bayar),
 * dan Inventory (sisa stok) lewat kontrak publik masing-masing.
 */
class ReportController
{
    private const PRESETS = ['today' => 'Hari ini', '7d' => '7 hari', 'month' => 'Bulan ini', 'last_month' => 'Bulan lalu'];

    public function index(Request $request, SalesReport $sales, PaymentStats $payments, PackageStockReader $stock): Response
    {
        [$period, $preset] = $this->period($request);

        $packages = $sales->byPackage($period);
        $stockByPackage = $stock->stockFor(array_column($packages, 'package_id'));

        return Inertia::render('Admin/Reports', [
            'period' => [
                'preset' => $preset,
                'from' => $period->from->toDateString(),
                'until' => $period->until->toDateString(),
                'timezone' => $period->timezone,
            ],
            'presets' => array_map(fn ($value, $label) => ['value' => $value, 'label' => $label], array_keys(self::PRESETS), self::PRESETS),
            'summary' => $sales->summary($period),
            'daily' => $sales->daily($period),
            'packages' => array_map(fn (array $row) => [
                ...$row,
                'available' => ($stockByPackage[$row['package_id']] ?? null)?->available ?? 0,
            ], $packages),
            'channels' => $payments->byChannel($period),
        ]);
    }

    public function export(Request $request, SalesReport $sales): StreamedResponse
    {
        [$period] = $this->period($request);
        $filename = "pesanan-{$period->from->toDateString()}-sd-{$period->until->toDateString()}.csv";

        return response()->streamDownload(function () use ($sales, $period) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            $header = false;

            foreach ($sales->exportRows($period) as $row) {
                if (! $header) {
                    fputcsv($out, array_keys($row), ';');
                    $header = true;
                }
                fputcsv($out, $row, ';');
            }

            if (! $header) {
                fputcsv($out, ['invoice', 'dibuat', 'dibayar', 'status', 'nama', 'email', 'hp', 'paket', 'jumlah', 'total'], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: SalesPeriod, 1: string}
     */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'preset' => ['nullable', 'in:'.implode(',', [...array_keys(self::PRESETS), 'custom'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $timezone = (string) config('voucher.business_timezone', 'Asia/Jakarta');
        $today = CarbonImmutable::now($timezone);
        $preset = $data['preset'] ?? (isset($data['from']) ? 'custom' : 'month');

        [$from, $until] = match ($preset) {
            'today' => [$today, $today],
            '7d' => [$today->subDays(6), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'custom' => [CarbonImmutable::parse($data['from'] ?? $today->toDateString(), $timezone), CarbonImmutable::parse($data['until'] ?? $data['from'] ?? $today->toDateString(), $timezone)],
            default => [$today->startOfMonth(), $today],
        };

        return [SalesPeriod::days($from->toDateString(), $until->toDateString(), $timezone), $preset];
    }
}
