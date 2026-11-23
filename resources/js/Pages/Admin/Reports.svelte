<script>
    import { untrack } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import ApexChart from '@/Components/Charts/ApexChart.svelte';
    import { channelOptions, dailyRevenueOptions } from '@/lib/charts.js';
    import { formatNumber, formatRupiah } from '@/lib/format.js';

    let { period, presets, summary, daily, packages, channels } = $props();

    let from = $state(untrack(() => period.from));
    let until = $state(untrack(() => period.until));
    let showTable = $state(false);

    const dateFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    const periodLabel = $derived(
        period.from === period.until
            ? dateFormat.format(new Date(`${period.from}T00:00:00`))
            : `${dateFormat.format(new Date(`${period.from}T00:00:00`))} – ${dateFormat.format(new Date(`${period.until}T00:00:00`))}`,
    );

    const dailyOptions = $derived(dailyRevenueOptions(daily));
    const channelChart = $derived(channelOptions(channels));
    const exportUrl = $derived(`/admin/laporan/export?preset=custom&from=${period.from}&until=${period.until}`);

    const tiles = $derived([
        { label: 'Pendapatan', value: formatRupiah(summary.revenue) },
        { label: 'Pesanan lunas', value: formatNumber(summary.orders) },
        { label: 'Voucher terjual', value: formatNumber(summary.vouchers) },
        { label: 'Rata-rata per pesanan', value: formatRupiah(summary.average) },
    ]);

    function choose(preset) {
        router.get('/admin/laporan', { preset }, { preserveScroll: true });
    }

    function applyCustom(event) {
        event.preventDefault();
        router.get('/admin/laporan', { preset: 'custom', from, until }, { preserveScroll: true });
    }
</script>

<AdminLayout title="Laporan Penjualan">
    {#snippet actions()}
        <a href={exportUrl} class="rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Export CSV
        </a>
    {/snippet}

    <!-- Filter: satu baris di atas semua grafik -->
    <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4">
        <div class="flex flex-wrap gap-1" role="group" aria-label="Rentang cepat">
            {#each presets as preset (preset.value)}
                <button
                    type="button"
                    aria-pressed={period.preset === preset.value}
                    class="rounded-lg px-3 py-1.5 text-sm font-medium
                        {period.preset === preset.value ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'}"
                    onclick={() => choose(preset.value)}
                >
                    {preset.label}
                </button>
            {/each}
        </div>
        <form class="flex flex-wrap items-end gap-2" onsubmit={applyCustom}>
            <label class="text-xs text-gray-600">
                Dari
                <input type="date" bind:value={from} class="mt-1 block rounded-lg border border-gray-300 px-2 py-1.5 text-sm" />
            </label>
            <label class="text-xs text-gray-600">
                Sampai
                <input type="date" bind:value={until} min={from} class="mt-1 block rounded-lg border border-gray-300 px-2 py-1.5 text-sm" />
            </label>
            <button type="submit" class="rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-700">Terapkan</button>
        </form>
        <p class="ml-auto text-xs text-gray-500">{periodLabel} · {period.timezone === 'Asia/Jakarta' ? 'WIB' : period.timezone}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {#each tiles as tile (tile.label)}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-600">{tile.label}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{tile.value}</p>
            </div>
        {/each}
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">Pendapatan per Hari</h2>
            <button type="button" class="text-sm text-blue-700 hover:underline" onclick={() => (showTable = !showTable)}>
                {showTable ? 'Lihat grafik' : 'Lihat tabel'}
            </button>
        </div>

        {#if summary.orders === 0}
            <p class="py-12 text-center text-sm text-gray-500">Belum ada penjualan pada periode ini.</p>
        {:else if showTable}
            <div class="mt-3 max-h-80 overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 bg-white text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr><th class="py-2 pr-4">Tanggal</th><th class="py-2 pr-4 text-right">Pesanan</th><th class="py-2 text-right">Pendapatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {#each daily as day (day.date)}
                            <tr>
                                <td class="py-1.5 pr-4">{dateFormat.format(new Date(`${day.date}T00:00:00`))}</td>
                                <td class="py-1.5 pr-4 text-right tabular-nums">{formatNumber(day.orders)}</td>
                                <td class="py-1.5 text-right tabular-nums">{formatRupiah(day.revenue)}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {:else}
            <div class="mt-2">
                <ApexChart options={dailyOptions} label="Grafik pendapatan per hari, {periodLabel}" />
            </div>
        {/if}
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">Uang Masuk per Metode Pembayaran</h2>
            <p class="mt-0.5 text-xs text-gray-500">Termasuk pembayaran yang sedang menunggu refund; pendapatan di atas tidak menghitungnya.</p>
            {#if channels.length === 0}
                <p class="py-8 text-center text-sm text-gray-500">Belum ada pembayaran.</p>
            {:else}
                <ApexChart options={channelChart} label="Grafik pendapatan per metode pembayaran" />
                <table class="mt-2 min-w-full text-sm">
                    <caption class="sr-only">Rincian per metode pembayaran</caption>
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr><th class="py-2">Metode</th><th class="py-2 text-right">Transaksi</th><th class="py-2 text-right">Pendapatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {#each channels as channel (channel.channel)}
                            <tr>
                                <td class="py-1.5">{channel.channel}</td>
                                <td class="py-1.5 text-right tabular-nums">{formatNumber(channel.payments)}</td>
                                <td class="py-1.5 text-right tabular-nums">{formatRupiah(channel.amount)}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            {/if}
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">Per Paket</h2>
            {#if packages.length === 0}
                <p class="py-8 text-center text-sm text-gray-500">Belum ada paket terjual.</p>
            {:else}
                <div class="overflow-x-auto">
                    <table class="mt-2 min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="py-2 pr-3">Paket</th>
                                <th class="py-2 pr-3 text-right">Terjual</th>
                                <th class="py-2 pr-3 text-right">Pendapatan</th>
                                <th class="py-2 text-right">Sisa stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            {#each packages as pkg (pkg.package_id)}
                                <tr>
                                    <td class="py-1.5 pr-3">{pkg.package_name}</td>
                                    <td class="py-1.5 pr-3 text-right tabular-nums">{formatNumber(pkg.vouchers)}</td>
                                    <td class="py-1.5 pr-3 text-right tabular-nums">{formatRupiah(pkg.revenue)}</td>
                                    <td class="py-1.5 text-right tabular-nums {pkg.available === 0 ? 'font-semibold text-red-600' : ''}">
                                        {formatNumber(pkg.available)}
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </section>
    </div>
</AdminLayout>
