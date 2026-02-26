<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
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
    }

    interface Order {
        id: string;
        number: string;
        customer: { name: string; email: string; phone: string };
        drop_point?: { name: string; address: string };
        payment_method?: { name: string; category: string };
        delivery_date: string;
        delivery_time: string;
        shipping_method: string;
        delivery_detail: string;
        order_status: string;
        payment_status: string;
        total_amount: number;
        discount_amount: number;
        delivery_fee: number;
        admin_fee: number;
        tax_amount: number;
        payment_expired_at?: string;
        note?: string;
        created_at: string;
        items: OrderItem[];
    }

    let order = $derived($page.props.order.data as Order);

    function formatCurrency(amount: number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
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
        <div class="flex gap-2">
            <Badge
                size="lg"
                variant={getStatusBadge(order.order_status).variant}
            >
                {#snippet children()}{getStatusBadge(order.order_status)
                        .label}{/snippet}
            </Badge>
            <Badge
                size="lg"
                variant={getPaymentBadge(order.payment_status).variant}
            >
                {#snippet children()}{getPaymentBadge(order.payment_status)
                        .label}{/snippet}
            </Badge>
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
                                Drop Point
                            </div>
                            <div class="mt-1 text-gray-900 dark:text-white">
                                {order.drop_point?.name ?? "Tidak ada"}
                            </div>
                            <div class="text-sm text-gray-500">
                                {order.drop_point?.address ?? ""}
                            </div>
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
                        <div>
                            <div
                                class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Detail Alamat / Nama Penerima
                            </div>
                            <div
                                class="mt-1 text-gray-900 dark:text-white whitespace-pre-line"
                            >
                                {order.delivery_detail || "-"}
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
        </div>
    </div>
</section>
