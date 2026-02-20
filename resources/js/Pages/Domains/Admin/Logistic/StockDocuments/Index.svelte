<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { useForm } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import { toastStore } from "@/Lib/Admin/Stores/toast";
    import { isHighestRole } from "@/Lib/Admin/Utils/roles";

    type Warehouse = { id: string; name: string };
    type DocUser = { id: string | null; name: string | null };
    type StockDocumentItem = {
        id: string;
        number: string;
        document_date: string | null;
        type: string;
        reason: string;
        status: string;
        status_label?: string | null;
        bucket: string | null;
        warehouse: Warehouse | { id: string | null; name: string | null };
        user: DocUser;
        notes: string | null;
    };

    let documents = $derived(
        ($page.props.documents ?? []) as StockDocumentItem[],
    );
    let meta = $derived(
        ($page.props.meta ?? {
            current_page: 1,
            per_page: 10,
            total: 0,
            last_page: 1,
        }) as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );

    type LocalFilters = {
        tab: "in" | "out";
        q: string;
        warehouse_id: string;
        date_from: string;
        date_to: string;
        bucket: string;
        reason: string;
        per_page?: string;
    };

    let filters = $state<LocalFilters>({
        tab: (($page.props.filters as any)?.tab ?? "in") as "in" | "out",
        q: ($page.props.filters as { q?: string })?.q ?? "",
        warehouse_id:
            ($page.props.filters as { warehouse_id?: string })?.warehouse_id ??
            "",
        date_from:
            ($page.props.filters as { date_from?: string })?.date_from ?? "",
        date_to: ($page.props.filters as { date_to?: string })?.date_to ?? "",
        bucket: ($page.props.filters as { bucket?: string })?.bucket ?? "",
        reason: ($page.props.filters as { reason?: string })?.reason ?? "",
        per_page: String(($page.props.filters as any)?.per_page ?? ""),
    });

    let warehouses = $derived(($page.props.warehouses ?? []) as Warehouse[]);
    let reasonOptionsRaw = $derived(
        (($page.props.reasonOptions ?? []) as string[]) ?? [],
    );
    let bucketOptionsRaw = $derived(
        (($page.props.bucketOptions ?? []) as string[]) ?? [],
    );
    let tabCounters = $derived(
        ($page.props.tabCounters ?? { in: 0, out: 0 }) as {
            in: number;
            out: number;
        },
    );

    const warehouseOptions = $derived(
        warehouses.map((w) => ({ value: String(w.id), label: w.name })),
    );
    const bucketOptions = $derived(
        bucketOptionsRaw.map((b) => ({
            value: b,
            label: b === "vat" ? "PPN" : "Non PPN",
        })),
    );
    function reasonLabel(v: string): string {
        const map: Record<string, string> = {
            goods_come: "Barang Datang",
            purchase: "Pembelian",
            sales: "Penjualan",
            purchase_return: "Retur Pembelian",
            sales_return: "Retur Penjualan",
            stock_opname: "Stok Opname",
            damaged: "Rusak",
        };
        return map[v] ?? v;
    }
    function statusLabel(v: string): string {
        const map: Record<string, string> = {
            draft: "Draft",
            pending_ho_approval: "Menunggu Persetujuan HO",
            rejected_by_ho: "Ditolak HO",
            completed: "Selesai",
            canceled: "Dibatalkan",
        };
        return map[v] ?? v;
    }
    const reasonOptions = $derived(
        reasonOptionsRaw.map((r) => ({ value: r, label: reasonLabel(r) })),
    );

    type TabItem = {
        id: "in" | "out";
        label: string;
        count?: number;
    };
    const statusTabs = $derived<TabItem[]>([
        { id: "in", label: "Stok Masuk", count: tabCounters.in },
        { id: "out", label: "Stok Keluar", count: tabCounters.out },
    ]);

    let showImportModal = $state(false);
    const importForm = useForm<{
        file: File | null;
        type: "in" | "out";
        reason: string;
        bucket: string;
        notes: string;
        warehouse_id: string;
    }>({
        file: null,
        type: (($page.props.filters as any)?.tab ?? "in") as "in" | "out",
        reason: "",
        bucket: "",
        notes: "",
        warehouse_id: ($page.props.filters as any)?.warehouse_id ?? "",
    });

    function typeBadgeVariant(
        t: string,
    ):
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple"
        | "white" {
        return t === "in" ? "success" : "danger";
    }
    function statusBadgeVariant(
        s: string,
    ):
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple"
        | "white" {
        if (s === "completed") return "success";
        if (s === "pending_ho_approval") return "warning";
        if (s === "rejected_by_ho") return "danger";
        if (s === "canceled") return "dark";
        return "secondary";
    }

    function applyFilters(pageNum?: number) {
        const params = new URLSearchParams();
        params.set("tab", filters.tab);
        if (filters.q) params.set("q", filters.q);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.date_from) params.set("date_from", filters.date_from);
        if (filters.date_to) params.set("date_to", filters.date_to);
        if (filters.bucket) params.set("bucket", filters.bucket);
        if (filters.reason) params.set("reason", filters.reason);
        if (filters.per_page) params.set("per_page", filters.per_page);
        if (pageNum) params.set("page", String(pageNum));
        router.visit(`/stock-documents?${params.toString()}`);
    }

    function resetFilters() {
        filters.q = "";
        filters.warehouse_id = "";
        filters.date_from = "";
        filters.date_to = "";
        filters.bucket = "";
        filters.reason = "";
        applyFilters();
    }

    function openDetail(id: string) {
        router.visit(`/stock-documents/${id}/show`);
    }
    function printDoc(id: string) {
        openCenteredWindow(`/stock-documents/${id}/print`, {
            width: 960,
            height: 700,
            fallbackWhenBlocked: false,
        });
    }

    function exportExcel() {
        const params = new URLSearchParams();
        params.set("tab", filters.tab);
        if (filters.q) params.set("q", filters.q);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.date_from) params.set("date_from", filters.date_from);
        if (filters.date_to) params.set("date_to", filters.date_to);
        if (filters.bucket) params.set("bucket", filters.bucket);
        if (filters.reason) params.set("reason", filters.reason);
        window.location.href = `/stock-documents/export?${params.toString()}`;
    }

    function openImport() {
        $importForm.type = filters.tab;
        $importForm.reason = "";
        $importForm.bucket = "";
        $importForm.notes = "";
        $importForm.file = null;
        $importForm.warehouse_id = filters.warehouse_id ?? "";
        showImportModal = true;
    }

    function submitImport(e: SubmitEvent) {
        e.preventDefault();
        if (!$importForm.file) return;
        $importForm.post("/stock-documents/import", {
            preserveScroll: true,
            onSuccess: () => {
                showImportModal = false;
            },
        });
    }

    type SelectedDoc = StockDocumentItem | null;
    let selectedDoc = $state<SelectedDoc>(null);
    let showAdvanceDialog = $state(false);
    let showRejectModal = $state(false);
    let rejectReason = $state("");

    function getNextStatus(
        current: string | null,
    ): { value: string; label: string } | null {
        const c = String(current ?? "");
        switch (c) {
            case "draft":
                return { value: "pending_ho_approval", label: "Ajukan ke HO" };
            case "pending_ho_approval":
                return isHighestRole($page.props.auth?.user?.role)
                    ? { value: "completed", label: "Setujui HO" }
                    : null;
            default:
                return null;
        }
    }
    function canEdit(status: string | null): boolean {
        const s = String(status ?? "");
        return s === "draft" || s === "rejected_by_ho";
    }
    function canRejectHo(status: string | null): boolean {
        return (
            isHighestRole($page.props.auth?.user?.role) &&
            String(status ?? "") === "pending_ho_approval"
        );
    }
    function advanceStatus(d: StockDocumentItem) {
        selectedDoc = d;
        showAdvanceDialog = true;
    }
    function openRejectModal(d: StockDocumentItem) {
        selectedDoc = d;
        rejectReason = "";
        if (canRejectHo(d.status)) {
            showRejectModal = true;
        }
    }
</script>

<svelte:head>
    <title>Dokumen Stok | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Dokumen Stok
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola stok masuk dan stok keluar
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/stock-documents/create"
                icon="fa-solid fa-plus">Buat Dokumen</Button
            >
            <Button
                variant="info"
                icon="fa-solid fa-file-import"
                onclick={openImport}>Import</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-file-excel"
                onclick={exportExcel}>Export</Button
            >
        </div>
    </header>

    <div>
        <Tab
            tabs={statusTabs}
            activeTab={filters.tab}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.tab = tabId as "in" | "out";
                applyFilters();
            }}
        />
    </div>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={() => applyFilters()}
                >Terapkan</Button
            >
            <Button variant="secondary" onclick={() => resetFilters()}
                >Reset</Button
            >
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <TextInput
                id="q"
                name="q"
                label="Cari Dokumen"
                placeholder="Nomor dokumen, catatan..."
                bind:value={filters.q}
            />
            <Select
                id="warehouse_id"
                name="warehouse_id"
                label="Gudang"
                options={warehouseOptions}
                searchable={true}
                bind:value={filters.warehouse_id}
            />
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <DateInput
                    id="date_from"
                    name="date_from"
                    label="Tanggal dari"
                    bind:value={filters.date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="date_to"
                    name="date_to"
                    label="Tanggal sampai"
                    bind:value={filters.date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
            <Select
                id="reason"
                name="reason"
                label="Alasan"
                options={reasonOptions}
                searchable={true}
                bind:value={filters.reason}
            />
            <Select
                id="bucket"
                name="bucket"
                label="Bucket"
                options={bucketOptions}
                searchable={true}
                bind:value={filters.bucket}
            />
        </div>
    </Card>

    <Card title="Daftar Dokumen Stok" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Gudang</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Bucket</th>
                            <th>Pembuat</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if documents?.length}
                            {#each documents as d}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {d.number}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {d.warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(d.document_date)}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={typeBadgeVariant(d.type)}
                                            title={d.type === "in"
                                                ? "Masuk"
                                                : "Keluar"}
                                        >
                                            {#snippet children()}
                                                {d.type === "in"
                                                    ? "Masuk"
                                                    : "Keluar"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td>
                                        {reasonLabel(d.reason)}
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={statusBadgeVariant(
                                                d.status,
                                            )}
                                            title={d.status_label ??
                                                statusLabel(d.status)}
                                        >
                                            {#snippet children()}
                                                {d.status_label ??
                                                    statusLabel(d.status)}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td>
                                        {d.bucket === "vat"
                                            ? "PPN"
                                            : d.bucket
                                              ? "Non PPN"
                                              : "-"}
                                    </td>
                                    <td>
                                        {d.user?.name || "-"}
                                    </td>
                                    <td>
                                        {d.notes || "-"}
                                    </td>
                                    <td>
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                onclick={() => openDetail(d.id)}
                                                >Detail</Button
                                            >
                                            {#if canEdit(d.status)}
                                                <Button
                                                    variant="warning"
                                                    size="sm"
                                                    icon="fa-solid fa-edit"
                                                    href={`/stock-documents/${d.id}/edit`}
                                                    >Edit</Button
                                                >
                                            {/if}
                                            {#if getNextStatus(d.status)}
                                                <Button
                                                    variant="success"
                                                    size="sm"
                                                    icon="fa-solid fa-forward"
                                                    onclick={() =>
                                                        advanceStatus(d)}
                                                    >Lanjut: {getNextStatus(
                                                        d.status,
                                                    )?.label}</Button
                                                >
                                            {/if}
                                            {#if canRejectHo(d.status)}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-xmark"
                                                    onclick={() =>
                                                        openRejectModal(d)}
                                                    >Tolak HO</Button
                                                >
                                            {/if}
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                icon="fa-solid fa-print"
                                                onclick={() => printDoc(d.id)}
                                                >Cetak</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="9"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data dokumen stok.
                                </td>
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
                onPageChange={(p: number) => applyFilters(p)}
                onItemsPerPageChange={(n: number) => {
                    filters.per_page = String(n);
                    applyFilters(1);
                }}
            />
        {/snippet}
    </Card>
</section>

<Modal bind:isOpen={showImportModal} title="Import Dokumen Stok">
    {#snippet children()}
        <form onsubmit={submitImport}>
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <Select
                        id="warehouse_id_import"
                        name="warehouse_id"
                        label="Gudang"
                        options={warehouseOptions}
                        searchable={true}
                        bind:value={$importForm.warehouse_id}
                    />
                    <Select
                        id="type"
                        name="type"
                        label="Jenis"
                        options={[
                            { value: "in", label: "Masuk" },
                            { value: "out", label: "Keluar" },
                        ]}
                        bind:value={$importForm.type}
                        onchange={() => ($importForm.reason = "")}
                    />
                    <Select
                        id="reason"
                        name="reason"
                        label="Alasan"
                        options={reasonOptions.filter((r) => {
                            if ($importForm.type === "in") {
                                return [
                                    "goods_come",
                                    "purchase",
                                    "sales_return",
                                    "stock_opname",
                                ].includes(r.value);
                            }
                            return [
                                "sales",
                                "purchase_return",
                                "damaged",
                                "stock_opname",
                            ].includes(r.value);
                        })}
                        bind:value={$importForm.reason}
                    />
                </div>
                <Select
                    id="bucket"
                    name="bucket"
                    label="Bucket"
                    options={bucketOptions}
                    bind:value={$importForm.bucket}
                />
                <TextInput
                    id="notes"
                    name="notes"
                    label="Catatan"
                    placeholder="Opsional"
                    bind:value={$importForm.notes}
                />
                <FileUpload
                    id="file"
                    name="file"
                    label="File Excel"
                    bind:value={$importForm.file}
                    accept=".xlsx,.xls,.csv"
                    required
                    error={$importForm.errors.file}
                    uploadText="Pilih atau drag file Excel"
                    onchange={(files) => ($importForm.file = files[0] ?? null)}
                    onerror={(msg) => toastStore.error("Upload gagal", msg)}
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
                        {#if $importForm.type === "in"}
                            <a
                                href="/stock-documents/import/in/template"
                                class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                            >
                                <i class="fa-solid fa-download"></i>
                                Download Template
                            </a>
                        {:else}
                            <a
                                href="/stock-documents/import/out/template"
                                class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                            >
                                <i class="fa-solid fa-download"></i>
                                Download Template
                            </a>
                        {/if}
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>Product Name / SKU (wajib)</li>
                        <li>quantity (wajib, angka)</li>
                        <li>unit_cost (opsional untuk OUT, angka)</li>
                        <li>notes (opsional)</li>
                    </ul>
                </div>
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showImportModal = false)}
            >Batal</Button
        >
        <Button
            variant="primary"
            onclick={(e) => submitImport(e as any)}
            icon="fa-solid fa-upload">Upload</Button
        >
    {/snippet}
</Modal>

<Dialog
    bind:isOpen={showAdvanceDialog}
    type="warning"
    title="Konfirmasi Perpindahan Status"
    message={`Status akan diubah dari "${selectedDoc?.status_label ?? statusLabel(selectedDoc?.status ?? "")}" menjadi "${getNextStatus(selectedDoc?.status ?? null)?.label ?? "-"}". Lanjutkan?`}
    confirmText="Lanjutkan"
    cancelText="Batal"
    showCancel={true}
    onConfirm={async () => {
        if (!selectedDoc) return;
        router.post(
            `/stock-documents/${selectedDoc.id}/advance`,
            {},
            { preserveScroll: true, preserveState: true },
        );
    }}
    onClose={() => {
        showAdvanceDialog = false;
        selectedDoc = null;
    }}
/>

<Modal
    bind:isOpen={showRejectModal}
    title="Tolak Dokumen Stok oleh HO"
    size="md"
>
    {#snippet children()}
        <form
            id="reject-stock-document-form"
            onsubmit={(e) => {
                e.preventDefault();
                if (!selectedDoc) return;
                router.put(
                    `/stock-documents/${selectedDoc.id}/reject/ho`,
                    { reason: String(rejectReason) },
                    { preserveScroll: true, preserveState: true },
                );
                showRejectModal = false;
                rejectReason = "";
                selectedDoc = null;
            }}
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Alasan penolakan wajib diisi untuk catatan audit.
                </p>
                <TextArea
                    id="reject-reason"
                    name="reason"
                    label="Alasan Penolakan"
                    bind:value={rejectReason}
                    placeholder="Tuliskan alasan penolakan..."
                    required
                    rows={5}
                />
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showRejectModal = false)}
            >Batal</Button
        >
        <Button
            variant="danger"
            type="submit"
            form="reject-stock-document-form"
            icon="fa-solid fa-xmark">Tolak</Button
        >
    {/snippet}
</Modal>
