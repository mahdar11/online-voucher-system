<script>
    import { untrack } from 'svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import SelectField from '@/Components/SelectField.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import Pagination from '@/Components/Pagination.svelte';
    import { formatDateTime, formatRupiah } from '@/lib/format.js';

    let { orders, filters, statuses } = $props();

    // Nilai awal dari URL; selanjutnya dikendalikan input.
    let status = $state(untrack(() => filters.status));
    let from = $state(untrack(() => filters.from));
    let until = $state(untrack(() => filters.until));
    let search = $state(untrack(() => filters.search));

    function apply(event) {
        event?.preventDefault();
        const query = Object.fromEntries(
            Object.entries({ status, from, until: from ? until : '', search: search.trim() }).filter(([, v]) => v),
        );
        router.get('/admin/pesanan', query, { preserveState: true, preserveScroll: true, replace: true });
    }

    function reset() {
        status = from = until = search = '';
        apply();
    }
</script>

<AdminLayout title="Pesanan">
    <form class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-[1fr_auto_auto_1.4fr_auto]" onsubmit={apply}>
        <SelectField id="filter-status" label="Status" placeholder="Semua status" options={statuses} bind:value={status} onchange={apply} />
        <label class="text-sm font-medium text-gray-700">
            Dari
            <input type="date" bind:value={from} class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </label>
        <label class="text-sm font-medium text-gray-700">
            Sampai
            <input type="date" bind:value={until} min={from} class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </label>
        <div>
            <label for="filter-search" class="block text-sm font-medium text-gray-700">Cari</label>
            <input
                id="filter-search"
                type="search"
                placeholder="Invoice, nama, atau email"
                bind:value={search}
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-gray-700">Cari</button>
            <button type="button" class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100" onclick={reset}>Reset</button>
        </div>
    </form>

    {#if orders.data.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-600">Tidak ada pesanan yang cocok.</div>
    {:else}
        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Pembeli</th>
                        <th class="px-4 py-3">Paket</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dibuat</th>
                        <th class="px-4 py-3">Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {#each orders.data as order (order.invoice_number)}
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <Link href="/admin/pesanan/{order.invoice_number}" class="font-mono font-medium text-blue-700 hover:underline">
                                    {order.invoice_number}
                                </Link>
                            </td>
                            <td class="px-4 py-2.5">
                                <p class="font-medium">{order.customer.name}</p>
                                <p class="text-xs text-gray-500">{order.customer.email}{order.customer.is_guest ? ' · guest' : ''}</p>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                {order.items.map((i) => `${i.package_name} × ${i.quantity}`).join(', ')}
                            </td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap tabular-nums">{formatRupiah(order.total)}</td>
                            <td class="px-4 py-2.5"><StatusBadge status={order.status} label={order.status_label} /></td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{formatDateTime(order.created_at)}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{formatDateTime(order.paid_at)}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}

    <Pagination paginator={orders} />
</AdminLayout>
