<script>
    import { page } from '@inertiajs/svelte';
    import AppLayout from '../Layouts/AppLayout.svelte';
</script>

<svelte:head>
    <title>Beranda</title>
</svelte:head>

<AppLayout>
    <section class="py-12 text-center sm:py-20">
        <h1 class="text-3xl font-semibold sm:text-4xl">Beli Voucher Internet Online</h1>
        <p class="mx-auto mt-3 max-w-xl text-gray-600">
            Pilih paket, bayar lewat QRIS, transfer bank, atau e-wallet, dan kode voucher langsung dikirim ke email Anda.
        </p>
        <p class="mt-8 text-sm text-gray-400">Katalog paket {page.props.appName} segera hadir.</p>
    </section>
</AppLayout>
