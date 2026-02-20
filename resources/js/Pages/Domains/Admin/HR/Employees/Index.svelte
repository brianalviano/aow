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

    type Role = { id: string | null; name: string | null };

    type Employee = {
        id: string;
        name: string;
        email: string;
        phone_number: string | null;
        join_date: string | null;
        role: Role;
    };

    let employees = $derived($page.props.employees as Employee[]);
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
        role_id: string;
        join_date_from: string;
        join_date_to: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        role_id: ($page.props.filters as { role_id?: string })?.role_id ?? "",
        join_date_from:
            ($page.props.filters as { join_date_from?: string })
                ?.join_date_from ?? "",
        join_date_to:
            ($page.props.filters as { join_date_to?: string })?.join_date_to ??
            "",
    });

    type RoleItem = { id: string; name: string };
    let roles = $derived($page.props.roles as RoleItem[] | undefined);
    let roleOptions = $derived([
        { value: "", label: "Semua Role" },
        ...(roles ?? []).map((r) => ({ value: r.id, label: r.name })),
    ]);

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedEmployee = $state<Employee | null>(null);
    let showImportModal = $state(false);
    let importFile = $state<File | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.role_id) params.set("role_id", filters.role_id);
        if (filters.join_date_from)
            params.set("join_date_from", filters.join_date_from);
        if (filters.join_date_to)
            params.set("join_date_to", filters.join_date_to);
        router.get(
            "/employees?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.role_id = "";
        filters.join_date_from = "";
        filters.join_date_to = "";
        applyFilters();
    }

    function exportExcel() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        const url = params.toString()
            ? `/employees/export?${params.toString()}`
            : "/employees/export";
        window.open(url, "_blank");
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.role_id) params.set("role_id", filters.role_id);
        if (filters.join_date_from)
            params.set("join_date_from", filters.join_date_from);
        if (filters.join_date_to)
            params.set("join_date_to", filters.join_date_to);
        params.set("page", String(pageNum));
        router.get(
            "/employees?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(e: Employee) {
        selectedEmployee = e;
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
        $importForm.post("/employees/import", {
            onSuccess: () => {
                closeImportModal();
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Karyawan | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Karyawan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola data karyawan
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/employees/create"
                icon="fa-solid fa-plus">Tambah Karyawan</Button
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
                label="Cari karyawan"
                placeholder="Nama, email, username, atau nomor telepon..."
                bind:value={filters.q}
            />
            <Select
                id="role_id"
                name="role_id"
                label="Role"
                options={roleOptions}
                searchable={true}
                bind:value={filters.role_id}
            />

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <DateInput
                    id="join_date_from"
                    name="join_date_from"
                    label="Bergabung dari"
                    bind:value={filters.join_date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="join_date_to"
                    name="join_date_to"
                    label="Bergabung sampai"
                    bind:value={filters.join_date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Karyawan" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Telepon</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if employees?.length}
                            {#each employees as e}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {e.name}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {e.email}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {e.role?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {e.phone_number || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(e.join_date)}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/employees/${e.id}`}
                                                >Detail</Button
                                            >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/employees/${e.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteDialog(e)}
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
    title="Import Karyawan dari Excel"
    size="md"
>
    {#snippet children()}
        <form id="import-employee-form" onsubmit={submitImport}>
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
                            href="/employees/import/template"
                            class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                        >
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>name (wajib, nama karyawan)</li>
                        <li>email (wajib, unik)</li>
                        <li>role_name (opsional, nama role)</li>
                        <li>phone_number (opsional)</li>
                        <li>
                            join_date (opsional, mendukung: YYYY-MM-DD,
                            DD-MM-YYYY, MM-DD-YYYY, atau angka serial Excel)
                        </li>
                        <li>address (opsional)</li>
                        <li>
                            birth_date (opsional, mendukung: YYYY-MM-DD,
                            DD-MM-YYYY, MM-DD-YYYY, atau angka serial Excel)
                        </li>
                        <li>
                            gender (opsional, male/female atau:
                            L/laki-laki/cowok/M, P/perempuan/cewek/F)
                        </li>
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
            form="import-employee-form"
            loading={$importForm.processing}
            disabled={$importForm.processing || !importFile}
            icon="fa-solid fa-file-import">Import</Button
        >
    {/snippet}
</Modal>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Karyawan"
    message={`Apakah Anda yakin ingin menghapus karyawan ${selectedEmployee?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedEmployee) {
            await router.delete(`/employees/${selectedEmployee.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedEmployee = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedEmployee = null;
    }}
    onClose={() => {
        selectedEmployee = null;
        deleteProcessing = false;
    }}
/>
