import { formatNumber, formatRupiah } from '@/lib/format.js';

/**
 * Gaya grafik bersama (lihat panduan dataviz): satu seri = satu warna,
 * batang tipis (<= 24px) dengan ujung membulat 4px dari baseline, grid hairline
 * yang tidak menonjol, teks memakai warna teks (bukan warna data), dan tooltip.
 */
export const CHART_TOKENS = {
    series1: '#2a78d6',
    surface: '#ffffff',
    textPrimary: '#111827',
    textSecondary: '#6b7280',
    grid: '#e5e7eb',
};

/** 1.250.000 -> "1,3 jt", 12.000 -> "12 rb" (sumbu ringkas). */
export function compactRupiah(value) {
    const abs = Math.abs(value);
    if (abs >= 1_000_000_000) return `${formatNumber(Math.round(value / 100_000_000) / 10)} M`;
    if (abs >= 1_000_000) return `${formatNumber(Math.round(value / 100_000) / 10)} jt`;
    if (abs >= 1_000) return `${formatNumber(Math.round(value / 1_000))} rb`;
    return formatNumber(value);
}

function baseOptions() {
    return {
        chart: {
            fontFamily: 'inherit',
            foreColor: CHART_TOKENS.textSecondary,
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, speed: 300 },
            parentHeightOffset: 0,
        },
        colors: [CHART_TOKENS.series1],
        fill: { opacity: 1 },
        dataLabels: { enabled: false },
        legend: { show: false }, // satu seri: judul kartu yang menamai seri
        grid: {
            borderColor: CHART_TOKENS.grid,
            strokeDashArray: 0,
            padding: { left: 8, right: 8 },
        },
        states: {
            hover: { filter: { type: 'darken', value: 0.12 } },
            active: { filter: { type: 'none' } },
        },
        tooltip: { theme: 'light' },
    };
}

/**
 * Kolom pendapatan per hari. `days` = [{ date: 'YYYY-MM-DD', revenue, orders }].
 */
export function dailyRevenueOptions(days) {
    const dateLabel = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' });
    const fullDate = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const parse = (iso) => new Date(`${iso}T00:00:00`);

    return {
        ...baseOptions(),
        chart: { ...baseOptions().chart, type: 'bar', height: 280 },
        series: [{ name: 'Pendapatan', data: days.map((d) => d.revenue) }],
        plotOptions: {
            bar: {
                columnWidth: days.length <= 7 ? '24px' : '70%',
                borderRadius: 4,
                borderRadiusApplication: 'end',
            },
        },
        stroke: { show: true, width: 2, colors: ['transparent'] }, // celah 2px antar kolom
        xaxis: {
            categories: days.map((d) => dateLabel.format(parse(d.date))),
            axisBorder: { color: CHART_TOKENS.grid },
            axisTicks: { show: false },
            labels: { hideOverlappingLabels: true, rotate: 0 },
            tooltip: { enabled: false },
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: { formatter: (value) => compactRupiah(value) },
        },
        tooltip: {
            theme: 'light',
            custom: ({ dataPointIndex }) => {
                const day = days[dataPointIndex];
                return `<div class="px-3 py-2 text-xs">
                    <div class="font-medium text-gray-900">${fullDate.format(parse(day.date))}</div>
                    <div class="mt-1 text-gray-600">Pendapatan: <span class="font-semibold text-gray-900">${formatRupiah(day.revenue)}</span></div>
                    <div class="text-gray-600">Pesanan lunas: <span class="font-semibold text-gray-900">${formatNumber(day.orders)}</span></div>
                </div>`;
            },
        },
    };
}

/**
 * Batang horizontal per metode pembayaran, terbesar di atas. Angka persis ada di
 * tabel tepat di bawah grafik (dan tooltip), jadi tanpa label di ujung batang.
 * `channels` = [{ channel, payments, amount }].
 */
export function channelOptions(channels) {
    return {
        ...baseOptions(),
        chart: { ...baseOptions().chart, type: 'bar', height: Math.max(120, channels.length * 44 + 40) },
        series: [{ name: 'Pendapatan', data: channels.map((c) => c.amount) }],
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '24px',
                borderRadius: 4,
                borderRadiusApplication: 'end',
            },
        },
        grid: { ...baseOptions().grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } }, padding: { left: 8, right: 16 } },
        xaxis: {
            categories: channels.map((c) => c.channel),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { formatter: (value) => compactRupiah(Number(value)) },
        },
        yaxis: { labels: { style: { colors: CHART_TOKENS.textPrimary, fontSize: '12px' } } },
        tooltip: {
            theme: 'light',
            custom: ({ dataPointIndex }) => {
                const c = channels[dataPointIndex];
                return `<div class="px-3 py-2 text-xs">
                    <div class="font-medium text-gray-900">${c.channel}</div>
                    <div class="mt-1 text-gray-600">Pendapatan: <span class="font-semibold text-gray-900">${formatRupiah(c.amount)}</span></div>
                    <div class="text-gray-600">Transaksi: <span class="font-semibold text-gray-900">${formatNumber(c.payments)}</span></div>
                </div>`;
            },
        },
    };
}
