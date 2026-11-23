<script>
    import { onMount } from 'svelte';

    /**
     * Pembungkus ApexCharts. Library dimuat saat dibutuhkan (dynamic import),
     * jadi tidak membebani halaman lain. `options` reaktif: perubahan data
     * memperbarui grafik tanpa membuat ulang.
     */
    let { options, label } = $props();

    let element;
    let chart = $state(null);

    onMount(() => {
        let disposed = false;

        import('apexcharts').then(({ default: ApexCharts }) => {
            if (disposed) return;
            chart = new ApexCharts(element, $state.snapshot(options));
            chart.render();
        });

        return () => {
            disposed = true;
            chart?.destroy();
        };
    });

    $effect(() => {
        const next = $state.snapshot(options);
        if (chart) chart.updateOptions(next, false, true);
    });
</script>

<div bind:this={element} role="img" aria-label={label}></div>
