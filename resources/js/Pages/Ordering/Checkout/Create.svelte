<script>
    import { untrack } from 'svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import InputField from '@/Components/InputField.svelte';
    import PrimaryButton from '@/Components/PrimaryButton.svelte';
    import { formatDuration, formatQuota, formatRupiah, formatValidity } from '@/lib/format.js';

    let { package: pkg, maxQuantity, reservationMinutes, customer } = $props();

    const form = useForm({
        quantity: 1,
        ...untrack(() => customer),
    });

    const soldOut = $derived(maxQuantity < 1);
    const quantity = $derived(Math.min(Math.max(Number(form.quantity) || 1, 1), Math.max(maxQuantity, 1)));
    const total = $derived(pkg.price * quantity);

    function setQuantity(value) {
        form.quantity = Math.min(Math.max(value, 1), maxQuantity);
    }

    function submit(event) {
        event.preventDefault();
        form.post(`/beli/${pkg.slug}`);
    }
</script>

<svelte:head>
    <title>Beli {pkg.name}</title>
</svelte:head>

<AppLayout>
    <Link href="/" class="text-sm text-gray-600 hover:text-gray-900">‹ Kembali ke katalog</Link>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <form class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" onsubmit={submit} novalidate>
            <h1 class="text-xl font-semibold">Data Pembeli</h1>

            <div>
                <span class="block text-sm font-medium text-gray-700">Jumlah Voucher</span>
                <div class="mt-1 flex items-center gap-2">
                    <button
                        type="button"
                        class="h-10 w-10 rounded-lg border border-gray-300 text-lg disabled:opacity-40"
                        aria-label="Kurangi jumlah"
                        disabled={quantity <= 1 || soldOut}
                        onclick={() => setQuantity(quantity - 1)}
                    >−</button>
                    <input
                        id="quantity"
                        type="number"
                        min="1"
                        max={maxQuantity}
                        inputmode="numeric"
                        aria-label="Jumlah voucher"
                        bind:value={form.quantity}
                        disabled={soldOut}
                        class="h-10 w-20 rounded-lg border border-gray-300 text-center text-sm"
                    />
                    <button
                        type="button"
                        class="h-10 w-10 rounded-lg border border-gray-300 text-lg disabled:opacity-40"
                        aria-label="Tambah jumlah"
                        disabled={quantity >= maxQuantity || soldOut}
                        onclick={() => setQuantity(quantity + 1)}
                    >+</button>
                </div>
                {#if form.errors.quantity}
                    <p class="mt-1 text-sm text-red-600">{form.errors.quantity}</p>
                {:else if !soldOut}
                    <p class="mt-1 text-xs text-gray-500">Maksimal {maxQuantity} voucher per pesanan.</p>
                {/if}
            </div>

            <InputField id="name" label="Nama" autocomplete="name" required bind:value={form.name} error={form.errors.name} />
            <InputField
                id="email"
                label="Email"
                type="email"
                autocomplete="email"
                required
                hint="Kode voucher akan dikirim ke email ini."
                bind:value={form.email}
                error={form.errors.email}
            />
            <InputField
                id="phone"
                label="Nomor HP (opsional)"
                type="tel"
                autocomplete="tel"
                placeholder="081234567890"
                bind:value={form.phone}
                error={form.errors.phone}
            />

            <p class="rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-800">
                Voucher akan disimpan untuk Anda selama {reservationMinutes} menit setelah pesanan dibuat.
            </p>

            <PrimaryButton disabled={form.processing || soldOut}>
                {soldOut ? 'Stok Habis' : form.processing ? 'Membuat pesanan…' : `Buat Pesanan · ${formatRupiah(total)}`}
            </PrimaryButton>
        </form>

        <aside class="h-fit space-y-4 rounded-2xl border border-gray-200 bg-white p-5">
            <div>
                <p class="text-sm text-gray-500">Paket</p>
                <h2 class="text-lg font-semibold">{pkg.name}</h2>
            </div>

            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Durasi</dt><dd>{formatDuration(pkg.duration_minutes)}</dd></div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Masa aktif</dt>
                    <dd class="text-right">{formatValidity(pkg.validity_days)}{pkg.validity_days ? ' sejak login pertama' : ''}</dd>
                </div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Kuota</dt><dd>{formatQuota(pkg.quota_mb)}</dd></div>
                {#if pkg.speed_limit}
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Kecepatan</dt><dd>{pkg.speed_limit}</dd></div>
                {/if}
            </dl>

            <dl class="space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{formatRupiah(pkg.price)} × {quantity}</dt>
                    <dd>{formatRupiah(total)}</dd>
                </div>
                <div class="flex justify-between gap-3 text-base font-semibold">
                    <dt>Total</dt>
                    <dd>{formatRupiah(total)}</dd>
                </div>
            </dl>
        </aside>
    </div>
</AppLayout>
