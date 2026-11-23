<script>
    import { Link, page } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import { formatNumber, formatRupiah } from '@/lib/format.js';

    let { stats, today, attention, packages } = $props();

    const user = $derived(page.props.auth.user);
    const roleLabel = $derived(user.roleLabel);
    const canManageVouchers = $derived(user.can.manageVouchers);
    const canManageOrders = $derived(user.can.manageOrders);

    const cards = $derived([
        { label: 'Paket Aktif', value: stats.active_packages },
        { label: 'Stok Tersedia', value: stats.available },
        { label: 'Sedang Dipesan', value: stats.reserved },
        { label: 'Terjual', value: stats.sold },
    ]);

    const needsAction = $derived(attention.refund_required + attention.unsent_vouchers);
</script>

<AdminLayout title="Dashboard">
    <p class="text-sm text-gray-600">
        Masuk sebagai <span class="font-medium text-gray-900">{user.name}</span>
        <span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{roleLabel}</span>
    </p>

    {#if needsAction > 0 && canManageOrders}
        <Link
            href="/admin/pesanan?status=perlu-tindakan"
            class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 hover:border-red-300"
        >
            <span>
                <span class="font-semibold">{formatNumber(needsAction)} pesanan perlu tindakan</span>
                ({formatNumber(attention.refund_required)} perlu refund, {formatNumber(attention.unsent_vouchers)} email voucher belum terkirim)
            </span>
            <span class="font-medium">Lihat ›</span>
        </Link>
    {/if}

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-600">Pendapatan hari ini</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{formatRupiah(today.revenue)}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-600">Pesanan lunas hari ini</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{formatNumber(today.orders)}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-600">Menunggu pembayaran</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{formatNumber(attention.pending)}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {#each cards as card (card.label)}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-600">{card.label}</p>
                <p class="mt-1 text-2xl font-semibold">{formatNumber(card.value)}</p>
            </div>
        {/each}
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white">
        <h2 class="border-b border-gray-200 px-5 py-4 font-semibold">Stok per Paket</h2>

        {#if packages.length === 0}
            <p class="px-5 py-8 text-center text-sm text-gray-600">
                Belum ada paket.
                <Link href="/admin/paket/tambah" class="text-blue-700 hover:underline">Tambah paket</Link>
            </p>
        {:else}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Paket</th>
                            <th class="px-5 py-3 text-right">Harga</th>
                            <th class="px-5 py-3 text-right">Tersedia</th>
                            <th class="px-5 py-3 text-right">Dipesan</th>
                            <th class="px-5 py-3 text-right">Terjual</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {#each packages as pkg (pkg.id)}
                            <tr>
                                <td class="px-5 py-3 font-medium">{pkg.name}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">{formatRupiah(pkg.price)}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class={pkg.available_count === 0 ? 'font-semibold text-red-600' : ''}>{formatNumber(pkg.available_count)}</span>
                                    {#if pkg.available_count === 0 && canManageVouchers}
                                        <Link href="/admin/voucher/generate?package={pkg.id}" class="ml-2 text-xs text-blue-700 hover:underline">Isi stok</Link>
                                    {/if}
                                </td>
                                <td class="px-5 py-3 text-right">{formatNumber(pkg.reserved_count)}</td>
                                <td class="px-5 py-3 text-right">{formatNumber(pkg.sold_count)}</td>
                                <td class="px-5 py-3">
                                    <StatusBadge status={pkg.is_active ? 'active' : 'inactive'} label={pkg.is_active ? 'Aktif' : 'Nonaktif'} />
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {/if}
    </section>
</AdminLayout>
