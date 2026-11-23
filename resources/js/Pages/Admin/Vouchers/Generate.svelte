<script>
    import { untrack } from 'svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import AdminLayout from '../../../Layouts/AdminLayout.svelte';
    import InputField from '../../../Components/InputField.svelte';
    import SelectField from '../../../Components/SelectField.svelte';
    import { formatNumber } from '../../../lib/format.js';

    let { packages, selectedPackage, maxQuantity } = $props();

    const form = useForm({
        package_id: untrack(() => selectedPackage),
        quantity: 100,
        length: 8,
        prefix: '',
        password_mode: 'none',
        note: '',
    });

    const passwordModes = [
        { value: 'none', label: 'Tanpa password (kode saja)' },
        { value: 'same', label: 'Password sama dengan kode' },
        { value: 'random', label: 'Password acak 6 karakter' },
    ];

    const example = $derived(
        `${(form.prefix || '').toUpperCase()}${'X'.repeat(Math.min(Math.max(Number(form.length) || 0, 0), 16))}`,
    );

    function submit(event) {
        event.preventDefault();
        form.post('/admin/voucher/generate');
    }
</script>

<AdminLayout title="Generate Voucher">
    {#if packages.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Belum ada paket.</p>
            <Link href="/admin/paket/tambah" class="mt-2 inline-block text-sm text-blue-700 hover:underline">Tambah paket dulu</Link>
        </div>
    {:else}
        <form class="max-w-2xl space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" onsubmit={submit} novalidate>
            <SelectField
                id="package_id"
                label="Paket"
                placeholder="Pilih paket"
                options={packages}
                bind:value={form.package_id}
                error={form.errors.package_id}
            />

            <div class="grid gap-5 sm:grid-cols-3">
                <InputField
                    id="quantity"
                    label="Jumlah"
                    type="number"
                    min="1"
                    max={maxQuantity}
                    inputmode="numeric"
                    hint="Maksimal {formatNumber(maxQuantity)}."
                    bind:value={form.quantity}
                    error={form.errors.quantity}
                />
                <InputField
                    id="length"
                    label="Panjang Kode"
                    type="number"
                    min="6"
                    max="16"
                    inputmode="numeric"
                    hint="6–16 karakter, tanpa prefix."
                    bind:value={form.length}
                    error={form.errors.length}
                />
                <InputField
                    id="prefix"
                    label="Prefix (opsional)"
                    placeholder="VC-"
                    maxlength="8"
                    bind:value={form.prefix}
                    error={form.errors.prefix}
                />
            </div>

            <p class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">
                Contoh format: <span class="font-mono font-semibold text-gray-900">{example}</span>
                <span class="block text-xs text-gray-500">Huruf besar dan angka, tanpa 0/O dan 1/I/L agar tidak tertukar.</span>
            </p>

            <SelectField
                id="password_mode"
                label="Password Voucher"
                options={passwordModes}
                bind:value={form.password_mode}
                error={form.errors.password_mode}
            />

            <InputField id="note" label="Catatan (opsional)" placeholder="Contoh: stok awal November" bind:value={form.note} error={form.errors.note} />

            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
                <button
                    type="submit"
                    disabled={form.processing}
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                >
                    {form.processing ? 'Membuat voucher…' : `Generate ${formatNumber(form.quantity || 0)} Voucher`}
                </button>
                <Link href="/admin/voucher" class="text-sm text-gray-600 hover:text-gray-900">Batal</Link>
            </div>
        </form>
    {/if}
</AdminLayout>
