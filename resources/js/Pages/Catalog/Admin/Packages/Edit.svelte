<script>
    import AdminLayout from '@/Layouts/AdminLayout.svelte';
    import PackageForm from '@/Components/Catalog/PackageForm.svelte';

    let { package: pkg } = $props();
</script>

<AdminLayout title="Ubah Paket">
    <PackageForm package={pkg} submitLabel="Simpan Perubahan" />
</AdminLayout>
