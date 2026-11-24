<script>
    import { Link, page } from '@inertiajs/svelte';
    import Alert from '@/Components/Alert.svelte';

    let { title, actions, children } = $props();

    let menuOpen = $state(false);

    const user = $derived(page.props.auth.user);

    // "permission" adalah key dari auth.user.can yang dikirim backend.
    const menu = $derived(
        [
            { href: '/admin', label: 'Dashboard', exact: true },
            { href: '/admin/paket', label: 'Paket Voucher', permission: 'managePackages' },
            { href: '/admin/voucher', label: 'Stok Voucher', permission: 'manageVouchers' },
            { href: '/admin/batch', label: 'Riwayat Batch', permission: 'manageVouchers' },
            { href: '/admin/pesanan', label: 'Pesanan', permission: 'manageOrders' },
            { href: '/admin/laporan', label: 'Laporan', permission: 'viewReports' },
            { href: '/admin/pengguna', label: 'Pengguna', permission: 'manageUsers' },
            { href: '/admin/pengaturan', label: 'Pengaturan', permission: 'manageSettings' },
            { href: '/admin/log', label: 'Log Aktivitas', permission: 'manageSettings' },
        ].filter((item) => !item.permission || user?.can?.[item.permission]),
    );

    const currentPath = $derived(page.url.split('?')[0]);

    function isActive(item) {
        return item.exact ? currentPath === item.href : currentPath.startsWith(item.href);
    }
</script>

<svelte:head>
    <title>{title}</title>
</svelte:head>

<div class="min-h-screen lg:flex">
    <aside class="border-b border-gray-200 bg-white lg:min-h-screen lg:w-60 lg:shrink-0 lg:border-r lg:border-b-0">
        <div class="flex items-center justify-between px-4 py-3 lg:py-5">
            <Link href="/admin" class="font-semibold text-blue-700">{page.props.appName}</Link>
            <button
                type="button"
                class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-700 lg:hidden"
                aria-expanded={menuOpen}
                aria-controls="admin-menu"
                onclick={() => (menuOpen = !menuOpen)}
            >
                Menu
            </button>
        </div>

        <nav id="admin-menu" class="{menuOpen ? 'block' : 'hidden'} px-3 pb-3 lg:block">
            <ul class="space-y-1">
                {#each menu as item (item.href)}
                    <li>
                        <Link
                            href={item.href}
                            aria-current={isActive(item) ? 'page' : undefined}
                            class="block rounded-lg px-3 py-2 text-sm font-medium
                                {isActive(item) ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100'}"
                        >
                            {item.label}
                        </Link>
                    </li>
                {/each}
            </ul>

            <div class="mt-6 border-t border-gray-200 pt-4 text-sm">
                <p class="px-3 font-medium text-gray-900">{user.name}</p>
                <p class="px-3 text-xs text-gray-500">{user.email}</p>
                <div class="mt-3 flex gap-2 px-3">
                    <Link href="/" class="text-gray-600 hover:text-blue-700">Situs</Link>
                    <span class="text-gray-300">·</span>
                    <Link href="/logout" method="post" as="button" class="text-gray-600 hover:text-blue-700">Keluar</Link>
                </div>
            </div>
        </nav>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-4 sm:px-6">
            <h1 class="text-xl font-semibold">{title}</h1>
            {#if actions}
                <div class="flex flex-wrap gap-2">{@render actions()}</div>
            {/if}
        </header>

        <main class="space-y-4 px-4 py-6 sm:px-6">
            <Alert type="success" message={page.props.flash?.success} details={page.props.flash?.details} />
            <Alert type="error" message={page.props.flash?.error} />

            {@render children?.()}
        </main>
    </div>
</div>
