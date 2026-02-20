<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";

    type Warehouse = {
        name: string | null;
        address?: string | null;
        phone?: string | null;
    };
    type Supplier = { name: string | null };
    type PurchaseOrderInfo = {
        id: string;
        number: string | null;
        order_date: string | null;
        supplier: Supplier;
        warehouse: Warehouse;
        status: string | null;
        status_label: string | null;
    };
    type DeliveryItem = {
        id: string;
        number: string;
        delivery_date: string | null;
        status: string | null;
        status_label: string | null;
        doc_type: string;
        source_id: string;
        purchase_order: PurchaseOrderInfo;
    };

    let deliveries = $derived(($page.props.deliveries as DeliveryItem[]) ?? []);
    let meta = $derived(
        $page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );
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
    type LocalFilters = { q: string; doc_type: string; status: string };
    let filters = $state<LocalFilters>({
        q: (($page.props.filters as { q?: string })?.q ?? "") as string,
        doc_type: (($page.props.filters as { doc_type?: string })?.doc_type ??
            "") as string,
        status: (($page.props.filters as { status?: string })?.status ??
            "") as string,
    });
    type InDeliveryCounts = { po: number; pr: number; total: number };
    let inDeliveryCounts = $derived<InDeliveryCounts>(
        ($page.props.counts?.in_delivery as InDeliveryCounts) ?? {
            po: 0,
            pr: 0,
            total: 0,
        },
    );
    let typeTabs = $derived<TabItemLocal[]>([
        {
            id: "",
            label: "Semua",
            badge: inDeliveryCounts.total,
            badgeVariant: "secondary",
        },
        {
            id: "po",
            label: "Purchase Order",
            badge: inDeliveryCounts.po,
            badgeVariant: "info",
        },
        {
            id: "pr",
            label: "Retur Pembelian",
            badge: inDeliveryCounts.pr,
            badgeVariant: "warning",
        },
    ]);
    const statusOptions = $derived([
        { value: "", label: "Semua" },
        { value: "in_delivery", label: "Dalam Pengiriman" },
        { value: "completed", label: "Selesai" },
    ] as Array<{ value: string; label: string }>);

    function applyFilters() {
        router.get(
            "/goods-receipts",
            {
                q: filters.q,
                doc_type: filters.doc_type,
                status: filters.status,
            },
            { preserveState: true, preserveScroll: true },
        );
    }
    function resetFilters() {
        filters.q = "";
        filters.doc_type = "";
        filters.status = "";
        applyFilters();
    }
    function goToPage(pageNum: number) {
        router.get(
            "/goods-receipts",
            {
                q: filters.q,
                doc_type: filters.doc_type,
                status: filters.status,
                page: String(pageNum),
            },
            { preserveState: true, preserveScroll: true },
        );
    }
    function statusVariant(
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
        if (s === "in_delivery") return "info";
        if (s === "completed") return "success";
        if (s === "canceled") return "danger";
        return "secondary";
    }
</script>

<svelte:head>
    <title>Penerimaan Barang | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Penerimaan Barang
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Daftar pengiriman pemasok yang siap diterima
            </p>
        </div>
    </header>
    <div>
        <Tab
            tabs={typeTabs}
            activeTab={filters.doc_type}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.doc_type = tabId;
                applyFilters();
            }}
        />
    </div>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <TextInput
                id="q"
                name="q"
                label="Cari"
                placeholder="Nomor SJ, Nomor PO, Nama Supplier..."
                bind:value={filters.q}
            />
            <Select
                id="status"
                name="status"
                label="Status"
                options={statusOptions}
                bind:value={filters.status}
            />
        </div>
    </Card>

    <Card title="Siap Diterima" bodyWithoutPadding={true}>
        <div class="overflow-x-auto">
            <table class="custom-table min-w-full">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr
                        class="text-sm text-left text-gray-600 dark:text-gray-400"
                    >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >Nomor SJ</th
                        >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >Tgl Kirim</th
                        >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >PO</th
                        >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >Supplier</th
                        >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >Gudang</th
                        >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >Status</th
                        >
                        <th
                            class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                            >Aksi</th
                        >
                    </tr>
                </thead>
                <tbody>
                    {#each deliveries as d}
                        <tr
                            class="border-b border-gray-200 dark:border-gray-700"
                        >
                            <td class="px-4 py-3">
                                <div
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    {d.number}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {#if d.delivery_date}{formatDateDisplay(
                                            d.delivery_date,
                                        )}{:else}-{/if}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span>{d.purchase_order.number ?? "-"}</span
                                    >
                                    <Badge variant="secondary" size="xs">
                                        {#if d.purchase_order.order_date}{formatDateDisplay(
                                                d.purchase_order.order_date,
                                            )}{:else}-{/if}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {d.purchase_order.supplier?.name ?? "-"}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {d.purchase_order.warehouse?.name ?? "-"}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge
                                    variant={statusVariant(d.status)}
                                    size="xs">{d.status_label}</Badge
                                >
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {#if String(d.status ?? "").toLowerCase() === "in_delivery"}
                                    <Button
                                        variant="primary"
                                        size="xs"
                                        icon="fa-solid fa-box-open"
                                        href={`/goods-receipts/${d.id}/create`}
                                        >Terima</Button
                                    >
                                {/if}
                                {#if String(d.status ?? "").toLowerCase() === "completed"}
                                    <Button
                                        variant="secondary"
                                        size="xs"
                                        icon="fa-solid fa-eye"
                                        href={`/goods-receipts/${d.id}/show`}
                                        class="ml-2">Detail</Button
                                    >
                                    <Button
                                        variant="primary"
                                        size="xs"
                                        icon="fa-solid fa-print"
                                        onclick={() => {
                                            const url = `/goods-receipts/${d.id}/print`;
                                            openCenteredWindow(url, {
                                                width: 960,
                                                height: 700,
                                                fallbackWhenBlocked: false,
                                            });
                                        }}
                                        class="ml-2">Cetak</Button
                                    >
                                {/if}
                            </td>
                        </tr>
                    {/each}
                    {#if deliveries.length === 0}
                        <tr>
                            <td
                                class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                colspan="7"
                            >
                                Tidak ada pengiriman pemasok yang siap diterima.
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
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
