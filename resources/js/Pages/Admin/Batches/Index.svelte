<script>
    import { Link } from '@inertiajs/svelte';
    import AdminLayout from '../../../Layouts/AdminLayout.svelte';
    import Pagination from '../../../Components/Pagination.svelte';
    import { formatDateTime, formatNumber } from '../../../lib/format.js';

    let { batches } = $props();
</script>

<AdminLayout title="Riwayat Batch">
    {#if batches.data.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Belum ada batch.</p>
            <p class="mt-1 text-sm text-gray-600">Setiap generate atau import voucher akan tercatat di sini.</p>
        </div>
    {:else}
        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Batch</th>
                        <th class="px-4 py-3">Paket</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3 text-right">Sisa Tersedia</th>
                        <th class="px-4 py-3">Catatan</th>
                        <th class="px-4 py-3">Dibuat oleh</th>
                        <th class="px-4 py-3">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {#each batches.data as batch (batch.id)}
                        <tr class="align-top">
                            <td class="px-4 py-3 font-medium">#{batch.id}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Link href="/admin/voucher?package={batch.package_id}" class="text-blue-700 hover:underline">{batch.package}</Link>
                            </td>
                            <td class="px-4 py-3">
                                {batch.source_label}
                                {#if batch.file_name}
                                    <p class="max-w-48 truncate text-xs text-gray-500" title={batch.file_name}>{batch.file_name}</p>
                                {/if}
                            </td>
                            <td class="px-4 py-3 text-right">{formatNumber(batch.quantity)}</td>
                            <td class="px-4 py-3 text-right">{formatNumber(batch.available_count)}</td>
                            <td class="px-4 py-3 text-gray-600">{batch.note ?? '-'}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{batch.creator ?? '-'}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{formatDateTime(batch.created_at)}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}

    <Pagination paginator={batches} />
</AdminLayout>
