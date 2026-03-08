<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import OrderCard from "../Components/OrderCard.svelte";

    interface Props {
        officer: {
            id: string;
            name: string;
            email: string;
        };
        pickUpPoint: {
            id: string;
            name: string;
            address: string;
        } | null;
        incomingOrders: {
            data: any[];
            meta?: any;
        };
        atPickupOrders: {
            data: any[];
            meta?: any;
        };
        onDeliveryOrders: {
            data: any[];
            meta?: any;
        };
        completedOrders: {
            data: any[];
            meta?: any;
        };
    }

    let {
        officer,
        pickUpPoint,
        incomingOrders,
        atPickupOrders,
        onDeliveryOrders,
        completedOrders,
    }: Props = $props();

    let activeTab = $state<
        "incoming" | "at_pickup" | "on_delivery" | "completed"
    >("incoming");

    const currentYear = new Date().getFullYear();

    function handleLogout() {
        router.post("/pic/logout");
    }

    function getOrdersForTab(tab: string) {
        switch (tab) {
            case "incoming":
                return incomingOrders?.data ?? [];
            case "at_pickup":
                return atPickupOrders?.data ?? [];
            case "on_delivery":
                return onDeliveryOrders?.data ?? [];
            case "completed":
                return completedOrders?.data ?? [];
            default:
                return [];
        }
    }

    function getTabCount(tab: string) {
        switch (tab) {
            case "incoming":
                return incomingOrders?.data?.length ?? 0;
            case "at_pickup":
                return atPickupOrders?.data?.length ?? 0;
            case "on_delivery":
                return onDeliveryOrders?.data?.length ?? 0;
            default:
                return 0;
        }
    }

    const tabs = [
        {
            id: "incoming" as const,
            label: "Menuju",
            icon: "fa-solid fa-truck-arrow-right",
        },
        {
            id: "at_pickup" as const,
            label: "Di Tempat",
            icon: "fa-solid fa-boxes-stacked",
        },
        {
            id: "on_delivery" as const,
            label: "Dikirim",
            icon: "fa-solid fa-motorcycle",
        },
        {
            id: "completed" as const,
            label: "Selesai",
            icon: "fa-solid fa-check-circle",
        },
    ];

    const orders = $derived(getOrdersForTab(activeTab));
</script>

<svelte:head>
    <title>Dashboard | Pickup Point | {appName($page.props.settings)}</title>
</svelte:head>

<div class="min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <h1
                    class="text-lg font-bold text-gray-900 flex items-center gap-2"
                >
                    <i class="fa-solid fa-warehouse text-blue-600"></i>
                    {pickUpPoint?.name ?? "Pickup Point"}
                </h1>
                <p class="text-xs text-gray-500">{officer.name}</p>
            </div>
            <button
                onclick={handleLogout}
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-red-600 transition-colors"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Keluar
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border-b border-gray-200 sticky top-[60px] z-10">
        <div class="flex gap-1">
            {#each tabs as tab}
                <button
                    onclick={() => (activeTab = tab.id)}
                    class="flex-1 flex items-center justify-center gap-1.5 py-3 text-sm font-medium transition-colors relative
                        {activeTab === tab.id
                        ? 'text-blue-600'
                        : 'text-gray-500 hover:text-gray-700'}"
                >
                    <i class="{tab.icon} text-xs"></i>
                    <span>{tab.label}</span>
                    {#if getTabCount(tab.id) > 0}
                        <span
                            class="inline-flex items-center justify-center min-w-[16px] h-[16px] px-1 text-[9px] font-bold rounded-full
                                {activeTab === tab.id
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-600'}"
                        >
                            {getTabCount(tab.id)}
                        </span>
                    {/if}
                    {#if activeTab === tab.id}
                        <div
                            class="absolute bottom-0 left-2 right-2 h-0.5 bg-blue-600 rounded-t-full"
                        ></div>
                    {/if}
                </button>
            {/each}
        </div>
    </div>

    <!-- Content -->
    <div class="p-4 space-y-4 pb-24">
        {#if orders.length === 0}
            <div
                class="bg-white rounded-2xl border border-gray-100 p-8 text-center"
            >
                <div
                    class="inline-flex items-center justify-center w-14 h-14 bg-gray-100 rounded-2xl mb-3"
                >
                    {#if activeTab === "incoming"}
                        <i
                            class="fa-solid fa-truck-arrow-right text-gray-400 text-xl"
                        ></i>
                    {:else if activeTab === "at_pickup"}
                        <i
                            class="fa-solid fa-boxes-stacked text-gray-400 text-xl"
                        ></i>
                    {:else if activeTab === "on_delivery"}
                        <i class="fa-solid fa-motorcycle text-gray-400 text-xl"
                        ></i>
                    {:else}
                        <i
                            class="fa-solid fa-check-circle text-gray-400 text-xl"
                        ></i>
                    {/if}
                </div>
                <h3 class="text-sm font-semibold text-gray-600 mb-1">
                    {#if activeTab === "incoming"}
                        Belum ada pesanan yang menuju
                    {:else if activeTab === "at_pickup"}
                        Belum ada pesanan di pickup point
                    {:else if activeTab === "on_delivery"}
                        Belum ada pesanan yang sedang dikirim
                    {:else}
                        Belum ada pesanan yang selesai
                    {/if}
                </h3>
                <p class="text-xs text-gray-400">
                    Pesanan akan muncul di sini secara otomatis.
                </p>
            </div>
        {:else}
            {#each orders as order (order.id)}
                <OrderCard {order} tab={activeTab} />
            {/each}
        {/if}
    </div>

    <!-- Bottom Info -->
    <div
        class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 z-10"
    >
        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-400">
                &copy; {currentYear}
                {appName($page.props.settings)}
            </p>
            {#if pickUpPoint?.address}
                <p class="text-xs text-gray-500 truncate max-w-[200px]">
                    <i class="fa-solid fa-location-dot mr-1"></i>
                    {pickUpPoint.address}
                </p>
            {/if}
        </div>
    </div>
</div>
