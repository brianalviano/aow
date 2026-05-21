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
        note?: string;
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
                item.chef_status !== "pending",
        );
    }

    function getStatusVariant(status: string) {
        switch (status) {
            case "accepted":
                return "warning";
            case "shipped":
                return "primary";
            case "delivered":
                return "success";
            case "rejected":
                return "danger";
            case "cancelled":
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
            case "cancelled":
                return "Dibatalkan";
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
    class="bg-slate-950 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden mb-4 hover:border-[#FFD700]/30 transition-all duration-300"
>
    <div
        class="bg-slate-950/50 px-4 py-3 border-b border-slate-800 flex flex-wrap justify-between items-center gap-4"
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
            <div class="text-sm font-black text-slate-100">
                {group.order.customer?.name}
                <span class="text-slate-600 mx-2">•</span>
                <span class="text-slate-400 font-medium text-xs"
                    >{group.order.drop_point?.name || "Alamat Kustom"}</span
                >
            </div>
        </div>
        <div>
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

    {#if group.order.note}
        <div
            class="bg-amber-900/10 px-4 py-2.5 border-b border-amber-900/20 flex items-center gap-3"
        >
            <div class="bg-amber-500/20 p-2 rounded-lg border border-amber-500/30">
                <i class="fa-solid fa-comment-dots text-amber-500"></i>
            </div>
            <div>
                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest block mb-0.5">Catatan Pesanan</span>
                <p class="text-sm font-medium text-amber-200">
                    "{group.order.note}"
                </p>
            </div>
        </div>
    {/if}

    {#if group.order.pick_up_point}
        <div
            class="bg-blue-900/10 px-4 py-2.5 border-b border-blue-900/20 flex flex-wrap items-center justify-between gap-4"
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
                <p class="text-sm font-black text-slate-100">
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
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white text-[10px] font-black rounded-lg hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20"
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
                class="px-4 py-4 hover:bg-slate-800/20 transition-colors flex flex-col gap-4"
            >
                <div class="flex justify-between items-start gap-4">
                    <div class="flex items-center gap-3">
                        {#if item.product?.image}
                            <img
                                src={item.product.image}
                                alt={item.product.name}
                                class="{context === 'dashboard'
                                    ? 'w-16 h-16 rounded-xl'
                                    : 'w-12 h-12 rounded-lg'} object-cover border border-slate-800 shadow-xl"
                            />
                        {:else}
                            <div
                                class="{context === 'dashboard'
                                    ? 'w-16 h-16 rounded-xl'
                                    : 'w-12 h-12 rounded-lg'} bg-slate-800 flex items-center justify-center border border-slate-700"
                            >
                                <i
                                    class="fa-solid fa-bowl-food text-slate-600 text-xl"
                                ></i>
                            </div>
                        {/if}
                        <div>
                            <h4 class="font-semibold text-slate-100 text-base">
                                {item.product?.name}
                            </h4>
                            <p
                                class="{context === 'dashboard'
                                    ? 'text-sm'
                                    : 'text-xs'} text-slate-400 mt-1"
                            >
                                Jumlah: <span class="font-black text-[#FFD700]"
                                    >{item.quantity}x</span
                                >
                            </p>
                            {#if item.note}
                                <p
                                    class="text-[10px] font-black tracking-wider uppercase text-[#FFD700] bg-[#FFD700]/10 px-3 py-1 rounded-full mt-3 inline-flex items-center gap-2 border border-[#FFD700]/20"
                                >
                                    <i class="fa-solid fa-comment-dots"></i>
                                    {item.note}
                                </p>
                            {/if}
                        </div>
                    </div>

                    <div class="shrink-0">
                        <Badge
                            variant={getStatusVariant(item.chef_status)}
                            size="sm"
                        >
                            {#if item.chef_status === "shipped"}
                                <i class="fa-solid fa-truck mr-1"></i>
                            {/if}
                            {getStatusLabel(item.chef_status)}
                        </Badge>
                    </div>
                </div>

                {#if item.chef_status === "pending" || (item.chef_status === "accepted" && (group.order.order_status === "confirmed" || group.order.order_status === "shipped") && isAllItemsApproved(group.order))}
                    <div class="flex flex-col sm:flex-row gap-2 w-full mt-1">
                        {#if item.chef_status === "pending"}
                            <Button
                                variant="success"
                                size="sm"
                                class="w-full flex-1"
                                icon="fa-solid fa-check"
                                onclick={() => onApprove?.(item.id)}
                            >
                                Terima
                            </Button>
                            <Button
                                variant="outline-danger"
                                size="sm"
                                class="w-full flex-1"
                                icon="fa-solid fa-xmark"
                                onclick={() => onReject?.(item.id)}
                            >
                                Tolak
                            </Button>
                        {:else if item.chef_status === "accepted"}
                            <Button
                                variant="primary"
                                size="sm"
                                class="w-full"
                                icon="fa-solid fa-truck"
                                fullWidth
                                onclick={() => onShip?.(item.id)}
                            >
                                Kirim ke Pickup Point
                            </Button>
                        {/if}
                    </div>
                {/if}
            </div>
        {/each}
    </div>
</div>
