<script>
    import { Link } from '@inertiajs/svelte';
    import StatusBadge from '@/Components/StatusBadge.svelte';
    import { formatDateTime, formatRupiah } from '@/lib/format.js';

    let { order } = $props();
</script>

<Link
    href="/pesanan/{order.invoice_number}"
    class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 hover:border-blue-300"
>
    <div>
        <p class="font-mono text-sm font-semibold">{order.invoice_number}</p>
        <p class="text-sm text-gray-600">
            {order.items.map((item) => `${item.package_name} × ${item.quantity}`).join(', ')}
        </p>
        <p class="text-xs text-gray-500">{formatDateTime(order.created_at)}</p>
    </div>
    <div class="text-right">
        <p class="font-semibold">{formatRupiah(order.total)}</p>
        <StatusBadge status={order.status} label={order.status_label} />
    </div>
</Link>
