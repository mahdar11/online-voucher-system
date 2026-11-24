<script>
    import { Link, useForm } from '@inertiajs/svelte';
    import GuestLayout from '@/Layouts/GuestLayout.svelte';
    import InputField from '@/Components/InputField.svelte';
    import PrimaryButton from '@/Components/PrimaryButton.svelte';

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        form.post('/register', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    }
</script>

<svelte:head>
    <title>Daftar</title>
</svelte:head>

<GuestLayout title="Daftar Akun" subtitle="Simpan riwayat pembelian voucher di akun Anda.">
    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField id="name" label="Nama" autocomplete="name" required bind:value={form.name} error={form.errors.name} />
        <InputField
            id="email"
            label="Email"
            type="email"
            autocomplete="email"
            required
            bind:value={form.email}
            error={form.errors.email}
        />
        <InputField
            id="phone"
            label="Nomor HP (opsional)"
            type="tel"
            autocomplete="tel"
            placeholder="081234567890"
            bind:value={form.phone}
            error={form.errors.phone}
        />
        <InputField
            id="password"
            label="Password"
            type="password"
            autocomplete="new-password"
            required
            hint="Minimal 8 karakter."
            bind:value={form.password}
            error={form.errors.password}
        />
        <InputField
            id="password_confirmation"
            label="Konfirmasi Password"
            type="password"
            autocomplete="new-password"
            required
            bind:value={form.password_confirmation}
            error={form.errors.password_confirmation}
        />

        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Memproses…' : 'Daftar'}
        </PrimaryButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Sudah punya akun?
        <Link href="/login" class="font-medium text-blue-700 hover:underline">Masuk</Link>
    </p>
</GuestLayout>
