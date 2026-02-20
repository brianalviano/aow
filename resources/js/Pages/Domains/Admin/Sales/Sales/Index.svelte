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
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type Warehouse = { id: string; name: string };
    type StatusItem = { value: string; label: string };
    type Customer = {
        id: string | null;
        name: string | null;
        phone: string | null;
    };

    type SalesItemList = {
        id: string;
        receipt_number: string;
        invoice_number: string;
        sale_datetime: string | null;
        warehouse: Warehouse | { id: string | null; name: string | null };
        customer: Customer;
        payment_status: string | null;
        payment_status_label: string | null;
        grand_total: number;
    };

    let sales = $derived($page.props.sales as SalesItemList[]);
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
        payment_status: string;
        warehouse_id: string;
        sale_date_from: string;
        sale_date_to: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        payment_status:
            ($page.props.filters as { payment_status?: string })
                ?.payment_status ?? "",
        warehouse_id:
            ($page.props.filters as { warehouse_id?: string })?.warehouse_id ??
            "",
        sale_date_from:
            ($page.props.filters as { sale_date_from?: string })
                ?.sale_date_from ?? "",
        sale_date_to:
            ($page.props.filters as { sale_date_to?: string })?.sale_date_to ??
            "",
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

    function getStatusVariant(
        status: string | null,
    ): "primary" | "success" | "warning" | "danger" | "info" | "secondary" {
        const s = String(status ?? "").toLowerCase();
        if (!s) return "secondary";
        if (s === "unpaid") return "danger";
        if (s === "partially_paid") return "warning";
        if (s === "paid") return "success";
        return "secondary";
    }

    type TabItemLocal = {
        id: string;
        label: string;
        badge?: number | string;
        badgeVariant?: BadgeVariant;
    };
    let statusTabs = $derived<TabItemLocal[]>([
        {
            id: "",
            label: "Semua",
            badge: statusCounters[""] ?? 0,
            badgeVariant: getStatusVariant(""),
        },
        ...statusOptions.map((s) => ({
            id: s.value,
            label: s.label,
            badge: statusCounters[s.value] ?? 0,
            badgeVariant: getStatusVariant(s.value),
        })),
    ]);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.payment_status)
            params.set("payment_status", filters.payment_status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.sale_date_from)
            params.set("sale_date_from", filters.sale_date_from);
        if (filters.sale_date_to)
            params.set("sale_date_to", filters.sale_date_to);
        router.get(
            "/sales?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.payment_status = "";
        filters.warehouse_id = "";
        filters.sale_date_from = "";
        filters.sale_date_to = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.payment_status)
            params.set("payment_status", filters.payment_status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.sale_date_from)
            params.set("sale_date_from", filters.sale_date_from);
        if (filters.sale_date_to)
            params.set("sale_date_to", filters.sale_date_to);
        params.set("page", String(pageNum));
        router.get(
            "/sales?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Riwayat Penjualan | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Riwayat Penjualan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Daftar transaksi hasil POS
            </p>
        </div>
    </header>

    <div>
        <Tab
            tabs={statusTabs}
            activeTab={filters.payment_status}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.payment_status = tabId;
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
                label="Cari Penjualan"
                placeholder="No struk/faktur, nama customer..."
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
                    id="sale_date_from"
                    name="sale_date_from"
                    label="Tanggal dari"
                    bind:value={filters.sale_date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="sale_date_to"
                    name="sale_date_to"
                    label="Tanggal sampai"
                    bind:value={filters.sale_date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Penjualan" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
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
                                >Customer</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Gudang</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Tanggal</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status Bayar</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Grand Total</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if sales?.length}
                            {#each sales as s}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {s.receipt_number ||
                                                s.invoice_number ||
                                                "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {s.customer?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {s.warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(s.sale_datetime)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getStatusVariant(
                                                s.payment_status,
                                            )}
                                            title={s.payment_status_label ??
                                                s.payment_status ??
                                                "-"}
                                        >
                                            {#snippet children()}
                                                {s.payment_status_label ??
                                                    s.payment_status ??
                                                    "-"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(s.grand_total)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/sales/${s.id}`}
                                                >Detail</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-4 py-6 text-center text-gray-600 dark:text-gray-400"
                                >
                                    Tidak ada data
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">
                <Pagination
                    currentPage={meta.current_page}
                    totalPages={meta.last_page}
                    totalItems={meta.total}
                    itemsPerPage={meta.per_page}
                    onPageChange={goToPage}
                />
            </div>
        {/snippet}
    </Card>
</section>
