<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    interface PaymentMethod {
        id: string;
        name: string;
        category: string;
    }

    interface Customer {
        id: string;
        name: string;
        email: string;
    }

    interface Order {
        id: string;
        number: string;
        total_amount: number;
        payment_status: string;
        payment_expired_at: string | null;
        created_at: string;
        customer?: Customer;
        payment_method?: PaymentMethod;
        items?: any[];
    }

    let orders = $derived(
        $page.props.orders as {
            data: Order[];
            meta?: any;
        },
    );

    let meta = $derived(
        orders?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(orders?.data ?? []);

    function goToPage(pageNumber: number) {
        router.get(
            "/admin/orders/payments",
            { page: pageNumber },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function formatCurrency(amount: number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function formatDateTime(dateStr: string) {
        return new Date(dateStr).toLocaleString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    function getExpiryStatus(expiredAt: string | null): {
        label: string;
        classes: string;
    } {
        if (!expiredAt) return { label: "-", classes: "text-gray-400" };

        const now = new Date();
        const expiry = new Date(expiredAt);
        const diffMs = expiry.getTime() - now.getTime();
        const diffHours = diffMs / (1000 * 60 * 60);

        if (diffMs < 0) {
            return {
                label: "Kedaluwarsa",
                classes: "text-red-600 font-semibold",
            };
        } else if (diffHours <= 6) {
            return {
                label: formatDateTime(expiredAt),
                classes: "text-orange-500 font-semibold",
            };
        } else {
            return {
                label: formatDateTime(expiredAt),
                classes: "text-gray-700 dark:text-gray-300",
            };
        }
    }
</script>

<svelte:head>
    <title>Approval Pembayaran | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Approval Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Pesanan yang menunggu konfirmasi pembayaran dari pelanggan
            </p>
        </div>
    </header>

    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800"
    >
        <div class="overflow-x-auto">
            <table class="custom-table min-w-full">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Metode Bayar</th>
                        <th>Waktu Order</th>
                        <th>Batas Bayar</th>
                        <th class="w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#if items.length > 0}
                        {#each items as item}
                            {@const expiry = getExpiryStatus(
                                item.payment_expired_at,
                            )}
                            <tr>
                                <td
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {item.number}
                                </td>
                                <td>
                                    <div
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.customer?.name ?? "-"}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {item.customer?.email ?? ""}
                                    </div>
                                    {#if item.items && item.items.length > 0}
                                        <div
                                            class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium italic border-t border-gray-100 dark:border-gray-800 pt-1"
                                        >
                                            {item.items
                                                .map(
                                                    (i: any) =>
                                                        `${i.product?.name ?? "Produk"} ${i.quantity}`,
                                                )
                                                .join(", ")}
                                        </div>
                                    {/if}
                                </td>
                                <td>
                                    <div
                                        class="text-sm font-bold text-gray-900 dark:text-white"
                                    >
                                        {formatCurrency(item.total_amount)}
                                    </div>
                                </td>
                                <td>
                                    <div
                                        class="text-sm text-gray-700 dark:text-gray-300"
                                    >
                                        {item.payment_method?.name ?? "-"}
                                    </div>
                                    {#if item.payment_method?.category}
                                        <div
                                            class="text-xs text-gray-400 capitalize"
                                        >
                                            {item.payment_method.category}
                                        </div>
                                    {/if}
                                </td>
                                <td>
                                    <div
                                        class="text-sm text-gray-700 dark:text-gray-300"
                                    >
                                        {formatDateTime(item.created_at)}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm {expiry.classes}">
                                        {expiry.label}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-center"
                                >
                                    <Button
                                        variant="primary"
                                        size="sm"
                                        icon="fa-solid fa-eye"
                                        href={`/admin/orders/${item.id}?from=payments`}
                                    >
                                        Detail
                                    </Button>
                                </td>
                            </tr>
                        {/each}
                    {:else}
                        <tr>
                            <td
                                colspan="7"
                                class="py-12 text-sm text-center text-gray-500 dark:text-gray-400"
                            >
                                <div
                                    class="flex flex-col items-center justify-center space-y-2"
                                >
                                    <i
                                        class="fa-solid fa-check-circle text-4xl text-green-300"
                                    ></i>
                                    <p>
                                        Tidak ada pesanan yang menunggu
                                        pembayaran
                                    </p>
                                </div>
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        </div>
    </div>
</section>
