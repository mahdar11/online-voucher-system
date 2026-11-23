<script>
    let { status, label } = $props();

    const colors = {
        available: 'bg-green-50 text-green-700 ring-green-200',
        reserved: 'bg-amber-50 text-amber-700 ring-amber-200',
        sold: 'bg-blue-50 text-blue-700 ring-blue-200',
        used: 'bg-gray-100 text-gray-700 ring-gray-200',
        expired: 'bg-red-50 text-red-700 ring-red-200',
        withdrawn: 'bg-orange-50 text-orange-700 ring-orange-200',
        // status pesanan
        pending: 'bg-amber-50 text-amber-700 ring-amber-200',
        paid: 'bg-green-50 text-green-700 ring-green-200',
        completed: 'bg-green-50 text-green-700 ring-green-200',
        cancelled: 'bg-gray-100 text-gray-600 ring-gray-200',
        failed: 'bg-red-50 text-red-700 ring-red-200',
        refunded: 'bg-gray-100 text-gray-600 ring-gray-200',
        active: 'bg-green-50 text-green-700 ring-green-200',
        inactive: 'bg-gray-100 text-gray-600 ring-gray-200',
    };
</script>

<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {colors[status] ?? colors.inactive}">
    {label}
</span>
