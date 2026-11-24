<script>
    import { Link, page, useForm } from '@inertiajs/svelte';
    import GuestLayout from '@/Layouts/GuestLayout.svelte';
    import InputField from '@/Components/InputField.svelte';
    import PrimaryButton from '@/Components/PrimaryButton.svelte';

    const form = useForm({ email: '' });

    function submit(event) {
        event.preventDefault();
        form.post('/riwayat', { preserveScroll: true });
    }
</script>

<svelte:head>
    <title>Riwayat Pembelian</title>
</svelte:head>

<GuestLayout
    title="Riwayat Pembelian"
    subtitle="Masukkan email yang Anda pakai saat memesan. Kami akan mengirim link untuk melihat semua pesanan Anda."
    status={page.props.flash?.status}
>
    {#if page.props.flash?.error}
        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{page.props.flash.error}</p>
    {/if}

    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField id="email" label="Email" type="email" autocomplete="email" required bind:value={form.email} error={form.errors.email} />

        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Mengirim…' : 'Kirim Link Riwayat'}
        </PrimaryButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Punya nomor pesanan?
        <Link href="/cek-pesanan" class="font-medium text-blue-700 hover:underline">Cek pesanan langsung</Link>
    </p>
</GuestLayout>
