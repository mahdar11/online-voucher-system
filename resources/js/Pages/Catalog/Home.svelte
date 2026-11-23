<script>
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import PackageCard from '@/Components/Catalog/PackageCard.svelte';

    let { packages, announcement = null } = $props();
</script>

<svelte:head>
    <title>Beli Voucher Internet</title>
</svelte:head>

<AppLayout>
    {#if announcement}
        <p class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-center text-sm text-blue-900" role="note">{announcement}</p>
    {/if}

    <section class="py-6 text-center sm:py-10">
        <h1 class="text-3xl font-semibold sm:text-4xl">Beli Voucher Internet Online</h1>
        <p class="mx-auto mt-3 max-w-xl text-gray-600">
            Pilih paket, selesaikan pembayaran, dan kode voucher dikirim ke email Anda.
            Masa aktif dihitung sejak login pertama di hotspot.
        </p>
    </section>

    {#if packages.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600">
            Belum ada paket voucher yang dijual. Silakan kembali lagi nanti.
        </div>
    {:else}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {#each packages as pkg (pkg.id)}
                <PackageCard {pkg} />
            {/each}
        </div>
    {/if}
</AppLayout>
