<script>
    import { Link, useForm } from '@inertiajs/svelte';
    import GuestLayout from '../../Layouts/GuestLayout.svelte';
    import InputField from '../../Components/InputField.svelte';
    import PrimaryButton from '../../Components/PrimaryButton.svelte';

    let { status = null } = $props();

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(event) {
        event.preventDefault();
        form.post('/login', {
            onFinish: () => form.reset('password'),
        });
    }
</script>

<svelte:head>
    <title>Masuk</title>
</svelte:head>

<GuestLayout title="Masuk" subtitle="Masuk untuk melihat riwayat pembelian voucher Anda." {status}>
    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField
            id="email"
            label="Email"
            type="email"
            autocomplete="username"
            required
            bind:value={form.email}
            error={form.errors.email}
        />
        <InputField
            id="password"
            label="Password"
            type="password"
            autocomplete="current-password"
            required
            bind:value={form.password}
            error={form.errors.password}
        />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-gray-700">
                <input type="checkbox" bind:checked={form.remember} class="rounded border-gray-300" />
                Ingat saya
            </label>
            <Link href="/forgot-password" class="text-blue-700 hover:underline">Lupa password?</Link>
        </div>

        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Memproses…' : 'Masuk'}
        </PrimaryButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Belum punya akun?
        <Link href="/register" class="font-medium text-blue-700 hover:underline">Daftar</Link>
    </p>
</GuestLayout>
