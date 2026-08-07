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

    interface ChefBreakdown {
        chef_id: string | null;
        chef_name: string;
        business_name?: string | null;
        total_orders: number;
        total_quantity: number;
        total_revenue: number;
        percentage?: number;
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
        chef?: { id: string; name: string; business_name?: string } | null;
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
    let product = $derived(($page.props.product as { data: ProductData })?.data ?? ($page.props.product as ProductData));
    let summary = $derived(($page.props.summary as Summary) ?? { total_orders: 0, total_quantity: 0, total_revenue: 0, avg_qty_per_order: 0 });
    let chefBreakdown = $derived(($page.props.chefBreakdown as ChefBreakdown[]) ?? []);
    let variantBreakdown = $derived(($page.props.variantBreakdown as VariantBreakdown[]) ?? []);
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

    let hasActiveFilters = $derived(
        !!dateFrom || !!dateTo || !!chefId || !!dropPointId || !!statusFilter,
    );

    let rows = $derived(itemsData?.data ?? []);

    let meta = $derived({
        total: itemsData?.total ?? itemsData?.meta?.total ?? rows.length,
        per_page: itemsData?.per_page ?? itemsData?.meta?.per_page ?? 15,
        current_page: itemsData?.current_page ?? itemsData?.meta?.current_page ?? 1,
        last_page: itemsData?.last_page ?? itemsData?.meta?.last_page ?? 1,
    });

    // Dropdown Options
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
        const parts = options
            .map((opt) => {
                const optName = opt.product_option?.name ?? opt.productOption?.name;
                const itemName = opt.product_option_item?.name ?? opt.productOptionItem?.name;
                if (optName && itemName) {
                    return `${optName}: ${itemName}`;
                }
                if (itemName) {
                    return itemName;
                }
                if (optName) {
                    return optName;
                }
                return "";
            })
            .filter(Boolean);

        return parts.length > 0 ? parts.join(", ") : "-";
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
    <title>Detail Transaksi Produk - {product?.name || 'Detail'} | {name($page.props.settings)}</title>
</svelte:head>

<div class="space-y-6">
    <!-- Top Navigation Link -->
    <div>
        <Link
            href="/admin/products"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors"
        >
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Kembali ke Daftar Produk
        </Link>
    </div>

    <!-- Product Overview Banner -->
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5"
    >
        <div class="flex items-center gap-4">
            {#if product?.image_url}
                <img
                    src={product.image_url}
                    alt={product.name}
                    class="w-14 h-14 rounded-xl object-cover border border-slate-200 dark:border-slate-800 shadow-sm shrink-0"
                />
            {:else}
                <div
                    class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-400 shrink-0"
                >
                    <i class="fa-solid fa-box text-2xl"></i>
                </div>
            {/if}
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {product?.name}
                    </h1>
                    {#if product?.is_active}
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Aktif
                        </span>
                    {:else}
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Nonaktif
                        </span>
                    {/if}
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-slate-500 dark:text-slate-400">
                    <span>Kategori: <strong class="text-slate-800 dark:text-slate-200">{product?.product_category?.name ?? "-"}</strong></span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span>Harga: <strong class="text-slate-800 dark:text-slate-200">{formatCurrency(product?.price ?? 0)}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6 border-t md:border-t-0 border-slate-100 dark:border-slate-800/80 pt-3 md:pt-0">
            <div class="text-left md:text-right">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Pemasukan</span>
                <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">{formatCurrency(summary.total_revenue)}</span>
            </div>
            <div class="text-left md:text-right border-l border-slate-200 dark:border-slate-800 pl-6">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Terjual</span>
                <span class="text-xl font-extrabold text-slate-900 dark:text-white">{formatNumber(summary.total_quantity)} <span class="text-xs font-normal text-slate-400">porsi</span></span>
            </div>
        </div>
    </div>

    <!-- GLOBAL FILTER TOOLBAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-filter text-indigo-500 text-sm"></i>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                    Filter Pesanan & Tanggal
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <DateInput id="date_from" label="Dari Tanggal" bind:value={dateFrom} />
            </div>
            <div>
                <DateInput id="date_to" label="Sampai Tanggal" bind:value={dateTo} />
            </div>
            <div>
                <Select id="chef_filter" label="Dapur / Chef" options={chefOptions} bind:value={chefId} />
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

    <!-- DUAL BREAKDOWN SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <!-- Panel 1: Penjualan Per Dapur / Chef -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Penjualan per Dapur / Chef</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Berapa banyak makanan yang dimasak oleh masing-masing dapur</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-md border border-purple-200 dark:border-purple-800/60">
                    {chefBreakdown.length} Dapur
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Dapur / Chef</th>
                            <th class="py-3 px-3 text-center">Pesanan</th>
                            <th class="py-3 px-3 text-center">Porsi</th>
                            <th class="py-3 px-3 text-right">Pemasukan</th>
                            <th class="py-3 px-4 text-right">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        {#if chefBreakdown.length > 0}
                            {#each chefBreakdown as cb}
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                        <div class="font-bold">{cb.chef_name}</div>
                                        {#if cb.business_name}
                                            <div class="text-[10px] text-slate-400 font-normal">{cb.business_name}</div>
                                        {/if}
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                        {cb.total_orders}
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-indigo-600 dark:text-indigo-400">
                                        {cb.total_quantity}
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-slate-900 dark:text-white">
                                        {formatCurrency(cb.total_revenue)}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="inline-flex items-center gap-1 font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/40 px-2 py-0.5 rounded border border-purple-200 dark:border-purple-800/50">
                                            {cb.percentage ?? 0}%
                                        </span>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada data dapur
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel 2: Rincian Ukuran & Varian Produk -->
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
                        {#if variantBreakdown.length > 0}
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
                        {:else}
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada data varian
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                        <th>Dapur / Chef</th>
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
                                <td>
                                    <div class="text-sm font-semibold text-purple-600 dark:text-purple-400">
                                        {item.chef?.name ?? "Belum Diassign"}
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
                            <td colspan="10" class="py-12 text-center text-sm text-slate-400 dark:text-slate-500">
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
