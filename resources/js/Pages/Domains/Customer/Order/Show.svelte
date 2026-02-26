<script lang="ts">
    import { Link, useForm } from "@inertiajs/svelte";
    import dayjs from "dayjs";
    import id from "dayjs/locale/id";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";

    dayjs.locale(id);

    export let order: {
        id: string;
        number: string;
        total_amount: number;
        payment_status: string;
        order_status: string;
        created_at: string;
        delivery_date: string;
        delivery_time?: string;
        cancellation_note?: string;
        drop_point?: { name: string; address: string };
        payment_method?: {
            name: string;
            type: string;
            category: string;
        };
        items: Array<{
            id: string;
            quantity: number;
            price: number;
            product: { name: string; image_url: string };
        }>;
        tax_amount: number;
        admin_fee: number;
        final_delivery_fee: number;
        discount_amount: number;
        delivery_photo_url?: string;
        delivered_at?: string;
        can_give_testimonial?: boolean;
        testimonial_available_at?: string;
        testimonial?: {
            rating: string;
            content: string;
            photo_url: string;
            created_at: string;
        };
    };

    function formatCurrency(amount: number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    function getStatusBadge(paymentStatus: string, orderStatus: string) {
        const isCash = order.payment_method?.category === "cash";

        if (orderStatus === "cancelled" || paymentStatus === "failed") {
            return {
                text: "Dibatalkan",
                classes: "bg-red-50 text-red-600 border border-red-200",
                icon: "fa-solid fa-circle-xmark",
            };
        }
        if (orderStatus === "delivered") {
            return {
                text: "Selesai",
                classes: "bg-green-50 text-green-600 border border-green-200",
                icon: "fa-solid fa-circle-check",
            };
        }

        if (orderStatus === "shipped") {
            return {
                text: isCash ? "Dikirim (COD)" : "Dikirim",
                classes:
                    "bg-purple-50 text-purple-600 border border-purple-200",
                icon: "fa-solid fa-truck-fast",
            };
        }

        if (orderStatus === "pending" || orderStatus === "confirmed") {
            if (paymentStatus === "pending") {
                if (isCash) {
                    return {
                        text: "Diproses (COD)",
                        classes:
                            "bg-blue-50 text-blue-600 border border-blue-200",
                        icon: "fa-solid fa-spinner fa-spin",
                    };
                }

                return {
                    text: "Belum Dibayar",
                    classes:
                        "bg-yellow-50 text-yellow-600 border border-yellow-200",
                    icon: "fa-solid fa-clock",
                };
            }

            return {
                text: "Diproses",
                classes: "bg-blue-50 text-blue-600 border border-blue-200",
                icon: "fa-solid fa-spinner fa-spin",
            };
        }

        return {
            text: "Status Tidak Diketahui",
            classes: "bg-gray-50 text-gray-600 border border-gray-200",
            icon: "fa-solid fa-circle-question",
        };
    }

    const badge = getStatusBadge(order.payment_status, order.order_status);
    const subtotal = order.items.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0,
    );

    let processing = false;
    let showTestimonialForm = false;

    const testimonialForm = useForm({
        rating: "5",
        content: "",
        photo: null as File | null,
    });

    function submitTestimonial() {
        testimonialForm.post(`/orders/${order.id}/testimonial`, {
            preserveScroll: true,
            onSuccess: () => {
                showTestimonialForm = false;
                testimonialForm.reset();
            },
        });
    }

    // Timer logic for "Mohon tunggu"
    let timeLeft = "";
    if (
        order.order_status === "delivered" &&
        !order.can_give_testimonial &&
        order.testimonial_available_at
    ) {
        const availableAt = dayjs(order.testimonial_available_at);
        const now = dayjs();
        const diff = availableAt.diff(now, "minute");
        if (diff > 0) {
            timeLeft = `${diff} menit lagi`;
        }
    }
</script>

<svelte:head>
    <title>Detail Pesanan {order.number}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="bg-white px-4 py-4 flex items-center justify-between sticky top-0 z-20 shadow-sm border-b border-gray-100"
    >
        <div class="flex items-center gap-3">
            <Link
                href="/orders"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-50 text-gray-700 hover:bg-gray-100 transition-colors"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </Link>
            <h1 class="text-lg font-bold text-gray-900 leading-none">
                Detail Pesanan
            </h1>
        </div>
    </header>

    <div class="p-4 space-y-4">
        <!-- Status & Timeline Info -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <div
                class="flex items-center justify-between mb-3 pb-3 border-b border-gray-50"
            >
                <span class="text-sm text-gray-500">Status Pesanan</span>
                <div
                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold {badge.classes}"
                >
                    <i class={badge.icon}></i>
                    <span>{badge.text}</span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Tanggal Transaksi</span>
                    <span class="font-medium text-gray-900"
                        >{dayjs(order.created_at).format(
                            "DD MMM YYYY, HH:mm",
                        )}</span
                    >
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Nomor Pesanan</span>
                    <span class="font-medium text-blue-600">{order.number}</span
                    >
                </div>
            </div>
        </div>

        {#if order.order_status === "cancelled" && order.cancellation_note}
            <div
                class="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3"
            >
                <i class="fa-solid fa-circle-info text-red-500 mt-0.5 shrink-0"
                ></i>
                <div>
                    <div class="text-sm font-semibold text-red-700 mb-1">
                        Alasan Pembatalan
                    </div>
                    <p class="text-sm text-red-600 leading-relaxed">
                        {order.cancellation_note}
                    </p>
                </div>
            </div>
        {/if}

        {#if order.payment_status === "pending" && order.payment_method?.category !== "cash"}
            <Link
                href={`/payment/${order.id}?from=detail`}
                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white text-center font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 {processing
                    ? 'opacity-80 pointer-events-none'
                    : ''}"
                on:click={() => (processing = true)}
            >
                {#if processing}
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Tunggu sebentar...</span>
                {:else}
                    Lanjutkan Pembayaran
                {/if}
            </Link>
        {/if}

        {#if order.order_status === "shipped"}
            <Link
                href={`/orders/${order.id}/complete`}
                method="post"
                as="button"
                preserve-scroll
                class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white text-center font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 {processing
                    ? 'opacity-80 pointer-events-none'
                    : ''}"
                on:click={() => (processing = true)}
                on:finish={() => (processing = false)}
            >
                {#if processing}
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Memproses...</span>
                {:else}
                    Pesanan Diterima (Selesaikan)
                {/if}
            </Link>
        {/if}

        <!-- Drop Point / Shipping Info -->
        <div
            class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-3"
        >
            <div class="font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-location-dot text-red-500"></i>
                Detail Pengiriman
            </div>
            <div class="pt-2 border-t border-gray-50">
                <div class="text-sm font-medium text-gray-900 mb-1">
                    {order.drop_point?.name || "Drop Point"}
                </div>
                <p class="text-xs text-gray-500 leading-relaxed mb-3">
                    {order.drop_point?.address || ""}
                </p>
                <div class="flex gap-4">
                    <div class="flex-1 bg-gray-50 rounded-lg p-2.5">
                        <div class="text-xs text-gray-500 mb-0.5">
                            Estimasi Tiba
                        </div>
                        <div class="text-sm font-medium text-gray-900">
                            {order.delivery_date
                                ? dayjs(order.delivery_date).format(
                                      "DD MMM YYYY",
                                  )
                                : "-"}
                            {#if order.delivery_time}
                                <span class="text-gray-400 mx-1">•</span>
                                <span>{order.delivery_time} WIB</span>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {#if order.order_status === "delivered"}
            <div
                class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-3"
            >
                <div
                    class="font-bold text-gray-900 flex items-center gap-2 mb-1"
                >
                    <i class="fa-solid fa-star text-yellow-400"></i>
                    Testimoni Pesanan
                </div>

                {#if order.testimonial}
                    <div
                        class="bg-gray-50 rounded-xl p-4 border border-gray-100"
                    >
                        <div class="flex items-center gap-1 mb-2">
                            {#each Array(5) as _, i}
                                <i
                                    class="fa-solid fa-star {i <
                                    parseInt(order.testimonial.rating)
                                        ? 'text-yellow-400'
                                        : 'text-gray-200'}"
                                ></i>
                            {/each}
                        </div>
                        <p
                            class="text-sm text-gray-700 leading-relaxed italic mb-3"
                        >
                            "{order.testimonial.content || "Tanpa komentar"}"
                        </p>
                        {#if order.testimonial.photo_url}
                            <div
                                class="rounded-lg overflow-hidden border border-gray-100 max-w-[200px]"
                            >
                                <img
                                    src={order.testimonial.photo_url}
                                    alt="Foto Testimoni"
                                    class="w-full h-auto object-cover"
                                />
                            </div>
                        {/if}
                        <div class="mt-2 text-[10px] text-gray-400">
                            Dikirim pada {dayjs(
                                order.testimonial.created_at,
                            ).format("DD MMM YYYY, HH:mm")}
                        </div>
                    </div>
                {:else if order.can_give_testimonial}
                    {#if !showTestimonialForm}
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500 mb-4">
                                Bagikan pengalaman Anda tentang pesanan ini!
                            </p>
                            <button
                                type="button"
                                on:click={() => (showTestimonialForm = true)}
                                class="w-full py-2.5 px-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                                Beri Testimoni
                            </button>
                        </div>
                    {:else}
                        <form
                            on:submit|preventDefault={submitTestimonial}
                            class="space-y-4 pt-2"
                        >
                            <div>
                                <span
                                    class="block text-xs font-semibold text-gray-700 mb-2"
                                    >Rating</span
                                >
                                <div class="flex gap-3">
                                    {#each ["1", "2", "3", "4", "5"] as star}
                                        <button
                                            type="button"
                                            aria-label="{star} Bintang"
                                            on:click={() =>
                                                (testimonialForm.rating = star)}
                                            class="w-10 h-10 rounded-lg flex items-center justify-center border transition-all {testimonialForm.rating ===
                                            star
                                                ? 'bg-yellow-50 border-yellow-400 text-yellow-600'
                                                : 'bg-white border-gray-200 text-gray-400'}"
                                        >
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    {/each}
                                </div>
                            </div>

                            <TextArea
                                id="testimonial-content"
                                name="content"
                                label="Komentar"
                                bind:value={testimonialForm.content}
                                placeholder="Bagaimana pesanan Anda?"
                                error={$testimonialForm.errors.content}
                            />

                            <FileUpload
                                id="testimonial-photo"
                                name="photo"
                                label="Foto (Opsional)"
                                bind:value={testimonialForm.photo}
                                error={$testimonialForm.errors.photo}
                                accept="image/*"
                                variant="button"
                                uploadText="Klik untuk pilih foto"
                            />

                            <div class="flex gap-2 pt-2">
                                <button
                                    type="button"
                                    on:click={() =>
                                        (showTestimonialForm = false)}
                                    class="flex-1 py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={$testimonialForm.processing}
                                    class="flex-2 py-2.5 px-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 disabled:opacity-50"
                                    style="flex: 2"
                                >
                                    {#if $testimonialForm.processing}
                                        <i class="fa-solid fa-spinner fa-spin"
                                        ></i>
                                        <span>Mengirim...</span>
                                    {:else}
                                        Kirim Testimoni
                                    {/if}
                                </button>
                            </div>
                        </form>
                    {/if}
                {:else if timeLeft}
                    <div
                        class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center gap-3"
                    >
                        <i class="fa-solid fa-clock text-blue-500 shrink-0"></i>
                        <p class="text-xs text-blue-700 leading-relaxed">
                            Anda dapat memberi testimoni dalam <strong
                                >{timeLeft}</strong
                            >. Pastikan makanan telah Anda nikmati!
                        </p>
                    </div>
                {/if}
            </div>
        {/if}

        {#if order.order_status === "delivered" && order.delivery_photo_url}
            <div
                class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <div
                    class="font-bold text-gray-900 flex items-center gap-2 mb-3"
                >
                    <i class="fa-solid fa-camera text-green-500"></i>
                    Bukti Penerimaan
                </div>
                <p class="text-xs text-gray-500 mb-3 leading-relaxed">
                    Foto bukti pesanan yang telah diterima.
                </p>
                <div class="rounded-lg overflow-hidden border border-gray-50">
                    <a
                        href={order.delivery_photo_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block"
                    >
                        <img
                            src={order.delivery_photo_url}
                            alt="Bukti Penerimaan"
                            class="w-full h-auto object-cover max-h-64 hover:opacity-90 transition-opacity"
                        />
                    </a>
                </div>
            </div>
        {/if}

        <!-- Order Items -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <div
                class="font-bold text-gray-900 flex items-center gap-2 mb-3 pb-3 border-b border-gray-50"
            >
                <i class="fa-solid fa-box text-blue-500"></i>
                Daftar Produk
            </div>
            <div class="space-y-4">
                {#each order.items as item}
                    <div class="flex gap-3">
                        <!-- Product Image Placeholder -->
                        <div
                            class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden shadow-sm shrink-0 relative"
                        >
                            {#if item.product?.image_url}
                                <img
                                    src={item.product?.image_url}
                                    alt={item.product?.name}
                                    class="w-full h-full object-cover"
                                />
                            {:else}
                                <div
                                    class="w-full h-full flex items-center justify-center text-gray-300"
                                >
                                    <i class="fa-solid fa-image text-xl"></i>
                                </div>
                            {/if}
                        </div>
                        <div class="flex-1">
                            <h3
                                class="font-bold text-sm text-gray-900 mb-1 leading-tight"
                            >
                                {item.product?.name || "Produk"}
                            </h3>
                            <div class="text-xs text-gray-500 mb-1">
                                {item.quantity} x {formatCurrency(item.price)}
                            </div>
                        </div>
                        <div class="font-bold text-sm text-gray-900">
                            {formatCurrency(item.price * item.quantity)}
                        </div>
                    </div>
                {/each}
            </div>
        </div>

        <!-- Payment Details -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <div
                class="font-bold text-gray-900 mb-3 pb-3 border-b border-gray-50"
            >
                Rincian Pembayaran
            </div>

            <div class="space-y-2.5 mb-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500"
                        >Total Harga ({order.items.length} Barang)</span
                    >
                    <span class="text-gray-900">{formatCurrency(subtotal)}</span
                    >
                </div>

                {#if order.discount_amount > 0}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Diskon</span>
                        <span class="text-green-600"
                            >-{formatCurrency(order.discount_amount)}</span
                        >
                    </div>
                {/if}

                {#if order.final_delivery_fee > 0}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Ongkos Kirim</span>
                        <span class="text-gray-900"
                            >{formatCurrency(order.final_delivery_fee)}</span
                        >
                    </div>
                {/if}

                {#if order.admin_fee > 0}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Biaya Administrasi</span>
                        <span class="text-gray-900"
                            >{formatCurrency(order.admin_fee)}</span
                        >
                    </div>
                {/if}

                {#if order.tax_amount > 0}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Pajak</span>
                        <span class="text-gray-900"
                            >{formatCurrency(order.tax_amount)}</span
                        >
                    </div>
                {/if}
            </div>

            <div
                class="flex justify-between items-center border-t border-gray-50 pt-3"
            >
                <span class="font-bold text-gray-900">Total Belanja</span>
                <span class="font-bold text-orange-600 text-lg"
                    >{formatCurrency(order.total_amount)}</span
                >
            </div>

            <div
                class="mt-4 bg-gray-50 p-3 rounded-lg flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-gray-500"></i>
                    <span class="text-sm text-gray-600">Metode Pembayaran</span>
                </div>
                <div class="font-medium text-sm text-gray-900">
                    {order.payment_method?.name || "-"}
                </div>
            </div>
        </div>
    </div>
</div>
