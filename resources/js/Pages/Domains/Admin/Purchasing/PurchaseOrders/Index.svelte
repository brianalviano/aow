<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import { isHighestRole } from "@/Lib/Admin/Utils/roles";

    type Warehouse = { id: string; name: string };
    type StatusItem = { value: string; label: string };

    type PurchaseOrder = {
        id: string;
        number: string;
        warehouse: Warehouse | { id: string | null; name: string | null };
        supplier: {
            id: string | null;
            name: string | null;
            phone: string | null;
        };
        order_date: string | null;
        status: string | null;
        status_label: string | null;
        grand_total: number;
        has_delivery?: boolean;
        current_receiving_sdo_id?: string | null;
    };

    let purchaseOrders = $derived(
        $page.props.purchase_orders as PurchaseOrder[],
    );
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
        status: string;
        warehouse_id: string;
        order_date_from: string;
        order_date_to: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        status: ($page.props.filters as { status?: string })?.status ?? "",
        warehouse_id:
            ($page.props.filters as { warehouse_id?: string })?.warehouse_id ??
            "",
        order_date_from:
            ($page.props.filters as { order_date_from?: string })
                ?.order_date_from ?? "",
        order_date_to:
            ($page.props.filters as { order_date_to?: string })
                ?.order_date_to ?? "",
    });

    let statusOptions = $derived(
        ($page.props.statusOptions as StatusItem[]) ?? [],
    );
    let statusCounters = $derived(
        ($page.props.statusCounters as Record<string, number>) ?? {},
    );
    let warehouses = $derived(($page.props.warehouses as Warehouse[]) ?? []);
    let warehouseOptions = $derived([
        { value: "", label: "Semua Gudang" },
        ...warehouses.map((w) => ({ value: w.id, label: w.name })),
    ]);
    type BadgeVariant =
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple";
    type TabItemLocal = {
        id: string;
        label: string;
        badge?: number | string;
        badgeVariant?: BadgeVariant;
    };
    function toBadgeVariant(statusValue: string): BadgeVariant {
        const v = getPOStatusVariant(statusValue);
        return v === "white" ? "secondary" : (v as BadgeVariant);
    }
    let statusTabs = $derived<TabItemLocal[]>([
        {
            id: "",
            label: "Semua",
            badge: statusCounters[""] ?? 0,
            badgeVariant: "secondary",
        },
        ...statusOptions.map((s) => ({
            id: s.value,
            label: s.label,
            badge: statusCounters[s.value] ?? 0,
            badgeVariant: toBadgeVariant(s.value),
        })),
    ]);

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedPO = $state<PurchaseOrder | null>(null);
    let showAdvanceDialog = $state(false);

    function getNextStatus(
        current: string | null,
    ): { value: string; label: string } | null {
        const c = String(current ?? "");
        switch (c) {
            case "draft":
                return { value: "pending_ho_approval", label: "Ajukan ke HO" };
            case "pending_ho_approval":
                return isHighestRole($page.props.auth?.user?.role)
                    ? { value: "head_office_approved", label: "Setujui HO" }
                    : null;
            case "head_office_approved":
                return {
                    value: "pending_supplier_approval",
                    label: "Kirim ke Pemasok",
                };
            case "pending_supplier_approval":
                return {
                    value: "supplier_confirmed",
                    label: "Konfirmasi Pemasok",
                };
            default:
                return null;
        }
    }

    function advanceStatus(po: PurchaseOrder) {
        selectedPO = po;
        showAdvanceDialog = true;
    }

    let showRejectModal = $state(false);
    let rejectReason = $state("");
    let rejectTarget: "ho" | "supplier" | null = $state(null);
    let showCreateDeliveryModal = $state(false);
    let createDeliveryNumber = $state("");
    let createDeliveryDate = $state("");
    let createDeliveryNotes = $state("");
    let createDeliveryLoading = $state(false);

    function canRejectHo(status: string | null): boolean {
        return (
            isHighestRole($page.props.auth?.user?.role) &&
            String(status ?? "") === "pending_ho_approval"
        );
    }
    function canRejectSupplier(status: string | null): boolean {
        return String(status ?? "") === "pending_supplier_approval";
    }
    function canReceive(status: string | null): boolean {
        const s = String(status ?? "");
        return s === "in_delivery" || s === "partially_delivered";
    }
    function canCreateDelivery(
        status: string | null,
        hasDelivery?: boolean,
    ): boolean {
        const s = String(status ?? "");
        if (hasDelivery) return false;
        return s === "supplier_confirmed";
    }
    function canDelete(status: string | null): boolean {
        const s = String(status ?? "");
        return (
            s === "draft" ||
            s === "rejected_by_ho" ||
            s === "rejected_by_supplier"
        );
    }
    function openCreateDeliveryModal(po: PurchaseOrder) {
        selectedPO = po;
        createDeliveryNumber = "";
        createDeliveryDate =
            new Date().toISOString().split("T")[0] ||
            ($page.props.default_delivery_date as any) ||
            "";
        createDeliveryNotes = "";
        showCreateDeliveryModal = true;
    }
    function submitCreateDelivery(e: SubmitEvent) {
        e.preventDefault();
        if (!selectedPO) return;
        createDeliveryLoading = true;
        router.post(
            `/purchase-orders/${selectedPO.id}/deliveries`,
            {
                delivery_date: String(createDeliveryDate),
                number: String(createDeliveryNumber),
                notes: String(createDeliveryNotes || ""),
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => {
                    createDeliveryLoading = true;
                },
                onError: () => {
                    createDeliveryLoading = false;
                },
                onSuccess: () => {
                    createDeliveryLoading = false;
                    showCreateDeliveryModal = false;
                    selectedPO = null;
                    createDeliveryNumber = "";
                    createDeliveryDate = "";
                    createDeliveryNotes = "";
                },
            },
        );
    }
    function canEdit(status: string | null): boolean {
        const s = String(status ?? "");
        return (
            s === "draft" ||
            s === "rejected_by_ho" ||
            s === "rejected_by_supplier"
        );
    }
    function openRejectModal(po: PurchaseOrder) {
        selectedPO = po;
        rejectReason = "";
        if (canRejectHo(po.status)) {
            rejectTarget = "ho";
        } else if (canRejectSupplier(po.status)) {
            rejectTarget = "supplier";
        } else {
            rejectTarget = null;
        }
        if (rejectTarget) {
            showRejectModal = true;
        }
    }
    function submitReject(e: SubmitEvent) {
        e.preventDefault();
        if (!selectedPO || !rejectTarget) return;
        const url =
            rejectTarget === "ho"
                ? `/purchase-orders/${selectedPO.id}/reject/ho`
                : `/purchase-orders/${selectedPO.id}/reject/supplier`;
        router.put(
            url,
            { reason: rejectReason },
            { preserveScroll: true, preserveState: true },
        );
        showRejectModal = false;
        rejectReason = "";
        selectedPO = null;
        rejectTarget = null;
    }

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.order_date_from)
            params.set("order_date_from", filters.order_date_from);
        if (filters.order_date_to)
            params.set("order_date_to", filters.order_date_to);
        router.get(
            "/purchase-orders?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.status = "";
        filters.warehouse_id = "";
        filters.order_date_from = "";
        filters.order_date_to = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.order_date_from)
            params.set("order_date_from", filters.order_date_from);
        if (filters.order_date_to)
            params.set("order_date_to", filters.order_date_to);
        params.set("page", String(pageNum));
        router.get(
            "/purchase-orders?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(po: PurchaseOrder) {
        selectedPO = po;
        showDeleteDialog = true;
    }

    function getPOStatusVariant(
        status: string | null,
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
        const s = String(status ?? "").toLowerCase();
        if (!s || s === "draft") return "secondary";
        if (s === "pending_ho_approval") return "warning";
        if (s === "head_office_approved") return "info";
        if (s === "rejected_by_ho") return "danger";
        if (s === "pending_supplier_approval") return "warning";
        if (s === "supplier_confirmed") return "success";
        if (s === "rejected_by_supplier") return "danger";
        if (s === "in_delivery") return "info";
        if (s === "partially_delivered") return "purple";
        if (s === "completed") return "success";
        if (s === "canceled") return "danger";
        return "secondary";
    }
</script>

<svelte:head>
    <title>Purchase Orders | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Purchase Orders
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola dokumen Purchase Order
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/purchase-orders/create"
                icon="fa-solid fa-plus">Buat PO</Button
            >
        </div>
    </header>
    <div>
        <Tab
            tabs={statusTabs}
            activeTab={filters.status}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.status = tabId;
                applyFilters();
            }}
        />
    </div>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <TextInput
                id="q"
                name="q"
                label="Cari PO"
                placeholder="Nomor PO, nama pemasok, no faktur pemasok..."
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
                    id="order_date_from"
                    name="order_date_from"
                    label="Order dari"
                    bind:value={filters.order_date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="order_date_to"
                    name="order_date_to"
                    label="Order sampai"
                    bind:value={filters.order_date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Purchase Order" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Pemasok</th>
                            <th>Gudang</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Grand Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if purchaseOrders?.length}
                            {#each purchaseOrders as po}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {po.number}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {po.supplier?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {po.warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(po.order_date)}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getPOStatusVariant(
                                                po.status,
                                            )}
                                            title={po.status_label ??
                                                po.status ??
                                                "-"}
                                        >
                                            {#snippet children()}
                                                {po.status_label ??
                                                    po.status ??
                                                    "-"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(po.grand_total)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/purchase-orders/${po.id}`}
                                                >Detail</Button
                                            >
                                            {#if canReceive(po.status)}
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fa-solid fa-box-open"
                                                    href={po.current_receiving_sdo_id
                                                        ? `/goods-receipts/${po.current_receiving_sdo_id}/create`
                                                        : `/goods-receipts`}
                                                    >Penerimaan Barang</Button
                                                >
                                            {/if}
                                            {#if canCreateDelivery(po.status, po.has_delivery)}
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fa-solid fa-truck-fast"
                                                    onclick={() =>
                                                        openCreateDeliveryModal(
                                                            po,
                                                        )}
                                                    >Buat Pengiriman</Button
                                                >
                                            {/if}
                                            {#if canEdit(po.status)}
                                                <Button
                                                    variant="warning"
                                                    size="sm"
                                                    icon="fa-solid fa-edit"
                                                    href={`/purchase-orders/${po.id}/edit`}
                                                    >Edit</Button
                                                >
                                            {/if}
                                            {#if getNextStatus(po.status)}
                                                <Button
                                                    variant="success"
                                                    size="sm"
                                                    icon="fa-solid fa-forward"
                                                    onclick={() =>
                                                        advanceStatus(po)}
                                                    >Lanjut: {getNextStatus(
                                                        po.status,
                                                    )?.label}</Button
                                                >
                                            {/if}
                                            {#if canRejectHo(po.status)}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-xmark"
                                                    onclick={() =>
                                                        openRejectModal(po)}
                                                    >Tolak HO</Button
                                                >
                                            {/if}
                                            {#if canRejectSupplier(po.status)}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-xmark"
                                                    onclick={() =>
                                                        openRejectModal(po)}
                                                    >Tolak Pemasok</Button
                                                >
                                            {/if}
                                            {#if canDelete(po.status)}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-trash"
                                                    onclick={() =>
                                                        openDeleteDialog(po)}
                                                    >Hapus</Button
                                                >
                                            {/if}
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

<Dialog
    bind:isOpen={showAdvanceDialog}
    type="warning"
    title="Konfirmasi Perpindahan Status"
    message={`Status akan diubah dari "${selectedPO?.status_label ?? selectedPO?.status ?? "-"}" menjadi "${getNextStatus(selectedPO?.status ?? null)?.label ?? "-"}". Lanjutkan?`}
    confirmText="Lanjutkan"
    cancelText="Batal"
    showCancel={true}
    onConfirm={async () => {
        if (!selectedPO) return;
        const nextVal = getNextStatus(selectedPO.status)?.value;
        if (nextVal === "in_delivery") {
            showAdvanceDialog = false;
            openCreateDeliveryModal(selectedPO);
            return;
        }
        router.post(
            `/purchase-orders/${selectedPO.id}/advance`,
            {},
            { preserveScroll: true, preserveState: true },
        );
    }}
    onClose={() => {
        showAdvanceDialog = false;
        if (!showCreateDeliveryModal) {
            selectedPO = null;
        }
    }}
/>

<Modal
    bind:isOpen={showRejectModal}
    title={rejectTarget === "ho" ? "Tolak PO oleh HO" : "Tolak PO oleh Pemasok"}
    size="md"
>
    {#snippet children()}
        <form id="reject-po-form" onsubmit={submitReject}>
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
            form="reject-po-form"
            icon="fa-solid fa-xmark">Tolak</Button
        >
    {/snippet}
</Modal>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Purchase Order"
    message={`Apakah Anda yakin ingin menghapus PO ${selectedPO?.number ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedPO) {
            await router.delete(`/purchase-orders/${selectedPO.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedPO = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedPO = null;
    }}
    onClose={() => {
        selectedPO = null;
        deleteProcessing = false;
    }}
/>

<Modal
    bind:isOpen={showCreateDeliveryModal}
    title="Buat Pengiriman Supplier"
    size="md"
>
    {#snippet children()}
        <form id="create-delivery-form" onsubmit={submitCreateDelivery}>
            <div class="space-y-4">
                <TextInput
                    id="create-delivery-number"
                    name="number"
                    label="Nomor Dokumen"
                    placeholder="SJ/202602/0001"
                    bind:value={createDeliveryNumber}
                    required
                />
                <DateInput
                    id="create-delivery-date"
                    name="delivery_date"
                    label="Tanggal Pengiriman"
                    bind:value={createDeliveryDate}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                    required
                />
                <TextArea
                    id="create-delivery-notes"
                    name="notes"
                    label="Catatan"
                    placeholder="Catatan tambahan (opsional)"
                    bind:value={createDeliveryNotes}
                />
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button
            variant="secondary"
            onclick={() => {
                showCreateDeliveryModal = false;
                selectedPO = null;
            }}>Batal</Button
        >
        <Button
            variant="success"
            type="submit"
            form="create-delivery-form"
            loading={createDeliveryLoading}
            icon="fa-solid fa-truck-fast">Simpan Pengiriman</Button
        >
    {/snippet}
</Modal>
