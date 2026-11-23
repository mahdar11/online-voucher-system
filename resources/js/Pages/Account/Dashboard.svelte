<script>
    import { page } from '@inertiajs/svelte';
    import AppLayout from '../../Layouts/AppLayout.svelte';

    const user = $derived(page.props.auth.user);
</script>

<svelte:head>
    <title>Akun Saya</title>
</svelte:head>

<AppLayout>
    <h1 class="text-2xl font-semibold">Halo, {user.name}</h1>
    <p class="mt-1 text-gray-600">{user.email}</p>

    <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold">Riwayat Pembelian</h2>
        <p class="mt-2 text-sm text-gray-600">Belum ada pembelian voucher.</p>
    </section>
</AppLayout>
