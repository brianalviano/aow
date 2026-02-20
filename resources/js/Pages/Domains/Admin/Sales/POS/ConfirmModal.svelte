<script lang="ts">
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type CartItem = {
        id: string;
        name: string;
        category: string;
        price: number;
        qty: number;
        note?: string | null;
        levelId?: string;
    };
    type CustomerItem = {
        id: string;
        name: string;
        phone?: string | null;
        address?: string | null;
    };
    type PaymentMethodItem = {
        id: string;
        name: string;
    };

    export let isOpen: boolean;
    export let onClose: () => void;

    export let cart: CartItem[];
    export let selectedCustomerId: string;
    export let findCustomer: (id: string) => CustomerItem | null;

    export let subtotal: number;
    export let itemDiscountTotal: number;
    export let extraDiscountTotal: number;
    export let extraDiscountPercentage: string | null;
    export let totalAfterDiscount: number;
    export let taxEnabled: boolean;
    export let taxRate: number;
    export let taxAmount: number;
    export let shippingCost: number;
    export let grandTotal: number;
    export let discountTextByItemId: Record<string, string>;

    export let useShipping: boolean;
    export let shippingRecipientName: string;
    export let shippingRecipientPhone: string | null;
    export let shippingAddress: string | null;
    export let shippingNote: string | null;
    export let voucherCode: string;
    export let voucherAmount: number;

    export let paymentMethods: PaymentMethodItem[];
    export let selectedPayment1Id: string;
    export let selectedPayment2Id: string;
    export let payment1Amount: number;
    export let payment2Amount: number;
    export let paymentNote1: string;
    export let paymentNote2: string;
    export let paymentTotal: number;
    export let changeAmount: number;
    export let shortageAmount: number;
    export let selectedWarehouseId: string;
    export let findWarehouse: (
        id: string,
    ) => { id: string; name: string } | null;
    export let onConfirm: () => void;
    export let processing: boolean = false;
</script>

<Modal bind:isOpen title="Konfirmasi Keranjang" {onClose}>
    {#snippet children()}
        {#if cart.length > 0}
            <div class="space-y-3">
                {#if selectedCustomerId !== ""}
                    <div
                        class="pb-3 border-b border-gray-200 dark:border-[#1a1a1a] space-y-1"
                    >
                        <div
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Customer
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {findCustomer(selectedCustomerId)?.name}
                            {#if findCustomer(selectedCustomerId)?.phone}
                                <span class="text-gray-600 dark:text-gray-400">
                                    ({findCustomer(selectedCustomerId)?.phone})
                                </span>
                            {/if}
                        </div>
                        {#if findCustomer(selectedCustomerId)?.address}
                            <div
                                class="text-xs text-gray-600 dark:text-gray-400"
                            >
                                Alamat: {findCustomer(selectedCustomerId)
                                    ?.address}
                            </div>
                        {/if}
                    </div>
                {/if}
                <div
                    class="pb-3 border-b border-gray-200 dark:border-[#1a1a1a] space-y-1"
                >
                    <div
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Gudang
                    </div>
                    <div class="text-sm text-gray-900 dark:text-white">
                        {findWarehouse(selectedWarehouseId)?.name || "-"}
                    </div>
                </div>
                {#each cart as it (it.id)}
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
                                {formatCurrency(it.price)} × {it.qty}
                            </div>
                            {#if discountTextByItemId && discountTextByItemId[it.id]}
                                <div
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    Diskon: {discountTextByItemId[it.id]}
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
                            {formatCurrency(it.price * it.qty)}
                        </div>
                    </div>
                {/each}
                <div
                    class="space-y-2 pt-2 border-t border-gray-200 dark:border-[#1a1a1a]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Subtotal</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(subtotal)}
                        </span>
                    </div>
                    {#if (itemDiscountTotal ?? 0) > 0}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Total Diskon Item</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(itemDiscountTotal)}
                            </span>
                        </div>
                    {/if}
                    {#if (extraDiscountTotal ?? 0) > 0}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Diskon Tambahan{extraDiscountPercentage
                                    ? ` (${extraDiscountPercentage}%)`
                                    : ""}
                            </span>
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(extraDiscountTotal)}
                            </span>
                        </div>
                    {/if}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Total Setelah Diskon</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(totalAfterDiscount)}
                        </span>
                    </div>
                    {#if (voucherCode ?? "") !== "" || (voucherAmount ?? 0) > 0}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Voucher{voucherCode ? ` (${voucherCode})` : ""}
                            </span>
                            {#if (voucherAmount ?? 0) > 0}
                                <span
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    {formatCurrency(voucherAmount ?? 0)}
                                </span>
                            {:else}
                                <span
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    -
                                </span>
                            {/if}
                        </div>
                    {/if}
                    {#if taxEnabled}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >PPN ({taxRate}%)</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(taxAmount)}
                            </span>
                        </div>
                    {/if}
                    {#if useShipping}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Biaya Pengiriman</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(shippingCost)}
                            </span>
                        </div>
                    {/if}
                    <div class="flex items-center justify-between">
                        <span
                            class="text-base font-semibold text-gray-900 dark:text-white"
                            >Grand Total</span
                        >
                        <span
                            class="text-xl font-bold text-gray-900 dark:text-white"
                        >
                            {formatCurrency(grandTotal)}
                        </span>
                    </div>
                </div>

                {#if useShipping}
                    <div
                        class="pt-3 border-t border-gray-200 dark:border-[#1a1a1a] space-y-1"
                    >
                        <div
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Pengiriman
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="text-gray-900 dark:text-white">
                                Nama: {shippingRecipientName || "-"}
                            </div>
                            <div class="text-gray-900 dark:text-white">
                                Telepon: {shippingRecipientPhone || "-"}
                            </div>
                            <div class="text-gray-900 dark:text-white">
                                Alamat: {shippingAddress || "-"}
                            </div>
                            <div
                                class="text-xs text-gray-600 dark:text-gray-400"
                            >
                                Catatan: {shippingNote || "Tidak ada"}
                            </div>
                        </div>
                    </div>
                {/if}
                <div
                    class="pt-3 border-t border-gray-200 dark:border-[#1a1a1a] space-y-2"
                >
                    <div
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Pembayaran
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Metode #1</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {paymentMethods.find(
                                    (m) => m.id === selectedPayment1Id,
                                )?.name || "-"}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Nominal #1</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(payment1Amount)}
                            </span>
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Catatan: {paymentNote1 || "Tidak ada"}
                        </div>
                    </div>
                    {#if selectedPayment2Id !== ""}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                    >Metode #2</span
                                >
                                <span
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    {paymentMethods.find(
                                        (m) => m.id === selectedPayment2Id,
                                    )?.name || "-"}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                    >Nominal #2</span
                                >
                                <span
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    {formatCurrency(payment2Amount)}
                                </span>
                            </div>
                            <div
                                class="text-xs text-gray-600 dark:text-gray-400"
                            >
                                Catatan: {paymentNote2 || "Tidak ada"}
                            </div>
                        </div>
                    {/if}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400"
                            >Total Bayar</span
                        >
                        <span class="text-sm text-gray-900 dark:text-white">
                            {formatCurrency(paymentTotal)}
                        </span>
                    </div>
                    {#if changeAmount > 0}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Kembalian</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(changeAmount)}
                            </span>
                        </div>
                    {/if}
                    {#if shortageAmount > 0}
                        <div class="flex items-center justify-between">
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Kekurangan</span
                            >
                            <span class="text-sm text-gray-900 dark:text-white">
                                {formatCurrency(shortageAmount)}
                            </span>
                        </div>
                    {/if}
                </div>
            </div>
        {:else}
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Keranjang kosong
            </div>
        {/if}
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={onClose}>Tutup</Button>
        <Button variant="primary" loading={processing} onclick={onConfirm}
            >Proses</Button
        >
    {/snippet}
</Modal>
