<script lang="ts">
    import { Link, router } from "@inertiajs/svelte";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { onMount } from "svelte";

    dayjs.locale(id);

    interface Order {
        id: string;
        number: string;
        total_amount: number;
        payment_status: string;
        order_status: string;
        created_at: string;
        cancellation_note?: string;
        drop_point?: { name: string };
        customer_address?: { name: string };
        payment_method?: {
            name: string;
            category: string;
        };
    }

    export let orders: {
        data: Order[];
        next_page_url: string | null;
        current_page: number;
    };
    export let filters: {
        search?: string;
        date_range?: string;
        start_date?: string;
        end_date?: string;
        status?: string;
    };

    let activeTab = filters.status || "all";
    let search = filters.search || "";
    let dateRange = filters.date_range || "all";
    let startDate = filters.start_date || "";
    let endDate = filters.end_date || "";
    let navigatingId: string | null = null;
    let isLoadingMore = false;
    let searchTimeout: ReturnType<typeof setTimeout>;

    const tabs = [
        { id: "all", label: "Semua" },
        { id: "unpaid", label: "Belum Bayar" },
        { id: "process", label: "Diproses" },
        { id: "shipped", label: "Dikirim" },
        { id: "completed", label: "Selesai" },
        { id: "cancelled", label: "Dibatalkan" },
    ];

    function applyFilters() {
        router.get(
            "/orders",
            {
                search: search || undefined,
                date_range: dateRange !== "all" ? dateRange : undefined,
                start_date: dateRange === "custom" ? startDate : undefined,
                end_date: dateRange === "custom" ? endDate : undefined,
                status: activeTab !== "all" ? activeTab : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function handleTabClick(tabId: string) {
        activeTab = tabId;
        applyFilters();
    }

    function handleSearchInput() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    }

    function handleDateRangeChange(range: string) {
        dateRange = range;
        if (range !== "custom") {
            applyFilters();
        }
    }

    function loadMore() {
        if (!orders.next_page_url || isLoadingMore) return;

        isLoadingMore = true;
        router.get(
            orders.next_page_url,
            {
                search: search || undefined,
                date_range: dateRange !== "all" ? dateRange : undefined,
                start_date: dateRange === "custom" ? startDate : undefined,
                end_date: dateRange === "custom" ? endDate : undefined,
                status: activeTab !== "all" ? activeTab : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ["orders"],
                // @ts-ignore - merge is a feature of Inertia 2.0
                merge: true,
                onFinish: () => {
                    isLoadingMore = false;
                },
            },
        );
    }

    let sentinel: HTMLElement;
    onMount(() => {
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    loadMore();
                }
            },
            { threshold: 1.0 },
        );

        if (sentinel) {
            observer.observe(sentinel);
        }

        return () => observer.disconnect();
    });

    function formatCurrency(amount: number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    function getStatusBadge(
        paymentStatus: string,
        orderStatus: string,
        o?: any,
    ) {
        const isCash = o?.payment_method?.category === "cash";

        if (orderStatus === "cancelled" || paymentStatus === "failed") {
            return {
                text: "Dibatalkan",
                classes: "bg-red-50 text-red-600 border border-red-200",
            };
        }
        if (orderStatus === "delivered") {
            return {
                text: "Selesai",
                classes: "bg-green-50 text-green-600 border border-green-200",
            };
        }

        if (orderStatus === "shipped") {
            return {
                text: isCash ? "Dikirim (COD)" : "Dikirim",
                classes:
                    "bg-purple-50 text-purple-600 border border-purple-200",
            };
        }

        if (orderStatus === "pending" || orderStatus === "confirmed") {
            if (paymentStatus === "pending") {
                if (isCash) {
                    return {
                        text: "Diproses (COD)",
                        classes:
                            "bg-blue-50 text-blue-600 border border-blue-200",
                    };
                }

                return {
                    text: "Belum Dibayar",
                    classes:
                        "bg-yellow-50 text-yellow-600 border border-yellow-200",
                };
            }

            return {
                text: "Diproses",
                classes: "bg-blue-50 text-blue-600 border border-blue-200",
            };
        }

        return {
            text: "Status Tidak Diketahui",
            classes: "bg-gray-50 text-gray-600 border border-gray-200",
        };
    }
</script>

<svelte:head>
    <title>Riwayat Pesanan</title>
</svelte:head>

<div class="min-h-screen bg-gray-50 pb-20">
    <!-- Header -->
    <header class="bg-white sticky top-0 z-30 shadow-sm">
        <div class="px-4 py-4 flex items-center gap-3">
            <Link
                href="/menu"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-50 text-gray-700 hover:bg-gray-100 transition-colors"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </Link>
            <h1 class="text-lg font-bold text-gray-900 leading-none">
                Riwayat Pesanan
            </h1>
        </div>

        <!-- Search Bar -->
        <div class="px-4 pb-4">
            <div class="relative">
                <i
                    class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"
                ></i>
                <input
                    type="text"
                    bind:value={search}
                    on:input={handleSearchInput}
                    placeholder="Cari nomor pesanan atau produk..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                />
            </div>
        </div>

        <!-- Date Filters -->
        <div class="px-4 pb-4 flex gap-2 overflow-x-auto hide-scrollbar">
            <button
                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                'all'
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                on:click={() => handleDateRangeChange("all")}
            >
                Semua Waktu
            </button>
            <button
                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                '30_days'
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                on:click={() => handleDateRangeChange("30_days")}
            >
                30 Hari Terakhir
            </button>
            <button
                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                '90_days'
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                on:click={() => handleDateRangeChange("90_days")}
            >
                90 Hari Terakhir
            </button>
            <button
                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                'custom'
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                on:click={() => handleDateRangeChange("custom")}
            >
                Pilih Tanggal
            </button>
        </div>

        {#if dateRange === "custom"}
            <div
                class="px-4 pb-4 flex flex-col gap-2 animate-in fade-in slide-in-from-top-1"
            >
                <DateInput
                    bind:value={startDate}
                    format="yyyy-mm-dd"
                    placeholder="Tanggal Mulai"
                />
                <DateInput
                    bind:value={endDate}
                    format="yyyy-mm-dd"
                    placeholder="Tanggal Selesai"
                />
                <button
                    on:click={applyFilters}
                    class="w-full mt-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition-all shadow-sm active:scale-[0.98]"
                >
                    Terapkan Rentang Tanggal
                </button>
            </div>
        {/if}

        <!-- Tabs -->
        <div class="bg-white border-b border-gray-100 sticky top-[60px] z-10">
            <div class="flex overflow-x-auto hide-scrollbar scroll-smooth">
                {#each tabs as tab}
                    <button
                        class="shrink-0 px-4 py-3 text-sm font-medium transition-colors border-b-2 {activeTab ===
                        tab.id
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'}"
                        on:click={() => handleTabClick(tab.id)}
                    >
                        {tab.label}
                    </button>
                {/each}
            </div>
        </div>
    </header>

    <!-- Order List -->
    <div class="p-4 space-y-4">
        {#if orders.data.length === 0}
            <div
                class="flex flex-col items-center justify-center py-12 text-center"
            >
                <div
                    class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400 text-2xl"
                >
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3 class="text-gray-900 font-medium mb-1">
                    Tidak Ada Pesanan
                </h3>
                <p class="text-gray-500 text-sm">
                    Belum ada pesanan dengan kriteria ini.
                </p>
            </div>
        {:else}
            {#each orders.data as order (order.id)}
                {@const badge = getStatusBadge(
                    order.payment_status,
                    order.order_status,
                    order,
                )}
                <Link
                    href={`/orders/${order.id}`}
                    class="block bg-white border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden"
                    on:click={() => (navigatingId = order.id)}
                >
                    {#if navigatingId === order.id}
                        <div
                            class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center transition-all"
                        >
                            <div
                                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
                            ></div>
                        </div>
                    {/if}
                    <div
                        class="flex items-center justify-between mb-3 border-b border-gray-50 pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-shop text-gray-400 text-sm"
                            ></i>
                            <span class="text-sm font-medium text-gray-900"
                                >{order.drop_point?.name ||
                                    order.customer_address?.name ||
                                    "Alamat Kustom"}</span
                            >
                        </div>
                        <span
                            class="text-[10px] font-semibold px-2 py-1 rounded-md {badge.classes}"
                        >
                            {badge.text}
                        </span>
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">
                                {dayjs(order.created_at).format(
                                    "DD MMM YYYY, HH:mm",
                                )}
                            </div>
                            <div class="text-sm font-medium text-gray-900 mb-1">
                                {order.number}
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50"
                    >
                        <div class="text-xs text-gray-500">Total Belanja</div>
                        <div class="text-sm font-bold text-gray-900">
                            {formatCurrency(order.total_amount)}
                        </div>
                    </div>

                    {#if order.order_status === "cancelled" && order.cancellation_note}
                        <div
                            class="mt-2 flex items-start gap-1.5 text-xs text-red-500"
                        >
                            <i class="fa-solid fa-circle-info mt-0.5 shrink-0"
                            ></i>
                            <span class="line-clamp-2"
                                >{order.cancellation_note}</span
                            >
                        </div>
                    {/if}
                </Link>
            {/each}

            <!-- Loading Indicator for Infinite Scroll -->
            <div
                bind:this={sentinel}
                class="flex justify-center py-4 {isLoadingMore
                    ? 'visible'
                    : 'invisible'}"
            >
                <div
                    class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"
                ></div>
            </div>
            {#if !orders.next_page_url && orders.data.length > 0}
                <p class="text-center text-xs text-gray-400 py-4">
                    Semua pesanan telah ditampilkan
                </p>
            {/if}
        {/if}
    </div>
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
