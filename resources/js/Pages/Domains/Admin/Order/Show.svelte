<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    interface Product {
        id: string;
        name: string;
        image_url: string;
    }

    interface OrderItemOption {
        id: string;
        product_option: { name: string };
        product_option_item: { name: string };
        extra_price: number;
    }

    interface OrderItem {
        id: string;
        product: Product;
        quantity: number;
        price: number;
        subtotal: number;
        note?: string;
        options: OrderItemOption[];
        testimonial?: {
            id: string;
            rating: string;
            content: string;
            photo_url: string;
            is_approved: boolean;
            created_at: string;
        };
    }

    interface Order {
        id: string;
        number: string;
        customer: { name: string; email: string; phone: string };
        drop_point?: { name: string; address: string };
        customer_address?: {
            name: string;
            address: string;
            latitude?: number;
            longitude?: number;
            note?: string;
        };
        payment_method?: { name: string; category: string };
        delivery_date: string;
        delivery_time: string;
        shipping_method: string;
        order_status: string;
        payment_status: string;
        total_amount: number;
        discount_amount: number;
        delivery_fee: number;
        admin_fee: number;
        tax_amount: number;
        payment_expired_at?: string;
        note?: string;
        cancellation_note?: string;
        payment_proof_url?: string;
        delivery_photo_url?: string;
        delivered_at?: string;
        created_at: string;
        items: (OrderItem & {
            chef?: { id: string; name: string };
            chef_status: string;
            chef_confirmed_at?: string;
        })[];
        shippings: Array<{
            chef?: { name: string };
            courier_company: string;
            courier_name: string;
            shipping_fee: number;
            biteship_status?: string;
            biteship_waybill_id?: string;
        }>;
        pick_up_point?: {
            id: string;
            name: string;
            address: string;
            latitude?: number;
            longitude?: number;
        };
        pick_up_point_id?: string;
        chef_status_summary?: string;
    }

    interface Chef {
        id: string;
        name: string;
    }

    interface PickUpPointOption {
        id: string;
        name: string;
        address: string;
    }

    let {
        order: orderProp,
        chefs = [],
        pickUpPoints = [],
        canChangePickUpPoint = false,
    }: {
        order: Order;
        chefs: Chef[];
        pickUpPoints: PickUpPointOption[];
        canChangePickUpPoint: boolean;
    } = $props();
    let order = $derived(orderProp);

    interface ConfirmDialog {
        open: boolean;
        title: string;
        message: string;
        action: (() => void) | null;
        variant: "danger" | "primary" | "success" | "warning";
    }

    let confirmDialog = $state<ConfirmDialog>({
        open: false,
        title: "",
        message: "",
        action: null,
        variant: "primary",
    });

    let isProcessing = $state(false);
    let cancelModalOpen = $state(false);
    let cancelNote = $state("");
    let deliverModalOpen = $state(false);
    let deliveryPhotoFile = $state<File | null>(null);
    let deliveryPhotoError = $state<string | undefined>(undefined);
    let isMediaViewerOpen = $state(false);
    let mediaViewerItems = $state<string | string[]>([]);
    let mediaViewerInitialIndex = $state(0);

    let reassignModalOpen = $state(false);
    let selectedItemForReassign = $state<any>(null);
    let selectedNewChefId = $state("");

    let selectedPickUpPointId = $state("");
    $effect(() => {
        selectedPickUpPointId = order.pick_up_point_id ?? "";
    });
    let isPickupUpdating = $state(false);

    function openMediaViewer(items: string | string[], index: number = 0) {
        mediaViewerItems = items;
        mediaViewerInitialIndex = index;
        isMediaViewerOpen = true;
    }

    function submitDeliver() {
        if (!deliveryPhotoFile) {
            deliveryPhotoError = undefined;
            return;
        }
        isProcessing = true;
        const formData = new FormData();
        formData.append("delivery_photo", deliveryPhotoFile);
        router.post(`/admin/orders/${order.id}/deliver`, formData, {
            forceFormData: true,
            onFinish: () => {
                isProcessing = false;
                deliverModalOpen = false;
                deliveryPhotoFile = null;
            },
        });
    }

    function openConfirm(
        title: string,
        message: string,
        action: () => void,
        variant: ConfirmDialog["variant"] = "primary",
    ) {
        confirmDialog = { open: true, title, message, action, variant };
    }

    function closeConfirm() {
        confirmDialog = { ...confirmDialog, open: false, action: null };
    }

    function executeAction() {
        if (!confirmDialog.action) return;
        isProcessing = true;
        confirmDialog.action();
        closeConfirm();
    }

    function postAction(routeName: string) {
        router.post(
            `/admin/orders/${order.id}/${routeName}`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }

    function formatCurrency(amount: number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function approveTestimonial(testimonialId: string) {
        isProcessing = true;
        router.patch(
            `/admin/testimonials/${testimonialId}/approve`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }

    function rejectTestimonial(testimonialId: string) {
        if (!confirm("Apakah Anda yakin ingin menghapus testimoni ini?"))
            return;
        isProcessing = true;
        router.delete(`/admin/testimonials/${testimonialId}`, {
            onFinish: () => {
                isProcessing = false;
            },
        });
    }

    function openReassignModal(item: any) {
        selectedItemForReassign = item;
        selectedNewChefId = item.chef?.id || "";
        reassignModalOpen = true;
    }

    function submitReassign() {
        if (!selectedNewChefId) return;
        isProcessing = true;
        router.post(
            `/admin/order-items/${selectedItemForReassign.id}/reassign-chef`,
            {
                chef_id: selectedNewChefId,
            },
            {
                onFinish: () => {
                    isProcessing = false;
                    reassignModalOpen = false;
                },
            },
        );
    }

    function submitPickUpPointChange() {
        if (!selectedPickUpPointId) return;
        isPickupUpdating = true;
        router.patch(
            `/admin/orders/${order.id}/pickup-point`,
            { pick_up_point_id: selectedPickUpPointId },
            {
                onFinish: () => {
                    isPickupUpdating = false;
                },
            },
        );
    }

    type BadgeVariant =
        | "dark"
        | "light"
        | "success"
        | "warning"
        | "info"
        | "primary"
        | "danger"
        | "white"
        | "secondary"
        | "purple";

    function getStatusBadge(status: string): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Menunggu" };
            case "confirmed":
                return { variant: "info", label: "Dikonfirmasi" };
            case "shipped":
                return { variant: "primary", label: "Dikirim ke Pickup" };
            case "at_pickup_point":
                return { variant: "info", label: "Di Pickup Point" };
            case "on_delivery":
                return { variant: "primary", label: "Sedang Dikirim" };
            case "delivered":
                return { variant: "success", label: "Selesai" };
            case "cancelled":
                return { variant: "danger", label: "Dibatalkan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    function getPaymentBadge(status: string): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Belum Bayar" };
            case "paid":
                return { variant: "success", label: "Lunas" };
            case "failed":
                return { variant: "danger", label: "Gagal" };
            case "refunded":
                return { variant: "info", label: "Dikembalikan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    const itemsByChef = $derived.by(() => {
        const groups: Record<string, { chef: any; items: any[] }> = {};

        order.items.forEach((item: any) => {
            const chefId = item.chef?.id || "unknown";
            if (!groups[chefId]) {
                groups[chefId] = {
                    chef: item.chef,
                    items: [],
                };
            }
            groups[chefId].items.push(item);
        });

        return Object.values(groups);
    });
</script>

<svelte:head>
    <title>Detail Pesanan {order.number} | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <div class="flex items-center gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    icon="fa-solid fa-arrow-left"
                    href="/admin/orders"
                />
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Detail Pesanan
                </h1>
            </div>
            <p class="mt-2 text-gray-600 dark:text-gray-400">#{order.number}</p>
        </div>
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex flex-col gap-1.5">
                <span
                    class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                    Status Pesanan
                </span>
                <Badge
                    size="sm"
                    variant={getStatusBadge(order.order_status).variant}
                    outlined={true}
                    dot={true}
                    rounded="pill"
                    pulse={order.order_status === "pending"}
                >
                    {#snippet children()}{getStatusBadge(order.order_status)
                            .label}{/snippet}
                </Badge>
            </div>
            <div class="flex flex-col gap-1.5">
                <span
                    class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                    Status Pembayaran
                </span>
                <Badge
                    size="sm"
                    variant={getPaymentBadge(order.payment_status).variant}
                    outlined={true}
                    dot={true}
                    rounded="pill"
                    pulse={order.payment_status === "pending"}
                >
                    {#snippet children()}{getPaymentBadge(order.payment_status)
                            .label}{/snippet}
                </Badge>
            </div>

            <!-- Status Action Buttons -->
            <div class="flex flex-wrap gap-2">
                {#if order.order_status === "pending"}
                    <Button
                        variant="primary"
                        icon="fa-solid fa-check"
                        disabled={isProcessing}
                        onclick={() =>
                            openConfirm(
                                "Konfirmasi Pesanan",
                                `Apakah Anda yakin ingin mengkonfirmasi pesanan #${order.number}?`,
                                () => postAction("confirm"),
                                "primary",
                            )}
                    >
                        {#snippet children()}Konfirmasi{/snippet}
                    </Button>
                    <Button
                        variant="danger"
                        icon="fa-solid fa-xmark"
                        disabled={isProcessing}
                        onclick={() => {
                            cancelNote = "";
                            cancelModalOpen = true;
                        }}
                    >
                        {#snippet children()}Batalkan{/snippet}
                    </Button>
                {/if}
            </div>
        </div>
    </header>

    {#if order.chef_status_summary === "rejected"}
        <div
            class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                >
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h4 class="font-bold text-red-800 dark:text-red-300">
                        Konfirmasi Dapur Ditolak
                    </h4>
                    <p class="text-sm text-red-700 dark:text-red-400">
                        Salah satu atau lebih item dalam pesanan ini ditolak
                        oleh Dapur. Silakan lakukan pemindahan (reassign) ke
                        Dapur lain agar pesanan bisa diproses.
                    </p>
                </div>
            </div>
        </div>
    {:else if order.chef_status_summary === "pending" || order.chef_status_summary === "partial"}
        <div
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"
                >
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <h4 class="font-bold text-amber-800 dark:text-amber-300">
                        Menunggu Konfirmasi Dapur
                    </h4>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        Ada {order.items.filter(
                            (i: any) => i.chef_status === "pending",
                        ).length} item yang masih menunggu konfirmasi dari Dapur.
                    </p>
                </div>
            </div>
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <Card title="Item Pesanan" bodyWithoutPadding={true}>
                <div class="overflow-x-auto">
                    <table class="custom-table min-w-full">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Dapur</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each itemsByChef as group}
                                <tr class="bg-gray-50/50 dark:bg-gray-800/20">
                                    <td
                                        colspan="5"
                                        class="py-2 px-4 border-b border-gray-100 dark:border-gray-800"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <i
                                                    class="fa-solid fa-kitchen-set text-indigo-500 text-xs"
                                                ></i>
                                                <span
                                                    class="text-sm font-bold text-gray-900 dark:text-white"
                                                >
                                                    Dapur: {group.chef?.name ||
                                                        "Lainnya"}
                                                </span>
                                            </div>
                                            {#if group.chef}
                                                <Badge
                                                    size="xs"
                                                    variant={group.items.every(
                                                        (i) =>
                                                            i.chef_status ===
                                                            "delivered",
                                                    )
                                                        ? "success"
                                                        : group.items.some(
                                                                (i) =>
                                                                    i.chef_status ===
                                                                    "shipped",
                                                            )
                                                          ? "primary"
                                                          : group.items.some(
                                                                  (i) =>
                                                                      i.chef_status ===
                                                                      "accepted",
                                                              )
                                                            ? "info"
                                                            : "warning"}
                                                    dot={true}
                                                >
                                                    {#snippet children()}
                                                        {group.items.every(
                                                            (i) =>
                                                                i.chef_status ===
                                                                "delivered",
                                                        )
                                                            ? "Selesai"
                                                            : group.items.some(
                                                                    (i) =>
                                                                        i.chef_status ===
                                                                        "shipped",
                                                                )
                                                              ? "Dikirim"
                                                              : group.items.some(
                                                                      (i) =>
                                                                          i.chef_status ===
                                                                          "accepted",
                                                                  )
                                                                ? "Diproses"
                                                                : "Menunggu"}
                                                    {/snippet}
                                                </Badge>
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                                {#each group.items as item}
                                    <tr>
                                        <td class="pl-8">
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                {#if item.product.image_url}
                                                    <img
                                                        src={item.product
                                                            .image_url}
                                                        alt={item.product.name}
                                                        class="w-10 h-10 object-cover rounded shadow-sm"
                                                    />
                                                {/if}
                                                <div>
                                                    <div
                                                        class="font-medium text-gray-900 dark:text-white"
                                                    >
                                                        {item.product.name}
                                                    </div>
                                                    {#if item.options.length > 0}
                                                        <div
                                                            class="text-[10px] text-gray-500 mt-0.5"
                                                        >
                                                            {#each item.options as opt}
                                                                <span>
                                                                    {opt
                                                                        .product_option
                                                                        .name}: {opt
                                                                        .product_option_item
                                                                        .name}
                                                                    {#if opt.extra_price > 0}
                                                                        (+{formatCurrency(
                                                                            opt.extra_price,
                                                                        )})
                                                                    {/if}
                                                                </span>
                                                                {#if opt !== item.options[item.options.length - 1]}<span
                                                                        class="mx-1"
                                                                        >|</span
                                                                    >{/if}
                                                            {/each}
                                                        </div>
                                                    {/if}
                                                    {#if item.note}
                                                        <div
                                                            class="text-[10px] text-amber-600 mt-0.5 italic"
                                                        >
                                                            Catatan: {item.note}
                                                        </div>
                                                    {/if}
                                                </div>
                                            </div>

                                            {#if item.testimonial}
                                                <!-- Testimonial UI remain same -->
                                                <div
                                                    class="mt-2 p-2 bg-gray-50 dark:bg-gray-800/50 rounded border border-gray-100 dark:border-gray-700/50 max-w-sm ml-10"
                                                >
                                                    <div
                                                        class="flex items-center justify-between mb-1"
                                                    >
                                                        <div
                                                            class="flex items-center gap-0.5"
                                                        >
                                                            {#each Array(5) as _, i}
                                                                <i
                                                                    class="fa-solid fa-star text-[8px] {i <
                                                                    parseInt(
                                                                        item
                                                                            .testimonial!
                                                                            .rating,
                                                                    )
                                                                        ? 'text-yellow-400'
                                                                        : 'text-gray-200'}"
                                                                ></i>
                                                            {/each}
                                                        </div>
                                                        <Badge
                                                            size="xs"
                                                            variant={item
                                                                .testimonial!
                                                                .is_approved
                                                                ? "success"
                                                                : "warning"}
                                                        >
                                                            {#snippet children()}{item
                                                                    .testimonial!
                                                                    .is_approved
                                                                    ? "Disetujui"
                                                                    : "Menunggu"}{/snippet}
                                                        </Badge>
                                                    </div>
                                                    <p
                                                        class="text-[10px] text-gray-600 dark:text-gray-400 italic"
                                                    >
                                                        "{item.testimonial
                                                            .content ||
                                                            "Tanpa komentar"}"
                                                    </p>
                                                    <div
                                                        class="flex gap-1 mt-2"
                                                    >
                                                        {#if !item.testimonial.is_approved}
                                                            <button
                                                                class="text-[10px] text-green-600 font-bold"
                                                                onclick={() =>
                                                                    approveTestimonial(
                                                                        item
                                                                            .testimonial!
                                                                            .id,
                                                                    )}
                                                                >Setujui</button
                                                            >
                                                        {/if}
                                                        <button
                                                            class="text-[10px] text-red-600 font-bold"
                                                            onclick={() =>
                                                                rejectTestimonial(
                                                                    item
                                                                        .testimonial!
                                                                        .id,
                                                                )}>Hapus</button
                                                        >
                                                    </div>
                                                </div>
                                            {/if}
                                        </td>
                                        <td class="text-center">
                                            <div
                                                class="flex flex-col items-center gap-1"
                                            >
                                                <Badge
                                                    size="xs"
                                                    variant={item.chef_status ===
                                                    "delivered"
                                                        ? "success"
                                                        : item.chef_status ===
                                                            "shipped"
                                                          ? "primary"
                                                          : item.chef_status ===
                                                              "accepted"
                                                            ? "info"
                                                            : item.chef_status ===
                                                                "rejected"
                                                              ? "danger"
                                                              : "warning"}
                                                    dot={true}
                                                >
                                                    {#snippet children()}
                                                        {item.chef_status ===
                                                        "delivered"
                                                            ? "Selesai"
                                                            : item.chef_status ===
                                                                "shipped"
                                                              ? "Dikirim"
                                                              : item.chef_status ===
                                                                  "accepted"
                                                                ? "Diterima"
                                                                : item.chef_status ===
                                                                    "rejected"
                                                                  ? "Ditolak"
                                                                  : "Menunggu"}
                                                    {/snippet}
                                                </Badge>
                                                {#if order.order_status === "confirmed"}
                                                    <button
                                                        class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium underline"
                                                        onclick={() =>
                                                            openReassignModal(
                                                                item,
                                                            )}
                                                    >
                                                        Ganti Dapur
                                                    </button>
                                                {/if}
                                            </div>
                                        </td>
                                        <td class="text-center text-sm"
                                            >{item.quantity}</td
                                        >
                                        <td class="text-right text-sm"
                                            >{formatCurrency(item.price)}</td
                                        >
                                        <td class="text-right text-sm font-bold"
                                            >{formatCurrency(item.subtotal)}</td
                                        >
                                    </tr>
                                {/each}
                            {/each}
                        </tbody>
                        <tfoot>
                            <tr
                                class="border-t border-gray-200 dark:border-gray-700"
                            >
                                <td
                                    colspan="3"
                                    class="text-right py-3 px-4 font-medium text-gray-600 dark:text-gray-400"
                                    >Total Item</td
                                >
                                <td
                                    class="text-right py-3 px-4 font-bold text-gray-900 dark:text-white"
                                >
                                    {formatCurrency(
                                        order.items.reduce(
                                            (acc: number, i: any) =>
                                                acc + (i.subtotal || 0),
                                            0,
                                        ),
                                    )}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card title="Informasi Pengiriman">
                    <div class="space-y-4">
                        <div>
                            <div
                                class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                {order.drop_point
                                    ? "Drop Point"
                                    : "Alamat Pengiriman"}
                            </div>
                            <div class="mt-1 text-gray-900 dark:text-white">
                                {order.drop_point?.name ||
                                    order.customer_address?.name ||
                                    "Tidak ada"}
                            </div>
                            <div class="text-sm text-gray-500">
                                {order.drop_point?.address ||
                                    order.customer_address?.address ||
                                    ""}
                            </div>
                            {#if order.customer_address?.note}
                                <div class="mt-1 text-xs text-amber-600 italic">
                                    Catatan: {order.customer_address.note}
                                </div>
                            {/if}
                            {#if order.customer_address?.latitude && order.customer_address?.longitude}
                                <div
                                    class="mt-1 text-[10px] text-blue-500 flex items-center gap-1"
                                >
                                    <i class="fa-solid fa-map-pin"></i>
                                    {order.customer_address.latitude}, {order
                                        .customer_address.longitude}
                                </div>
                            {/if}
                        </div>
                        <div>
                            <div
                                class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Jadwal Pengiriman
                            </div>
                            <div class="mt-1 text-gray-900 dark:text-white">
                                {order.delivery_date
                                    ? new Date(
                                          order.delivery_date,
                                      ).toLocaleDateString("id-ID", {
                                          weekday: "long",
                                          year: "numeric",
                                          month: "long",
                                          day: "numeric",
                                      })
                                    : "-"}
                            </div>
                            <div class="text-sm text-gray-500">
                                Pukul {order.delivery_time || "-"}
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Pickup Point Card -->
                <Card title="Pickup Point">
                    <div class="space-y-4">
                        {#if order.pick_up_point}
                            <div>
                                <div
                                    class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                                >
                                    Pickup Point Saat Ini
                                </div>
                                <div
                                    class="mt-1 font-medium text-gray-900 dark:text-white"
                                >
                                    {order.pick_up_point.name}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {order.pick_up_point.address}
                                </div>
                                {#if order.pick_up_point.latitude && order.pick_up_point.longitude}
                                    <a
                                        href="https://www.google.com/maps/dir/?api=1&destination={order
                                            .pick_up_point.latitude},{order
                                            .pick_up_point.longitude}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1 mt-2 text-xs text-blue-600 hover:text-blue-800"
                                    >
                                        <i class="fa-solid fa-map-location-dot"
                                        ></i>
                                        Buka Google Maps
                                    </a>
                                {/if}
                            </div>
                        {:else}
                            <div class="text-sm text-gray-400 italic">
                                Belum ada pickup point
                            </div>
                        {/if}

                        {#if canChangePickUpPoint && pickUpPoints.length > 0}
                            <div class="border-t border-gray-100 pt-3">
                                <label
                                    for="pickup-point-select"
                                    class="text-sm font-semibold text-gray-500 uppercase tracking-wider block mb-2"
                                >
                                    Ubah Pickup Point
                                </label>
                                <select
                                    id="pickup-point-select"
                                    bind:value={selectedPickUpPointId}
                                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value=""
                                        >-- Pilih Pickup Point --</option
                                    >
                                    {#each pickUpPoints as pp}
                                        <option value={pp.id}
                                            >{pp.name} - {pp.address}</option
                                        >
                                    {/each}
                                </select>
                                <Button
                                    variant="primary"
                                    size="sm"
                                    icon="fa-solid fa-save"
                                    disabled={!selectedPickUpPointId ||
                                        isPickupUpdating}
                                    loading={isPickupUpdating}
                                    onclick={submitPickUpPointChange}
                                    class="mt-2"
                                >
                                    {#snippet children()}Simpan{/snippet}
                                </Button>
                            </div>
                        {:else if !canChangePickUpPoint && order.pick_up_point}
                            <div
                                class="text-xs text-amber-600 bg-amber-50 p-2 rounded"
                            >
                                <i class="fa-solid fa-lock mr-1"></i>
                                Pickup point tidak dapat diubah karena semua item
                                sudah dikirim.
                            </div>
                        {/if}
                    </div>
                </Card>

                <Card title="Metode Pembayaran">
                    <div class="space-y-4">
                        <div>
                            <div
                                class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Metode
                            </div>
                            <div class="mt-1 text-gray-900 dark:text-white">
                                {order.payment_method?.name ?? "-"}
                            </div>
                            <div class="text-xs text-gray-500 uppercase">
                                {order.payment_method?.category || ""}
                            </div>
                        </div>
                        {#if order.note}
                            <div>
                                <div
                                    class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                                >
                                    Catatan Pesanan
                                </div>
                                <div
                                    class="mt-1 text-gray-900 dark:text-white italic"
                                >
                                    "{order.note}"
                                </div>
                            </div>
                        {/if}
                    </div>
                </Card>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <Card title="Pelanggan">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold"
                        >
                            {order.customer.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div
                                class="font-bold text-gray-900 dark:text-white"
                            >
                                {order.customer.name}
                            </div>
                            <div class="text-sm text-gray-500">
                                {order.customer.email}
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                    >
                        <i class="fa-solid fa-phone w-4"></i>
                        {order.customer.phone}
                    </div>
                </div>
            </Card>

            <Card title="Ringkasan Pembayaran">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400"
                            >Total Item</span
                        >
                        <span class="text-gray-900 dark:text-white"
                            >{formatCurrency(
                                order.items.reduce(
                                    (acc: number, i: any) =>
                                        acc + (i.subtotal || 0),
                                    0,
                                ),
                            )}</span
                        >
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400"
                            >Diskon</span
                        >
                        <span class="text-red-500 text-right"
                            >-{formatCurrency(order.discount_amount)}</span
                        >
                    </div>
                    {#if order.shippings && order.shippings.length > 0}
                        <div class="flex flex-col gap-1.5 pt-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Ongkos Kirim</span
                                >
                                <span
                                    class="text-gray-900 dark:text-white font-medium"
                                    >{formatCurrency(order.delivery_fee)}</span
                                >
                            </div>
                            <div
                                class="pl-2 border-l-2 border-gray-100 dark:border-gray-700 space-y-1"
                            >
                                {#each order.shippings as shipping}
                                    <div
                                        class="space-y-1 pb-2 mb-2 border-b border-gray-50 dark:border-gray-800 last:border-0 last:mb-0 last:pb-0"
                                    >
                                        <div
                                            class="flex justify-between items-center text-xs"
                                        >
                                            <span
                                                class="text-gray-500 dark:text-gray-400 font-medium"
                                            >
                                                <i
                                                    class="fa-solid fa-truck-fast text-[10px] mr-1 text-indigo-400"
                                                ></i>
                                                {shipping.chef?.name || "Dapur"}
                                                <span class="opacity-75"
                                                    >({shipping.courier_name})</span
                                                >
                                            </span>
                                            <span
                                                class="text-gray-700 dark:text-gray-300 font-bold"
                                            >
                                                {formatCurrency(
                                                    shipping.shipping_fee,
                                                )}
                                            </span>
                                        </div>

                                        {#if shipping.biteship_waybill_id}
                                            <div
                                                class="flex flex-col gap-1 mt-1"
                                            >
                                                <div
                                                    class="flex items-center justify-between text-[10px]"
                                                >
                                                    <span class="text-gray-400"
                                                        >No. Resi:</span
                                                    >
                                                    <span
                                                        class="text-indigo-600 dark:text-indigo-400 font-mono font-bold"
                                                    >
                                                        {shipping.biteship_waybill_id}
                                                    </span>
                                                </div>
                                                <div
                                                    class="flex items-center justify-between text-[10px]"
                                                >
                                                    <span class="text-gray-400"
                                                        >Status:</span
                                                    >
                                                    <Badge
                                                        size="xs"
                                                        variant={shipping.biteship_status ===
                                                        "delivered"
                                                            ? "success"
                                                            : "info"}
                                                        dot={true}
                                                    >
                                                        {#snippet children()}{shipping.biteship_status ||
                                                                "Pending"}{/snippet}
                                                    </Badge>
                                                </div>
                                                <a
                                                    href={`https://biteship.com/id/tracking/${shipping.biteship_waybill_id}`}
                                                    target="_blank"
                                                    class="text-[9px] text-center text-indigo-500 hover:text-indigo-700 underline mt-0.5"
                                                >
                                                    Lacak di Biteship
                                                </a>
                                            </div>
                                        {/if}
                                    </div>
                                {/each}
                            </div>
                        </div>
                    {:else}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Ongkos Kirim</span
                            >
                            <span class="text-gray-900 dark:text-white"
                                >{formatCurrency(order.delivery_fee)}</span
                            >
                        </div>
                    {/if}
                    {#if order.admin_fee > 0}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Biaya Admin</span
                            >
                            <span class="text-gray-900 dark:text-white"
                                >{formatCurrency(order.admin_fee)}</span
                            >
                        </div>
                    {/if}
                    {#if order.tax_amount > 0}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Pajak</span
                            >
                            <span class="text-gray-900 dark:text-white"
                                >{formatCurrency(order.tax_amount)}</span
                            >
                        </div>
                    {/if}
                    <div
                        class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between"
                    >
                        <span class="font-bold text-gray-900 dark:text-white"
                            >Total Bayar</span
                        >
                        <span class="font-bold text-indigo-600 text-lg"
                            >{formatCurrency(order.total_amount)}</span
                        >
                    </div>
                </div>
            </Card>

            <Card title="Waktu Transaksi">
                <div class="space-y-4">
                    <div>
                        <div
                            class="text-xs font-semibold text-gray-500 uppercase"
                        >
                            Dibuat Pada
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {new Date(order.created_at).toLocaleString(
                                "id-ID",
                                {
                                    year: "numeric",
                                    month: "long",
                                    day: "numeric",
                                    hour: "2-digit",
                                    minute: "2-digit",
                                },
                            )}
                        </div>
                    </div>
                    {#if order.payment_status === "pending" && order.payment_expired_at}
                        <div>
                            <div
                                class="text-xs font-semibold text-gray-500 uppercase"
                            >
                                Batas Waktu Bayar
                            </div>
                            <div class="text-sm text-amber-600 font-medium">
                                {new Date(
                                    order.payment_expired_at,
                                ).toLocaleString("id-ID", {
                                    year: "numeric",
                                    month: "long",
                                    day: "numeric",
                                    hour: "2-digit",
                                    minute: "2-digit",
                                })}
                            </div>
                        </div>
                    {/if}
                </div>
            </Card>

            {#if order.order_status === "cancelled" && order.cancellation_note}
                <Card title="Alasan Pembatalan">
                    <div
                        class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400 italic"
                    >
                        "{order.cancellation_note}"
                    </div>
                </Card>
            {/if}

            {#if order.payment_proof_url}
                <Card title="Bukti Pembayaran">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Bukti pembayaran yang diunggah oleh pelanggan.
                        </p>
                        <button
                            type="button"
                            class="block w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity text-left"
                            onclick={() =>
                                openMediaViewer(order.payment_proof_url!)}
                        >
                            <img
                                src={order.payment_proof_url}
                                alt="Bukti pembayaran pesanan #{order.number}"
                                class="w-full object-cover max-h-64"
                            />
                        </button>
                    </div>
                </Card>
            {/if}

            {#if order.order_status === "delivered" && order.delivery_photo_url}
                <Card title="Bukti Penerimaan">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Foto bukti pesanan diterima oleh pelanggan.
                        </p>
                        <button
                            type="button"
                            class="block w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity text-left"
                            onclick={() =>
                                openMediaViewer(order.delivery_photo_url!)}
                        >
                            <img
                                src={order.delivery_photo_url}
                                alt="Bukti penerimaan pesanan #{order.number}"
                                class="w-full object-cover max-h-64"
                            />
                        </button>
                    </div>
                </Card>
            {/if}
        </div>
    </div>
</section>

<!-- Deliver Order Modal (Upload Bukti Foto) -->
{#if deliverModalOpen}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deliver-modal-title"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400"
                >
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div class="flex-1">
                    <h3
                        id="deliver-modal-title"
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        Upload Bukti Penerimaan
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Upload foto sebagai bukti bahwa pesanan
                        <strong>#{order.number}</strong> telah diterima oleh pelanggan.
                    </p>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                <FileUpload
                    id="delivery_photo"
                    name="delivery_photo"
                    label="Foto Bukti Penerimaan"
                    required={true}
                    accept="image/*"
                    bind:value={deliveryPhotoFile}
                    error={deliveryPhotoError}
                />
            </div>

            <div class="mt-5 flex justify-end gap-3">
                <Button
                    variant="secondary"
                    size="sm"
                    onclick={() => (deliverModalOpen = false)}
                    disabled={isProcessing}
                >
                    {#snippet children()}Kembali{/snippet}
                </Button>
                <Button
                    variant="success"
                    size="sm"
                    disabled={isProcessing || !deliveryPhotoFile}
                    onclick={submitDeliver}
                >
                    {#snippet children()}
                        {#if isProcessing}
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...
                        {:else}
                            <i class="fa-solid fa-circle-check mr-1"></i> Tandai
                            Selesai
                        {/if}
                    {/snippet}
                </Button>
            </div>
        </div>
    </div>
{/if}

<!-- Cancel Order Modal -->
{#if cancelModalOpen}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cancel-modal-title"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                >
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="flex-1">
                    <h3
                        id="cancel-modal-title"
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        Batalkan Pesanan
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Apakah Anda yakin ingin membatalkan pesanan
                        <strong>#{order.number}</strong>? Tindakan ini tidak
                        dapat dibatalkan.
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <label
                    for="cancellation-note"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    Catatan Pembatalan
                    <span class="ml-1 font-normal text-gray-400"
                        >(opsional)</span
                    >
                </label>
                <textarea
                    id="cancellation-note"
                    bind:value={cancelNote}
                    rows="3"
                    maxlength="500"
                    placeholder="Tuliskan alasan pembatalan..."
                    class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                        placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500
                        dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500
                        dark:focus:border-indigo-400"
                ></textarea>
                <p class="mt-1 text-right text-xs text-gray-400">
                    {cancelNote.length}/500
                </p>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <Button
                    variant="secondary"
                    size="sm"
                    onclick={() => (cancelModalOpen = false)}
                    disabled={isProcessing}
                >
                    {#snippet children()}Kembali{/snippet}
                </Button>
                <Button
                    variant="danger"
                    size="sm"
                    disabled={isProcessing}
                    onclick={() => {
                        isProcessing = true;
                        router.post(
                            `/admin/orders/${order.id}/cancel`,
                            { cancellation_note: cancelNote || null },
                            {
                                onFinish: () => {
                                    isProcessing = false;
                                    cancelModalOpen = false;
                                },
                            },
                        );
                    }}
                >
                    {#snippet children()}Ya, Batalkan Pesanan{/snippet}
                </Button>
            </div>
        </div>
    </div>
{/if}

<!-- Confirmation Dialog Modal -->
{#if confirmDialog.open}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
                        {confirmDialog.variant === 'danger'
                        ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                        : confirmDialog.variant === 'success'
                          ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                          : confirmDialog.variant === 'warning'
                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                            : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'}"
                >
                    <i
                        class="fa-solid
                            {confirmDialog.variant === 'danger'
                            ? 'fa-triangle-exclamation'
                            : confirmDialog.variant === 'success'
                              ? 'fa-circle-check'
                              : confirmDialog.variant === 'warning'
                                ? 'fa-circle-exclamation'
                                : 'fa-circle-question'}"
                    ></i>
                </div>
                <div class="flex-1">
                    <h3
                        id="confirm-dialog-title"
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        {confirmDialog.title}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {confirmDialog.message}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" size="sm" onclick={closeConfirm}>
                    {#snippet children()}Batal{/snippet}
                </Button>
                <Button
                    variant={confirmDialog.variant === "danger"
                        ? "danger"
                        : confirmDialog.variant === "success"
                          ? "success"
                          : "primary"}
                    size="sm"
                    onclick={executeAction}
                >
                    {#snippet children()}Ya, Lanjutkan{/snippet}
                </Button>
            </div>
        </div>
    </div>
{/if}

<!-- Reassign Chef Modal -->
{#if reassignModalOpen}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                Pindahkan ke Dapur Lain
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Pilih Dapur baru untuk item <strong
                    >{selectedItemForReassign?.product?.name}</strong
                >.
            </p>

            <div class="space-y-4">
                <div>
                    <label
                        for="chef-select"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                        Pilih Dapur
                    </label>
                    <select
                        id="chef-select"
                        bind:value={selectedNewChefId}
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">-- Pilih Dapur --</option>
                        {#each chefs as chef}
                            <option value={chef.id}>{chef.name}</option>
                        {/each}
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Button
                    variant="secondary"
                    size="sm"
                    onclick={() => (reassignModalOpen = false)}
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    size="sm"
                    disabled={isProcessing || !selectedNewChefId}
                    onclick={submitReassign}
                >
                    {#if isProcessing}
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memproses...
                    {:else}
                        <i class="fa-solid fa-exchange mr-1"></i> Pindahkan Dapur
                    {/if}
                </Button>
            </div>
        </div>
    </div>
{/if}

<MediaViewer
    bind:isOpen={isMediaViewerOpen}
    items={mediaViewerItems}
    initialIndex={mediaViewerInitialIndex}
/>
