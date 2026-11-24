<script>
    /** Daftar kode voucher yang sudah lunas, dengan tombol salin. */
    let { vouchers } = $props();

    let copied = $state(null);

    async function copy(text, key) {
        try {
            await navigator.clipboard.writeText(text);
            copied = key;
            setTimeout(() => (copied = copied === key ? null : copied), 2000);
        } catch {
            copied = null;
        }
    }
</script>

<ul class="space-y-2">
    {#each vouchers as voucher, i (voucher.code)}
        <li class="rounded-xl border border-green-200 bg-green-50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs text-green-800">Voucher {i + 1} · Kode</p>
                    <p class="font-mono text-lg font-semibold tracking-wide text-gray-900">{voucher.code}</p>
                    {#if voucher.password}
                        <p class="text-xs text-green-800">Password: <span class="font-mono font-semibold text-gray-900">{voucher.password}</span></p>
                    {/if}
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-green-300 bg-white px-3 py-1.5 text-sm font-medium text-green-800 hover:bg-green-100"
                    onclick={() => copy(voucher.code, i)}
                >
                    {copied === i ? 'Tersalin ✓' : 'Salin kode'}
                </button>
            </div>
        </li>
    {/each}
</ul>
