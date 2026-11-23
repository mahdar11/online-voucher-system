<script>
    import { page, router } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import Countdown from '@/Components/Countdown.svelte';
    import VoucherCodes from '@/Components/Ordering/VoucherCodes.svelte';
    import { formatDateTime, formatRupiah } from '@/lib/format.js';

    let { order } = $props();

    const POLL_INTERVAL_MS = 3000;
    const MAX_POLLS = 40; // ± 2 menit

    let cancelling = $state(false);
    let paying = $state(false);
    let polls = $state(0);

    const isPending = $derived(order.status === 'pending');
    const isPaid = $derived(order.vouchers !== undefined);
    const paymentResult = $derived(new URL(page.url, 'http://localhost').searchParams.get('bayar'));
    // Kembali dari halaman Xendit, tapi konfirmasi pembayaran belum diterima.
    const awaitingConfirmation = $derived(isPending && paymentResult === 'selesai' && polls < MAX_POLLS);
    const confirmationSlow = $derived(isPending && paymentResult === 'selesai' && polls >= MAX_POLLS);

    $effect(() => {
        if (!awaitingConfirmation) return;

        const timer = setTimeout(() => {
            polls += 1;
            router.reload({ only: ['order'] });
        }, POLL_INTERVAL_MS);

        return () => clearTimeout(timer);
    });

    function pay() {
        paying = true;
        router.post(`/pesanan/${order.invoice_number}/bayar`, {}, {
            preserveScroll: true,
            onFinish: () => (paying = false),
        });
    }

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

            {#if awaitingConfirmation}
                <div class="mt-5 flex items-center gap-3 rounded-xl bg-blue-50 p-4 text-blue-900" role="status">
                    <span class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-blue-300 border-t-blue-700"></span>
                    <p class="text-sm">Menunggu konfirmasi pembayaran… Halaman ini akan diperbarui otomatis.</p>
                </div>
            {:else if confirmationSlow}
                <p class="mt-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-900">
                    Konfirmasi pembayaran belum kami terima. Jika Anda sudah membayar, muat ulang halaman ini dalam beberapa menit.
                </p>
            {:else if isPending}
                {#if paymentResult === 'gagal'}
                    <p class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">Pembayaran belum berhasil. Anda bisa mencoba lagi sebelum batas waktu.</p>
                {/if}
                <div class="mt-5 rounded-xl bg-amber-50 p-4 text-amber-900">
                    <p class="text-sm">Selesaikan pembayaran dalam</p>
                    <p class="text-3xl font-semibold">
                        {#key order.expires_in_seconds}
                            <Countdown seconds={order.expires_in_seconds} onexpire={onExpire} />
                        {/key}
                    </p>
                    <p class="mt-1 text-xs">Batas: {formatDateTime(order.expires_at)}. Setelah itu pesanan otomatis dibatalkan.</p>
                </div>
            {:else if isPaid}
                <div class="mt-5 space-y-3">
                    <p class="text-sm text-gray-700">
                        Pembayaran diterima {order.paid_at ? `pada ${formatDateTime(order.paid_at)}` : ''}. Berikut kode voucher Anda:
                    </p>
                    <VoucherCodes vouchers={order.vouchers} />
                    <p class="text-xs text-gray-500">
                        Simpan kode ini. Masukkan di halaman login hotspot; masa aktif dihitung sejak login pertama.
                    </p>
                </div>
            {:else if order.status === 'refund_required'}
                <p class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                    Pembayaran Anda kami terima setelah batas waktu, dan stok voucher sudah habis. Dana akan dikembalikan;
                    admin kami akan menghubungi Anda melalui {order.customer.email}.
                </p>
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

        {#if isPending && !awaitingConfirmation}
            <section class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <button
                    type="button"
                    disabled={!order.can_pay || paying}
                    class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    onclick={pay}
                >
                    {paying ? 'Membuka halaman pembayaran…' : `Bayar Sekarang · ${formatRupiah(order.total)}`}
                </button>
                <p class="text-center text-xs text-gray-500">QRIS, transfer bank (virtual account), dan e-wallet lewat Xendit.</p>
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
