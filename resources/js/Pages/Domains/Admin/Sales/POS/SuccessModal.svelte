<script lang="ts">
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    type IdName = { id: string; name: string };
    type Customer = {
        id: string | null;
        name: string | null;
        phone?: string | null;
        address?: string | null;
    } | null;
    type SaleItem = {
        id: string;
        name: string;
        unit_price: number;
        subtotal: number;
        qty: number;
        note?: string | null;
        discounted?: boolean;
        discount_text?: string | null;
    };
    type PaymentEntry = {
        payment_method_id: string | null;
        payment_method_name: string | null;
        amount: number;
        notes?: string | null;
    };
    type SaleCompleted = {
        id: string;
        receipt_number: string;
        invoice_number: string;
        sale_datetime: string | null;
        warehouse: IdName | null;
        customer: Customer;
        payment_status: string | null;
        payment_status_label: string | null;
        subtotal: number;
        discount_percentage: string | null;
        discount_amount: number;
        item_discount_total?: number;
        extra_discount_total?: number;
        voucher_code?: string | null;
        voucher_amount?: number;
        total_after_discount: number;
        is_value_added_tax_enabled: boolean;
        value_added_tax_percentage: string | null;
        value_added_tax_amount: number;
        grand_total: number;
        outstanding_amount: number;
        requires_delivery: boolean;
        items?: SaleItem[];
        shipping_amount?: number;
        shipping_recipient_name?: string | null;
        shipping_recipient_phone?: string | null;
        shipping_address?: string | null;
        shipping_note?: string | null;
        payments?: PaymentEntry[];
        payment_total?: number;
        change_amount?: number;
        shortage_amount?: number;
    };
    export let isOpen: boolean;
    export let onClose: () => void;
    export let sale: SaleCompleted;
    function printReceipt(): void {
        try {
            openCenteredWindow(`/pos/${sale.id}/receipt`, {
                width: 480,
                height: 720,
                fallbackWhenBlocked: false,
            });
        } catch {}
    }
    function printInvoice(): void {
        try {
            openCenteredWindow(`/pos/${sale.id}/invoice`, {
                width: 1024,
                height: 768,
                fallbackWhenBlocked: false,
            });
        } catch {}
    }
    function printDelivery(): void {
        try {
            openCenteredWindow(`/pos/${sale.id}/delivery`, {
                width: 1024,
                height: 768,
                fallbackWhenBlocked: false,
            });
        } catch {}
    }
</script>

<Modal size="lg" bind:isOpen title="Transaksi Berhasil" {onClose}>
    {#snippet children()}
        <div class="space-y-3">
            <div
                class="pb-3 border-b border-gray-200 dark:border-[#1a1a1a] space-y-1"
            >
                <div
                    class="text-sm font-semibold text-gray-900 dark:text-white"
                >
                    Ringkasan
                </div>
                <div class="text-sm text-gray-900 dark:text-white">
                    Nomor Struk: {sale.receipt_number || "-"}
                </div>
                <div class="text-sm text-gray-900 dark:text-white">
                    Nomor Invoice: {sale.invoice_number || "-"}
                </div>
                <div class="text-sm text-gray-900 dark:text-white">
                    Tanggal: {sale.sale_datetime || "-"}
                </div>
                <div class="text-sm text-gray-900 dark:text-white">
                    Gudang: {sale.warehouse?.name || "-"}
                </div>
            </div>
            <div
                class="pb-3 border-b border-gray-200 dark:border-[#1a1a1a] space-y-1"
            >
                <div
                    class="text-sm font-semibold text-gray-900 dark:text-white"
                >
                    Customer
                </div>
                <div class="text-sm text-gray-900 dark:text-white">
                    {sale.customer?.name || "Tanpa Customer"}
                    {#if sale.customer?.phone}
                        <span class="text-gray-600 dark:text-gray-400"
                            >({sale.customer?.phone})</span
                        >
                    {/if}
                </div>
                {#if sale.customer?.address}
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Alamat: {sale.customer?.address}
                    </div>
                {/if}
            </div>
            {#if (sale.items ?? []).length > 0}
                {#each sale.items as it (it.id)}
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <div
                                class="text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {it.name}
                            </div>
                            <div
                                class="text-xs text-gray-600 dark:text-gray-400"
                            >
                                {formatCurrency(it.unit_price)} × {it.qty}
                            </div>
                            {#if it.discount_text}
                                <div
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    Diskon: {it.discount_text}
                                </div>
                            {/if}
                            {#if it.note}
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 italic mt-0.5"
                                >
                                    Note: {it.note}
                                </div>
                            {/if}
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(it.subtotal)}
                        </div>
                    </div>
                {/each}
            {/if}
            <div
                class="space-y-2 pt-2 border-t border-gray-200 dark:border-[#1a1a1a]"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >Subtotal</span
                    >
                    <span class="text-sm text-gray-900 dark:text-white"
                        >{formatCurrency(sale.subtotal)}</span
                    >
                </div>
                {#if (sale.item_discount_total ?? 0) > 0}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Total Diskon Item
                        </span>
                        <span class="text-sm text-gray-900 dark:text-white"
                            >{formatCurrency(
                                sale.item_discount_total ?? 0,
                            )}</span
                        >
                    </div>
                {/if}
                {#if (sale.extra_discount_total ?? 0) > 0}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Diskon Tambahan{sale.discount_percentage
                                ? ` (${sale.discount_percentage}%)`
                                : ""}
                        </span>
                        <span class="text-sm text-gray-900 dark:text-white"
                            >{formatCurrency(
                                sale.extra_discount_total ?? 0,
                            )}</span
                        >
                    </div>
                {/if}
                {#if (sale.voucher_amount ?? 0) > 0}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Voucher{sale.voucher_code
                                ? ` (${sale.voucher_code})`
                                : ""}
                        </span>
                        <span class="text-sm text-gray-900 dark:text-white"
                            >{formatCurrency(sale.voucher_amount ?? 0)}</span
                        >
                    </div>
                {/if}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >Setelah Diskon</span
                    >
                    <span class="text-sm text-gray-900 dark:text-white"
                        >{formatCurrency(sale.total_after_discount)}</span
                    >
                </div>
                {#if sale.is_value_added_tax_enabled}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            PPN{sale.value_added_tax_percentage
                                ? ` (${sale.value_added_tax_percentage}%)`
                                : ""}
                        </span>
                        <span class="text-sm text-gray-900 dark:text-white"
                            >{formatCurrency(sale.value_added_tax_amount)}</span
                        >
                    </div>
                {/if}
                {#if (sale.shipping_amount ?? 0) > 0}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Biaya Pengiriman</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white"
                            >{formatCurrency(sale.shipping_amount ?? 0)}</span
                        >
                    </div>
                {/if}
                <div class="flex items-center justify-between">
                    <span
                        class="text-base font-semibold text-gray-900 dark:text-white"
                        >Total</span
                    >
                    <span
                        class="text-xl font-bold text-gray-900 dark:text-white"
                        >{formatCurrency(sale.grand_total)}</span
                    >
                </div>
            </div>
            <div
                class="pt-3 border-t border-gray-200 dark:border-[#1a1a1a] space-y-1"
            >
                <div
                    class="text-sm font-semibold text-gray-900 dark:text-white"
                >
                    Pembayaran
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >Status</span
                    >
                    <span class="text-sm text-gray-900 dark:text-white"
                        >{sale.payment_status_label || "-"}</span
                    >
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >Piutang</span
                    >
                    <span class="text-sm text-gray-900 dark:text-white"
                        >{formatCurrency(sale.outstanding_amount)}</span
                    >
                </div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Metode #1</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {sale.payments?.[0]?.payment_method_name ?? "-"}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Nominal #1</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(sale.payments?.[0]?.amount ?? 0)}
                        </span>
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Catatan: {sale.payments?.[0]?.notes || "Tidak ada"}
                    </div>
                </div>
                {#if (sale.payments ?? []).length > 1}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Metode #2</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {sale.payments?.[1]?.payment_method_name ?? "-"}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Nominal #2</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(
                                    sale.payments?.[1]?.amount ?? 0,
                                )}
                            </span>
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Catatan: {sale.payments?.[1]?.notes || "Tidak ada"}
                        </div>
                    </div>
                {/if}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >Total Bayar</span
                    >
                    <span class="text-sm text-gray-900 dark:text-white">
                        {formatCurrency(sale.payment_total ?? 0)}
                    </span>
                </div>
                {#if (sale.change_amount ?? 0) > 0}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Kembalian</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(sale.change_amount ?? 0)}
                        </span>
                    </div>
                {/if}
                {#if (sale.shortage_amount ?? 0) > 0}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Kekurangan</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(sale.shortage_amount ?? 0)}
                        </span>
                    </div>
                {/if}
            </div>
            <div
                class="pt-3 border-t border-gray-200 dark:border-[#1a1a1a] space-y-1"
            >
                <div
                    class="text-sm font-semibold text-gray-900 dark:text-white"
                >
                    Pengiriman
                </div>
                {#if sale.requires_delivery}
                    <div class="space-y-1 text-sm">
                        <div class="text-gray-900 dark:text-white">
                            Nama: {sale.shipping_recipient_name || "-"}
                        </div>
                        <div class="text-gray-900 dark:text-white">
                            Telepon: {sale.shipping_recipient_phone || "-"}
                        </div>
                        <div class="text-gray-900 dark:text-white">
                            Alamat: {sale.shipping_address || "-"}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Catatan: {sale.shipping_note || "Tidak ada"}
                        </div>
                    </div>
                {:else}
                    <div class="text-sm text-gray-900 dark:text-white">
                        Tanpa pengiriman
                    </div>
                {/if}
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="primary" onclick={printReceipt}>Cetak Struk</Button>
        <Button variant="secondary" onclick={printInvoice}>Cetak Invoice</Button
        >
        {#if sale.requires_delivery}
            <Button variant="secondary" onclick={printDelivery}
                >Cetak Surat Jalan</Button
            >
        {/if}
        <Button variant="secondary" onclick={onClose}>Tutup</Button>
    {/snippet}
</Modal>
