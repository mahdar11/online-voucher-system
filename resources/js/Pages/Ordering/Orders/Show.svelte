<script>
    import { router } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import Countdown from '@/Components/Countdown.svelte';
    import { formatDateTime, formatRupiah } from '@/lib/format.js';

    let { order } = $props();

    let cancelling = $state(false);

    const isPending = $derived(order.status === 'pending');

    function cancel() {
        if (!confirm('Batalkan pesanan ini? Voucher akan dikembalikan ke stok.')) return;

        cancelling = true;
        router.post(`/pesanan/${order.invoice_number}/batal`, {}, {
            preserveScroll: true,
            onFinish: () => (cancelling = false),
        });
    }

    // Saat waktu habis, muat ulang supaya server menandai pesanan kedaluwarsa.
    function onExpire() {
        router.reload({ only: ['order'] });
    }
</script>

<svelte:head>
    <title>Pesanan {order.invoice_number}</title>
</svelte:head>

<AppLayout>
    <div class="mx-auto max-w-2xl space-y-4">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500">Nomor pesanan</p>
                    <h1 class="font-mono text-lg font-semibold">{order.invoice_number}</h1>
                </div>
                <StatusBadge status={order.status} label={order.status_label} />
            </div>

            {#if isPending}
                <div class="mt-5 rounded-xl bg-amber-50 p-4 text-amber-900">
                    <p class="text-sm">Selesaikan pembayaran dalam</p>
                    <p class="text-3xl font-semibold">
                        {#key order.expires_in_seconds}
                            <Countdown seconds={order.expires_in_seconds} onexpire={onExpire} />
                        {/key}
                    </p>
                    <p class="mt-1 text-xs">Batas: {formatDateTime(order.expires_at)}. Setelah itu pesanan otomatis dibatalkan.</p>
                </div>
            {:else if order.status === 'expired'}
                <p class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-700">
                    Batas waktu pembayaran sudah lewat, jadi pesanan ini dibatalkan otomatis. Silakan buat pesanan baru.
                </p>
            {:else if order.status === 'cancelled'}
                <p class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-700">Pesanan ini sudah dibatalkan.</p>
            {/if}
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="font-semibold">Rincian</h2>
            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                {#each order.items as item, i (i)}
                    <li class="flex justify-between gap-3 py-2">
                        <span>{item.package_name} <span class="text-gray-500">× {item.quantity}</span></span>
                        <span>{formatRupiah(item.total)}</span>
                    </li>
                {/each}
            </ul>
            <dl class="mt-2 space-y-1 border-t border-gray-100 pt-3 text-sm">
                {#if order.fee > 0}
                    <div class="flex justify-between"><dt class="text-gray-500">Biaya layanan</dt><dd>{formatRupiah(order.fee)}</dd></div>
                {/if}
                <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd>{formatRupiah(order.total)}</dd></div>
            </dl>

            <dl class="mt-4 grid gap-1 border-t border-gray-100 pt-3 text-sm sm:grid-cols-[8rem_1fr]">
                <dt class="text-gray-500">Nama</dt><dd>{order.customer.name}</dd>
                <dt class="text-gray-500">Email</dt><dd class="break-all">{order.customer.email}</dd>
                {#if order.customer.phone}<dt class="text-gray-500">Nomor HP</dt><dd>{order.customer.phone}</dd>{/if}
                <dt class="text-gray-500">Dibuat</dt><dd>{formatDateTime(order.created_at)}</dd>
            </dl>
        </section>

        {#if isPending}
            <section class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <button
                    type="button"
                    disabled={!order.can_pay}
                    class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Bayar Sekarang · {formatRupiah(order.total)}
                </button>
                {#if !order.can_pay}
                    <p class="text-center text-xs text-gray-500">Pembayaran online (QRIS, transfer bank, e-wallet) segera tersedia.</p>
                {/if}
                {#if order.can_cancel}
                    <button
                        type="button"
                        disabled={cancelling}
                        class="w-full rounded-lg px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-60"
                        onclick={cancel}
                    >
                        {cancelling ? 'Membatalkan…' : 'Batalkan Pesanan'}
                    </button>
                {/if}
            </section>
        {/if}
    </div>
</AppLayout>
