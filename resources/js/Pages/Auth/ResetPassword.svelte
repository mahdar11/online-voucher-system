<script>
    import { useForm } from '@inertiajs/svelte';
    import GuestLayout from '../../Layouts/GuestLayout.svelte';
    import InputField from '../../Components/InputField.svelte';
    import PrimaryButton from '../../Components/PrimaryButton.svelte';

    let { token, email = '' } = $props();

    const form = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        form.post('/reset-password', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    }
</script>

<svelte:head>
    <title>Reset Password</title>
</svelte:head>

<GuestLayout title="Buat Password Baru">
    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField
            id="email"
            label="Email"
            type="email"
            autocomplete="username"
            required
            bind:value={form.email}
            error={form.errors.email || form.errors.token}
        />
        <InputField
            id="password"
            label="Password Baru"
            type="password"
            autocomplete="new-password"
            required
            hint="Minimal 8 karakter."
            bind:value={form.password}
            error={form.errors.password}
        />
        <InputField
            id="password_confirmation"
            label="Konfirmasi Password Baru"
            type="password"
            autocomplete="new-password"
            required
            bind:value={form.password_confirmation}
            error={form.errors.password_confirmation}
        />

        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Menyimpan…' : 'Simpan Password'}
        </PrimaryButton>
    </form>
</GuestLayout>
