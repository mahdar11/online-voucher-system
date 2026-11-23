import { createInertiaApp } from '@inertiajs/svelte';

const appName = import.meta.env.VITE_APP_NAME || 'Online Voucher';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true });
        const page = pages[`./Pages/${name}.svelte`];

        if (!page) {
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        }

        return page;
    },
    progress: {
        color: '#2563eb',
    },
});
