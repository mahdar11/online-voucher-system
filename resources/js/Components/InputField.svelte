<script>
    let {
        id,
        label,
        type = 'text',
        value = $bindable(''),
        error = null,
        hint = null,
        ...rest
    } = $props();
</script>

<div>
    <label for={id} class="block text-sm font-medium text-gray-700">{label}</label>
    <input
        {id}
        name={id}
        {type}
        bind:value
        aria-invalid={error ? 'true' : undefined}
        aria-describedby={error ? `${id}-error` : hint ? `${id}-hint` : undefined}
        class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2
            {error ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200'}"
        {...rest}
    />
    {#if error}
        <p id="{id}-error" class="mt-1 text-sm text-red-600">{error}</p>
    {:else if hint}
        <p id="{id}-hint" class="mt-1 text-xs text-gray-500">{hint}</p>
    {/if}
</div>
