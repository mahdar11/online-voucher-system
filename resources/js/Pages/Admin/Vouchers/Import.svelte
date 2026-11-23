<script>
    import { untrack } from 'svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import AdminLayout from '../../../Layouts/AdminLayout.svelte';
    import InputField from '../../../Components/InputField.svelte';
    import SelectField from '../../../Components/SelectField.svelte';
    import { formatNumber } from '../../../lib/format.js';

    let { packages, selectedPackage, maxRows, maxFileKb } = $props();

    const form = useForm({
        package_id: untrack(() => selectedPackage),
        file: null,
        note: '',
    });

    function pickFile(event) {
        form.file = event.currentTarget.files?.[0] ?? null;
    }

    function submit(event) {
        event.preventDefault();
        form.post('/admin/voucher/import', { forceFormData: true });
    }
</script>

<AdminLayout title="Import Voucher dari CSV">
    {#if packages.length === 0}
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="font-medium">Belum ada paket.</p>
            <Link href="/admin/paket/tambah" class="mt-2 inline-block text-sm text-blue-700 hover:underline">Tambah paket dulu</Link>
        </div>
    {:else}
        <div class="grid gap-4 lg:grid-cols-[minmax(0,42rem)_1fr]">
            <form class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" onsubmit={submit} novalidate>
                <SelectField
                    id="package_id"
                    label="Paket"
                    placeholder="Pilih paket"
                    options={packages}
                    bind:value={form.package_id}
                    error={form.errors.package_id}
                />

                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">File CSV</label>
                    <input
                        id="file"
                        type="file"
                        accept=".csv,text/csv,text/plain"
                        onchange={pickFile}
                        class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                    />
                    {#if form.errors.file}
                        <p class="mt-1 text-sm text-red-600">{form.errors.file}</p>
                    {:else}
                        <p class="mt-1 text-xs text-gray-500">
                            Maksimal {formatNumber(maxRows)} baris dan {formatNumber(maxFileKb / 1024)} MB.
                        </p>
                    {/if}
                </div>

                {#if form.progress}
                    <progress value={form.progress.percentage} max="100" class="h-2 w-full">{form.progress.percentage}%</progress>
                {/if}

                <InputField id="note" label="Catatan (opsional)" placeholder="Contoh: kiriman supplier 23 Nov" bind:value={form.note} error={form.errors.note} />

                <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
                    <button
                        type="submit"
                        disabled={form.processing || !form.file}
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                    >
                        {form.processing ? 'Mengimport…' : 'Import Voucher'}
                    </button>
                    <Link href="/admin/voucher" class="text-sm text-gray-600 hover:text-gray-900">Batal</Link>
                </div>
            </form>

            <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-5 text-sm text-gray-700">
                <h2 class="font-semibold text-gray-900">Format File</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Kolom 1: <code class="font-mono">code</code> (wajib, 4–64 karakter: huruf, angka, <code>-</code>, <code>_</code>)</li>
                    <li>Kolom 2: <code class="font-mono">password</code> (boleh kosong)</li>
                    <li>Baris judul (header) boleh ada atau tidak.</li>
                    <li>Pemisah koma <code>,</code> atau titik koma <code>;</code> (Excel).</li>
                    <li>Kode yang sudah ada di paket ini otomatis dilewati.</li>
                </ul>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-50 p-3 font-mono text-xs">code,password
VC-7KQ2MZ9P,
VC-H4XW8N3R,rahasia1</pre>
                <a href="/admin/voucher/import/contoh" class="mt-3 inline-block font-medium text-blue-700 hover:underline" download>
                    Unduh contoh CSV
                </a>
            </aside>
        </div>
    {/if}
</AdminLayout>
