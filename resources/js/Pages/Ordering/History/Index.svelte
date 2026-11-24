<script>
    import { Link } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import Pagination from '@/Components/Pagination.svelte';
    import OrderListItem from '@/Components/Ordering/OrderListItem.svelte';

    let { email, orders } = $props();
</script>

<svelte:head>
    <title>Riwayat Pembelian</title>
</svelte:head>

<AppLayout>
    <div>
        <h1 class="text-2xl font-semibold">Riwayat Pembelian</h1>
        <p class="mt-1 text-sm text-gray-600">Pesanan dengan email <span class="font-medium text-gray-900">{email}</span>.</p>
    </div>

    {#if orders.data.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Belum ada pesanan dengan email ini.</p>
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

    <p class="text-xs text-gray-500">
        Akses ini berlaku sementara di browser ini. Daftar akun dengan email yang sama agar riwayat tersimpan permanen.
    </p>
</AppLayout>
