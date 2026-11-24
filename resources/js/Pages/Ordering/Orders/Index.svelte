<script>
    import { Link } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import Pagination from '@/Components/Pagination.svelte';
    import OrderListItem from '@/Components/Ordering/OrderListItem.svelte';

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
                <li><OrderListItem {order} /></li>
            {/each}
        </ul>
    {/if}

    <Pagination paginator={orders} />
</AppLayout>
