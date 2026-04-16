<script lang="ts">
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";
    import { router } from "@inertiajs/svelte";

    dayjs.locale(id);

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
            phone?: string;
        };
        drop_point?: {
            name: string;
            address?: string;
            latitude?: number;
            longitude?: number;
        };
        customer_address?: {
            address: string;
            latitude?: number;
            longitude?: number;
        };
        pick_up_point?: PickUpPoint;
        items?: {
            id: string;
            quantity: number;
            note?: string;
            chef_status: string;
            product?: {
                name: string;
                image?: string;
            };
        }[];
        shippings?: {
            id: string;
            biteship_tracking_id?: string;
            biteship_status?: string;
            courier_company?: string;
        }[];
    }

    let {
        order,
        tab = "incoming",
        processing = false,
    } = $props<{
        order: Order;
        tab?: "incoming" | "at_pickup" | "on_delivery" | "completed";
        processing?: boolean;
    }>();

    let isProcessing = $state(false);

    let confirmDialog = $state({
        isOpen: false,
        type: "info" as "info" | "warning" | "danger" | "success",
        title: "",
        message: "",
        confirmText: "Ya, Lanjutkan",
        action: null as "approve" | "send" | "complete" | null,
    });

    function openGoogleMaps(lat?: number, lng?: number) {
        if (lat && lng) {
            window.open(
                `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`,
                "_blank",
            );
        }
    }

    function executePendingAction(): Promise<void> {
        return new Promise((resolve) => {
            if (!confirmDialog.action || isProcessing) {
                resolve();
                return;
            }

            isProcessing = true;

            let endpoint = "";
            if (confirmDialog.action === "approve") {
                endpoint = `/pic/orders/${order.id}/approve`;
            } else if (confirmDialog.action === "send") {
                endpoint = `/pic/orders/${order.id}/send`;
            } else if (confirmDialog.action === "complete") {
                endpoint = `/pic/orders/${order.id}/complete`;
            }

            router.post(
                endpoint,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        isProcessing = false;
                        confirmDialog.action = null;
                        resolve();
                    },
                },
            );
        });
    }

    function confirmApprove() {
        confirmDialog = {
            isOpen: true,
            type: "success",
            title: "Konfirmasi Pesanan Datang",
            message: `Apakah Anda yakin pesanan ${order.number} telah sampai di titik kumpul dengan aman dan lengkap?`,
            confirmText: "Ya, Pesanan Sampai",
            action: "approve",
        };
    }

    function confirmSend() {
        const isPre = isPreOrder();
        confirmDialog = {
            isOpen: true,
            type: "info",
            title: isPre ? "Kirim ke Drop Point" : "Pesan Kurir",
            message: isPre
                ? `Apakah Anda yakin ingin mengirim pesanan ${order.number} ke drop point sekarang?`
                : `Apakah Anda yakin ingin memesan kurir (Grab/Gojek) untuk pesanan ${order.number} sekarang?`,
            confirmText: isPre ? "Ya, Kirim Pesanan" : "Ya, Pesan Kurir",
            action: "send",
        };
    }

    function confirmComplete() {
        confirmDialog = {
            isOpen: true,
            type: "success",
            title: "Konfirmasi Pesanan Tiba",
            message: `Apakah Anda yakin pesanan ${order.number} telah sampai di lokasi tujuan?`,
            confirmText: "Ya, Pesanan Sampai",
            action: "complete",
        };
    }

    function getDeliveryDestination() {
        if (order.drop_point) {
            return {
                label: `Drop Point: ${order.drop_point.name}`,
                address: order.drop_point.address,
                lat: order.drop_point.latitude,
                lng: order.drop_point.longitude,
                type: "preorder" as const,
            };
        }
        if (order.customer_address) {
            return {
                label: "Alamat Customer",
                address: order.customer_address.address,
                lat: order.customer_address.latitude,
                lng: order.customer_address.longitude,
                type: "instant" as const,
            };
        }
        return null;
    }

    function getTrackingUrl() {
        const shipping = order.shippings?.[0];
        if (shipping?.biteship_tracking_id) {
            return `https://biteship.com/tracking/${shipping.biteship_tracking_id}`;
        }
        return null;
    }

    const destination = $derived(getDeliveryDestination());
    const trackingUrl = $derived(getTrackingUrl());

    function isPreOrder(): boolean {
        return order.drop_point !== null && order.drop_point !== undefined;
    }
</script>

<div
    class="bg-slate-950 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden hover:border-[#FFD700]/30 transition-all duration-300"
>
    <!-- Header -->
    <div class="bg-slate-950/50 p-6 border-b border-slate-800">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <Badge
                        variant="primary"
                        size="sm"
                        class="bg-[#FFD700] text-slate-900 font-black border-none"
                        >{order.number}</Badge
                    >
                    {#if isPreOrder()}
                        <Badge
                            variant="warning"
                            size="sm"
                            class="bg-amber-900/20 text-amber-400 border border-amber-500/20 font-black uppercase tracking-widest text-[10px]"
                            >Pre-Order</Badge
                        >
                    {:else}
                        <Badge
                            variant="primary"
                            size="sm"
                            class="bg-blue-900/20 text-blue-400 border border-blue-500/20 font-black uppercase tracking-widest text-[10px]"
                            >Instant</Badge
                        >
                    {/if}
                </div>
                <p class="text-base font-black text-slate-100">
                    {order.customer?.name}
                    {#if order.customer?.phone}
                        <span class="text-slate-600 mx-2">•</span>
                        <span class="text-slate-400 font-medium"
                            >{order.customer.phone}</span
                        >
                    {/if}
                </p>
            </div>
            <div class="text-right">
                <span
                    class="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em] block mb-1"
                    >Pengiriman</span
                >
                <span class="text-sm font-bold text-slate-200">
                    {dayjs(order.delivery_date).format("D MMM YYYY")}
                    {#if order.delivery_time}
                        <span class="text-slate-600 mx-1">•</span>
                        <span class="text-[#FFD700] font-black">
                            {order.delivery_time.includes("T")
                                ? new Date(
                                      order.delivery_time,
                                  ).toLocaleTimeString("id-ID", {
                                      hour: "2-digit",
                                      minute: "2-digit",
                                  })
                                : order.delivery_time.substring(0, 5)} WIB
                        </span>
                    {/if}
                </span>
            </div>
        </div>
    </div>

    <!-- Destination Info -->
    {#if destination && (tab === "at_pickup" || tab === "on_delivery" || tab === "completed")}
        <div class="bg-emerald-900/10 px-6 py-4 border-b border-emerald-900/20">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <i
                            class="fa-solid fa-location-dot text-emerald-400 text-sm"
                        ></i>
                        <span
                            class="text-[10px] font-black text-emerald-400 uppercase tracking-widest"
                            >Tujuan Pengiriman</span
                        >
                    </div>
                    <p class="text-base font-black text-slate-100">
                        {destination.label}
                    </p>
                    {#if destination.address}
                        <p class="text-xs text-slate-500 mt-1">
                            {destination.address}
                        </p>
                    {/if}
                </div>
                {#if destination.lat && destination.lng}
                    <button
                        onclick={() =>
                            openGoogleMaps(destination.lat, destination.lng)}
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-xs font-black rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20"
                    >
                        <i class="fa-solid fa-map-location-dot"></i>
                        Google Maps
                    </button>
                {/if}
            </div>
        </div>
    {/if}

    <!-- Tracking Info -->
    {#if (tab === "on_delivery" || tab === "completed") && trackingUrl}
        <div class="bg-violet-900/10 px-6 py-4 border-b border-violet-900/20">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-violet-900/20 text-violet-400 rounded-xl flex items-center justify-center"
                    >
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <span class="text-sm font-black text-slate-100 block">
                            {order.shippings?.[0]?.courier_company ?? "Kurir"}
                        </span>
                        {#if order.shippings?.[0]?.biteship_status}
                            <span
                                class="text-[10px] font-black text-violet-400 uppercase tracking-widest"
                            >
                                {order.shippings[0].biteship_status}
                            </span>
                        {/if}
                    </div>
                </div>
                <a
                    href={trackingUrl}
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 text-white text-xs font-black rounded-xl hover:bg-violet-700 transition-all shadow-lg shadow-violet-600/20"
                >
                    <i class="fa-solid fa-external-link-alt"></i>
                    Lacak Pengiriman
                </a>
            </div>
        </div>
    {/if}

    <!-- Items -->
    <div class="divide-y divide-slate-800">
        {#each order.items ?? [] as item}
            <div
                class="p-6 flex items-center gap-6 hover:bg-slate-800/20 transition-colors"
            >
                {#if item.product?.image}
                    <img
                        src={item.product.image}
                        alt={item.product.name}
                        class="w-16 h-16 rounded-2xl object-cover border border-slate-800 shadow-xl"
                    />
                {:else}
                    <div
                        class="w-16 h-16 rounded-2xl bg-slate-800 flex items-center justify-center border border-slate-700"
                    >
                        <i class="fa-solid fa-bowl-food text-slate-600 text-xl"
                        ></i>
                    </div>
                {/if}
                <div class="flex-1 min-w-0">
                    <h4 class="font-black text-slate-100 text-base truncate">
                        {item.product?.name}
                    </h4>
                    <p class="text-xs text-slate-500 mt-1">
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
        {/each}
    </div>

    <!-- Actions -->
    <div
        class="p-6 border-t border-slate-800 bg-slate-950/50 flex flex-wrap gap-3 justify-end"
    >
        {#if tab === "incoming"}
            <Button
                variant="success"
                size="lg"
                icon="fa-solid fa-check-circle"
                disabled={isProcessing}
                onclick={confirmApprove}
                class="rounded-2xl font-black bg-emerald-600 hover:bg-emerald-700 text-white"
            >
                Konfirmasi Sampai
            </Button>
        {:else if tab === "at_pickup"}
            {#if isPreOrder()}
                <Button
                    variant="primary"
                    size="lg"
                    icon="fa-solid fa-truck"
                    disabled={isProcessing}
                    onclick={confirmSend}
                    class="rounded-2xl font-black bg-blue-600 hover:bg-blue-700 text-white"
                >
                    Kirim ke Drop Point
                </Button>
            {:else}
                <Button
                    variant="primary"
                    size="lg"
                    icon="fa-solid fa-motorcycle"
                    disabled={isProcessing}
                    onclick={confirmSend}
                    class="rounded-2xl font-black bg-blue-600 hover:bg-blue-700 text-white"
                >
                    Pesan Kurir (Grab/Gojek)
                </Button>
            {/if}
        {:else if tab === "on_delivery"}
            {#if order.order_status === "arrived"}
                <Badge
                    variant="info"
                    size="lg"
                    class="rounded-full bg-blue-900/20 text-blue-400 border border-blue-500/20 font-black px-6 py-2"
                >
                    <i class="fa-solid fa-clock mr-2"></i>
                    Menunggu Konfirmasi Pelanggan
                </Badge>
            {:else if isPreOrder()}
                <Button
                    variant="success"
                    size="lg"
                    icon="fa-solid fa-circle-check"
                    disabled={isProcessing}
                    onclick={confirmComplete}
                    class="rounded-2xl font-black bg-emerald-600 hover:bg-emerald-700 text-white"
                >
                    Pesanan Tiba
                </Button>
            {:else}
                <Badge
                    variant="info"
                    size="lg"
                    class="rounded-full bg-blue-900/20 text-blue-400 border border-blue-500/20 font-black px-6 py-2"
                >
                    <i class="fa-solid fa-clock mr-2"></i>
                    Menunggu Konfirmasi Kurir
                </Badge>
            {/if}
        {/if}
    </div>
</div>

<!-- Dialog Konfirmasi -->
<Dialog
    bind:isOpen={confirmDialog.isOpen}
    type={confirmDialog.type}
    title={confirmDialog.title}
    message={confirmDialog.message}
    confirmText={confirmDialog.confirmText}
    cancelText="Batal"
    loading={isProcessing}
    onConfirm={executePendingAction}
/>
