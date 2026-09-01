<script lang="ts">
    import { page, router, Link } from "@inertiajs/svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
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
        avg_qty_per_order?: number;
    }

    interface VariantBreakdown {
        variant_name: string;
        total_orders: number;
        total_quantity: number;
        total_revenue: number;
        percentage?: number;
    }

    interface TransactionOption {
        id: string;
        product_option?: { name: string };
        product_option_item?: { name: string };
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
        options?: TransactionOption[];
    }

    interface PaginatedItems {
        data: TransactionItem[];
        total?: number;
        per_page?: number;
        current_page?: number;
        last_page?: number;
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
    let product = $derived((page.props.product as { data: ProductData })?.data ?? (page.props.product as ProductData));
    let summary = $derived((page.props.summary as Summary) ?? { total_orders: 0, total_quantity: 0, total_revenue: 0, avg_qty_per_order: 0 });
    let variantBreakdown = $derived((page.props.variantBreakdown as VariantBreakdown[]) ?? []);
    let itemsData = $derived((page.props.items as PaginatedItems) ?? { data: [] });
    let filters = $derived((page.props.filters as Record<string, string | null>) ?? {});
    let dropPointsList = $derived((page.props.dropPoints as { id: string; name: string }[]) ?? []);

    // Filter local state
    let dateFrom = $state(untrack(() => filters.date_from ?? ""));
    let dateTo = $state(untrack(() => filters.date_to ?? ""));
    let dropPointId = $state(untrack(() => filters.drop_point_id ?? ""));
    let statusFilter = $state(untrack(() => filters.status ?? ""));

    let hasActiveFilters = $derived(
        !!dateFrom || !!dateTo || !!dropPointId || !!statusFilter,
    );

    let rows = $derived(itemsData?.data ?? []);

    let meta = $derived({
        total: itemsData?.total ?? itemsData?.meta?.total ?? rows.length,
        per_page: itemsData?.per_page ?? itemsData?.meta?.per_page ?? 15,
        current_page: itemsData?.current_page ?? itemsData?.meta?.current_page ?? 1,
        last_page: itemsData?.last_page ?? itemsData?.meta?.last_page ?? 1,
    });

    let dropPointOptions = $derived<SelectOption[]>([
        { value: "", label: "Semua Drop Point" },
        ...dropPointsList.map((dp) => ({ value: dp.id, label: dp.name })),
    ]);

    const statusOptions: SelectOption[] = [
        { value: "", label: "Semua Status Pesanan" },
        { value: "pending", label: "Menunggu" },
        { value: "confirmed", label: "Dikonfirmasi" },
        { value: "on_delivery", label: "Sedang Dikirim" },
        { value: "arrived", label: "Tiba di Tujuan" },
        { value: "delivered", label: "Selesai" },
        { value: "cancelled", label: "Dibatalkan" },
    ];

    function buildParams(extra: Record<string, string | number> = {}): URLSearchParams {
        const p = new URLSearchParams();
        if (dateFrom) p.set("date_from", dateFrom);
        if (dateTo) p.set("date_to", dateTo);
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

    function getOrderStatusBadge(status: string): { variant: BadgeVariant; label: string } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Menunggu" };
            case "confirmed":
                return { variant: "info", label: "Dikonfirmasi" };
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
                const group = opt.product_option?.name ?? opt.productOption?.name ?? "";
                const item = opt.product_option_item?.name ?? opt.productOptionItem?.name ?? "";
                return group && item ? `${group}: ${item}` : item || group;
            })
            .filter(Boolean)
            .join(" | ");
    }

    $effect(() => {
        void dateFrom;
        void dateTo;
        void dropPointId;
        void statusFilter;
        untrack(() => applyFilters());
    });
</script>

<svelte:head>
    <title>Transaksi: {product.name} | {name(page.props.settings)}</title>
</svelte:head>

<div class="space-y-6">
    <!-- TOP NAVIGATION BAR / BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <Link
                href="/admin/products"
                class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white shadow-xs transition-colors"
                title="Kembali ke Daftar Menu"
            >
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </Link>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Riwayat Transaksi Menu
                    </h1>
                    <Badge
                        size="xs"
                        rounded="pill"
                        variant={product.is_active ? "success" : "secondary"}
                    >
                        {#snippet children()}{product.is_active ? "Aktif" : "Nonaktif"}{/snippet}
                    </Badge>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Laporan penjualan dan log pesanan masuk untuk <strong class="text-slate-700 dark:text-slate-200">{product.name}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            <Button
                variant="secondary"
                size="sm"
                icon="fa-solid fa-pen-to-square"
                href={`/admin/products/${product.id}/edit`}
            >
                {#snippet children()}Edit Menu Ini{/snippet}
            </Button>
            <Button
                variant="primary"
                size="sm"
                icon="fa-solid fa-table-list"
                href="/admin/orders"
            >
                {#snippet children()}Lihat Semua Order{/snippet}
            </Button>
        </div>
    </div>

    <!-- PRODUCT QUICK SUMMARY BANNER -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0 flex items-center justify-center">
                {#if product.image_url}
                    <img src={product.image_url} alt={product.name} class="w-full h-full object-cover" />
                {:else}
                    <i class="fa-solid fa-utensils text-2xl text-slate-300 dark:text-slate-600"></i>
                {/if}
            </div>
            <div>
                <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                    {product.product_category?.name ?? "Kategori Menu"}
                </div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white leading-tight">
                    {product.name}
                </h2>
                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5">
                    Harga Dasar: {formatCurrency(product.price)}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6 border-t md:border-t-0 md:border-l border-slate-100 dark:border-slate-800 pt-3 md:pt-0 md:pl-6">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Penjualan</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">
                    {formatNumber(product.total_sales ?? summary.total_quantity)} <span class="text-xs font-normal text-slate-400">Porsi</span>
                </div>
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Porsi Terverifikasi</div>
                <div class="text-lg font-black text-indigo-600 dark:text-indigo-400">
                    {formatNumber(product.real_sales ?? summary.total_quantity)} <span class="text-xs font-normal text-slate-400">Porsi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200 font-bold text-xs">
                <i class="fa-solid fa-filter text-indigo-500"></i>
                <span>Filter Laporan Transaksi</span>
                <span class="text-slate-400 font-normal text-[11px]">
                    (Kombinasikan tanggal, drop point, atau status)
                </span>
            </div>
            {#if hasActiveFilters}
                <button
                    onclick={resetFilters}
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline transition-colors"
                    type="button"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset Filter
                </button>
            {/if}
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <DateInput id="date_from" label="Dari Tanggal" bind:value={dateFrom} />
            </div>
            <div>
                <DateInput id="date_to" label="Sampai Tanggal" bind:value={dateTo} />
            </div>
            <div>
                <Select id="drop_point_filter" label="Drop Point" options={dropPointOptions} bind:value={dropPointId} />
            </div>
            <div>
                <Select id="status_filter" label="Status Pesanan" options={statusOptions} bind:value={statusFilter} />
            </div>
        </div>
    </div>

    <!-- METRICS SUMMARY BAR -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Revenue Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Total Pemasukan</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {formatCurrency(summary.total_revenue)}
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Total uang penjualan produk ini</p>
        </div>

        <!-- Quantity Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Total Porsi Terjual</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                {formatNumber(summary.total_quantity)} <span class="text-sm font-semibold text-slate-400">Porsi</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Jumlah porsi yang dipesan pembeli</p>
        </div>

        <!-- Orders Count Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Total Pesanan</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {formatNumber(summary.total_orders)} <span class="text-sm font-semibold text-slate-400">Pesanan</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Jumlah transaksi / nota masuk</p>
        </div>

        <!-- Average per order -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Rata-rata Porsi per Pesanan</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                {summary.avg_qty_per_order ?? 0} <span class="text-sm font-semibold text-slate-400">Porsi/Pesanan</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Berapa porsi rata-rata per pesanan</p>
        </div>
    </div>

    <!-- Rincian Ukuran & Varian Produk -->
    {#if variantBreakdown.length > 0}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Rincian Ukuran & Varian Produk</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Berapa banyak ukuran Besar, Kecil, atau pilihan varian yang dipesan</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-md border border-blue-200 dark:border-blue-800/60">
                    {variantBreakdown.length} Varian
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Ukuran / Varian</th>
                            <th class="py-3 px-3 text-center">Pesanan</th>
                            <th class="py-3 px-3 text-center">Porsi</th>
                            <th class="py-3 px-3 text-right">Pemasukan</th>
                            <th class="py-3 px-4 text-right">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        {#each variantBreakdown as vb}
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                                    {vb.variant_name}
                                </td>
                                <td class="py-3 px-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {vb.total_orders}
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-indigo-600 dark:text-indigo-400">
                                    {vb.total_quantity}
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-slate-900 dark:text-white">
                                    {formatCurrency(vb.total_revenue)}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="inline-flex items-center gap-1 font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800/50">
                                        {vb.percentage ?? 0}%
                                    </span>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}

    <!-- TRANSACTION LOG TABLE -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Daftar Riwayat Pesanan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Daftar lengkap pesanan pembeli untuk produk ini</p>
            </div>
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                Menampilkan <strong class="text-slate-900 dark:text-white">{rows.length}</strong> dari <strong class="text-slate-900 dark:text-white">{meta.total}</strong> pesanan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="custom-table min-w-full">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Tanggal & Waktu</th>
                        <th>Pelanggan</th>
                        <th>Drop Point</th>
                        <th>Ukuran Kemasan / Varian</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-right">Subtotal</th>
                        <th>Status Pesanan</th>
                        <th class="w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#if rows.length > 0}
                        {#each rows as item}
                            {@const order = item.order}
                            {@const statusBadge = order ? getOrderStatusBadge(order.order_status) : { variant: "secondary" as BadgeVariant, label: "-" }}
                            {@const formattedOpt = formatOptions(item.options)}
                            <tr>
                                <td class="font-semibold text-slate-900 dark:text-white">
                                    {order?.number ?? "-"}
                                </td>
                                <td>
                                    <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        {order?.created_at ? new Date(order.created_at).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" }) : "-"}
                                    </div>
                                    {#if order?.created_at}
                                        <div class="text-[10px] text-slate-400">
                                            {new Date(order.created_at).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" })} WIB
                                        </div>
                                    {/if}
                                </td>
                                <td>
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {order?.customer?.name ?? "-"}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {order?.customer?.email ?? ""}
                                    </div>
                                </td>
                                <td class="text-sm text-slate-700 dark:text-slate-300">
                                    {order?.drop_point?.name ?? "-"}
                                </td>
                                <td class="text-xs max-w-xs">
                                    {#if formattedOpt !== "-"}
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                            <i class="fa-solid fa-box text-[10px] text-indigo-500"></i>
                                            {formattedOpt}
                                        </span>
                                    {:else}
                                        <span class="text-slate-400 dark:text-slate-500">-</span>
                                    {/if}
                                </td>
                                <td class="text-center font-extrabold text-indigo-600 dark:text-indigo-400 text-sm">
                                    {item.quantity} porsi
                                </td>
                                <td class="text-right font-extrabold text-slate-900 dark:text-white">
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
                            <td colspan="9" class="py-12 text-center text-sm text-slate-400 dark:text-slate-500">
                                <i class="fa-solid fa-inbox text-4xl mb-3 block opacity-30"></i>
                                Tidak ada riwayat transaksi untuk filter yang dipilih
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800/80">
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        </div>
    </div>
</div>
