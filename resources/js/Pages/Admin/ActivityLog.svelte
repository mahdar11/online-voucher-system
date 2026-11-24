<script>
    import { untrack } from 'svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import SelectField from '@/Components/SelectField.svelte';
    import Pagination from '@/Components/Pagination.svelte';
    import { formatDateTime } from '@/lib/format.js';

    let { logs, filters, actions: actionNames, actors } = $props();

    let action = $state(untrack(() => filters.action));
    let user = $state(untrack(() => filters.user));
    let from = $state(untrack(() => filters.from));
    let until = $state(untrack(() => filters.until));

    const actionOptions = $derived(actionNames.map((name) => ({ value: name, label: name })));

    function apply(event) {
        event?.preventDefault();
        const query = Object.fromEntries(Object.entries({ action, user, from, until: from ? until : '' }).filter(([, v]) => v));
        router.get('/admin/log', query, { preserveState: true, replace: true });
    }
</script>

<AdminLayout title="Log Aktivitas">
    <form class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-[1.2fr_1fr_auto_auto_auto]" onsubmit={apply}>
        <SelectField id="log-action" label="Aksi" placeholder="Semua aksi" options={actionOptions} bind:value={action} onchange={apply} />
        <SelectField id="log-user" label="Pelaku" placeholder="Semua pelaku" options={actors} bind:value={user} onchange={apply} />
        <label class="text-sm font-medium text-gray-700">Dari<input type="date" bind:value={from} class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" /></label>
        <label class="text-sm font-medium text-gray-700">Sampai<input type="date" bind:value={until} min={from} class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" /></label>
        <div class="flex items-end"><button type="submit" class="rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-gray-700">Terapkan</button></div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Aksi</th><th class="px-4 py-3">Pelaku</th><th class="px-4 py-3">Detail</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                {#each logs.data as log (log.id)}
                    <tr class="align-top">
                        <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{formatDateTime(log.created_at)}</td>
                        <td class="px-4 py-2.5 font-mono text-xs">{log.action}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">{log.actor}<span class="block text-xs text-gray-400">{log.ip_address ?? ''}</span></td>
                        <td class="px-4 py-2.5 text-xs text-gray-600">
                            {#if log.invoice}
                                <Link href="/admin/pesanan/{log.invoice}" class="font-mono text-blue-700 hover:underline">{log.invoice}</Link>
                            {/if}
                            {#each Object.entries(log.properties) as [key, value] (key)}
                                <span class="mr-2 inline-block">{key}: <span class="text-gray-900">{value ?? '-'}</span></span>
                            {/each}
                        </td>
                    </tr>
                {:else}
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Belum ada aktivitas.</td></tr>
                {/each}
            </tbody>
        </table>
    </div>

    <Pagination paginator={logs} />
</AdminLayout>
