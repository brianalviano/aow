<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { useForm } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";

    type Customer = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        is_active: boolean;
        last_transaction_at: string | null;
        address?: string | null;
        is_visible_in_pos?: boolean;
        is_visible_in_marketing?: boolean;
        marketers?: { id: string; name: string }[] | null;
    };

    let customers = $derived($page.props.customers as Customer[]);
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
        is_active: string;
        last_transaction_from: string;
        last_transaction_to: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        is_active:
            ($page.props.filters as { is_active?: string })?.is_active ?? "",
        last_transaction_from:
            ($page.props.filters as { last_transaction_from?: string })
                ?.last_transaction_from ?? "",
        last_transaction_to:
            ($page.props.filters as { last_transaction_to?: string })
                ?.last_transaction_to ?? "",
    });

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedCustomer = $state<Customer | null>(null);
    let showImportModal = $state(false);
    let importFile = $state<File | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.is_active) params.set("is_active", filters.is_active);
        if (filters.last_transaction_from)
            params.set("last_transaction_from", filters.last_transaction_from);
        if (filters.last_transaction_to)
            params.set("last_transaction_to", filters.last_transaction_to);
        router.get(
            "/customers?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.is_active = "";
        filters.last_transaction_from = "";
        filters.last_transaction_to = "";
        applyFilters();
    }

    function exportExcel() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        const url = params.toString()
            ? `/customers/export?${params.toString()}`
            : "/customers/export";
        window.open(url, "_blank");
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.is_active) params.set("is_active", filters.is_active);
        if (filters.last_transaction_from)
            params.set("last_transaction_from", filters.last_transaction_from);
        if (filters.last_transaction_to)
            params.set("last_transaction_to", filters.last_transaction_to);
        params.set("page", String(pageNum));
        router.get(
            "/customers?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(c: Customer) {
        selectedCustomer = c;
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
        $importForm.post("/customers/import", {
            onSuccess: () => {
                closeImportModal();
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Pelanggan | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Pelanggan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola data pelanggan
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/customers/create"
                icon="fa-solid fa-plus">Tambah Pelanggan</Button
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
                label="Cari pelanggan"
                placeholder="Nama, email, telepon, atau alamat..."
                bind:value={filters.q}
            />
            <Select
                id="is_active"
                name="is_active"
                label="Status"
                options={[
                    { value: "", label: "Semua" },
                    { value: "1", label: "Aktif" },
                    { value: "0", label: "Tidak Aktif" },
                ]}
                bind:value={filters.is_active}
            />

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <DateInput
                    id="last_transaction_from"
                    name="last_transaction_from"
                    label="Transaksi dari"
                    bind:value={filters.last_transaction_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="last_transaction_to"
                    name="last_transaction_to"
                    label="Transaksi sampai"
                    bind:value={filters.last_transaction_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Pelanggan" bodyWithoutPadding={true}>
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
                                >Email</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Telepon</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Terakhir Transaksi</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Visibilitas</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Marketer</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if customers?.length}
                            {#each customers as c}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {c.name}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {c.email}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {c.phone || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(
                                                c.last_transaction_at,
                                            )}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class={`px-2 py-1 text-xs rounded ${
                                                c.is_active
                                                    ? "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                                    : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300"
                                            }`}
                                            aria-label={c.is_active
                                                ? "Aktif"
                                                : "Tidak Aktif"}
                                            >{c.is_active
                                                ? "Aktif"
                                                : "Tidak Aktif"}</span
                                        >
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {[
                                                c.is_visible_in_pos ? "POS" : "",
                                                c.is_visible_in_marketing ? "Marketing" : "",
                                            ]
                                                .filter(Boolean)
                                                .join(", ") || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {(c.marketers ?? [])
                                                .map((m) => m.name)
                                                .join(", ") || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/customers/${c.id}`}
                                                >Detail</Button
                                            >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/customers/${c.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteDialog(c)}
                                                >Hapus</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="8"
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
    title="Import Pelanggan dari Excel"
    size="md"
>
    {#snippet children()}
        <form id="import-customer-form" onsubmit={submitImport}>
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
                            href="/customers/import/template"
                            class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                        >
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>name (wajib, nama pelanggan)</li>
                        <li>email (wajib, unik)</li>
                        <li>phone (opsional)</li>
                        <li>address (opsional)</li>
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
            form="import-customer-form"
            loading={$importForm.processing}
            disabled={$importForm.processing || !importFile}
            icon="fa-solid fa-file-import">Import</Button
        >
    {/snippet}
</Modal>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Pelanggan"
    message={`Apakah Anda yakin ingin menghapus pelanggan ${selectedCustomer?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedCustomer) {
            await router.delete(`/customers/${selectedCustomer.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedCustomer = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedCustomer = null;
    }}
    onClose={() => {
        selectedCustomer = null;
        deleteProcessing = false;
    }}
/>
