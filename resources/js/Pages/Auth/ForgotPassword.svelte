<script>
    import { Link, useForm } from '@inertiajs/svelte';
    import GuestLayout from '../../Layouts/GuestLayout.svelte';
    import InputField from '../../Components/InputField.svelte';
    import PrimaryButton from '../../Components/PrimaryButton.svelte';

    let { status = null } = $props();

    const form = useForm({ email: '' });

    function submit(event) {
        event.preventDefault();
        form.post('/forgot-password');
    }
</script>

<svelte:head>
    <title>Lupa Password</title>
</svelte:head>

<GuestLayout
    title="Lupa Password"
    subtitle="Masukkan email akun Anda. Kami akan mengirim link untuk membuat password baru."
    {status}
>
    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField
            id="email"
            label="Email"
            type="email"
            autocomplete="email"
            required
            bind:value={form.email}
            error={form.errors.email}
        />

        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Mengirim…' : 'Kirim Link Reset'}
        </PrimaryButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <Link href="/login" class="font-medium text-blue-700 hover:underline">Kembali ke halaman masuk</Link>
    </p>
</GuestLayout>
