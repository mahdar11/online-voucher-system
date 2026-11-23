<script>
    import { untrack } from 'svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import InputField from '../InputField.svelte';
    import SelectField from '../SelectField.svelte';
    import { DURATION_UNITS, formatDuration, formatRupiah, splitDuration } from '../../lib/format.js';

    /** package: data paket saat edit, atau null saat tambah. */
    let { package: pkg = null, submitLabel = 'Simpan' } = $props();

    // Form hanya memakai data paket sebagai nilai awal.
    const initial = untrack(() => pkg);
    const initialDuration = splitDuration(initial?.duration_minutes ?? 60);

    const form = useForm({
        name: initial?.name ?? '',
        description: initial?.description ?? '',
        price: initial?.price ?? '',
        duration_amount: initialDuration.amount,
        duration_unit: String(initialDuration.unit),
        quota_mb: initial?.quota_mb ?? '',
        speed_limit: initial?.speed_limit ?? '',
        is_active: initial?.is_active ?? true,
        sort_order: initial?.sort_order ?? 0,
    });

    const durationMinutes = $derived(Number(form.duration_amount || 0) * Number(form.duration_unit));
    const unitOptions = DURATION_UNITS.map((u) => ({ value: String(u.value), label: u.label }));

    function submit(event) {
        event.preventDefault();

        form.transform((data) => {
            const { duration_amount, duration_unit, ...rest } = data;
            return {
                ...rest,
                duration_minutes: Number(duration_amount || 0) * Number(duration_unit),
            };
        });

        if (pkg) {
            form.put(`/admin/paket/${pkg.id}`);
        } else {
            form.post('/admin/paket');
        }
    }
</script>

<form class="max-w-2xl space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" onsubmit={submit} novalidate>
    <InputField id="name" label="Nama Paket" placeholder="Contoh: Voucher 1 Hari" required bind:value={form.name} error={form.errors.name} />

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi (opsional)</label>
        <textarea
            id="description"
            rows="3"
            bind:value={form.description}
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
        ></textarea>
        {#if form.errors.description}
            <p class="mt-1 text-sm text-red-600">{form.errors.description}</p>
        {/if}
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <InputField
            id="price"
            label="Harga (Rp)"
            type="number"
            min="0"
            step="500"
            inputmode="numeric"
            required
            hint={form.price !== '' ? formatRupiah(form.price) : null}
            bind:value={form.price}
            error={form.errors.price}
        />

        <div>
            <span class="block text-sm font-medium text-gray-700">Durasi</span>
            <div class="mt-1 grid grid-cols-[1fr_auto] gap-2">
                <InputField
                    id="duration_amount"
                    label=""
                    type="number"
                    min="1"
                    inputmode="numeric"
                    aria-label="Lama durasi"
                    bind:value={form.duration_amount}
                />
                <SelectField id="duration_unit" label="" aria-label="Satuan durasi" options={unitOptions} bind:value={form.duration_unit} />
            </div>
            {#if form.errors.duration_minutes}
                <p class="mt-1 text-sm text-red-600">{form.errors.duration_minutes}</p>
            {:else if durationMinutes > 0}
                <p class="mt-1 text-xs text-gray-500">= {formatDuration(durationMinutes)}</p>
            {/if}
        </div>

        <InputField
            id="quota_mb"
            label="Kuota (MB, opsional)"
            type="number"
            min="1"
            inputmode="numeric"
            hint="Kosongkan untuk unlimited. 1 GB = 1024 MB."
            bind:value={form.quota_mb}
            error={form.errors.quota_mb}
        />

        <InputField
            id="speed_limit"
            label="Kecepatan (opsional)"
            placeholder="5M/5M"
            hint="Format upload/download, contoh 5M/5M."
            bind:value={form.speed_limit}
            error={form.errors.speed_limit}
        />

        <InputField
            id="sort_order"
            label="Urutan Tampil"
            type="number"
            min="0"
            inputmode="numeric"
            hint="Angka kecil tampil lebih dulu."
            bind:value={form.sort_order}
            error={form.errors.sort_order}
        />
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" bind:checked={form.is_active} class="rounded border-gray-300" />
        Aktif (tampil di katalog)
    </label>

    <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
        <button
            type="submit"
            disabled={form.processing}
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
        >
            {form.processing ? 'Menyimpan…' : submitLabel}
        </button>
        <Link href="/admin/paket" class="text-sm text-gray-600 hover:text-gray-900">Batal</Link>
    </div>
</form>
