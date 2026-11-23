<script>
    import { Link, page } from '@inertiajs/svelte';
    import Alert from '@/Components/Alert.svelte';

    let { children } = $props();

    const user = $derived(page.props.auth?.user);
    const isStaff = $derived(user?.isStaff ?? false);
</script>

<div class="min-h-screen">
    <header class="border-b border-gray-200 bg-white">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3">
            <Link href="/" class="text-lg font-semibold text-blue-700">{page.props.appName}</Link>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm sm:gap-x-4">
                {#if user}
                    {#if isStaff}
                        <Link href="/admin" class="font-medium text-gray-700 hover:text-blue-700">Admin</Link>
                    {/if}
                    <Link href="/akun/pesanan" class="font-medium text-gray-700 hover:text-blue-700">Pesanan Saya</Link>
                    <Link href="/akun" class="font-medium text-gray-700 hover:text-blue-700">Akun</Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Keluar
                    </Link>
                {:else}
                    <Link href="/login" class="font-medium text-gray-700 hover:text-blue-700">Masuk</Link>
                    <Link href="/register" class="rounded-lg bg-blue-600 px-3 py-1.5 font-medium text-white hover:bg-blue-700">
                        Daftar
                    </Link>
                {/if}
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl space-y-4 px-4 py-8">
        <Alert type="success" message={page.props.flash?.success} details={page.props.flash?.details} />
        <Alert type="error" message={page.props.flash?.error} />

        {@render children?.()}
    </main>
</div>
