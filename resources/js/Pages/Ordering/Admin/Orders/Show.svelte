<script>
    import { Link, router, useForm } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import VoucherCodes from '@/Components/Ordering/VoucherCodes.svelte';
    import { formatDateTime, formatRupiah } from '@/lib/format.js';

    let { order, vouchers, payments, activity } = $props();

    const refundForm = useForm({ note: '' });
    let busy = $state(false);

    const base = $derived(`/admin/pesanan/${order.invoice_number}`);

    const actionLabels = {
        'order.placed': 'Pesanan dibuat',
        'order.paid': 'Pesanan lunas',
        'order.paid_late': 'Lunas (dibayar terlambat)',
        'order.refund_required': 'Perlu refund',
        'order.refunded': 'Ditandai sudah direfund',
        'order.cancelled': 'Dibatalkan',
        'order.expired': 'Kedaluwarsa',
        'order.vouchers_emailed': 'Email voucher terkirim',
        'order.vouchers_resent': 'Email voucher dikirim ulang',
        'payment.started': 'Tagihan Xendit dibuat',
        'payment.paid': 'Pembayaran diterima',
        'payment.expired': 'Tagihan kedaluwarsa',
    };

    function post(path, message) {
        if (message && !confirm(message)) return;
        busy = true;
        router.post(`${base}/${path}`, {}, { preserveScroll: true, onFinish: () => (busy = false) });
    }

    function refund(event) {
        event.preventDefault();
        refundForm.post(`${base}/refund`, { preserveScroll: true, onSuccess: () => refundForm.reset() });
    }
</script>

<AdminLayout title="Pesanan {order.invoice_number}">
    {#snippet actions()}
        <Link href="/admin/pesanan" class="rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            ‹ Semua pesanan
        </Link>
    {/snippet}

    <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="font-mono text-lg font-semibold">{order.invoice_number}</p>
                    <StatusBadge status={order.status} label={order.status_label} />
                </div>
                <dl class="mt-4 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[8rem_1fr]">
                    <dt class="text-gray-500">Pembeli</dt><dd>{order.customer.name} {order.customer.is_guest ? '(guest)' : '(akun)'}</dd>
                    <dt class="text-gray-500">Email</dt><dd class="break-all">{order.customer.email}</dd>
                    <dt class="text-gray-500">Nomor HP</dt><dd>{order.customer.phone ?? '-'}</dd>
                    <dt class="text-gray-500">Dibuat</dt><dd>{formatDateTime(order.created_at)}</dd>
                    <dt class="text-gray-500">Batas bayar</dt><dd>{formatDateTime(order.expires_at)}</dd>
                    <dt class="text-gray-500">Dibayar</dt><dd>{formatDateTime(order.paid_at)}</dd>
                </dl>

                <ul class="mt-4 divide-y divide-gray-100 border-t border-gray-100 text-sm">
                    {#each order.items as item, i (i)}
                        <li class="flex justify-between gap-3 py-2">
                            <span>{item.package_name} × {item.quantity} <span class="text-gray-500">@ {formatRupiah(item.unit_price)}</span></span>
                            <span class="tabular-nums">{formatRupiah(item.total)}</span>
                        </li>
                    {/each}
                    <li class="flex justify-between py-2 font-semibold"><span>Total</span><span class="tabular-nums">{formatRupiah(order.total)}</span></li>
                </ul>
            </section>

            {#if vouchers.length}
                <section class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h2 class="mb-3 font-semibold">Kode Voucher</h2>
                    <VoucherCodes {vouchers} />
                </section>
            {/if}

            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="font-semibold">Pembayaran Xendit</h2>
                {#if payments.length === 0}
                    <p class="mt-2 text-sm text-gray-500">Pembeli belum memulai pembayaran.</p>
                {:else}
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr><th class="py-2 pr-3">Referensi</th><th class="py-2 pr-3">Status</th><th class="py-2 pr-3">Metode</th><th class="py-2 pr-3 text-right">Jumlah</th><th class="py-2">Waktu</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                {#each payments as payment (payment.reference)}
                                    <tr>
                                        <td class="py-1.5 pr-3 font-mono text-xs">{payment.reference}</td>
                                        <td class="py-1.5 pr-3">{payment.status_label}</td>
                                        <td class="py-1.5 pr-3">{payment.channel ?? '-'}</td>
                                        <td class="py-1.5 pr-3 text-right tabular-nums">{formatRupiah(payment.amount)}</td>
                                        <td class="py-1.5 whitespace-nowrap text-gray-600">{formatDateTime(payment.paid_at ?? payment.created_at)}</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                {/if}
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="font-semibold">Riwayat</h2>
                <ol class="mt-3 space-y-2 text-sm">
                    {#each activity as entry (entry.id)}
                        <li class="flex flex-wrap justify-between gap-x-3 border-l-2 border-gray-200 pl-3">
                            <span>
                                <span class="font-medium">{actionLabels[entry.action] ?? entry.action}</span>
                                <span class="text-gray-500">· {entry.actor}</span>
                                {#if entry.note}<span class="block text-gray-600">Catatan: {entry.note}</span>{/if}
                            </span>
                            <span class="text-xs text-gray-500">{formatDateTime(entry.created_at)}</span>
                        </li>
                    {:else}
                        <li class="text-gray-500">Belum ada catatan.</li>
                    {/each}
                </ol>
            </section>
        </div>

        <aside class="h-fit space-y-3 rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">Tindakan</h2>

            {#if order.can.resend}
                <button type="button" disabled={busy} class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium hover:bg-gray-50 disabled:opacity-60"
                    onclick={() => post('kirim-ulang', `Kirim ulang email voucher ke ${order.customer.email}?`)}>
                    Kirim ulang email voucher
                </button>
            {/if}

            {#if order.can.refund}
                <form class="space-y-2 rounded-xl bg-red-50 p-3" onsubmit={refund}>
                    <p class="text-sm text-red-800">
                        Kembalikan dana {formatRupiah(order.total)} di luar aplikasi (dashboard Xendit atau transfer), lalu tandai di sini.
                    </p>
                    <label for="refund-note" class="block text-xs font-medium text-gray-700">Catatan / nomor referensi refund</label>
                    <input id="refund-note" bind:value={refundForm.note} class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                    {#if refundForm.errors.note}<p class="text-xs text-red-600">{refundForm.errors.note}</p>{/if}
                    <button type="submit" disabled={refundForm.processing} class="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                        Tandai sudah direfund
                    </button>
                </form>
            {/if}

            {#if order.can.cancel}
                <button type="button" disabled={busy} class="w-full rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-60"
                    onclick={() => post('batal', 'Batalkan pesanan ini? Voucher akan kembali ke stok.')}>
                    Batalkan pesanan
                </button>
            {/if}

            {#if !order.can.resend && !order.can.refund && !order.can.cancel}
                <p class="text-sm text-gray-500">Tidak ada tindakan untuk status ini.</p>
            {/if}
        </aside>
    </div>
</AdminLayout>
