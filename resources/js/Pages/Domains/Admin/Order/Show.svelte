<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import OrderPrintModal from "./OrderPrintModal.svelte";

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
        final_price: number;
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
        drop_point?: {
            name: string;
            address: string;
            latitude?: number;
            longitude?: number;
        };
        customer_address?: {
            name: string;
            address: string;
            latitude?: number;
            longitude?: number;
            note?: string;
        };
        school_class?: string;
        service_fee?: number;
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
        arrived_at?: string;
        created_at: string;
        items: OrderItem[];
        shippings: Array<{
            courier_company: string;
            courier_name: string;
            shipping_fee: number;
            biteship_status?: string;
            biteship_waybill_id?: string;
        }>;
    }

    let {
        order: orderProp,
        free_courier_min_order = 0,
    }: {
        order: Order;
        free_courier_min_order: number;
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
    let printModalOpen = $state(false);
    let cancelNote = $state("");
    let deliverModalOpen = $state(false);
    let deliveryPhotoFile = $state<File | null>(null);
    let deliveryPhotoError = $state<string | undefined>(undefined);
    let isMediaViewerOpen = $state(false);
    let mediaViewerItems = $state<string | string[]>([]);
    let mediaViewerInitialIndex = $state(0);
    let confirmOrderModalOpen = $state(false);

    function openConfirmOrderModal() {
        confirmOrderModalOpen = true;
    }

    function submitConfirmOrder() {
        isProcessing = true;
        router.post(
            `/admin/orders/${order.id}/confirm`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                    confirmOrderModalOpen = false;
                },
            },
        );
    }

    function startCooking() {
        isProcessing = true;
        router.post(
            `/admin/orders/${order.id}/cook`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }

    function shipOrder() {
        isProcessing = true;
        router.post(
            `/admin/orders/${order.id}/ship`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }

    function openMediaViewer(items: string | string[], index: number = 0) {
        mediaViewerItems = items;
        mediaViewerInitialIndex = index;
        isMediaViewerOpen = true;
    }

    function submitDeliver() {
        isProcessing = true;
        const formData = new FormData();
        if (deliveryPhotoFile) {
            formData.append("delivery_photo", deliveryPhotoFile);
        }
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
        openConfirm(
            "Hapus Testimoni",
            "Apakah Anda yakin ingin menghapus testimoni untuk produk ini?",
            () => {
                router.delete(`/admin/testimonials/${testimonialId}`, {
                    onFinish: () => {
                        isProcessing = false;
                    },
                });
            },
            "danger",
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
            case "cooking":
                return { variant: "warning", label: "Sedang Dimasak" };
            case "on_delivery":
                return { variant: "primary", label: "Sedang Dikirim" };
            case "arrived":
                return { variant: "info", label: "Tiba di Tujuan" };
            case "delivered":
                return { variant: "success", label: "Selesai" };
            case "cancelled":
                return { variant: "danger", label: "Dibatalkan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    const ORDER_LIFECYCLE_STEPS = [
        {
            key: "pending",
            label: "1. Menunggu",
            title: "Verifikasi Pembayaran",
            icon: "fa-solid fa-clock",
        },
        {
            key: "confirmed",
            label: "2. Dikonfirmasi",
            title: "Masuk Antrean Dapur",
            icon: "fa-solid fa-clipboard-check",
        },
        {
            key: "cooking",
            label: "3. Sedang Dimasak",
            title: "Proses Dapur Sentral",
            icon: "fa-solid fa-fire-burner",
        },
        {
            key: "on_delivery",
            label: "4. Sedang Dikirim",
            title: "Pengantaran Kurir",
            icon: "fa-solid fa-truck-fast",
        },
        {
            key: "arrived",
            label: "5. Tiba di Tujuan",
            title: "Sampai di Tujuan",
            icon: "fa-solid fa-location-dot",
        },
        {
            key: "delivered",
            label: "6. Selesai",
            title: "Diterima Pelanggan",
            icon: "fa-solid fa-circle-check",
        },
    ] as const;

    const stepOrderKeys = [
        "pending",
        "confirmed",
        "cooking",
        "on_delivery",
        "arrived",
        "delivered",
    ];
    const currentStepIdx = $derived(
        order.order_status === "cancelled"
            ? -1
            : stepOrderKeys.indexOf(order.order_status),
    );

    function getNextStepHint(status: string): string {
        switch (status) {
            case "pending":
                return "Langkah berikutnya: Periksa bukti pembayaran lalu klik 'Konfirmasi' atau 'Batalkan'.";
            case "confirmed":
                return "Langkah berikutnya: Saat dapur mulai menyiapkan pesanan, klik 'Mulai Memasak'.";
            case "cooking":
                return "Langkah berikutnya: Saat makanan matang & diserahkan ke kurir, klik 'Kirim Pesanan'.";
            case "on_delivery":
                return "Langkah berikutnya: Kurir dalam perjalanan menuju Drop Point atau Alamat Customer.";
            case "arrived":
                return "Langkah berikutnya: Pesanan telah tiba di lokasi, lakukan serah terima lalu klik 'Selesaikan Pesanan'.";
            case "delivered":
                return "Semua tahapan pesanan telah selesai dengan sukses.";
            case "cancelled":
                return "Pesanan ini telah dibatalkan.";
            default:
                return "";
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

    const backUrl = $derived.by(() => {
        const params = new URLSearchParams(window.location.search);
        const from = params.get("from");
        if (from === "processing") return "/admin/orders/processing";
        if (from === "payments") return "/admin/orders/payments";
        return "/admin/orders";
    });

    function resendNotifications(target: "customer" | "admin") {
        let targetLabel = "";
        if (target === "customer") targetLabel = "Customer (WhatsApp & Email)";
        if (target === "admin") targetLabel = "Admin (Telegram)";

        if (
            !confirm(
                `Apakah Anda yakin ingin mengirim ulang notifikasi ke ${targetLabel} untuk pesanan ini?`,
            )
        )
            return;
        isProcessing = true;
        router.post(
            `/admin/orders/${order.id}/resend-notifications/${target}`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }
</script>

<svelte:head>
    <title>Detail Pesanan {order.number} | {name(page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="flex items-center gap-3">
            <Button
                variant="secondary"
                size="sm"
                icon="fa-solid fa-arrow-left"
                href={backUrl}
            />
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                    Detail Pesanan
                </h1>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                    #{order.number}
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
            <!-- Badges -->
            <div class="flex items-center gap-2 flex-wrap">
                <div
                    class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800/80 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700"
                >
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        Order:
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
                <div
                    class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800/80 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700"
                >
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        Bayar:
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
            </div>

            <!-- Status Action Buttons -->
            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    variant="info"
                    size="sm"
                    icon="fa-solid fa-print"
                    onclick={() => (printModalOpen = true)}
                >
                    {#snippet children()}Print Struk{/snippet}
                </Button>

                {#if order.order_status === "pending"}
                    <Button
                        variant="primary"
                        size="sm"
                        icon="fa-solid fa-check"
                        disabled={isProcessing}
                        onclick={openConfirmOrderModal}
                    >
                        {#snippet children()}Konfirmasi{/snippet}
                    </Button>
                    <Button
                        variant="danger"
                        size="sm"
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

                {#if order.order_status === "confirmed"}
                    <Button
                        variant="warning"
                        size="sm"
                        icon="fa-solid fa-fire-burner"
                        disabled={isProcessing}
                        onclick={startCooking}
                    >
                        {#snippet children()}Mulai Memasak{/snippet}
                    </Button>
                    <Button
                        variant="primary"
                        size="sm"
                        icon="fa-solid fa-truck-fast"
                        disabled={isProcessing}
                        onclick={shipOrder}
                    >
                        {#snippet children()}Kirim Pesanan{/snippet}
                    </Button>
                {/if}

                {#if order.order_status === "cooking"}
                    <Button
                        variant="primary"
                        size="sm"
                        icon="fa-solid fa-truck-fast"
                        disabled={isProcessing}
                        onclick={shipOrder}
                    >
                        {#snippet children()}Kirim Pesanan{/snippet}
                    </Button>
                {/if}

                {#if order.order_status === "on_delivery" || order.order_status === "arrived"}
                    <Button
                        variant="success"
                        size="sm"
                        icon="fa-solid fa-circle-check"
                        disabled={isProcessing}
                        onclick={() => (deliverModalOpen = true)}
                    >
                        {#snippet children()}Selesaikan Pesanan{/snippet}
                    </Button>
                {/if}
            </div>
        </div>
    </header>

    <!-- Order Lifecycle Stepper Tracker -->
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-[#2c2c2c] dark:bg-[#0f0f0f]"
    >
        <div
            class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 border-b border-gray-100 pb-3 dark:border-gray-800"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 dark:bg-amber-400/10 dark:text-amber-400"
                >
                    <i class="fa-solid fa-timeline text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        Alur Status & Tahapan Pesanan
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {getNextStepHint(order.order_status)}
                    </p>
                </div>
            </div>

            {#if order.order_status === "cancelled"}
                <Badge variant="danger" size="sm" dot={true}>
                    {#snippet children()}Pesanan Dibatalkan{/snippet}
                </Badge>
            {:else}
                <div
                    class="flex items-center gap-1.5 text-xs bg-amber-500/10 dark:bg-amber-400/10 px-2.5 py-1 rounded-full w-fit"
                >
                    <span class="text-gray-500 dark:text-gray-400"
                        >Tahap Saat Ini:</span
                    >
                    <span class="font-bold text-amber-600 dark:text-amber-400">
                        {getStatusBadge(order.order_status).label} (Tahap {currentStepIdx +
                            1} dari 6)
                    </span>
                </div>
            {/if}
        </div>

        {#if order.order_status === "cancelled"}
            <div
                class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50/80 p-4 dark:border-red-900/50 dark:bg-red-950/20"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400"
                >
                    <i class="fa-solid fa-ban text-sm"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-red-800 dark:text-red-300">
                        Pesanan Telah Dibatalkan
                    </h4>
                    <p class="mt-0.5 text-xs text-red-700 dark:text-red-400">
                        {order.cancellation_note
                            ? `Alasan: "${order.cancellation_note}"`
                            : "Tidak ada catatan pembatalan."}
                    </p>
                </div>
            </div>
        {:else}
            <!-- Responsive Step Progress Bar -->
            <div class="relative py-2">
                <div
                    class="flex lg:grid lg:grid-cols-6 gap-3 overflow-x-auto no-scrollbar py-2 relative"
                >
                    {#each ORDER_LIFECYCLE_STEPS as step, index}
                        {@const isPassed = currentStepIdx > index}
                        {@const isCurrent = currentStepIdx === index}
                        {@const isUpcoming = currentStepIdx < index}

                        <div
                            class="flex-1 min-w-[130px] lg:min-w-0 flex flex-col items-center text-center relative group shrink-0 lg:shrink p-2.5 rounded-xl transition-all duration-200 {isCurrent
                                ? 'bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/20'
                                : 'bg-transparent'}"
                        >
                            <!-- Connecting Line for Desktop (LG) -->
                            {#if index < ORDER_LIFECYCLE_STEPS.length - 1}
                                <div
                                    class="hidden lg:block absolute top-6 left-1/2 w-full h-0.5 z-0 {isPassed
                                        ? 'bg-emerald-500'
                                        : isCurrent
                                          ? 'bg-gradient-to-r from-amber-500 to-gray-200 dark:to-gray-700'
                                          : 'bg-gray-200 dark:bg-gray-800'}"
                                ></div>
                            {/if}

                            <!-- Icon Node -->
                            <div
                                class="relative z-10 flex h-9 w-9 items-center justify-center rounded-full transition-all duration-300 {isPassed
                                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20 ring-4 ring-emerald-500/10'
                                    : isCurrent
                                      ? 'bg-amber-500 text-slate-950 font-bold shadow-lg shadow-amber-500/30 ring-4 ring-amber-500/25 scale-110'
                                      : 'border border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'}"
                            >
                                {#if isPassed}
                                    <i class="fa-solid fa-check text-sm"></i>
                                {:else}
                                    <i class="{step.icon} text-xs"></i>
                                {/if}
                            </div>

                            <!-- Texts -->
                            <div class="mt-2.5 space-y-0.5">
                                <div
                                    class="text-xs font-bold transition-colors whitespace-nowrap {isPassed
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : isCurrent
                                          ? 'text-amber-600 dark:text-amber-400 font-extrabold'
                                          : 'text-gray-400 dark:text-gray-500'}"
                                >
                                    {step.label}
                                </div>
                                <div
                                    class="text-[10px] text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                >
                                    {step.title}
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        {/if}
    </div>

    {#if order.order_status === "pending"}
        {@const orderSubtotal = order.items.reduce((sum, item) => sum + item.subtotal, 0)}
        {@const meetsMinOrder = orderSubtotal >= free_courier_min_order}
        <div class="rounded-xl border p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 {meetsMinOrder ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-950/50 dark:bg-emerald-950/20 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-950/50 dark:bg-amber-950/20 dark:text-amber-300'}">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {meetsMinOrder ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/30'}">
                    <i class="fa-solid {meetsMinOrder ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
                </div>
                <div>
                    <h4 class="font-bold">
                        {meetsMinOrder ? 'Memenuhi Batas Minimum Order Subsidi Ongkir' : 'Tidak Memenuhi Batas Minimum Order Subsidi Ongkir'}
                    </h4>
                    <p class="text-xs opacity-90 mt-1">
                        Subtotal order: <strong>Rp {new Intl.NumberFormat("id-ID").format(orderSubtotal)}</strong> (Minimal subsidi ongkir: <strong>Rp {new Intl.NumberFormat("id-ID").format(free_courier_min_order)}</strong>).
                    </p>
                </div>
            </div>
            {#if !meetsMinOrder}
                <div class="text-xs font-semibold bg-amber-100 px-3 py-1.5 rounded-lg dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                    Saran: Batalkan pesanan dengan remark "Minimum order tidak tercapai".
                </div>
            {/if}
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
                                <th class="text-center">Jumlah</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each order.items as item}
                                <tr>
                                    <td class="pl-4">
                                        <div class="flex items-center gap-3">
                                            {#if item.product?.image_url}
                                                <img
                                                    src={item.product.image_url}
                                                    alt={item.product.name}
                                                    class="w-10 h-10 object-cover rounded shadow-sm"
                                                />
                                            {/if}
                                            <div>
                                                <div
                                                    class="font-medium text-gray-900 dark:text-white"
                                                >
                                                    {item.product?.name ?? "Produk"}
                                                </div>
                                                {#if item.options && item.options.length > 0}
                                                    <div
                                                        class="text-[10px] text-gray-500 mt-0.5"
                                                    >
                                                        {#each item.options as opt}
                                                            <span>
                                                                {opt.product_option?.name}: {opt.product_option_item?.name}
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
                                            <div
                                                class="mt-3 p-3.5 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm space-y-2.5 max-w-xl"
                                            >
                                                <div
                                                    class="flex items-center justify-between gap-3"
                                                >
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <div
                                                            class="flex items-center gap-0.5 bg-amber-400/10 px-2 py-0.5 rounded-md border border-amber-400/20"
                                                        >
                                                            {#each Array(5) as _, i}
                                                                <i
                                                                    class="fa-solid fa-star text-xs {i <
                                                                    parseInt(
                                                                        item
                                                                            .testimonial!
                                                                            .rating,
                                                                    )
                                                                        ? 'text-amber-400'
                                                                        : 'text-slate-300 dark:text-slate-600'}"
                                                                ></i>
                                                            {/each}
                                                            <span
                                                                class="text-xs font-bold text-amber-500 ml-1"
                                                                >{item
                                                                    .testimonial!
                                                                    .rating}.0</span
                                                            >
                                                        </div>
                                                        <span
                                                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                                                            >Ulasan Pelanggan</span
                                                        >
                                                    </div>

                                                    <Badge
                                                        size="xs"
                                                        variant={item
                                                            .testimonial!
                                                            .is_approved
                                                            ? "success"
                                                            : "warning"}
                                                        dot={true}
                                                    >
                                                        {#snippet children()}{item
                                                                .testimonial!
                                                                .is_approved
                                                                ? "Disetujui"
                                                                : "Menunggu Moderasi"}{/snippet}
                                                    </Badge>
                                                </div>

                                                <div
                                                    class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed italic bg-white dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/80"
                                                >
                                                    "{item.testimonial
                                                        .content ||
                                                        "Tanpa komentar"}"
                                                </div>

                                                {#if item.testimonial.photo_url}
                                                    <div
                                                        class="flex items-center gap-2 pt-0.5"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="relative group rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 w-14 h-14 shrink-0 block"
                                                            onclick={() =>
                                                                openMediaViewer(
                                                                    item
                                                                        .testimonial!
                                                                        .photo_url,
                                                                )}
                                                            title="Lihat foto ulasan"
                                                        >
                                                            <img
                                                                src={item
                                                                    .testimonial
                                                                    .photo_url}
                                                                alt="Foto Ulasan"
                                                                class="w-full h-full object-cover transition-transform group-hover:scale-110"
                                                            />
                                                            <div
                                                                class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity text-white text-xs"
                                                            >
                                                                <i
                                                                    class="fa-solid fa-expand"
                                                                ></i>
                                                            </div>
                                                        </button>
                                                        <span
                                                            class="text-[11px] text-slate-500 dark:text-slate-400"
                                                            >Foto lampiran dari pelanggan (klik untuk memperbesar)</span
                                                        >
                                                    </div>
                                                {/if}

                                                <div
                                                    class="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-700/60"
                                                >
                                                    {#if !item.testimonial.is_approved}
                                                        <Button
                                                            variant="success"
                                                            size="xs"
                                                            icon="fa-solid fa-check"
                                                            disabled={isProcessing}
                                                            onclick={() =>
                                                                approveTestimonial(
                                                                    item
                                                                        .testimonial!
                                                                        .id,
                                                                )}
                                                        >
                                                            {#snippet children()}Setujui
                                                                Testimoni{/snippet}
                                                        </Button>
                                                    {/if}
                                                    <Button
                                                        variant="danger"
                                                        size="xs"
                                                        icon="fa-solid fa-trash-can"
                                                        disabled={isProcessing}
                                                        onclick={() =>
                                                            rejectTestimonial(
                                                                item
                                                                    .testimonial!
                                                                    .id,
                                                            )}
                                                    >
                                                        {#snippet children()}Hapus{/snippet}
                                                    </Button>
                                                </div>
                                            </div>
                                        {/if}
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

            {#if order.order_status !== 'delivered' && order.order_status !== 'cancelled'}
                <Card title="Kirim Ulang Notifikasi">
                    <div class="flex flex-col gap-2">
                        <Button
                            variant="success"
                            size="sm"
                            icon="fa-solid fa-user"
                            disabled={isProcessing}
                            onclick={() => resendNotifications('customer')}
                        >
                            {#snippet children()}Ke Customer (WhatsApp & Email){/snippet}
                        </Button>

                        <Button
                            variant="info"
                            size="sm"
                            icon="fa-brands fa-telegram"
                            disabled={isProcessing}
                            onclick={() => resendNotifications('admin')}
                        >
                            {#snippet children()}Ke Admin (Telegram){/snippet}
                        </Button>
                    </div>
                </Card>
            {/if}

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
                    {#if order.discount_amount > 0}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Diskon</span
                            >
                            <span class="text-red-500 text-right"
                                >-{formatCurrency(order.discount_amount)}</span
                            >
                        </div>
                    {/if}
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400"
                            >Ongkos Kirim</span
                        >
                        <span class="text-gray-900 dark:text-white"
                            >{formatCurrency(order.delivery_fee)}</span
                        >
                    </div>
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
                    {#if order.service_fee && order.service_fee > 0}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Biaya Layanan</span
                            >
                            <span class="text-gray-900 dark:text-white"
                                >{formatCurrency(order.service_fee)}</span
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
                    {#if order.arrived_at}
                        <div>
                            <div
                                class="text-xs font-semibold text-gray-500 uppercase"
                            >
                                Pesanan Tiba Pada
                            </div>
                            <div class="text-sm text-indigo-600 font-bold">
                                {new Date(order.arrived_at).toLocaleString(
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
                    {/if}
                    {#if order.delivered_at}
                        <div>
                            <div
                                class="text-xs font-semibold text-gray-500 uppercase"
                            >
                                Pesanan Selesai Pada
                            </div>
                            <div class="text-sm text-emerald-600 font-bold">
                                {new Date(order.delivered_at).toLocaleString(
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
                    {/if}
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
                        Konfirmasi Selesaikan Pesanan
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Konfirmasi bahwa pesanan
                        <strong>#{order.number}</strong> telah berhasil diserahkan/diterima di lokasi tujuan.
                    </p>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                <FileUpload
                    id="delivery_photo"
                    name="delivery_photo"
                    label="Foto Bukti Serah Terima (Opsional)"
                    placeholder="Pilih foto atau drag and drop bukti serah terima (jika ada)"
                    required={false}
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
                    disabled={isProcessing}
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

<!-- Confirm Order Modal -->
{#if confirmOrderModalOpen}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <div class="flex items-start gap-4 mb-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
                        {!order.payment_proof_url && order.payment_method?.category !== 'cash'
                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                            : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'}"
                >
                    <i
                        class="fa-solid {!order.payment_proof_url && order.payment_method?.category !== 'cash'
                            ? 'fa-triangle-exclamation'
                            : 'fa-circle-question'}"
                    ></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Konfirmasi Pesanan
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {#if !order.payment_proof_url && order.payment_method?.category !== 'cash'}
                            <span class="text-amber-600 font-bold"
                                >PERINGATAN: Bukti pembayaran belum diunggah.</span
                            > Apakah Anda yakin ingin mengkonfirmasi pesanan
                            <strong>#{order.number}</strong> secara manual?
                        {:else}
                            Apakah Anda yakin ingin mengkonfirmasi pesanan <strong
                                >#{order.number}</strong
                            >?
                        {/if}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Button
                    variant="secondary"
                    size="sm"
                    onclick={() => (confirmOrderModalOpen = false)}
                    disabled={isProcessing}
                >
                    {#snippet children()}Batal{/snippet}
                </Button>
                <Button
                    variant={!order.payment_proof_url && order.payment_method?.category !== 'cash' ? "warning" : "primary"}
                    size="sm"
                    disabled={isProcessing}
                    loading={isProcessing}
                    onclick={submitConfirmOrder}
                >
                    {#snippet children()}Ya, Konfirmasi{/snippet}
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

<OrderPrintModal
    order={order}
    open={printModalOpen}
    onClose={() => (printModalOpen = false)}
/>
