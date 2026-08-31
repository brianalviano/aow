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
        payment_proof_url?: string;
    }

    let orders = $derived(
        page.props.orders as {
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

    function approveOrder(orderId: string) {
        router.post(
            `/admin/orders/${orderId}/confirm`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }

    function rejectOrder(orderId: string) {
        router.post(
            `/admin/orders/${orderId}/cancel`,
            { cancellation_note: "Ditolak oleh Admin" },
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }
</script>

<svelte:head>
    <title>Approval Pembayaran | {name(page.props.settings)}</title>
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
                        <th>Bukti</th>
                        <th class="w-48 text-center">Aksi</th>
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
                                <td class="text-center">
                                    {#if item.payment_proof_url}
                                        <div
                                            class="flex items-center justify-center"
                                        >
                                            <img
                                                src={item.payment_proof_url}
                                                alt="Bukti"
                                                class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-700"
                                            />
                                        </div>
                                    {:else}
                                        <span
                                            class="text-[10px] text-gray-400 italic"
                                            >Belum ada</span
                                        >
                                    {/if}
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-center"
                                >
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <Button
                                            variant="primary"
                                            size="xs"
                                            icon="fa-solid fa-eye"
                                            href={`/admin/orders/${item.id}?from=payments`}
                                            title="Detail"
                                        />
                                        <Button
                                            variant="success"
                                            size="xs"
                                            icon="fa-solid fa-check"
                                            disabled={isProcessing}
                                            onclick={() =>
                                                openConfirm(
                                                    "Konfirmasi Pembayaran",
                                                    `Apakah Anda yakin ingin menyetujui pembayaran untuk pesanan #${item.number}?`,
                                                    () =>
                                                        approveOrder(item.id),
                                                    "success",
                                                )}
                                            title="Setujui"
                                        />
                                        <Button
                                            variant="danger"
                                            size="xs"
                                            icon="fa-solid fa-xmark"
                                            disabled={isProcessing}
                                            onclick={() =>
                                                openConfirm(
                                                    "Tolak Pesanan",
                                                    `Apakah Anda yakin ingin menolak/membatalkan pesanan #${item.number}?`,
                                                    () => rejectOrder(item.id),
                                                    "danger",
                                                )}
                                            title="Tolak"
                                        />
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    {:else}
                        <tr>
                            <td
                                colspan="8"
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

<!-- Confirmation Dialog Modal -->
{#if confirmDialog.open}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
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
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {confirmDialog.title}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {confirmDialog.message}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" size="sm" onclick={closeConfirm}>
                    Batal
                </Button>
                <Button
                    variant={confirmDialog.variant === "danger"
                        ? "danger"
                        : confirmDialog.variant === "success"
                          ? "success"
                          : "primary"}
                    size="sm"
                    disabled={isProcessing}
                    onclick={executeAction}
                >
                    Ya, Lanjutkan
                </Button>
            </div>
        </div>
    </div>
{/if}

