<script>
    import { Link } from '@inertiajs/svelte';
    import { formatNumber } from '../lib/format.js';

    /** Menerima objek paginator Laravel (LengthAwarePaginator). */
    let { paginator } = $props();

    const pages = $derived((paginator.links ?? []).slice(1, -1));
</script>

{#if paginator.total > 0}
    <nav class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row" aria-label="Navigasi halaman">
        <p class="text-gray-600">
            Menampilkan {formatNumber(paginator.from)}–{formatNumber(paginator.to)} dari {formatNumber(paginator.total)}
        </p>

        {#if paginator.last_page > 1}
            <div class="flex flex-wrap items-center gap-1">
                {#if paginator.prev_page_url}
                    <Link href={paginator.prev_page_url} preserveScroll class="rounded-md px-2.5 py-1 text-gray-700 hover:bg-gray-100">‹ Sebelumnya</Link>
                {/if}

                {#each pages as link, i (i)}
                    {#if link.url === null}
                        <span class="px-2 text-gray-400">…</span>
                    {:else}
                        <Link
                            href={link.url}
                            preserveScroll
                            aria-current={link.active ? 'page' : undefined}
                            class="min-w-8 rounded-md px-2.5 py-1 text-center {link.active ? 'bg-blue-600 font-semibold text-white' : 'text-gray-700 hover:bg-gray-100'}"
                        >
                            {link.label}
                        </Link>
                    {/if}
                {/each}

                {#if paginator.next_page_url}
                    <Link href={paginator.next_page_url} preserveScroll class="rounded-md px-2.5 py-1 text-gray-700 hover:bg-gray-100">Berikutnya ›</Link>
                {/if}
            </div>
        {/if}
    </nav>
{/if}
