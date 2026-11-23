<script>
    import { untrack } from 'svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AdminLayout from '../../../Layouts/AdminLayout.svelte';
    import SelectField from '../../../Components/SelectField.svelte';
    import StatusBadge from '../../../Components/StatusBadge.svelte';
    import Pagination from '../../../Components/Pagination.svelte';
    import { formatDateTime } from '../../../lib/format.js';

    let { vouchers, filters, packages, statuses } = $props();

    // Nilai awal filter dari URL; selanjutnya dikendalikan oleh input.
    let packageFilter = $state(untrack(() => filters.package));
    let statusFilter = $state(untrack(() => filters.status));
    let search = $state(untrack(() => filters.search));

    function applyFilters(event) {
        event?.preventDefault();

        const query = Object.fromEntries(
            Object.entries({ package: packageFilter, status: statusFilter, search: search.trim() }).filter(([, v]) => v !== ''),
        );

        router.get('/admin/voucher', query, { preserveState: true, preserveScroll: true, replace: true });
    }

    function resetFilters() {
        packageFilter = '';
        statusFilter = '';
        search = '';
        applyFilters();
    }

    function destroy(voucher) {
        if (confirm(`Hapus voucher ${voucher.code}?`)) {
            router.delete(`/admin/voucher/${voucher.id}`, { preserveScroll: true });
        }
    }
</script>

<AdminLayout title="Stok Voucher">
    {#snippet actions()}
        <Link
            href="/admin/voucher/import{packageFilter ? `?package=${packageFilter}` : ''}"
            class="rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
        >
            Import CSV
        </Link>
        <Link
            href="/admin/voucher/generate{packageFilter ? `?package=${packageFilter}` : ''}"
            class="rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
            Generate Voucher
        </Link>
    {/snippet}

    <form class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:grid-cols-[1fr_1fr_1fr_auto]" onsubmit={applyFilters}>
        <SelectField id="filter-package" label="Paket" placeholder="Semua paket" options={packages} bind:value={packageFilter} onchange={applyFilters} />
        <SelectField id="filter-status" label="Status" placeholder="Semua status" options={statuses} bind:value={statusFilter} onchange={applyFilters} />
        <div>
            <label for="filter-search" class="block text-sm font-medium text-gray-700">Cari Kode</label>
            <input
                id="filter-search"
                type="search"
                bind:value={search}
                placeholder="Ketik lalu Enter"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
            />
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-gray-700">Cari</button>
            <button type="button" class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100" onclick={resetFilters}>Reset</button>
        </div>
    </form>

    {#if vouchers.data.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Tidak ada voucher yang cocok.</p>
            <p class="mt-1 text-sm text-gray-600">Ubah filter, atau isi stok dengan Generate Voucher / Import CSV.</p>
        </div>
    {:else}
        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Password</th>
                        <th class="px-4 py-3">Paket</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Batch</th>
                        <th class="px-4 py-3">Dibuat</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {#each vouchers.data as voucher (voucher.id)}
                        <tr>
                            <td class="px-4 py-2.5 font-mono font-medium whitespace-nowrap">{voucher.code}</td>
                            <td class="px-4 py-2.5 font-mono whitespace-nowrap text-gray-600">{voucher.password ?? '-'}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">{voucher.package}</td>
                            <td class="px-4 py-2.5"><StatusBadge status={voucher.status} label={voucher.status_label} /></td>
                            <td class="px-4 py-2.5 text-gray-600">{voucher.batch_id ? `#${voucher.batch_id}` : '-'}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{formatDateTime(voucher.created_at)}</td>
                            <td class="px-4 py-2.5 text-right">
                                {#if voucher.status === 'available'}
                                    <button type="button" class="text-red-600 hover:underline" onclick={() => destroy(voucher)}>Hapus</button>
                                {/if}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}

    <Pagination paginator={vouchers} />
</AdminLayout>
