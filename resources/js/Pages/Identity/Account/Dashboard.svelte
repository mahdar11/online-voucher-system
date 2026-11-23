<script>
    import { Link, page } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';

    const user = $derived(page.props.auth.user);
</script>

<svelte:head>
    <title>Akun Saya</title>
</svelte:head>

<AppLayout>
    <div>
        <h1 class="text-2xl font-semibold">Halo, {user.name}</h1>
        <p class="mt-1 text-gray-600">{user.email}</p>
    </div>

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
