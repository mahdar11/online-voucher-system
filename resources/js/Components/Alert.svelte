<script>
    let { type = 'success', message, details = [] } = $props();

    const styles = {
        success: 'border-green-200 bg-green-50 text-green-800',
        error: 'border-red-200 bg-red-50 text-red-800',
        info: 'border-blue-200 bg-blue-50 text-blue-800',
    };
</script>

{#if message}
    <div class="rounded-lg border px-4 py-3 text-sm {styles[type]}" role={type === 'error' ? 'alert' : 'status'}>
        <p>{message}</p>
        {#if details?.length}
            <ul class="mt-2 list-disc space-y-0.5 pl-5 text-xs opacity-90">
                {#each details as detail, i (i)}
                    <li>{detail}</li>
                {/each}
            </ul>
        {/if}
    </div>
{/if}
