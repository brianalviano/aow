<script lang="ts">
    import { Link } from "@inertiajs/svelte";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";

    dayjs.locale(id);

    export let orders: Array<{
        id: string;
        number: string;
        total_amount: number;
        payment_status: string;
        order_status: string;
        created_at: string;
        drop_point?: { name: string };
        payment_method?: {
            name: string;
            category: string;
        };
    }> = [];

    // Filter status
    let activeTab = "all"; // all, upaid, process, completed, cancelled

    const tabs = [
        { id: "all", label: "Semua" },
        { id: "unpaid", label: "Belum Bayar" },
        { id: "process", label: "Diproses" },
        { id: "shipped", label: "Dikirim" },
        { id: "completed", label: "Selesai" },
        { id: "cancelled", label: "Dibatalkan" },
    ];

    $: filteredOrders = orders.filter((o) => {
        const isCash = o.payment_method?.category === "cash";

        if (activeTab === "all") return true;
        if (activeTab === "unpaid")
            return o.payment_status === "pending" && !isCash;
        if (activeTab === "process")
            return (
                (o.payment_status !== "pending" || isCash) &&
                (o.order_status === "pending" || o.order_status === "confirmed")
            );
        if (activeTab === "shipped") return o.order_status === "shipped";
        if (activeTab === "completed") return o.order_status === "delivered";
        if (activeTab === "cancelled")
            return (
                o.order_status === "cancelled" || o.payment_status === "failed"
            );
        return true;
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
        if (paymentStatus === "pending") {
            const isCash = o?.payment_method?.category === "cash";

            if (isCash) {
                return {
                    text: "Bayar di Tempat",
                    classes: "bg-blue-50 text-blue-600 border border-blue-200",
                };
            }

            return {
                text: "Belum Dibayar",
                classes:
                    "bg-yellow-50 text-yellow-600 border border-yellow-200",
            };
        }
        if (orderStatus === "shipped") {
            return {
                text: "Dikirim",
                classes:
                    "bg-purple-50 text-purple-600 border border-purple-200",
            };
        }
        if (orderStatus === "pending" || orderStatus === "confirmed") {
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

<div>
    <!-- Header -->
    <header
        class="bg-white px-4 py-4 flex items-center justify-between sticky top-0 z-20 shadow-sm"
    >
        <div class="flex items-center gap-3">
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
    </header>

    <!-- Tabs -->
    <div class="bg-white border-b border-gray-100 sticky top-[60px] z-10">
        <div class="flex overflow-x-auto hide-scrollbar scroll-smooth">
            {#each tabs as tab}
                <button
                    class="shrink-0 px-4 py-3 text-sm font-medium transition-colors border-b-2 {activeTab ===
                    tab.id
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'}"
                    on:click={() => (activeTab = tab.id)}
                >
                    {tab.label}
                </button>
            {/each}
        </div>
    </div>

    <!-- Order List -->
    <div class="p-4 space-y-4">
        {#if filteredOrders.length === 0}
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
                    Belum ada pesanan dengan status ini.
                </p>
            </div>
        {:else}
            {#each filteredOrders as order (order.id)}
                {@const badge = getStatusBadge(
                    order.payment_status,
                    order.order_status,
                    order,
                )}
                <Link
                    href={`/orders/${order.id}`}
                    class="block bg-white border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div
                        class="flex items-center justify-between mb-3 border-b border-gray-50 pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-shop text-gray-400 text-sm"
                            ></i>
                            <span class="text-sm font-medium text-gray-900"
                                >{order.drop_point?.name || "Drop Point"}</span
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
                </Link>
            {/each}
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
