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
            title: "Selesaikan Pesanan",
            message: `Apakah Anda yakin ingin menandai pesanan ${order.number} telah selesai dan diterima oleh pelanggan?`,
            confirmText: "Ya, Selesaikan",
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
    class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
>
    <!-- Header -->
    <div class="bg-gray-50/50 p-4 border-b border-gray-100">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <Badge variant="info" size="sm" outlined
                        >{order.number}</Badge
                    >
                    {#if isPreOrder()}
                        <Badge variant="warning" size="sm">Pre-Order</Badge>
                    {:else}
                        <Badge variant="primary" size="sm">Instant</Badge>
                    {/if}
                </div>
                <p class="text-sm font-medium text-gray-900">
                    {order.customer?.name}
                    {#if order.customer?.phone}
                        <span class="text-gray-400 mx-1">•</span>
                        <span class="text-gray-500 font-normal"
                            >{order.customer.phone}</span
                        >
                    {/if}
                </p>
            </div>
            <div>
                <span class="text-xs text-gray-400 block mb-0.5"
                    >Pengiriman</span
                >
                <span class="text-sm font-semibold text-gray-900">
                    {dayjs(order.delivery_date).format("D MMM YYYY")}
                    {#if order.delivery_time}
                        {order.delivery_time.includes("T")
                            ? new Date(order.delivery_time).toLocaleTimeString(
                                  "id-ID",
                                  { hour: "2-digit", minute: "2-digit" },
                              )
                            : order.delivery_time.substring(0, 5)}
                    {/if}
                </span>
            </div>
        </div>
    </div>

    <!-- Destination Info -->
    {#if destination && (tab === "at_pickup" || tab === "on_delivery" || tab === "completed")}
        <div class="bg-emerald-50/50 px-4 py-3 border-b border-emerald-100">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <i
                            class="fa-solid fa-location-dot text-emerald-500 text-sm"
                        ></i>
                        <span
                            class="text-xs font-bold text-emerald-600 uppercase"
                            >Tujuan Pengiriman</span
                        >
                    </div>
                    <p class="text-sm font-semibold text-gray-900">
                        {destination.label}
                    </p>
                    {#if destination.address}
                        <p class="text-xs text-gray-500 mt-0.5">
                            {destination.address}
                        </p>
                    {/if}
                </div>
                {#if destination.lat && destination.lng}
                    <button
                        onclick={() =>
                            openGoogleMaps(destination.lat, destination.lng)}
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-lg hover:bg-emerald-700 transition-colors"
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
        <div class="bg-violet-50/50 px-4 py-3 border-b border-violet-100">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-violet-500"></i>
                    <span class="text-sm font-medium text-violet-700">
                        {order.shippings?.[0]?.courier_company ?? "Kurir"}
                    </span>
                    {#if order.shippings?.[0]?.biteship_status}
                        <Badge variant="info" size="sm"
                            >{order.shippings[0].biteship_status}</Badge
                        >
                    {/if}
                </div>
                <a
                    href={trackingUrl}
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-600 text-white text-xs font-medium rounded-lg hover:bg-violet-700 transition-colors"
                >
                    <i class="fa-solid fa-external-link-alt"></i>
                    Lacak Pengiriman
                </a>
            </div>
        </div>
    {/if}

    <!-- Items -->
    <div class="divide-y divide-gray-100">
        {#each order.items ?? [] as item}
            <div class="p-4 flex items-center gap-4">
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
                        <i class="fa-solid fa-bowl-food text-gray-300"></i>
                    </div>
                {/if}
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-900 text-sm truncate">
                        {item.product?.name}
                    </h4>
                    <p class="text-xs text-gray-500">
                        Jumlah: <span class="font-semibold"
                            >{item.quantity}x</span
                        >
                    </p>
                    {#if item.note}
                        <p
                            class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded mt-1 inline-block"
                        >
                            <i class="fa-solid fa-comment-dots mr-1"
                            ></i>{item.note}
                        </p>
                    {/if}
                </div>
            </div>
        {/each}
    </div>

    <!-- Actions -->
    <div
        class="p-4 border-t border-gray-100 bg-gray-50/30 flex flex-wrap gap-2 justify-end"
    >
        {#if tab === "incoming"}
            <Button
                variant="success"
                size="sm"
                icon="fa-solid fa-check-circle"
                disabled={isProcessing}
                onclick={confirmApprove}
            >
                Konfirmasi Sampai
            </Button>
        {:else if tab === "at_pickup"}
            {#if isPreOrder()}
                <Button
                    variant="primary"
                    size="sm"
                    icon="fa-solid fa-truck"
                    disabled={isProcessing}
                    onclick={confirmSend}
                >
                    Kirim ke Drop Point
                </Button>
            {:else}
                <Button
                    variant="primary"
                    size="sm"
                    icon="fa-solid fa-motorcycle"
                    disabled={isProcessing}
                    onclick={confirmSend}
                >
                    Pesan Kurir (Grab/Gojek)
                </Button>
            {/if}
        {:else if tab === "on_delivery"}
            {#if isPreOrder()}
                <Button
                    variant="success"
                    size="sm"
                    icon="fa-solid fa-circle-check"
                    disabled={isProcessing}
                    onclick={confirmComplete}
                >
                    Tandai Selesai
                </Button>
            {:else}
                <Badge variant="info" size="sm">
                    <i class="fa-solid fa-clock mr-1"></i>
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
