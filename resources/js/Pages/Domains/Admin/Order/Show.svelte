<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
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
        delivery_photo_url?: string;
        delivered_at?: string;
        created_at: string;
        items: OrderItem[];
    }

    let order = $derived($page.props.order as Order);

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
                return { variant: "primary", label: "Dikirim" };
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

                {#if order.order_status === "confirmed"}
                    <Button
                        variant="primary"
                        size="sm"
                        icon="fa-solid fa-truck"
                        disabled={isProcessing}
                        onclick={() =>
                            openConfirm(
                                "Tandai Sebagai Dikirim",
                                `Apakah Anda yakin pesanan #${order.number} sudah dikirim?`,
                                () => postAction("ship"),
                                "primary",
                            )}
                    >
                        {#snippet children()}Tandai Dikirim{/snippet}
                    </Button>
                {/if}

                {#if order.order_status === "shipped"}
                    <Button
                        variant="success"
                        size="sm"
                        icon="fa-solid fa-circle-check"
                        disabled={isProcessing}
                        onclick={() => {
                            deliveryPhotoFile = null;
                            deliveryPhotoError = undefined;
                            deliverModalOpen = true;
                        }}
                    >
                        {#snippet children()}Tandai Selesai{/snippet}
                    </Button>
                {/if}
            </div>
        </div>
    </header>

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
                                    <td>
                                        <div class="flex items-center gap-3">
                                            {#if item.product.image_url}
                                                <img
                                                    src={item.product.image_url}
                                                    alt={item.product.name}
                                                    class="w-12 h-12 object-cover rounded"
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
                                                        class="text-xs text-gray-500 mt-1"
                                                    >
                                                        {#each item.options as opt}
                                                            <div>
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
                                                            </div>
                                                        {/each}
                                                    </div>
                                                {/if}
                                                {#if item.note}
                                                    <div
                                                        class="text-xs text-amber-600 mt-1 italic"
                                                    >
                                                        Catatan: {item.note}
                                                    </div>
                                                {/if}
                                            </div>
                                        </div>

                                        {#if item.testimonial}
                                            <div
                                                class="mt-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-700/50 max-w-md"
                                            >
                                                <div
                                                    class="flex items-center justify-between mb-2"
                                                >
                                                    <div
                                                        class="flex items-center gap-1"
                                                    >
                                                        {#each Array(5) as _, i}
                                                            <i
                                                                class="fa-solid fa-star text-[10px] {i <
                                                                parseInt(
                                                                    item
                                                                        .testimonial!
                                                                        .rating,
                                                                )
                                                                    ? 'text-yellow-400'
                                                                    : 'text-gray-200 dark:text-gray-700'}"
                                                            ></i>
                                                        {/each}
                                                        <span
                                                            class="ml-1.5 text-[10px] text-gray-500 font-medium"
                                                            >({item.testimonial!
                                                                .rating}/5)</span
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
                                                                : "Menunggu"}{/snippet}
                                                    </Badge>
                                                </div>
                                                <p
                                                    class="text-xs text-gray-700 dark:text-gray-300 italic mb-2"
                                                >
                                                    "{item.testimonial
                                                        .content ||
                                                        "Tanpa komentar"}"
                                                </p>
                                                {#if item.testimonial.photo_url}
                                                    <a
                                                        href={item.testimonial
                                                            .photo_url}
                                                        target="_blank"
                                                        class="block w-20 h-20 rounded border border-gray-200 overflow-hidden mb-3"
                                                    >
                                                        <img
                                                            src={item
                                                                .testimonial
                                                                .photo_url}
                                                            alt="Testimoni"
                                                            class="w-full h-full object-cover"
                                                        />
                                                    </a>
                                                {/if}
                                                <div class="flex gap-2">
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
                                                            {#snippet children()}Setujui{/snippet}
                                                        </Button>
                                                    {/if}
                                                    <Button
                                                        variant="danger"
                                                        size="xs"
                                                        icon="fa-solid fa-trash"
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
                                    <td class="text-center">{item.quantity}</td>
                                    <td class="text-right"
                                        >{formatCurrency(item.price)}</td
                                    >
                                    <td class="text-right font-medium"
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
                                            (acc, i) => acc + i.subtotal,
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

            <Card title="Ringkasan Pembayaran">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400"
                            >Total Item</span
                        >
                        <span class="text-gray-900 dark:text-white"
                            >{formatCurrency(
                                order.items.reduce(
                                    (acc, i) => acc + i.subtotal,
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

            {#if order.order_status === "delivered" && order.delivery_photo_url}
                <Card title="Bukti Penerimaan">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Foto bukti pesanan diterima oleh pelanggan.
                        </p>
                        <a
                            href={order.delivery_photo_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity"
                        >
                            <img
                                src={order.delivery_photo_url}
                                alt="Bukti penerimaan pesanan #{order.number}"
                                class="w-full object-cover max-h-64"
                            />
                        </a>
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
