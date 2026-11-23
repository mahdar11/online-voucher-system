<script>
    import { Link } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import Pagination from '@/Components/Pagination.svelte';
    import { formatDateTime, formatRupiah } from '@/lib/format.js';

    let { orders } = $props();
</script>

<svelte:head>
    <title>Pesanan Saya</title>
</svelte:head>

<AppLayout>
    <h1 class="text-2xl font-semibold">Pesanan Saya</h1>

    {#if orders.data.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Belum ada pesanan.</p>
            <Link href="/" class="mt-2 inline-block text-sm text-blue-700 hover:underline">Lihat paket voucher</Link>
        </div>
    {:else}
        <ul class="space-y-3">
            {#each orders.data as order (order.invoice_number)}
                <li>
                    <Link
                        href="/pesanan/{order.invoice_number}"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 hover:border-blue-300"
                    >
                        <div>
                            <p class="font-mono text-sm font-semibold">{order.invoice_number}</p>
                            <p class="text-sm text-gray-600">
                                {order.items.map((item) => `${item.package_name} × ${item.quantity}`).join(', ')}
                            </p>
                            <p class="text-xs text-gray-500">{formatDateTime(order.created_at)}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{formatRupiah(order.total)}</p>
                            <StatusBadge status={order.status} label={order.status_label} />
                        </div>
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}

    <Pagination paginator={orders} />
</AppLayout>
