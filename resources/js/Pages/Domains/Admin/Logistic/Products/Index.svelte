<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { useForm } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";

    type Rel = { id: string | null; name: string | null };

    type Product = {
        id: string;
        name: string;
        sku: string;
        description: string;
        image_url: string | null;
        is_active: boolean;
        category: Rel;
        unit: Rel;
    };

    let products = $derived($page.props.products as Product[]);
    let meta = $derived(
        $page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );

    type LocalFilters = {
        q: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
    });

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedProduct = $state<Product | null>(null);
    let showImportModal = $state(false);
    let importFile = $state<File | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        router.get(
            "/products?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        applyFilters();
    }

    function exportExcel() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        const url = params.toString()
            ? `/products/export?${params.toString()}`
            : "/products/export";
        window.open(url, "_blank");
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        params.set("page", String(pageNum));
        router.get(
            "/products?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(p: Product) {
        selectedProduct = p;
        showDeleteDialog = true;
    }

    const importForm = useForm({
        file: null as File | null,
    });

    function openImportModal() {
        showImportModal = true;
        importFile = null;
        $importForm.reset();
    }

    function closeImportModal() {
        showImportModal = false;
        importFile = null;
    }

    function submitImport(e: SubmitEvent) {
        e.preventDefault();
        if (!importFile) return;
        $importForm.post("/products/import", {
            onSuccess: () => {
                closeImportModal();
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Produk | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Produk
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola master data produk
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/products/create"
                icon="fa-solid fa-plus">Tambah Produk</Button
            >
            <Button
                variant="info"
                icon="fa-solid fa-file-import"
                onclick={openImportModal}>Import</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-file-excel"
                onclick={exportExcel}>Export</Button
            >
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <TextInput
                id="q"
                name="q"
                label="Cari produk"
                placeholder="Nama, SKU..."
                bind:value={filters.q}
            />
        </div>
    </Card>

    <Card title="Daftar Produk" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr
                            class="text-sm text-left text-gray-600 dark:text-gray-400"
                        >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Nama</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >SKU</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Kategori</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Unit</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aktif</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if products?.length}
                            {#each products as p}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {p.name}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {p.sku}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {p.category?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {p.unit?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="text-xs px-2 py-1 rounded-full {p.is_active
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'}"
                                        >
                                            {p.is_active ? "Aktif" : "Nonaktif"}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/products/${p.id}`}
                                                >Detail</Button
                                            >
                                            <Button
                                                variant="info"
                                                size="sm"
                                                icon="fa-solid fa-print"
                                                onclick={() =>
                                                    openCenteredWindow(
                                                        `/products/${p.id}/print`,
                                                        {
                                                            width: 960,
                                                            height: 700,
                                                            fallbackWhenBlocked:
                                                                false,
                                                        },
                                                    )}
                                                >Cetak</Button
                                            >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/products/${p.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteDialog(p)}
                                                >Hapus</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="7"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Tidak ada data</td
                                >
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
        {#snippet footer()}
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        {/snippet}
    </Card>
</section>

<Modal bind:isOpen={showImportModal} title="Import Produk dari Excel" size="md">
    {#snippet children()}
        <form id="import-product-form" onsubmit={submitImport}>
            <div class="space-y-4">
                <FileUpload
                    id="import-file"
                    name="file"
                    label="Pilih File Excel"
                    accept=".xlsx,.xls,.csv"
                    bind:value={importFile}
                    required
                    error={$importForm.errors.file}
                    uploadText="Pilih atau drag file Excel"
                    onchange={(files) => {
                        const f = files[0] ?? null;
                        importFile = f;
                        $importForm.file = f;
                    }}
                />
                <div
                    class="p-4 bg-blue-50 rounded-lg border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800"
                >
                    <div class="flex justify-between items-center mb-2">
                        <h4
                            class="text-sm font-semibold text-blue-900 dark:text-blue-300"
                        >
                            Format Excel yang dibutuhkan:
                        </h4>
                        <a
                            href="/products/import/template"
                            class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                        >
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>name (wajib)</li>
                        <li>sku (wajib, unik)</li>
                        <li>description (opsional)</li>
                        <li>
                            category_name, sub_category_name, unit_code,
                            factory_name, sub_factory_name, condition_name
                        </li>
                        <li>product_type (opsional: raw/ready)</li>
                        <li>
                            product_variant_type (opsional:
                            standalone/parent/variant)
                        </li>
                        <li>parent_sku (opsional)</li>
                        <li>
                            weight, is_active, min_stock, max_stock,
                            is_stock_tracked
                        </li>
                        <li>image_url (opsional, URL)</li>
                    </ul>
                </div>
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={closeImportModal}>Batal</Button>
        <Button
            variant="primary"
            type="submit"
            form="import-product-form"
            loading={$importForm.processing}
            disabled={$importForm.processing || !importFile}
            icon="fa-solid fa-file-import">Import</Button
        >
    {/snippet}
</Modal>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Produk"
    message={`Apakah Anda yakin ingin menghapus produk ${selectedProduct?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedProduct) {
            await router.delete(`/products/${selectedProduct.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedProduct = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedProduct = null;
    }}
    onClose={() => {
        selectedProduct = null;
        deleteProcessing = false;
    }}
/>
