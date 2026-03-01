<script lang="ts">
    import { page, Link, router } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { onMount, untrack } from "svelte";

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

    function deliverItem(itemId: string) {
        dialogState = {
            isOpen: true,
            type: "success",
            title: "Tandai Selesai / Diterima",
            message: "Tandai item ini telah berhasil dikirim dan diselesaikan?",
            confirmText: "Ya, Selesai",
            cancelText: "Batal",
            loading: false,
            formFields: [
                {
                    id: "delivery_photo",
                    name: "delivery_photo",
                    type: "file",
                    label: "Foto Bukti Pengiriman (Opsional)",
                    required: false,
                },
            ],
            onConfirm: async (formData) => {
                dialogState.loading = true;
                const uploadData = new FormData();
                uploadData.append("item_ids[0]", itemId);
                if (formData?.delivery_photo) {
                    uploadData.append(
                        "delivery_photo",
                        formData.delivery_photo,
                    );
                }

                router.post("/chef/orders/deliver", uploadData, {
                    onFinish: () => {
                        dialogState.isOpen = false;
                        dialogState.loading = false;
                        applyFilters();
                    },
                });
            },
        };
    }

    function getStatusVariant(status: string) {
        switch (status) {
            case "accepted":
                return "info";
            case "shipped":
                return "primary";
            case "delivered":
                return "success";
            case "rejected":
                return "danger";
            default:
                return "warning";
        }
    }

    function getStatusLabel(status: string) {
        switch (status) {
            case "accepted":
                return "Diproses";
            case "shipped":
                return "Dikirim";
            case "delivered":
                return "Selesai";
            case "rejected":
                return "Ditolak";
            default:
                return "Menunggu";
        }
    }

    function isAllItemsApproved(order: Order) {
        // Find the group to check all items for this order.
        // Note: group.order.items from frontend data doesn't contain all order items if they are filtered out by backend.
        // Wait, earlier we added `with('order.items')` in the backend so `order.items` should contain all items!
        const orderItems = (order as any).items || [];
        if (orderItems.length === 0) return true; // fallback

        // Every item must NOT be pending and NOT be rejected
        return orderItems.every(
            (item: any) =>
                item.chef_status !== "pending" &&
                item.chef_status !== "rejected",
        );
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
            <div class="bg-orange-500 text-white p-1.5 rounded-lg">
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
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all"
                />
            </div>
        </div>

        <!-- Date Filters -->
        <div class="px-4 pb-4 flex gap-2 overflow-x-auto hide-scrollbar">
            {#each ["all", "30_days", "90_days", "custom"] as range}
                <button
                    class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                    range
                        ? 'bg-orange-500 border-orange-500 text-white'
                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                    onclick={() => handleDateRangeChange(range)}
                >
                    {range === "all"
                        ? "Semua Waktu"
                        : range === "30_days"
                          ? "30 Hari"
                          : range === "90_days"
                            ? "90 Hari"
                            : "Pilih Tanggal"}
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
                    class="w-full mt-1 py-2 bg-orange-500 text-white rounded-xl text-sm font-bold hover:bg-orange-600 transition-all shadow-sm active:scale-[0.98]"
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
                        class="shrink-0 px-6 py-3 text-sm font-medium transition-colors border-b-2 {activeTab ===
                        tab.id
                            ? 'border-orange-500 text-orange-600'
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
                    <div
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                    >
                        <div
                            class="bg-gray-50/50 p-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4"
                        >
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span
                                        class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                                        >Nomor Pesanan</span
                                    >
                                    <Badge variant="info" size="sm" outlined
                                        >{group.order.number}</Badge
                                    >
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    {group.order.customer?.name}
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span class="text-gray-500 font-normal"
                                        >{group.order.drop_point?.name ||
                                            "Alamat Kustom"}</span
                                    >
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block mb-1"
                                    >Tanggal Pengiriman</span
                                >
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {dayjs(group.order.delivery_date).format(
                                        "dddd, D MMMM YYYY",
                                    )}
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100">
                            {#each group.items as item}
                                <div
                                    class="p-4 flex flex-wrap items-center justify-between gap-4"
                                >
                                    <div class="flex items-center gap-4">
                                        {#if item.product?.image}
                                            <img
                                                src={item.product.image}
                                                alt={item.product.name}
                                                class="w-12 h-12 rounded-lg object-cover border border-gray-100"
                                            />
                                        {:else}
                                            <div
                                                class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-100"
                                            >
                                                <i
                                                    class="fa-solid fa-bowl-food text-gray-300"
                                                ></i>
                                            </div>
                                        {/if}
                                        <div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <h4
                                                    class="font-bold text-gray-900"
                                                >
                                                    {item.product?.name}
                                                </h4>
                                                <span
                                                    class="text-xs text-gray-400"
                                                    >• {dayjs(
                                                        item.created_at,
                                                    ).format("DD/MM/YY")}</span
                                                >
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                Jumlah: <span
                                                    class="font-semibold text-gray-900"
                                                    >{item.quantity}x</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            variant={getStatusVariant(
                                                item.chef_status,
                                            )}
                                            size="sm"
                                        >
                                            {getStatusLabel(item.chef_status)}
                                        </Badge>
                                        {#if item.chef_status === "accepted" && group.order.order_status === "confirmed" && isAllItemsApproved(group.order)}
                                            <button
                                                class="text-xs bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-full font-medium transition-colors"
                                                onclick={() =>
                                                    shipItem(item.id)}
                                            >
                                                Kirim
                                            </button>
                                        {:else if item.chef_status === "shipped"}
                                            <button
                                                class="text-xs bg-green-50 text-green-600 hover:bg-green-100 px-3 py-1 rounded-full font-medium transition-colors"
                                                onclick={() =>
                                                    deliverItem(item.id)}
                                            >
                                                Selesai
                                            </button>
                                        {/if}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
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
                    class="w-8 h-8 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"
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
