<script>
    let { appName } = $props();
</script>

<svelte:head>
    <title>Beranda</title>
</svelte:head>

<main class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center px-4 text-center">
    <h1 class="text-3xl font-semibold">{appName}</h1>
    <p class="mt-3 text-gray-600">
        Laravel + Inertia + Svelte + PostgreSQL sudah terhubung.
    </p>
</main>
