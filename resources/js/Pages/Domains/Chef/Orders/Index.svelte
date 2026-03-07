<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { onMount, untrack } from "svelte";
    import OrderCard from "../Components/OrderCard.svelte";

    dayjs.locale(id);

    interface Product {
        id: string;
        name: string;
        image?: string;
    }

    interface Order {
        id: string;
        number: string;
        delivery_date: string;
        delivery_time?: string;
        order_status: string;
        customer?: {
            name: string;
        };
        drop_point?: {
            name: string;
        };
    }

    interface Item {
        id: string;
        quantity: number;
        note?: string;
        chef_status: string;
        product?: Product;
        order: Order;
        created_at: string;
    }

    interface Group {
        order: Order;
        items: Item[];
    }

    let {
        items = { data: [] as Item[], next_page_url: null, current_page: 1 },
        filters = {
            search: "",
            date_range: "all",
            start_date: "",
            end_date: "",
            status: "all",
        },
    } = $props<{
        items: {
            data: Item[];
            next_page_url: string | null;
            current_page: number;
        };
        filters: {
            search?: string;
            date_range?: string;
            start_date?: string;
            end_date?: string;
            status?: string;
        };
    }>();

    let activeTab = $state(untrack(() => filters.status || "all"));
    let search = $state(untrack(() => filters.search || ""));
    let dateRange = $state(untrack(() => filters.date_range || "all"));
    let startDate = $state(untrack(() => filters.start_date || ""));
    let endDate = $state(untrack(() => filters.end_date || ""));

    $effect(() => {
        activeTab = filters.status || "all";
        search = filters.search || "";
        dateRange = filters.date_range || "all";
        startDate = filters.start_date || "";
        endDate = filters.end_date || "";
    });

    let isLoadingMore = $state(false);
    let searchTimeout: ReturnType<typeof setTimeout>;

    const tabs = [
        { id: "all", label: "Semua" },
        { id: "pending", label: "Menunggu" },
        { id: "accepted", label: "Diproses" },
        { id: "completed", label: "Selesai" },
        { id: "rejected", label: "Ditolak" },
    ];

    // Group items by order_id
    const groupedItems = $derived(
        Object.values(
            items.data.reduce((acc: Record<string, Group>, item: Item) => {
                const orderId = item.order.id;
                if (!acc[orderId]) {
                    acc[orderId] = {
                        order: item.order,
                        items: [],
                    };
                }
                acc[orderId].items.push(item);
                return acc;
            }, {}),
        ) as Group[],
    );

    function applyFilters() {
        router.get(
            "/chef/orders",
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
        if (!items.next_page_url || isLoadingMore) return;

        isLoadingMore = true;
        router.get(
            items.next_page_url,
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
                only: ["items"],
                // @ts-ignore - merge is a feature of Inertia 2.0
                merge: true,
                onFinish: () => {
                    isLoadingMore = false;
                },
            },
        );
    }

    let sentinel: HTMLElement | undefined = $state();
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

    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";

    let dialogState = $state({
        isOpen: false,
        type: "info" as "info" | "warning" | "danger" | "success",
        title: "",
        message: "",
        confirmText: "Ya, Saya Yakin",
        cancelText: "Batal",
        loading: false,
        formFields: [] as any[],
        onConfirm: async (data?: any) => {},
    });

    function shipItem(itemId: string) {
        dialogState = {
            isOpen: true,
            type: "info",
            title: "Tandai Dikirim",
            message: "Apakah Anda yakin item ini sudah siap untuk dikirim?",
            confirmText: "Ya, Tandai",
            cancelText: "Batal",
            loading: false,
            formFields: [],
            onConfirm: async () => {
                dialogState.loading = true;
                router.post(
                    "/chef/orders/ship",
                    {
                        item_ids: [itemId],
                    },
                    {
                        onFinish: () => {
                            dialogState.isOpen = false;
                            dialogState.loading = false;
                            applyFilters();
                        },
                    },
                );
            },
        };
    }
</script>

<svelte:head>
    <title>Riwayat Pesanan | {appName($page.props.settings)}</title>
</svelte:head>

<Dialog
    bind:isOpen={dialogState.isOpen}
    type={dialogState.type}
    title={dialogState.title}
    message={dialogState.message}
    confirmText={dialogState.confirmText}
    cancelText={dialogState.cancelText}
    loading={dialogState.loading}
    form_fields={dialogState.formFields}
    onConfirm={dialogState.onConfirm}
/>

<div class="flex flex-col min-h-screen bg-gray-50 pb-20">
    <header class="bg-white sticky top-0 z-30 shadow-sm">
        <div class="px-4 py-4 flex items-center gap-3">
            <div class="bg-[#FFD700] text-white p-1.5 rounded-lg">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
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
                    oninput={handleSearchInput}
                    placeholder="Cari nomor pesanan, produk, atau customer..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700]/20 focus:border-[#FFD700] transition-all"
                />
            </div>
        </div>

        <!-- Date Filters -->
        <div class="px-4 pb-4 flex gap-2 overflow-x-auto hide-scrollbar">
            {#each ["all", "custom"] as range}
                <button
                    class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                    range
                        ? 'bg-[#FFD700] border-[#FFD700] text-gray-900'
                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                    onclick={() => handleDateRangeChange(range)}
                >
                    {range === "all" ? "Semua Waktu" : "Pilih Tanggal"}
                </button>
            {/each}
        </div>

        {#if dateRange === "custom"}
            <div
                class="px-4 pb-4 flex flex-col gap-2 animate-in fade-in slide-in-from-top-1"
            >
                <div class="grid grid-cols-2 gap-2">
                    <DateInput
                        bind:value={startDate}
                        format="yyyy-mm-dd"
                        placeholder="Mulai"
                    />
                    <DateInput
                        bind:value={endDate}
                        format="yyyy-mm-dd"
                        placeholder="Selesai"
                    />
                </div>
                <button
                    onclick={applyFilters}
                    class="w-full mt-1 py-2 bg-[#FFD700] text-gray-900 rounded-xl text-sm font-bold hover:bg-[#FFC700] transition-all shadow-sm active:scale-[0.98]"
                >
                    Terapkan Rentang Tanggal
                </button>
            </div>
        {/if}

        <!-- Tabs -->
        <div class="bg-white border-b border-gray-100">
            <div class="flex overflow-x-auto hide-scrollbar scroll-smooth">
                {#each tabs as tab}
                    <button
                        class="shrink-0 px-3 py-3 text-sm font-medium transition-colors border-b-2 {activeTab ===
                        tab.id
                            ? 'border-[#FFD700] text-[#997A00]'
                            : 'border-transparent text-gray-500 hover:text-gray-700'}"
                        onclick={() => handleTabClick(tab.id)}
                    >
                        {tab.label}
                    </button>
                {/each}
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 max-w-7xl mx-auto w-full">
        {#if groupedItems.length === 0}
            <div
                class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-sm"
            >
                <div
                    class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                    <i class="fa-solid fa-box-open text-2xl text-gray-300"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">
                    Pesanan tidak ditemukan
                </h3>
                <p class="text-gray-500">
                    Tidak ada pesanan yang sesuai dengan filter Anda.
                </p>
            </div>
        {:else}
            <div class="space-y-6">
                {#each groupedItems as group (group.order.id)}
                    <OrderCard {group} context="orders" onShip={shipItem} />
                {/each}
            </div>

            <!-- Loading Indicator for Infinite Scroll -->
            <div
                bind:this={sentinel}
                class="flex justify-center py-8 {isLoadingMore
                    ? 'visible'
                    : 'invisible'}"
            >
                <div
                    class="w-8 h-8 border-4 border-[#FFD700] border-t-transparent rounded-full animate-spin"
                ></div>
            </div>

            {#if !items.next_page_url && items.data.length > 0}
                <p class="text-center text-xs text-gray-400 py-4 pb-12">
                    Semua riwayat pesanan telah ditampilkan
                </p>
            {/if}
        {/if}
    </main>
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
