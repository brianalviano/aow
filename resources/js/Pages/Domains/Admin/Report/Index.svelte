<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    // ─── Types ────────────────────────────────────────────────────────────────

    interface DropPointOption {
        value: string;
        label: string;
    }

    interface OrderRow {
        id: string;
        number: string;
        created_at: string;
        order_status: string;
        payment_status: string;
        total_amount: number;
        customer?: { name: string; email: string } | null;
        drop_point?: { name: string } | null;
    }

    interface ProductRow {
        product_id: string;
        product_name: string;
        category_name: string | null;
        total_sold: number;
        total_revenue: number;
    }

    interface SalesSummary {
        total_orders: number;
        total_revenue: number;
        total_cancelled: number;
        total_pending: number;
    }

    interface ProductSummary {
        total_sold: number;
        total_revenue: number;
    }

    interface PaginatedOrders {
        data: OrderRow[];
        meta?: {
            total: number;
            per_page: number;
            current_page: number;
            last_page: number;
        };
    }

    interface PaginatedProducts {
        data: ProductRow[];
        meta?: {
            total: number;
            per_page: number;
            current_page: number;
            last_page: number;
        };
    }

    // ─── Props from Inertia ───────────────────────────────────────────────────

    let reportType = $derived(($page.props.type as string) ?? "orders");
    let filters = $derived(
        ($page.props.filters as {
            date_from?: string | null;
            date_to?: string | null;
            drop_point_id?: string | null;
        }) ?? {},
    );
    let report = $derived(
        $page.props.report as {
            summary: SalesSummary | ProductSummary;
            orders?: PaginatedOrders;
            products?: PaginatedProducts;
        },
    );
    let dropPoints = $derived(
        ($page.props.drop_points as DropPointOption[]) ?? [],
    );

    // ─── Local filter state ───────────────────────────────────────────────────

    let selectedType = $state(untrack(() => reportType));
    let dateFrom = $state(untrack(() => filters.date_from ?? ""));
    let dateTo = $state(untrack(() => filters.date_to ?? ""));
    let dropPointId = $state(untrack(() => filters.drop_point_id ?? ""));

    // ─── Derived data ─────────────────────────────────────────────────────────

    let salesSummary = $derived(
        reportType === "orders" ? (report?.summary as SalesSummary) : null,
    );
    let productSummary = $derived(
        reportType === "products" ? (report?.summary as ProductSummary) : null,
    );

    let ordersMeta = $derived(
        report?.orders?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );
    let productsMeta = $derived(
        report?.products?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let orderRows = $derived(report?.orders?.data ?? []);
    let productRows = $derived(report?.products?.data ?? []);

    // ─── Options ──────────────────────────────────────────────────────────────

    const typeOptions = [
        { value: "orders", label: "Laporan Pesanan" },
        { value: "products", label: "Laporan Produk" },
    ];

    // ─── Navigation ───────────────────────────────────────────────────────────

    function buildParams(
        extra: Record<string, string | number> = {},
    ): URLSearchParams {
        const p = new URLSearchParams();
        if (selectedType) p.set("type", selectedType);
        if (dateFrom) p.set("date_from", dateFrom);
        if (dateTo) p.set("date_to", dateTo);
        if (dropPointId) p.set("drop_point_id", dropPointId);
        Object.entries(extra).forEach(([k, v]) => p.set(k, String(v)));
        return p;
    }

    const applyFilters = debounce(() => {
        router.get(
            "/admin/reports?" + buildParams().toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }, 400);

    function goToPage(pageNumber: number) {
        router.get(
            "/admin/reports?" + buildParams({ page: pageNumber }).toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        dateFrom = "";
        dateTo = "";
        dropPointId = "";
        applyFilters();
    }

    // ─── Export ───────────────────────────────────────────────────────────────

    function exportUrl(format: "pdf" | "excel"): string {
        const base =
            format === "pdf"
                ? "/admin/reports/export-pdf"
                : "/admin/reports/export-excel";
        return base + "?" + buildParams().toString();
    }

    // ─── Formatting ───────────────────────────────────────────────────────────

    function formatCurrency(amount: number): string {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function formatNumber(n: number): string {
        return new Intl.NumberFormat("id-ID").format(n);
    }

    type BadgeVariant =
        | "dark"
        | "light"
        | "success"
        | "warning"
        | "info"
        | "primary"
        | "danger"
        | "white"
        | "secondary"
        | "purple";

    function getOrderStatusBadge(status: string): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Menunggu" };
            case "confirmed":
                return { variant: "info", label: "Dikonfirmasi" };
            case "shipped":
                return { variant: "primary", label: "Dikirim" };
            case "delivered":
                return { variant: "success", label: "Selesai" };
            case "cancelled":
                return { variant: "danger", label: "Dibatalkan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    function getPaymentBadge(status: string): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Belum Bayar" };
            case "paid":
                return { variant: "success", label: "Lunas" };
            case "cash":
                return { variant: "secondary", label: "Bayar di Tempat" };
            case "failed":
                return { variant: "danger", label: "Gagal" };
            case "refunded":
                return { variant: "info", label: "Dikembalikan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    // ─── Reactive effects ─────────────────────────────────────────────────────

    let mounted = $state(false);

    $effect(() => {
        if (!mounted) {
            mounted = true;
            return;
        }
        void dateFrom;
        void dateTo;
        void dropPointId;
        void selectedType;
        applyFilters();
    });
</script>

<svelte:head>
    <title>Laporan | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <!-- Page header -->
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Laporan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Ringkasan data penjualan dan produk
            </p>
        </div>

        <!-- Export buttons -->
        <div class="flex gap-2">
            <a
                href={exportUrl("excel")}
                id="btn-export-excel"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white
                       hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2
                       transition-colors duration-200"
            >
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>
            <a
                href={exportUrl("pdf")}
                id="btn-export-pdf"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white
                       hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2
                       transition-colors duration-200"
            >
                <i class="fa-solid fa-file-pdf"></i>
                Export PDF
            </a>
        </div>
    </header>

    <!-- Filters -->
    <Card>
        {#snippet children()}
            <div class="flex flex-wrap gap-3 items-end">
                <!-- Report type -->
                <div class="w-52">
                    <Select
                        label="Jenis Laporan"
                        id="type_filter"
                        options={typeOptions}
                        bind:value={selectedType}
                    />
                </div>

                <!-- Date from -->
                <div class="flex-1 min-w-44">
                    <DateInput
                        id="date_from"
                        label="Dari Tanggal"
                        bind:value={dateFrom}
                    />
                </div>

                <!-- Date to -->
                <div class="flex-1 min-w-44">
                    <DateInput
                        id="date_to"
                        label="Sampai Tanggal"
                        bind:value={dateTo}
                        {...dateFrom ? { min: dateFrom } : {}}
                    />
                </div>

                <!-- Drop point -->
                <div class="w-52">
                    <Select
                        id="drop_point_filter"
                        label="Drop Point"
                        options={dropPoints}
                        bind:value={dropPointId}
                    />
                </div>

                <!-- Reset -->
                {#if dateFrom || dateTo || dropPointId}
                    <Button
                        variant="secondary"
                        size="sm"
                        onclick={resetFilters}
                        icon="fa-solid fa-rotate-left"
                    >
                        Reset
                    </Button>
                {/if}
            </div>
        {/snippet}
    </Card>

    <!-- ─── ORDERS REPORT ────────────────────────────────────────────────────── -->
    {#if reportType === "orders" && salesSummary}
        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatCard
                label="Total Pesanan"
                value={formatNumber(salesSummary.total_orders)}
                icon="fa-solid fa-bag-shopping"
                color="indigo"
            />
            <StatCard
                label="Pendapatan"
                value={formatCurrency(salesSummary.total_revenue)}
                icon="fa-solid fa-circle-dollar-to-slot"
                color="green"
            />
            <StatCard
                label="Dalam Proses"
                value={formatNumber(salesSummary.total_pending)}
                icon="fa-solid fa-clock"
                color="amber"
            />
            <StatCard
                label="Dibatalkan"
                value={formatNumber(salesSummary.total_cancelled)}
                icon="fa-solid fa-circle-xmark"
                color="red"
            />
        </div>

        <!-- Orders table -->
        <Card title="Detail Pesanan" bodyWithoutPadding={true}>
            {#snippet children()}
                <div class="overflow-x-auto">
                    <table class="custom-table min-w-full">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Drop Point</th>
                                <th>Total</th>
                                <th>Status Pesanan</th>
                                <th>Status Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if orderRows.length > 0}
                                {#each orderRows as order}
                                    {@const osBadge = getOrderStatusBadge(
                                        order.order_status,
                                    )}
                                    {@const pyBadge = getPaymentBadge(
                                        order.payment_status,
                                    )}
                                    <tr>
                                        <td
                                            class="font-medium text-gray-900 dark:text-white"
                                        >
                                            {order.number}
                                        </td>
                                        <td
                                            class="text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            {new Date(
                                                order.created_at,
                                            ).toLocaleDateString("id-ID")}
                                        </td>
                                        <td>
                                            <div
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {order.customer?.name ?? "-"}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {order.customer?.email ?? ""}
                                            </div>
                                        </td>
                                        <td
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {order.drop_point?.name ?? "-"}
                                        </td>
                                        <td
                                            class="font-semibold text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(order.total_amount)}
                                        </td>
                                        <td>
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant={osBadge.variant}
                                            >
                                                {#snippet children()}{osBadge.label}{/snippet}
                                            </Badge>
                                        </td>
                                        <td>
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant={pyBadge.variant}
                                            >
                                                {#snippet children()}{pyBadge.label}{/snippet}
                                            </Badge>
                                        </td>
                                    </tr>
                                {/each}
                            {:else}
                                <tr>
                                    <td
                                        colspan="7"
                                        class="py-10 text-center text-sm text-gray-400 dark:text-gray-500"
                                    >
                                        <i
                                            class="fa-solid fa-inbox text-3xl mb-2 block opacity-40"
                                        ></i>
                                        Tidak ada data untuk filter yang dipilih
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            {/snippet}

            {#snippet footer()}
                <Pagination
                    currentPage={ordersMeta.current_page}
                    totalPages={ordersMeta.last_page}
                    totalItems={ordersMeta.total}
                    itemsPerPage={ordersMeta.per_page}
                    onPageChange={goToPage}
                    showItemsPerPage={false}
                />
            {/snippet}
        </Card>
    {/if}

    <!-- ─── PRODUCTS REPORT ──────────────────────────────────────────────────── -->
    {#if reportType === "products" && productSummary}
        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2">
            <StatCard
                label="Total Terjual (qty)"
                value={formatNumber(productSummary.total_sold)}
                icon="fa-solid fa-box-open"
                color="blue"
            />
            <StatCard
                label="Total Pendapatan"
                value={formatCurrency(productSummary.total_revenue)}
                icon="fa-solid fa-chart-line"
                color="green"
            />
        </div>

        <!-- Products table -->
        <Card title="Produk Terlaris" bodyWithoutPadding={true}>
            {#snippet children()}
                <div class="overflow-x-auto">
                    <table class="custom-table min-w-full">
                        <thead>
                            <tr>
                                <th class="w-10">#</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th class="text-right">Terjual (qty)</th>
                                <th class="text-right">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if productRows.length > 0}
                                {#each productRows as product, i}
                                    <tr>
                                        <td
                                            class="text-center font-bold text-gray-400 dark:text-gray-500 text-sm"
                                        >
                                            {(productsMeta.current_page - 1) *
                                                productsMeta.per_page +
                                                i +
                                                1}
                                        </td>
                                        <td
                                            class="font-medium text-gray-900 dark:text-white"
                                        >
                                            {product.product_name}
                                        </td>
                                        <td
                                            class="text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            {product.category_name ?? "-"}
                                        </td>
                                        <td class="text-right">
                                            <span
                                                class="inline-flex items-center gap-1 font-semibold text-indigo-600 dark:text-indigo-400"
                                            >
                                                {formatNumber(
                                                    product.total_sold,
                                                )}
                                                <span
                                                    class="text-xs font-normal text-gray-400"
                                                    >pcs</span
                                                >
                                            </span>
                                        </td>
                                        <td
                                            class="text-right font-semibold text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(
                                                product.total_revenue,
                                            )}
                                        </td>
                                    </tr>
                                {/each}
                            {:else}
                                <tr>
                                    <td
                                        colspan="5"
                                        class="py-10 text-center text-sm text-gray-400 dark:text-gray-500"
                                    >
                                        <i
                                            class="fa-solid fa-inbox text-3xl mb-2 block opacity-40"
                                        ></i>
                                        Tidak ada data untuk filter yang dipilih
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            {/snippet}

            {#snippet footer()}
                <Pagination
                    currentPage={productsMeta.current_page}
                    totalPages={productsMeta.last_page}
                    totalItems={productsMeta.total}
                    itemsPerPage={productsMeta.per_page}
                    onPageChange={goToPage}
                    showItemsPerPage={false}
                />
            {/snippet}
        </Card>
    {/if}
</section>
