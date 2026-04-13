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
    class="bg-slate-950 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden mb-6 hover:border-[#FFD700]/30 transition-all duration-300"
>
    <div
        class="bg-slate-950/50 p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-4"
    >
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span
                    class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]"
                    >Nomor Pesanan</span
                >
                <Badge
                    variant="primary"
                    size="sm"
                    class="bg-[#FFD700] text-slate-900 font-black border-none"
                    >{group.order.number}</Badge
                >
            </div>
            <div class="text-base font-black text-slate-100">
                {group.order.customer?.name}
                <span class="text-slate-600 mx-2">•</span>
                <span class="text-slate-400 font-medium"
                    >{group.order.drop_point?.name || "Alamat Kustom"}</span
                >
            </div>
        </div>
        <div class="text-right">
            <span
                class="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em] block mb-1"
                >Tanggal Pengiriman</span
            >
            <span class="text-sm font-bold text-slate-200">
                {dayjs(group.order.delivery_date).format("dddd, D MMMM YYYY")}
                {#if group.order.delivery_time}
                    <span class="text-slate-600 mx-2">•</span>
                    <span class="text-[#FFD700] font-black">
                        {group.order.delivery_time.includes("T")
                            ? new Date(
                                  group.order.delivery_time,
                              ).toLocaleTimeString("id-ID", {
                                  hour: "2-digit",
                                  minute: "2-digit",
                              })
                            : group.order.delivery_time.substring(0, 5)} WIB
                    </span>
                {/if}
            </span>
        </div>
    </div>

    {#if group.order.pick_up_point}
        <div
            class="bg-blue-900/10 px-6 py-4 border-b border-blue-900/20 flex flex-wrap items-center justify-between gap-4"
        >
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-location-dot text-blue-400 text-sm"
                    ></i>
                    <span
                        class="text-[10px] font-black text-blue-400 uppercase tracking-widest"
                        >Kirim ke Pickup Point</span
                    >
                </div>
                <p class="text-base font-black text-slate-100">
                    {group.order.pick_up_point.name}
                </p>
                <p class="text-xs text-slate-500 mt-1">
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
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-black rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20"
                >
                    <i class="fa-solid fa-map-location-dot"></i>
                    Google Maps
                </button>
            {/if}
        </div>
    {/if}

    <div class="divide-y divide-slate-800">
        {#each group.items as item}
            <div
                class="p-6 flex flex-wrap items-center justify-between gap-6 hover:bg-slate-800/20 transition-colors"
            >
                <div class="flex items-center gap-6">
                    {#if item.product?.image}
                        <img
                            src={item.product.image}
                            alt={item.product.name}
                            class="{context === 'dashboard'
                                ? 'w-20 h-20 rounded-2xl'
                                : 'w-14 h-14 rounded-xl'} object-cover border border-slate-800 shadow-xl"
                        />
                    {:else}
                        <div
                            class="{context === 'dashboard'
                                ? 'w-20 h-20 rounded-2xl'
                                : 'w-14 h-14 rounded-xl'} bg-slate-800 flex items-center justify-center border border-slate-700"
                        >
                            <i
                                class="fa-solid fa-bowl-food text-slate-600 text-2xl"
                            ></i>
                        </div>
                    {/if}
                    <div>
                        {#if context === "orders" && item.created_at}
                            <div class="flex items-center gap-3">
                                <h4 class="font-black text-slate-100 text-lg">
                                    {item.product?.name}
                                </h4>
                                <span
                                    class="text-[10px] font-black text-slate-500 uppercase tracking-widest"
                                    >• {dayjs(item.created_at).format(
                                        "DD/MM/YY",
                                    )}</span
                                >
                            </div>
                        {:else}
                            <h4 class="font-black text-slate-100 text-lg">
                                {item.product?.name}
                            </h4>
                        {/if}
                        <p
                            class="{context === 'dashboard'
                                ? 'text-sm'
                                : 'text-xs'} text-slate-400 mt-1"
                        >
                            Jumlah: <span class="font-black text-[#FFD700]"
                                >{item.quantity}x</span
                            >
                        </p>
                        {#if context === "dashboard" && item.note}
                            <p
                                class="text-[10px] font-black tracking-wider uppercase text-[#FFD700] bg-[#FFD700]/10 px-3 py-1 rounded-full mt-3 inline-flex items-center gap-2 border border-[#FFD700]/20"
                            >
                                <i class="fa-solid fa-comment-dots"></i>
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
