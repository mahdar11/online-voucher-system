<script>
    import { page, useForm } from '@inertiajs/svelte';
    import GuestLayout from '@/Layouts/GuestLayout.svelte';
    import InputField from '@/Components/InputField.svelte';
    import PrimaryButton from '@/Components/PrimaryButton.svelte';

    let { name, email, roleLabel } = $props();

    const form = useForm({ password: '', password_confirmation: '' });

    function submit(event) {
        event.preventDefault();
        // Kirim ke URL bertanda tangan yang sedang dibuka (signature ada di query string).
        form.post(page.url, { onFinish: () => form.reset() });
    }
</script>

<svelte:head>
    <title>Aktifkan Akun</title>
</svelte:head>

<GuestLayout title="Halo, {name}" subtitle="Anda diundang sebagai {roleLabel}. Buat password untuk mengaktifkan akun {email}.">
    <form class="space-y-4" onsubmit={submit} novalidate>
        <InputField id="password" label="Password" type="password" autocomplete="new-password" required
            hint="Minimal 8 karakter." bind:value={form.password} error={form.errors.password} />
        <InputField id="password_confirmation" label="Konfirmasi Password" type="password" autocomplete="new-password" required
            bind:value={form.password_confirmation} error={form.errors.password_confirmation} />
        <PrimaryButton disabled={form.processing}>{form.processing ? 'Menyimpan…' : 'Aktifkan Akun'}</PrimaryButton>
    </form>
</GuestLayout>
