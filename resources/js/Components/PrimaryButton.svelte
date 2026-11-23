<script>
    let { type = 'submit', disabled = false, children, ...rest } = $props();
</script>

<button
    {type}
    {disabled}
    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
        shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-60"
    {...rest}
>
    {@render children?.()}
</button>
