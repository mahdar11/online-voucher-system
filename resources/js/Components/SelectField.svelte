<script>
    let {
        id,
        label,
        options = [],
        value = $bindable(''),
        placeholder = null,
        error = null,
        hint = null,
        ...rest
    } = $props();
</script>

<div>
    {#if label}
        <label for={id} class="block text-sm font-medium text-gray-700">{label}</label>
    {/if}
    <select
        {id}
        name={id}
        bind:value
        aria-invalid={error ? 'true' : undefined}
        class="mt-1 block w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2
            {error ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200'}"
        {...rest}
    >
        {#if placeholder !== null}
            <option value="">{placeholder}</option>
        {/if}
        {#each options as option (option.value)}
            <option value={option.value}>{option.label}</option>
        {/each}
    </select>
    {#if error}
        <p class="mt-1 text-sm text-red-600">{error}</p>
    {:else if hint}
        <p class="mt-1 text-xs text-gray-500">{hint}</p>
    {/if}
</div>
