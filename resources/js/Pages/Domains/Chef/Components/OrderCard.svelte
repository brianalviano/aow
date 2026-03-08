<script lang="ts">
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";

    dayjs.locale(id);

    export interface Product {
        id: string;
        name: string;
        image?: string;
    }

    export interface PickUpPoint {
        id: string;
        name: string;
        address: string;
        latitude?: number;
        longitude?: number;
    }

    export interface Order {
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
        pick_up_point?: PickUpPoint;
        items?: any[];
    }

    export interface Item {
        id: string;
        quantity: number;
        note?: string;
        chef_status: string;
        product?: Product;
        order: Order;
        created_at?: string;
    }

    export interface Group {
        order: Order;
        items: Item[];
    }

    let {
        group,
        context = "dashboard",
        onApprove,
        onReject,
        onShip,
    } = $props<{
        group: Group;
        context?: "dashboard" | "orders";
        onApprove?: (id: string) => void;
        onReject?: (id: string) => void;
        onShip?: (id: string) => void;
    }>();

    function isAllItemsApproved(order: Order) {
        const orderItems = order.items || [];
        if (orderItems.length === 0) return true;

        return orderItems.every(
            (item: any) =>
                item.chef_status !== "pending" &&
                item.chef_status !== "rejected",
        );
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
                return "Dikirim ke Pickup Point";
            case "delivered":
                return "Selesai";
            case "rejected":
                return "Ditolak";
            default:
                return "Menunggu";
        }
    }

    function openGoogleMaps(lat?: number, lng?: number) {
        if (lat && lng) {
            window.open(
                `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`,
                "_blank",
            );
        }
    }
</script>

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
                    >{group.order.drop_point?.name || "Alamat Kustom"}</span
                >
            </div>
        </div>
        <div>
            <span class="text-xs text-gray-400 block mb-1"
                >Tanggal Pengiriman</span
            >
            <span class="text-sm font-semibold text-gray-900">
                {dayjs(group.order.delivery_date).format("dddd, D MMMM YYYY")}
                {#if group.order.delivery_time}
                    <span class="text-gray-400 mx-1">•</span>
                    {group.order.delivery_time.includes("T")
                        ? new Date(
                              group.order.delivery_time,
                          ).toLocaleTimeString("id-ID", {
                              hour: "2-digit",
                              minute: "2-digit",
                          })
                        : group.order.delivery_time.substring(0, 5)} WIB
                {/if}
            </span>
        </div>
    </div>

    {#if group.order.pick_up_point}
        <div
            class="bg-blue-50/50 px-4 py-3 border-b border-blue-100 flex flex-wrap items-center justify-between gap-3"
        >
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <i class="fa-solid fa-location-dot text-blue-500 text-sm"
                    ></i>
                    <span class="text-xs font-bold text-blue-600 uppercase"
                        >Kirim ke Pickup Point</span
                    >
                </div>
                <p class="text-sm font-semibold text-gray-900">
                    {group.order.pick_up_point.name}
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {group.order.pick_up_point.address}
                </p>
            </div>
            {#if group.order.pick_up_point.latitude && group.order.pick_up_point.longitude}
                <button
                    onclick={() =>
                        openGoogleMaps(
                            group.order.pick_up_point?.latitude,
                            group.order.pick_up_point?.longitude,
                        )}
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <i class="fa-solid fa-map-location-dot"></i>
                    Google Maps
                </button>
            {/if}
        </div>
    {/if}

    <div class="divide-y divide-gray-100">
        {#each group.items as item}
            <div class="p-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    {#if item.product?.image}
                        <img
                            src={item.product.image}
                            alt={item.product.name}
                            class="{context === 'dashboard'
                                ? 'w-16 h-16 rounded-xl'
                                : 'w-12 h-12 rounded-lg'} object-cover border border-gray-100"
                        />
                    {:else}
                        <div
                            class="{context === 'dashboard'
                                ? 'w-16 h-16 rounded-xl'
                                : 'w-12 h-12 rounded-lg'} bg-gray-100 flex items-center justify-center border border-gray-100"
                        >
                            <i class="fa-solid fa-bowl-food text-gray-300"></i>
                        </div>
                    {/if}
                    <div>
                        {#if context === "orders" && item.created_at}
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-gray-900">
                                    {item.product?.name}
                                </h4>
                                <span class="text-xs text-gray-400"
                                    >• {dayjs(item.created_at).format(
                                        "DD/MM/YY",
                                    )}</span
                                >
                            </div>
                        {:else}
                            <h4 class="font-bold text-gray-900">
                                {item.product?.name}
                            </h4>
                        {/if}
                        <p
                            class="{context === 'dashboard'
                                ? 'text-sm'
                                : 'text-xs'} text-gray-500"
                        >
                            Jumlah: <span class="font-semibold text-gray-900"
                                >{item.quantity}x</span
                            >
                        </p>
                        {#if context === "dashboard" && item.note}
                            <p
                                class="text-xs text-[#997A00] bg-[#FFF9E6] px-2 py-0.5 rounded mt-1 inline-block"
                            >
                                <i class="fa-solid fa-comment-dots mr-1"></i>
                                {item.note}
                            </p>
                        {/if}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    {#if context === "dashboard"}
                        {#if item.chef_status === "pending"}
                            <Button
                                variant="success"
                                size="sm"
                                icon="fa-solid fa-check"
                                onclick={() => onApprove?.(item.id)}
                            >
                                Terima
                            </Button>
                            <Button
                                variant="outline-danger"
                                size="sm"
                                icon="fa-solid fa-xmark"
                                onclick={() => onReject?.(item.id)}
                            >
                                Tolak
                            </Button>
                        {:else if item.chef_status === "accepted" && group.order.order_status === "confirmed" && isAllItemsApproved(group.order)}
                            <Button
                                variant="primary"
                                size="sm"
                                icon="fa-solid fa-truck"
                                onclick={() => onShip?.(item.id)}
                            >
                                Kirim ke Pickup Point
                            </Button>
                        {:else if item.chef_status === "shipped"}
                            <Badge variant="primary" size="sm">
                                <i class="fa-solid fa-truck mr-1"></i>
                                Dalam Pengiriman
                            </Badge>
                        {/if}
                    {:else if context === "orders"}
                        <Badge
                            variant={getStatusVariant(item.chef_status)}
                            size="sm"
                        >
                            {getStatusLabel(item.chef_status)}
                        </Badge>
                        {#if item.chef_status === "accepted" && group.order.order_status === "confirmed" && isAllItemsApproved(group.order)}
                            <Button
                                variant="primary"
                                size="sm"
                                icon="fa-solid fa-truck"
                                onclick={() => onShip?.(item.id)}
                            >
                                Kirim ke Pickup Point
                            </Button>
                        {/if}
                    {/if}
                </div>
            </div>
        {/each}
    </div>
</div>
