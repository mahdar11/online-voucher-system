<script>
    import { Link } from '@inertiajs/svelte';
    import { formatDuration, formatQuota, formatRupiah, formatValidity } from '@/lib/format.js';

    let { pkg } = $props();

    const soldOut = $derived(pkg.availability.state === 'sold_out');

    const availabilityStyle = {
        available: 'bg-green-50 text-green-700',
        low: 'bg-amber-50 text-amber-700',
        sold_out: 'bg-gray-100 text-gray-500',
    };
</script>

<article class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 {soldOut ? 'opacity-70' : ''}">
    <div class="flex items-start justify-between gap-3">
        <h2 class="text-lg font-semibold">{pkg.name}</h2>
        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {availabilityStyle[pkg.availability.state]}">
            {pkg.availability.label}
        </span>
    </div>

    <p class="mt-2 text-2xl font-semibold text-blue-700">{formatRupiah(pkg.price)}</p>

    <dl class="mt-4 space-y-1.5 text-sm text-gray-700">
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">Durasi</dt>
            <dd class="font-medium">{formatDuration(pkg.duration_minutes)}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">Masa aktif</dt>
            <dd class="text-right font-medium">{formatValidity(pkg.validity_days)}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">Kuota</dt>
            <dd class="font-medium">{formatQuota(pkg.quota_mb)}</dd>
        </div>
        {#if pkg.speed_limit}
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500">Kecepatan</dt>
                <dd class="font-medium">{pkg.speed_limit}</dd>
            </div>
        {/if}
    </dl>

    {#if pkg.description}
        <p class="mt-3 text-sm text-gray-600">{pkg.description}</p>
    {/if}

    <div class="mt-auto pt-5">
        {#if soldOut}
            <span class="block w-full rounded-lg bg-gray-100 px-4 py-2.5 text-center text-sm font-semibold text-gray-500">Stok Habis</span>
        {:else}
            <Link
                href="/beli/{pkg.slug}"
                class="block w-full rounded-lg bg-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-blue-700"
            >
                Beli
            </Link>
        {/if}
    </div>
</article>
