<script>
    import { Link, page } from '@inertiajs/svelte';

    let { children } = $props();

    const user = $derived(page.props.auth?.user);
    const isStaff = $derived(user?.roles?.some((role) => ['super_admin', 'admin'].includes(role)) ?? false);
</script>

<div class="min-h-screen">
    <header class="border-b border-gray-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <Link href="/" class="text-lg font-semibold text-blue-700">{page.props.appName}</Link>

            <div class="flex items-center gap-2 text-sm sm:gap-4">
                {#if user}
                    {#if isStaff}
                        <Link href="/admin" class="font-medium text-gray-700 hover:text-blue-700">Admin</Link>
                    {/if}
                    <Link href="/akun" class="font-medium text-gray-700 hover:text-blue-700">Akun Saya</Link>
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

    <main class="mx-auto max-w-6xl px-4 py-8">
        {@render children?.()}
    </main>
</div>
