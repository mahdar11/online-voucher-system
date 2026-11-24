<script>
    import { untrack } from 'svelte';
    import { useForm } from '@inertiajs/svelte';
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import InputField from '@/Components/InputField.svelte';

    let { settings } = $props();

    const form = useForm(untrack(() => ({ ...settings })));

    function submit(event) {
        event.preventDefault();
        form.put('/admin/pengaturan', { preserveScroll: true });
    }
</script>

<AdminLayout title="Pengaturan">
    <form class="max-w-2xl space-y-6" onsubmit={submit} novalidate>
        <section class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="font-semibold">Pemesanan</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <InputField id="reservation_minutes" label="Batas waktu bayar (menit)" type="number" min="5" max="60" inputmode="numeric"
                    hint="5–60 menit. Voucher dikunci selama ini; invoice Xendit ikut kedaluwarsa."
                    bind:value={form.reservation_minutes} error={form.errors.reservation_minutes} />
                <InputField id="max_quantity" label="Maksimal voucher per pesanan" type="number" min="1" max="20" inputmode="numeric"
                    hint="1–20 voucher." bind:value={form.max_quantity} error={form.errors.max_quantity} />
            </div>
        </section>

        <section class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="font-semibold">Kontak Bantuan</h2>
            <p class="text-sm text-gray-600">Ditampilkan di bagian bawah situs. Kosongkan bila tidak dipakai.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <InputField id="support_email" label="Email" type="email" placeholder="bantuan@domain.id"
                    bind:value={form.support_email} error={form.errors.support_email} />
                <InputField id="support_whatsapp" label="WhatsApp" type="tel" placeholder="081234567890"
                    bind:value={form.support_whatsapp} error={form.errors.support_whatsapp} />
            </div>
        </section>

        <section class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="font-semibold">Pengumuman di Beranda</h2>
            <label for="announcement" class="sr-only">Pengumuman</label>
            <textarea id="announcement" rows="3" maxlength="300" placeholder="Contoh: Promo akhir pekan, voucher 1 hari hanya Rp4.000!"
                bind:value={form.announcement}
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            <div class="flex justify-between text-xs text-gray-500">
                <span>{#if form.errors.announcement}<span class="text-red-600">{form.errors.announcement}</span>{:else}Kosongkan untuk menyembunyikan.{/if}</span>
                <span>{(form.announcement ?? '').length}/300</span>
            </div>
        </section>

        <button type="submit" disabled={form.processing} class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
            {form.processing ? 'Menyimpan…' : 'Simpan Pengaturan'}
        </button>
    </form>
</AdminLayout>
