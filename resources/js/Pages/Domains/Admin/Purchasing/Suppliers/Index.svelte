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

    type Supplier = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        is_active: boolean;
    };

    let suppliers = $derived($page.props.suppliers as Supplier[]);
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
    let selectedSupplier = $state<Supplier | null>(null);
    let showImportModal = $state(false);
    let importFile = $state<File | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        router.get(
            "/suppliers?" + params.toString(),
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
            ? `/suppliers/export?${params.toString()}`
            : "/suppliers/export";
        window.open(url, "_blank");
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        params.set("page", String(pageNum));
        router.get(
            "/suppliers?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(s: Supplier) {
        selectedSupplier = s;
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
        $importForm.post("/suppliers/import", {
            onSuccess: () => {
                closeImportModal();
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Supplier | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Supplier
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola data supplier
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/suppliers/create"
                icon="fa-solid fa-plus">Tambah Supplier</Button
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
                label="Cari supplier"
                placeholder="Nama, email, atau telepon..."
                bind:value={filters.q}
            />
        </div>
    </Card>

    <Card title="Daftar Supplier" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="min-w-full">
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
                                >Email</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Telepon</th
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
                        {#if suppliers?.length}
                            {#each suppliers as s}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {s.name}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {s.email}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {s.phone || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {s.is_active ? "Ya" : "Tidak"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/suppliers/${s.id}`}
                                                >Detail</Button
                                            >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/suppliers/${s.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteDialog(s)}
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

<Modal
    bind:isOpen={showImportModal}
    title="Import Supplier dari Excel"
    size="md"
>
    {#snippet children()}
        <form id="import-supplier-form" onsubmit={submitImport}>
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
                            href="/suppliers/import/template"
                            class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                        >
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>name (wajib, nama supplier)</li>
                        <li>email (wajib, unik)</li>
                        <li>phone (opsional)</li>
                        <li>address (opsional)</li>
                        <li>
                            birth_date (opsional, mendukung: YYYY-MM-DD,
                            DD-MM-YYYY, MM-DD-YYYY, atau angka serial Excel)
                        </li>
                        <li>
                            gender (opsional, male/female atau:
                            L/laki-laki/cowok/M, P/perempuan/cewek/F)
                        </li>
                        <li>is_active (opsional, 1/0 atau true/false)</li>
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
            form="import-supplier-form"
            loading={$importForm.processing}
            disabled={$importForm.processing || !importFile}
            icon="fa-solid fa-file-import">Import</Button
        >
    {/snippet}
</Modal>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Supplier"
    message={`Apakah Anda yakin ingin menghapus supplier ${selectedSupplier?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedSupplier) {
            await router.delete(`/suppliers/${selectedSupplier.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedSupplier = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedSupplier = null;
    }}
    onClose={() => {
        selectedSupplier = null;
        deleteProcessing = false;
    }}
/>
