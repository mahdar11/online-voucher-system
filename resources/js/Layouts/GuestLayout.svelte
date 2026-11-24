<script>
    import { Link, page } from '@inertiajs/svelte';

    let { title, subtitle = null, status = null, children } = $props();
</script>

<div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
    <Link href="/" class="mb-6 text-xl font-semibold text-blue-700">{page.props.appName}</Link>

    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-xl font-semibold">{title}</h1>
        {#if subtitle}
            <p class="mt-1 text-sm text-gray-600">{subtitle}</p>
        {/if}

        {#if status}
            <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700" role="status">{status}</div>
        {/if}

        <div class="mt-6">
            {@render children?.()}
        </div>
    </div>
</div>
