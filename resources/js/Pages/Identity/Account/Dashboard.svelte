<script>
    import { Link, page, router } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';

    const user = $derived(page.props.auth.user);
    const verifiedJustNow = $derived(new URL(page.url, 'http://localhost').searchParams.get('verified') === '1');

    let sending = $state(false);

    function resend() {
        sending = true;
        router.post('/email/verification-notification', {}, {
            preserveScroll: true,
            onSuccess: () => router.visit('/email/verify'),
            onFinish: () => (sending = false),
        });
    }
</script>

<svelte:head>
    <title>Akun Saya</title>
</svelte:head>

<AppLayout>
    <div>
        <h1 class="text-2xl font-semibold">Halo, {user.name}</h1>
        <p class="mt-1 text-gray-600">{user.email}</p>
    </div>

    {#if verifiedJustNow && user.emailVerified}
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">
            Email terverifikasi. Pesanan yang pernah Anda buat tanpa login dengan email ini sudah tersambung ke akun Anda.
        </div>
    {:else if !user.emailVerified}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p>Verifikasi email Anda agar pesanan yang dibuat tanpa login dengan email ini muncul di akun.</p>
            <button
                type="button"
                disabled={sending}
                class="rounded-lg bg-amber-600 px-3 py-1.5 font-medium text-white hover:bg-amber-700 disabled:opacity-60"
                onclick={resend}
            >
                {sending ? 'Mengirim…' : 'Kirim link verifikasi'}
            </button>
        </div>
    {/if}

    <div class="grid gap-4 sm:grid-cols-2">
        <Link href="/akun/pesanan" class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-blue-300">
            <h2 class="font-semibold">Pesanan Saya</h2>
            <p class="mt-1 text-sm text-gray-600">Lihat status pesanan dan kode voucher yang sudah dibeli.</p>
        </Link>
        <Link href="/" class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-blue-300">
            <h2 class="font-semibold">Beli Voucher</h2>
            <p class="mt-1 text-sm text-gray-600">Pilih paket voucher internet.</p>
        </Link>
    </div>
</AppLayout>
