<script lang="ts">
    import { page, router, Link } from "@inertiajs/svelte";
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

    interface ProductData {
        id: string;
        name: string;
        price: number;
        image_url?: string;
        is_active: boolean;
        product_category?: { name: string };
        real_sales?: number;
        total_sales?: number;
    }

    interface Summary {
        total_orders: number;
        total_quantity: number;
        total_revenue: number;
    }

    interface ChefBreakdown {
        chef_id: string | null;
        chef_name: string;
        business_name?: string | null;
        total_orders: number;
        total_quantity: number;
        total_revenue: number;
    }

    interface TransactionOption {
        id: string;
        productOption?: { name: string };
        productOptionItem?: { name: string };
    }

    interface TransactionItem {
        id: string;
        quantity: number;
        price: number;
        subtotal: number;
        created_at: string;
        order?: {
            id: string;
            number: string;
            order_status: string;
            payment_status: string;
            delivery_date?: string | null;
            created_at: string;
            customer?: { name: string; email: string } | null;
            drop_point?: { name: string } | null;
        } | null;
        chef?: { id: string; name: string; business_name?: string } | null;
        options?: TransactionOption[];
    }

    interface PaginatedItems {
        data: TransactionItem[];
        meta?: {
            total: number;
            per_page: number;
            current_page: number;
            last_page: number;
        };
    }

    interface SelectOption {
        value: string;
        label: string;
    }

    // Props from Inertia
    let product = $derived(($page.props.product as { data: ProductData })?.data ?? ($page.props.product as ProductData));
    let summary = $derived(($page.props.summary as Summary) ?? { total_orders: 0, total_quantity: 0, total_revenue: 0 });
    let chefBreakdown = $derived(($page.props.chefBreakdown as ChefBreakdown[]) ?? []);
    let itemsData = $derived(($page.props.items as PaginatedItems) ?? { data: [] });
    let filters = $derived(($page.props.filters as Record<string, string | null>) ?? {});
    let chefsList = $derived(($page.props.chefs as { id: string; name: string }[]) ?? []);
    let dropPointsList = $derived(($page.props.dropPoints as { id: string; name: string }[]) ?? []);

    // Filter local state
    let dateFrom = $state(untrack(() => filters.date_from ?? ""));
    let dateTo = $state(untrack(() => filters.date_to ?? ""));
    let chefId = $state(untrack(() => filters.chef_id ?? ""));
    let dropPointId = $state(untrack(() => filters.drop_point_id ?? ""));
    let statusFilter = $state(untrack(() => filters.status ?? ""));

    let meta = $derived(
        itemsData?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let rows = $derived(itemsData?.data ?? []);

    // Options dropdowns
    let chefOptions = $derived<SelectOption[]>([
        { value: "", label: "Semua Dapur / Chef" },
        ...chefsList.map((c) => ({ value: c.id, label: c.name })),
    ]);

    let dropPointOptions = $derived<SelectOption[]>([
        { value: "", label: "Semua Drop Point" },
        ...dropPointsList.map((dp) => ({ value: dp.id, label: dp.name })),
    ]);

    const statusOptions: SelectOption[] = [
        { value: "", label: "Semua Status Pesanan" },
        { value: "pending", label: "Menunggu" },
        { value: "confirmed", label: "Dikonfirmasi" },
        { value: "shipped", label: "Dikirim ke Pickup" },
        { value: "at_pickup_point", label: "Di Pickup Point" },
        { value: "on_delivery", label: "Sedang Dikirim" },
        { value: "arrived", label: "Tiba di Tujuan" },
        { value: "delivered", label: "Selesai" },
        { value: "cancelled", label: "Dibatalkan" },
    ];

    function buildParams(extra: Record<string, string | number> = {}): URLSearchParams {
        const p = new URLSearchParams();
        if (dateFrom) p.set("date_from", dateFrom);
        if (dateTo) p.set("date_to", dateTo);
        if (chefId) p.set("chef_id", chefId);
        if (dropPointId) p.set("drop_point_id", dropPointId);
        if (statusFilter) p.set("status", statusFilter);
        Object.entries(extra).forEach(([k, v]) => p.set(k, String(v)));
        return p;
    }

    const applyFilters = debounce(() => {
        router.get(
            `/admin/products/${product.id}/transactions?` + buildParams().toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }, 400);

    function goToPage(pageNumber: number) {
        router.get(
            `/admin/products/${product.id}/transactions?` + buildParams({ page: pageNumber }).toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        dateFrom = "";
        dateTo = "";
        chefId = "";
        dropPointId = "";
        statusFilter = "";
        applyFilters();
    }

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
                return { variant: "primary", label: "Dikirim ke Pickup" };
            case "at_pickup_point":
                return { variant: "purple", label: "Di Pickup Point" };
            case "on_delivery":
                return { variant: "primary", label: "Sedang Dikirim" };
            case "arrived":
                return { variant: "info", label: "Tiba di Tujuan" };
            case "delivered":
                return { variant: "success", label: "Selesai" };
            case "cancelled":
                return { variant: "danger", label: "Dibatalkan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    function formatOptions(options?: TransactionOption[]): string {
        if (!options || options.length === 0) return "-";
        return options
            .map((opt) => {
                if (opt.productOption && opt.productOptionItem) {
                    return `${opt.productOption.name}: ${opt.productOptionItem.name}`;
                }
                return "";
            })
            .filter(Boolean)
            .join(", ");
    }

    let mounted = $state(false);

    $effect(() => {
        if (!mounted) {
            mounted = true;
            return;
        }
        void dateFrom;
        void dateTo;
        void chefId;
        void dropPointId;
        void statusFilter;
        applyFilters();
    });
</script>

<svelte:head>
    <title>Transaksi Produk - {product?.name || 'Detail'} | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <!-- Top Bar: Navigation Back Button -->
    <div class="flex items-center justify-between">
        <Button variant="secondary" size="sm" icon="fa-solid fa-arrow-left" href="/admin/products">
            Kembali ke Daftar Produk
        </Button>
    </div>

    <!-- Product Summary Card -->
    <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            {#if product?.image_url}
                <img src={product.image_url} alt={product.name} class="w-16 h-16 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm shrink-0" />
            {:else}
                <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 shrink-0 border border-gray-200 dark:border-gray-700">
                    <i class="fa-solid fa-box text-2xl"></i>
                </div>
            {/if}
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                        {product?.name}
                    </h1>
                    {#if product?.is_active}
                        <Badge size="sm" rounded="pill" variant="success">Aktif</Badge>
                    {:else}
                        <Badge size="sm" rounded="pill" variant="danger">Nonaktif</Badge>
                    {/if}
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <span>
                        <i class="fa-solid fa-tag text-indigo-500 mr-1"></i>
                        Kategori: <strong class="text-gray-900 dark:text-white">{product?.product_category?.name ?? "-"}</strong>
                    </span>
                    <span class="text-gray-300 dark:text-gray-700">•</span>
                    <span>
                        <i class="fa-solid fa-money-bill-wave text-emerald-500 mr-1"></i>
                        Harga: <strong class="text-emerald-600 dark:text-emerald-400">{formatCurrency(product?.price ?? 0)}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard
            label="Total Transaksi Order"
            value={formatNumber(summary.total_orders)}
            icon="fa-solid fa-receipt"
            color="indigo"
        />
        <StatCard
            label="Total Porsi Terjual"
            value={`${formatNumber(summary.total_quantity)} porsi`}
            icon="fa-solid fa-utensils"
            color="blue"
        />
        <StatCard
            label="Total Omset Produk"
            value={formatCurrency(summary.total_revenue)}
            icon="fa-solid fa-circle-dollar-to-slot"
            color="green"
        />
    </div>

    <!-- Kitchen / Chef Breakdown -->
    <Card title="Rincian Transaksi per Dapur / Chef">
        {#snippet children()}
            {#if chefBreakdown.length > 0}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {#each chefBreakdown as cb}
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col justify-between space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-sm shrink-0">
                                    <i class="fa-solid fa-kitchen-set"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-white truncate">
                                        {cb.chef_name}
                                    </h4>
                                    {#if cb.business_name}
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{cb.business_name}</p>
                                    {/if}
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-200 dark:border-gray-700/60 text-center">
                                <div>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 block">Transaksi</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{cb.total_orders}</span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 block">Porsi</span>
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{cb.total_quantity}</span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 block">Omset</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate block">{formatCurrency(cb.total_revenue)}</span>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada transaksi dapur untuk produk ini
                </div>
            {/if}
        {/snippet}
    </Card>

    <!-- Filters & Detailed Table -->
    <Card title="Daftar Riwayat Transaksi Produk" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div class="flex flex-wrap gap-2 items-center">
                {#if dateFrom || dateTo || chefId || dropPointId || statusFilter}
                    <Button variant="secondary" size="sm" onclick={resetFilters} icon="fa-solid fa-rotate-left">
                        Reset Filter
                    </Button>
                {/if}
            </div>
        {/snippet}
        {#snippet children()}
            <!-- Filter inputs -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-gray-50/50 dark:bg-gray-900/50">
                <DateInput id="date_from" label="Dari Tanggal" bind:value={dateFrom} />
                <DateInput id="date_to" label="Sampai Tanggal" bind:value={dateTo} />
                <Select id="chef_filter" label="Dapur / Chef" options={chefOptions} bind:value={chefId} />
                <Select id="drop_point_filter" label="Drop Point" options={dropPointOptions} bind:value={dropPointId} />
                <Select id="status_filter" label="Status Pesanan" options={statusOptions} bind:value={statusFilter} />
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Dapur / Chef</th>
                            <th>Drop Point</th>
                            <th>Varian / Opsi</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-right">Subtotal</th>
                            <th>Status</th>
                            <th class="w-24 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if rows.length > 0}
                            {#each rows as item}
                                {@const order = item.order}
                                {@const statusBadge = order ? getOrderStatusBadge(order.order_status) : { variant: "secondary" as BadgeVariant, label: "-" }}
                                <tr>
                                    <td class="font-medium text-gray-900 dark:text-white">
                                        {order?.number ?? "-"}
                                    </td>
                                    <td class="text-sm text-gray-600 dark:text-gray-400">
                                        {order?.created_at ? new Date(order.created_at).toLocaleDateString("id-ID") : "-"}
                                    </td>
                                    <td>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {order?.customer?.name ?? "-"}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {order?.customer?.email ?? ""}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                            {item.chef?.name ?? "Belum Diassign"}
                                        </div>
                                    </td>
                                    <td class="text-sm text-gray-700 dark:text-gray-300">
                                        {order?.drop_point?.name ?? "-"}
                                    </td>
                                    <td class="text-xs text-gray-600 dark:text-gray-400 max-w-xs">
                                        {formatOptions(item.options)}
                                    </td>
                                    <td class="text-center font-bold text-indigo-600 dark:text-indigo-400 text-sm">
                                        {item.quantity} porsi
                                    </td>
                                    <td class="text-right font-bold text-gray-900 dark:text-white">
                                        {formatCurrency(item.subtotal)}
                                    </td>
                                    <td>
                                        <Badge size="sm" rounded="pill" variant={statusBadge.variant}>
                                            {#snippet children()}{statusBadge.label}{/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        {#if order}
                                            <Button variant="primary" size="sm" icon="fa-solid fa-eye" href={`/admin/orders/${order.id}`}>
                                                Detail
                                            </Button>
                                        {/if}
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td colspan="10" class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 block opacity-40"></i>
                                    Tidak ada riwayat transaksi untuk filter yang dipilih
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
