<script>
    import { untrack } from 'svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import InputField from '@/Components/InputField.svelte';
    import SelectField from '@/Components/SelectField.svelte';

    /** user = null saat mengundang staf baru. */
    let { user, roles, invitationHours } = $props();

    const editing = untrack(() => user !== null);
    const form = useForm(untrack(() => ({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        // Default hak paling kecil: Admin, bukan Super Admin.
        role: user?.role ?? roles.find((r) => r.value === 'admin')?.value ?? roles[0]?.value ?? '',
    })));

    function submit(event) {
        event.preventDefault();
        if (editing) {
            form.put(`/admin/pengguna/${user.id}`);
        } else {
            form.post('/admin/pengguna');
        }
    }
</script>

<AdminLayout title={editing ? `Ubah ${user.name}` : 'Undang Staf'}>
    <form class="max-w-xl space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" onsubmit={submit} novalidate>
        <InputField id="name" label="Nama" required bind:value={form.name} error={form.errors.name} />

        {#if editing}
            <div>
                <span class="block text-sm font-medium text-gray-700">Email</span>
                <p class="mt-1 text-sm text-gray-900">{user.email}</p>
            </div>
        {:else}
            <InputField id="email" label="Email" type="email" required
                hint="Undangan untuk membuat password dikirim ke email ini (berlaku {invitationHours} jam)."
                bind:value={form.email} error={form.errors.email} />
        {/if}

        <InputField id="phone" label="Nomor HP (opsional)" type="tel" placeholder="081234567890" bind:value={form.phone} error={form.errors.phone} />

        <SelectField id="role" label="Role" options={roles} bind:value={form.role} error={form.errors.role}
            disabled={user?.is_self}
            hint={user?.is_self ? 'Anda tidak bisa mengubah role akun sendiri.' : 'Super Admin bisa mengelola pengguna dan pengaturan; Admin mengelola paket, stok, pesanan, dan laporan.'} />

        <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" disabled={form.processing} class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                {form.processing ? 'Menyimpan…' : editing ? 'Simpan Perubahan' : 'Kirim Undangan'}
            </button>
            <Link href="/admin/pengguna" class="text-sm text-gray-600 hover:text-gray-900">Batal</Link>
        </div>
    </form>
</AdminLayout>
