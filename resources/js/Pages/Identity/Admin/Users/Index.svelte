<script>
    import { untrack } from 'svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import SelectField from '@/Components/SelectField.svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import Pagination from '@/Components/Pagination.svelte';

    let { users, filters, roles } = $props();

    let search = $state(untrack(() => filters.search));
    let role = $state(untrack(() => filters.role));

    function apply(event) {
        event?.preventDefault();
        const query = Object.fromEntries(Object.entries({ search: search.trim(), role }).filter(([, v]) => v));
        router.get('/admin/pengguna', query, { preserveState: true, replace: true });
    }

    function toggle(user) {
        const verb = user.is_active ? 'Nonaktifkan' : 'Aktifkan';
        if (confirm(`${verb} akun ${user.name}?`)) {
            router.patch(`/admin/pengguna/${user.id}/status`, {}, { preserveScroll: true });
        }
    }

    function resend(user) {
        router.post(`/admin/pengguna/${user.id}/undangan`, {}, { preserveScroll: true });
    }
</script>

<AdminLayout title="Pengguna">
    {#snippet actions()}
        <Link href="/admin/pengguna/tambah" class="rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            + Undang Staf
        </Link>
    {/snippet}

    <form class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:grid-cols-[2fr_1fr_auto]" onsubmit={apply}>
        <div>
            <label for="user-search" class="block text-sm font-medium text-gray-700">Cari</label>
            <input id="user-search" type="search" placeholder="Nama atau email" bind:value={search}
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <SelectField id="user-role" label="Role" placeholder="Semua role" options={roles} bind:value={role} onchange={apply} />
        <div class="flex items-end">
            <button type="submit" class="rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-gray-700">Cari</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                {#each users.data as user (user.id)}
                    <tr>
                        <td class="px-4 py-2.5">
                            <p class="font-medium">{user.name}{user.is_self ? ' (Anda)' : ''}</p>
                            <p class="text-xs text-gray-500">{user.email}</p>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">{user.role_label}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <StatusBadge status={user.is_active ? 'active' : 'inactive'} label={user.is_active ? 'Aktif' : 'Nonaktif'} />
                            {#if user.invitation_pending}
                                <span class="ml-1 text-xs text-amber-700">Undangan belum diterima</span>
                            {/if}
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex justify-end gap-3 whitespace-nowrap">
                                {#if user.invitation_pending}
                                    <button type="button" class="text-blue-700 hover:underline" onclick={() => resend(user)}>Kirim ulang undangan</button>
                                {/if}
                                <Link href="/admin/pengguna/{user.id}/ubah" class="text-gray-700 hover:underline">Ubah</Link>
                                {#if !user.is_self}
                                    <button type="button" class="{user.is_active ? 'text-red-600' : 'text-green-700'} hover:underline" onclick={() => toggle(user)}>
                                        {user.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                    </button>
                                {/if}
                            </div>
                        </td>
                    </tr>
                {:else}
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada pengguna yang cocok.</td></tr>
                {/each}
            </tbody>
        </table>
    </div>

    <Pagination paginator={users} />
</AdminLayout>
