<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type Warehouse = { id: string; name: string };
    type StatusItem = { value: string; label: string };
    type PurchaseReturn = {
        id: string;
        number: string;
        purchase_order: { id: string | null; number: string | null };
        supplier: { id: string | null; name: string | null };
        warehouse: { id: string | null; name: string | null };
        return_date: string | null;
        status: string | null;
        status_label: string | null;
        credit_amount: number;
        refund_amount: number;
        current_receiving_sdo_id?: string | null;
    };

    let returns = $derived($page.props.purchase_returns as PurchaseReturn[]);
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
        return_date_from: string;
        return_date_to: string;
    };
    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        status: ($page.props.filters as { status?: string })?.status ?? "",
        warehouse_id:
            ($page.props.filters as { warehouse_id?: string })?.warehouse_id ??
            "",
        return_date_from:
            ($page.props.filters as { return_date_from?: string })
                ?.return_date_from ?? "",
        return_date_to:
            ($page.props.filters as { return_date_to?: string })
                ?.return_date_to ?? "",
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

    function getStatusVariant(status: string | null): BadgeVariant {
        const s = String(status ?? "").toLowerCase();
        if (!s || s === "draft") return "secondary";
        if (s === "completed") return "success";
        if (s === "canceled") return "danger";
        return "secondary";
    }
    function toBadgeVariant(statusValue: string): BadgeVariant {
        return getStatusVariant(statusValue);
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

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.return_date_from)
            params.set("return_date_from", filters.return_date_from);
        if (filters.return_date_to)
            params.set("return_date_to", filters.return_date_to);
        router.get(
            "/purchase-returns?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.status = "";
        filters.warehouse_id = "";
        filters.return_date_from = "";
        filters.return_date_to = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.return_date_from)
            params.set("return_date_from", filters.return_date_from);
        if (filters.return_date_to)
            params.set("return_date_to", filters.return_date_to);
        params.set("page", String(pageNum));
        router.get(
            "/purchase-returns?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
    let selectedReturn = $state<PurchaseReturn | null>(null);
    let showCreateDeliveryModal = $state(false);
    let createDeliveryNumber = $state("");
    let createDeliveryDate = $state("");
    let createDeliveryNotes = $state("");
    let createDeliveryLoading = $state(false);
    function canCreateDelivery(status: string | null): boolean {
        const s = String(status ?? "");
        return s === "draft";
    }
    function canReceive(status: string | null): boolean {
        const s = String(status ?? "");
        return s === "in_delivery" || s === "partially_delivered";
    }
    function openCreateDeliveryModal(r: PurchaseReturn) {
        selectedReturn = r;
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
        if (!selectedReturn) return;
        createDeliveryLoading = true;
        router.post(
            `/purchase-returns/${selectedReturn.id}/deliveries`,
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
                    selectedReturn = null;
                    createDeliveryNumber = "";
                    createDeliveryDate = "";
                    createDeliveryNotes = "";
                },
            },
        );
    }
</script>

<svelte:head>
    <title>Retur Pembelian | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Retur Pembelian
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola dokumen retur pembelian dari pemasok
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/purchase-returns/create"
                icon="fa-solid fa-plus">Buat Retur</Button
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
                label="Cari Retur"
                placeholder="Nomor retur, nomor PO, nama pemasok..."
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
                    id="return_date_from"
                    name="return_date_from"
                    label="Retur dari"
                    bind:value={filters.return_date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="return_date_to"
                    name="return_date_to"
                    label="Retur sampai"
                    bind:value={filters.return_date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Retur Pembelian" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr
                            class="text-sm text-left text-gray-600 dark:text-gray-400"
                        >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Nomor</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >PO</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Pemasok</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Gudang</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Tanggal Retur</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Kredit</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Diskon</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if returns?.length}
                            {#each returns as r}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <a
                                            href={"/purchase-returns/" + r.id}
                                            class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                            >{r.number}</a
                                        >
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {r.purchase_order?.number || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {r.supplier?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {r.warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(r.return_date)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getStatusVariant(r.status)}
                                            title={r.status_label ??
                                                r.status ??
                                                "-"}
                                        >
                                            {#snippet children()}
                                                {r.status_label ??
                                                    r.status ??
                                                    "-"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(r.credit_amount)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(r.refund_amount)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/purchase-returns/${r.id}`}
                                                >Detail</Button
                                            >
                                            {#if canReceive(r.status)}
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fa-solid fa-box-open"
                                                    href={r.current_receiving_sdo_id
                                                        ? `/goods-receipts/${r.current_receiving_sdo_id}/create`
                                                        : `/goods-receipts`}
                                                    >Penerimaan Barang</Button
                                                >
                                            {/if}
                                            {#if canCreateDelivery(r.status)}
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fa-solid fa-truck-fast"
                                                    onclick={() =>
                                                        openCreateDeliveryModal(
                                                            r,
                                                        )}
                                                    >Buat Pengiriman</Button
                                                >
                                            {/if}
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
                                    Tidak ada data retur.
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
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        {/snippet}
    </Card>
</section>

{#if showCreateDeliveryModal}
    <Modal
        title="Buat Supplier Delivery Order (Retur)"
        bind:isOpen={showCreateDeliveryModal}
        onClose={() => (showCreateDeliveryModal = false)}
    >
        {#snippet children()}
            <form onsubmit={submitCreateDelivery} class="space-y-4">
                <TextInput
                    id="number"
                    name="number"
                    label="Nomor Pengiriman"
                    placeholder="SJ/202601/0001"
                    bind:value={createDeliveryNumber}
                    required={true}
                />
                <DateInput
                    id="delivery_date"
                    name="delivery_date"
                    label="Tanggal Pengiriman"
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                    bind:value={createDeliveryDate}
                    required={true}
                />
                <TextArea
                    id="notes"
                    name="notes"
                    label="Catatan"
                    placeholder="Catatan pengiriman"
                    bind:value={createDeliveryNotes}
                />
                <div class="flex gap-2 justify-end">
                    <Button
                        variant="secondary"
                        onclick={() => (showCreateDeliveryModal = false)}
                        type="button">Batal</Button
                    >
                    <Button
                        variant="primary"
                        type="submit"
                        disabled={createDeliveryLoading}
                        icon="fa-solid fa-truck-fast"
                        >{createDeliveryLoading
                            ? "Menyimpan..."
                            : "Buat Pengiriman"}</Button
                    >
                </div>
            </form>
        {/snippet}
    </Modal>
{/if}
