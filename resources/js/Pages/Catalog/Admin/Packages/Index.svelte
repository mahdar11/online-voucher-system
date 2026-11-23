<script>
    import { Link, router } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import { formatDuration, formatNumber, formatQuota, formatRupiah, formatValidity } from '@/lib/format.js';

    let { packages } = $props();

    function toggle(pkg) {
        router.patch(`/admin/paket/${pkg.id}/status`, {}, { preserveScroll: true });
    }

    function destroy(pkg) {
        if (confirm(`Hapus paket "${pkg.name}"? Tindakan ini tidak bisa dibatalkan.`)) {
            router.delete(`/admin/paket/${pkg.id}`, { preserveScroll: true });
        }
    }
</script>

<AdminLayout title="Paket Voucher">
    {#snippet actions()}
        <Link href="/admin/paket/tambah" class="rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            + Tambah Paket
        </Link>
    {/snippet}

    {#if packages.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Belum ada paket voucher.</p>
            <p class="mt-1 text-sm text-gray-600">Tambahkan paket pertama, lalu isi stoknya dengan generate atau import CSV.</p>
        </div>
    {:else}
        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Paket</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3">Durasi / Masa Aktif</th>
                        <th class="px-4 py-3">Kuota / Kecepatan</th>
                        <th class="px-4 py-3 text-right">Tersedia</th>
                        <th class="px-4 py-3 text-right">Terjual</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {#each packages as pkg (pkg.id)}
                        <tr class="align-top">
                            <td class="min-w-44 px-4 py-3">
                                <p class="font-medium text-gray-900">{pkg.name}</p>
                                <p class="text-xs text-gray-500">/{pkg.slug}</p>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">{formatRupiah(pkg.price)}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {formatDuration(pkg.duration_minutes)}
                                <span class="block text-xs text-gray-500">aktif {formatValidity(pkg.validity_days)}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {formatQuota(pkg.quota_mb)}
                                {#if pkg.speed_limit}<span class="text-gray-500"> · {pkg.speed_limit}</span>{/if}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link href="/admin/voucher?package={pkg.id}&status=available" class="font-medium text-blue-700 hover:underline">
                                    {formatNumber(pkg.available_count)}
                                </Link>
                                {#if pkg.available_count === 0}
                                    <p class="text-xs text-red-600">Stok habis</p>
                                {/if}
                            </td>
                            <td class="px-4 py-3 text-right">{formatNumber(pkg.sold_count)}</td>
                            <td class="px-4 py-3">
                                <StatusBadge status={pkg.is_active ? 'active' : 'inactive'} label={pkg.is_active ? 'Aktif' : 'Nonaktif'} />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3 whitespace-nowrap text-sm">
                                    <Link href="/admin/voucher/generate?package={pkg.id}" class="text-blue-700 hover:underline">Isi Stok</Link>
                                    <Link href="/admin/paket/{pkg.id}/ubah" class="text-gray-700 hover:underline">Ubah</Link>
                                    <button type="button" class="text-gray-700 hover:underline" onclick={() => toggle(pkg)}>
                                        {pkg.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                    </button>
                                    <button type="button" class="text-red-600 hover:underline" onclick={() => destroy(pkg)}>Hapus</button>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}
</AdminLayout>
