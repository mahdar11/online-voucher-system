<script>
    import { untrack } from 'svelte';

    /**
     * Hitung mundur dari jumlah detik yang dihitung server
     * (tidak bergantung pada jam perangkat pembeli).
     */
    let { seconds, onexpire = () => {} } = $props();

    let remaining = $state(untrack(() => Math.max(0, seconds)));

    $effect(() => {
        if (remaining <= 0) return;

        const timer = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                onexpire();
            }
        }, 1000);

        return () => clearInterval(timer);
    });

    const display = $derived(
        `${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`,
    );
</script>

<span class="font-mono tabular-nums" role="timer" aria-live="off">{display}</span>
