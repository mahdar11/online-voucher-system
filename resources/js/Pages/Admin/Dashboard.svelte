<script>
    import { page } from '@inertiajs/svelte';
    import AppLayout from '../../Layouts/AppLayout.svelte';

    const user = $derived(page.props.auth.user);
    const roleLabel = $derived(user.roles.includes('super_admin') ? 'Super Admin' : 'Admin');

    const menus = [
        { title: 'Paket Voucher', description: 'Atur paket, harga, durasi, dan kuota.' },
        { title: 'Stok Voucher', description: 'Generate kode atau import dari CSV.' },
        { title: 'Pesanan', description: 'Pantau pesanan dan status pembayaran.' },
        { title: 'Laporan', description: 'Penjualan per hari dan per paket.' },
    ];
</script>

<svelte:head>
    <title>Dashboard Admin</title>
</svelte:head>

<AppLayout>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold">Dashboard Admin</h1>
        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">{roleLabel}</span>
    </div>
    <p class="mt-1 text-gray-600">Masuk sebagai {user.name}.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {#each menus as menu (menu.title)}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="font-semibold">{menu.title}</h2>
                <p class="mt-1 text-sm text-gray-600">{menu.description}</p>
                <p class="mt-3 text-xs font-medium text-gray-400">Segera hadir</p>
            </div>
        {/each}
    </div>
</AppLayout>
