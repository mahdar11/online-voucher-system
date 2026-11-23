<script>
    import { Link, page, useForm } from '@inertiajs/svelte';
    import GuestLayout from '@/Layouts/GuestLayout.svelte';
    import PrimaryButton from '@/Components/PrimaryButton.svelte';

    let { status = null } = $props();

    const form = useForm({});
    const sent = $derived(status === 'verification-link-sent');

    function resend(event) {
        event.preventDefault();
        form.post('/email/verification-notification');
    }
</script>

<svelte:head>
    <title>Verifikasi Email</title>
</svelte:head>

<GuestLayout
    title="Cek Email Anda"
    subtitle="Kami sudah mengirim link verifikasi ke {page.props.auth.user?.email}. Klik link tersebut untuk memverifikasi akun."
    status={sent ? 'Link verifikasi baru sudah dikirim.' : null}
>
    <p class="text-sm text-gray-600">
        Setelah terverifikasi, pesanan yang pernah Anda buat tanpa login dengan email ini otomatis muncul di akun Anda.
    </p>

    <form class="mt-5" onsubmit={resend}>
        <PrimaryButton disabled={form.processing}>
            {form.processing ? 'Mengirim…' : 'Kirim Ulang Link Verifikasi'}
        </PrimaryButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <Link href="/akun" class="font-medium text-blue-700 hover:underline">Nanti saja, ke akun saya</Link>
    </p>
</GuestLayout>
