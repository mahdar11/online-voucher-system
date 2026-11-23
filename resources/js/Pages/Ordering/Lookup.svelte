<script>
    import { Link, useForm } from '@inertiajs/svelte';
    import GuestLayout from '@/Layouts/GuestLayout.svelte';
    import InputField from '@/Components/InputField.svelte';
    import PrimaryButton from '@/Components/PrimaryButton.svelte';

    const form = useForm({ invoice_number: '', contact: '' });

    function submit(event) {
        event.preventDefault();
        form.post('/cek-pesanan');
    }
</script>

<svelte:head>
    <title>Cek Pesanan</title>
</svelte:head>

<GuestLayout title="Cek Pesanan" subtitle="Buka pesanan Anda dengan nomor pesanan dan email atau nomor HP yang dipakai saat memesan.">
    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField
            id="invoice_number"
            label="Nomor Pesanan"
            placeholder="INV-20261123-7KQ2MZ"
            autocomplete="off"
            required
            bind:value={form.invoice_number}
            error={form.errors.invoice_number}
        />
        <InputField
            id="contact"
            label="Email atau Nomor HP"
            autocomplete="email"
            required
            bind:value={form.contact}
            error={form.errors.contact}
        />

        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Mencari…' : 'Buka Pesanan'}
        </PrimaryButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Lupa nomor pesanan?
        <Link href="/riwayat" class="font-medium text-blue-700 hover:underline">Lihat riwayat lewat email</Link>
    </p>
</GuestLayout>
