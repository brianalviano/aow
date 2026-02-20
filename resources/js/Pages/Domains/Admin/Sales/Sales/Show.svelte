<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";

    type IdName = { id: string | null; name: string | null };
    type Customer = {
        id: string | null;
        name: string | null;
        phone?: string | null;
        address?: string | null;
    } | null;
    type SaleItem = {
        sales_item_id: string;
        id: string;
        name: string;
        price?: number;
        unit_price: number;
        qty: number;
        subtotal: number;
        note?: string | null;
        discounted?: boolean;
        discount_text?: string | null;
        discount_amount?: number;
        voucher_share?: number;
        total_after_discount?: number;
        total_after_discount_and_voucher?: number;
    };
    type PaymentEntry = {
        payment_method_id: string | null;
        payment_method_name: string | null;
        amount: number;
        notes?: string | null;
        payment_date?: string | null;
        paid_at?: string | null;
    };
    type SaleCompleted = {
        id: string;
        receipt_number: string;
        invoice_number: string;
        delivery_number?: string;
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

    let sale = $derived(($page.props as any).sale as SaleCompleted);
    let selectedPayment1Id = $state("");
    let selectedPayment2Id = $state("");
    let payment1Str = $state("");
    let payment2Str = $state("");
    let paymentNote1 = $state("");
    let paymentNote2 = $state("");
    let useSecondPayment = $state(false);
    let showSettleDialog = $state(false);
    let settleProcessing = $state(false);

    function backToList() {
        router.visit("/sales");
    }

    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        }).format(d);
    }

    function paymentMethodOptions(): { value: string; label: string }[] {
        const rows = (($page.props as any).payment_methods ?? []) as {
            id: string;
            name: string;
        }[];
        return rows.map((pm) => ({ value: pm.id, label: pm.name }));
    }
    function getPaymentMethodNameById(id: string): string {
        const rows = (($page.props as any).payment_methods ?? []) as {
            id: string;
            name: string;
        }[];
        const found = rows.find((pm) => pm.id === id);
        return found?.name ?? "-";
    }

    function toggleSecondPayment() {
        useSecondPayment = !useSecondPayment;
        if (!useSecondPayment) {
            selectedPayment2Id = "";
            payment2Str = "";
            paymentNote2 = "";
        }
    }

    let showPaymentNoteModal = $state(false);
    let paymentNoteDraft = $state("");
    let paymentNoteModalMethodIndex = $state(1);

    function openPaymentNoteModal(idx: number) {
        paymentNoteModalMethodIndex = idx;
        paymentNoteDraft = idx === 1 ? paymentNote1 : paymentNote2;
        showPaymentNoteModal = true;
    }

    function closePaymentNoteModal() {
        showPaymentNoteModal = false;
        paymentNoteDraft = "";
    }

    function savePaymentNote() {
        const v = paymentNoteDraft.trim();
        if (paymentNoteModalMethodIndex === 1) {
            paymentNote1 = v;
        } else {
            paymentNote2 = v;
        }
        closePaymentNoteModal();
    }

    function toInt(s: string): number {
        return Number(String(s || "0").replace(/\D+/g, ""));
    }

    let shortageAmount = $derived(
        Math.max(
            sale.grand_total -
                ((sale.payment_total ?? 0) +
                    toInt(payment1Str) +
                    (useSecondPayment ? toInt(payment2Str) : 0)),
            0,
        ),
    );
    let changeAmount = $derived(
        Math.max(
            (sale.payment_total ?? 0) +
                toInt(payment1Str) +
                (useSecondPayment ? toInt(payment2Str) : 0) -
                sale.grand_total,
            0,
        ),
    );
    function settleDialogMessage(): string {
        const a1 = toInt(payment1Str);
        const a2 = useSecondPayment ? toInt(payment2Str) : 0;
        const parts: string[] = [];
        if (selectedPayment1Id && a1 > 0) {
            parts.push(
                `Metode #1 ${getPaymentMethodNameById(selectedPayment1Id)}: ${formatCurrency(a1)}`,
            );
        }
        if (useSecondPayment && selectedPayment2Id && a2 > 0) {
            parts.push(
                `Metode #2 ${getPaymentMethodNameById(selectedPayment2Id)}: ${formatCurrency(a2)}`,
            );
        }
        const totalAdd = a1 + a2;
        const status =
            shortageAmount > 0
                ? `Masih kurang ${formatCurrency(shortageAmount)}`
                : changeAmount > 0
                  ? `Kembalian ${formatCurrency(changeAmount)}`
                  : "Akan lunas";
        return `Apakah Anda yakin ingin menyelesaikan pembayaran ${parts.join(" + ")} (Total: ${formatCurrency(
            totalAdd,
        )})? ${status}`;
    }

    function openSettleDialog() {
        showSettleDialog = true;
    }
    async function settlePayments() {
        const p1 = Number(String(payment1Str || "0").replace(/\D+/g, ""));
        const p2 = Number(String(payment2Str || "0").replace(/\D+/g, ""));
        const entries: Array<{
            amount: number;
            payment_method_id: string;
            notes?: string | null;
        }> = [];
        if (selectedPayment1Id && p1 > 0) {
            entries.push({
                amount: p1,
                payment_method_id: selectedPayment1Id,
                notes: (paymentNote1 || "").trim() || null,
            });
        }
        if (useSecondPayment && selectedPayment2Id && p2 > 0) {
            entries.push({
                amount: p2,
                payment_method_id: selectedPayment2Id,
                notes: (paymentNote2 || "").trim() || null,
            });
        }
        if (entries.length === 0) return;
        await router.post(
            `/sales/${sale.id}/settle`,
            { payments: entries },
            { preserveScroll: true },
        );
    }

    let showCreateDeliveryModal = $state(false);
    let recipientName = $state("");
    let recipientPhone = $state("");
    let deliveryAddress = $state("");
    let shippingNote = $state("");
    let gratisShipping = $state(false);
    let shippingAmountStr = $state("");

    function openCreateDeliveryModal() {
        showCreateDeliveryModal = true;
    }
    function closeCreateDeliveryModal() {
        showCreateDeliveryModal = false;
    }
    async function createManualDelivery(payload: Record<string, any>) {
        await router.post(`/sales/${sale.id}/deliveries`, payload, {
            preserveScroll: true,
        });
    }

    let showCreateReturnModal = $state(false);
    let returnReason = $state("");
    let returnResolution = $state("");
    let returnNotes = $state("");
    let retQtyMap = $state<Record<string, number>>({});
    let retItemNotesMap = $state<Record<string, string>>({});
    function openCreateReturnModal() {
        showCreateReturnModal = true;
        returnReason = String(
            (($page.props as any).returnReasonOptions?.[0]?.value ?? "") || "",
        );
        returnResolution = String(
            (($page.props as any).returnResolutionOptions?.[0]?.value ?? "") ||
                "",
        );
        returnNotes = "";
        retQtyMap = {};
        retItemNotesMap = {};
    }
    function closeCreateReturnModal() {
        showCreateReturnModal = false;
    }
    function reasonOptions(): { value: string; label: string }[] {
        const rows = (($page.props as any).returnReasonOptions ?? []) as {
            value: string;
            label: string;
        }[];
        return rows.map((r) => ({ value: r.value, label: r.label }));
    }
    function resolutionOptions(): { value: string; label: string }[] {
        const rows = (($page.props as any).returnResolutionOptions ?? []) as {
            value: string;
            label: string;
        }[];
        return rows.map((r) => ({ value: r.value, label: r.label }));
    }
    function setRetQty(salesItemId: string, v: number, max: number) {
        const n = Math.max(Math.min(Number(v || 0), max), 0);
        retQtyMap = { ...retQtyMap, [salesItemId]: n };
    }
    function setRetItemNote(salesItemId: string, v: string) {
        retItemNotesMap = {
            ...retItemNotesMap,
            [salesItemId]: String(v || "").trim(),
        };
    }
    function computeRefundTotal(): number {
        const items = (sale.items ?? []) as SaleItem[];
        let total = 0;
        for (const it of items) {
            const sid = String(it.sales_item_id || "");
            const qty = Number(retQtyMap[sid] || 0);
            if (qty > 0) {
                total += Number(it.unit_price ?? 0) * qty;
            }
        }
        return total;
    }
    async function createSalesReturn() {
        const itemsPayload: Array<{
            sales_item_id: string;
            quantity: number;
            notes?: string | null;
        }> = [];
        const items = (sale.items ?? []) as any[];
        for (const it of items) {
            const sid = String(it.sales_item_id || "");
            const qty = Number(retQtyMap[sid] || 0);
            if (qty > 0) {
                itemsPayload.push({
                    sales_item_id: sid,
                    quantity: qty,
                    notes: (retItemNotesMap[sid] || "").trim() || null,
                });
            }
        }
        if (itemsPayload.length === 0) return;
        await router.post(
            `/sales/${sale.id}/returns`,
            {
                reason: returnReason,
                resolution: returnResolution,
                notes: (returnNotes || "").trim() || null,
                items: itemsPayload,
            },
            { preserveScroll: true },
        );
        showCreateReturnModal = false;
    }
</script>

<svelte:head>
    <title>Detail Penjualan | {siteName(($page.props as any).settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Penjualan
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {sale.invoice_number || sale.receipt_number}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="primary"
                icon="fa-solid fa-file-invoice"
                onclick={() =>
                    openCenteredWindow(`/pos/${sale.id}/invoice`, {
                        width: 960,
                        height: 700,
                        fallbackWhenBlocked: false,
                    })}>Cetak Invoice</Button
            >
            {#if sale.delivery_number}
                <Button
                    variant="primary"
                    icon="fa-solid fa-truck-fast"
                    onclick={() =>
                        openCenteredWindow(`/pos/${sale.id}/delivery`, {
                            width: 960,
                            height: 700,
                            fallbackWhenBlocked: false,
                        })}>Cetak Surat Jalan</Button
                >
            {:else}
                <Button
                    variant="warning"
                    icon="fa-solid fa-plus"
                    onclick={openCreateDeliveryModal}>Buat Surat Jalan</Button
                >
            {/if}
            <Button
                variant="primary"
                icon="fa-solid fa-receipt"
                onclick={() =>
                    openCenteredWindow(`/pos/${sale.id}/receipt`, {
                        width: 540,
                        height: 800,
                        fallbackWhenBlocked: false,
                    })}>Cetak Struk</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-rotate-left"
                onclick={openCreateReturnModal}>Buat Retur</Button
            >
        </div>
    </header>

    <Card collapsible={false} bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="p-6">
                <div
                    class="w-full flex flex-col md:flex-row justify-between items-start gap-6"
                >
                    <div class="w-full md:w-1/2">
                        <div class="mb-2">
                            <img
                                src={logo}
                                alt="Logo"
                                class="w-90 object-contain"
                                loading="lazy"
                            />
                        </div>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                        >
                            Gudang: {sale.warehouse?.name || "-"}
                        </p>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                        >
                            Alamat: {($page.props as any)?.settings?.address ||
                                "-"}
                        </p>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                        >
                            No. Whatsapp: {($page.props as any)?.settings
                                ?.whatsapp_number || "-"}
                        </p>
                    </div>
                    <div
                        class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                    >
                        <h2
                            class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                        >
                            PENJUALAN
                        </h2>
                        <div class="w-full flex justify-end gap-12 text-right">
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    No Invoice
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {sale.invoice_number || "-"}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    No Struk
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {sale.receipt_number || "-"}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    Tanggal
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {formatDateIndo(sale.sale_datetime)}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-[#212121] my-4" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            CUSTOMER
                        </p>
                        <div class="mb-2">
                            <h3
                                class="text-lg font-medium text-gray-800 dark:text-white"
                            >
                                {sale.customer?.name || "-"}
                            </h3>
                        </div>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 mb-1"
                        >
                            No. Whatsapp : {sale.customer?.phone || "-"}
                        </p>
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span>Alamat</span>
                            <span>: {sale.customer?.address || "-"}</span>
                        </div>
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                        >
                            CATATAN
                        </p>
                        <div
                            class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                        >
                            {(sale.shipping_note ?? "").trim() || "-"}
                        </div>
                    </div>
                </div>

                {#if sale.requires_delivery}
                    <hr class="border-gray-200 dark:border-[#212121] my-4" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                PENGIRIMAN
                            </p>
                            <div
                                class="space-y-1 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <div class="flex justify-between">
                                    <span>Penerima</span>
                                    <span
                                        >{sale.shipping_recipient_name ||
                                            "-"}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span>Telepon</span>
                                    <span
                                        >{sale.shipping_recipient_phone ||
                                            "-"}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span>Alamat</span>
                                    <span class="text-right"
                                        >{sale.shipping_address || "-"}</span
                                    >
                                </div>
                            </div>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                            >
                                Nomor Surat Jalan
                            </p>
                            <div
                                class="text-sm text-gray-800 dark:text-gray-300"
                            >
                                {sale.delivery_number || "-"}
                            </div>
                        </div>
                    </div>
                {/if}

                <hr class="border-gray-200 dark:border-[#212121] my-4" />

                <div class="overflow-x-auto mb-6">
                    <table
                        class="w-full text-sm text-left custom-table border-collapse dark:text-gray-300"
                    >
                        <thead
                            class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-200 dark:bg-gray-800"
                        >
                            <tr>
                                <th
                                    scope="col"
                                    class="px-3 py-2 text-center w-12">NO</th
                                >
                                <th scope="col" class="px-3 py-2 w-1/4"
                                    >PRODUK</th
                                >
                                <th
                                    scope="col"
                                    class="px-3 py-2 text-center w-20">QTY</th
                                >
                                <th scope="col" class="px-3 py-2 text-right"
                                    >HARGA</th
                                >
                                <th scope="col" class="px-3 py-2 text-right"
                                    >SUBTOTAL</th
                                >
                                <th scope="col" class="px-3 py-2 w-1/4"
                                    >CATATAN</th
                                >
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 dark:text-gray-300">
                            {#each sale.items ?? [] as item, index}
                                <tr>
                                    <td class="px-3 py-2 text-center"
                                        >{index + 1}</td
                                    >
                                    <td class="px-3 py-2">
                                        <span>{item.name || "-"}</span>
                                        {#if item.discounted}
                                            <span
                                                class="ml-2 text-xs text-green-700 dark:text-green-400 uppercase"
                                                >Diskon</span
                                            >
                                        {/if}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        {item.qty}
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex justify-end">
                                            <span>{formatCurrency(item.unit_price)}</span>
                                            <span>{formatCurrency(item.subtotal)}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-3 py-2 text-gray-600 dark:text-gray-400"
                                    >
                                        {item.note || "-"}
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                        >
                            PEMBAYARAN
                        </p>
                        <div class="space-y-2 text-sm">
                            {#if (sale.payments ?? []).length > 0}
                                {#each sale.payments as p, idx}
                                    <div class="flex justify-between">
                                        <span
                                            >Metode{(sale.payments ?? [])
                                                .length > 1
                                                ? ` #${idx + 1}`
                                                : ""}</span
                                        >
                                        <span
                                            >{p.payment_method_name ??
                                                "-"}</span
                                        >
                                    </div>
                                    {#if (sale.payments ?? []).length > 1}
                                        <div class="flex justify-between">
                                            <span
                                                >Nominal{(sale.payments ?? [])
                                                    .length > 1
                                                    ? ` #${idx + 1}`
                                                    : ""}</span
                                            >
                                            <span
                                                >{formatCurrency(
                                                    p.amount ?? 0,
                                                )}</span
                                            >
                                        </div>
                                    {/if}
                                    {#if p.notes}
                                        <div class="flex justify-between">
                                            <span>Catatan</span>
                                            <span>{p.notes}</span>
                                        </div>
                                    {/if}
                                {/each}
                            {/if}
                            <div class="flex justify-between font-semibold">
                                <span>Total Bayar</span>
                                <span
                                    >{formatCurrency(
                                        sale.payment_total ?? 0,
                                    )}</span
                                >
                            </div>
                            {#if (sale.change_amount ?? 0) > 0}
                                <div class="flex justify-between">
                                    <span>Kembalian</span>
                                    <span
                                        >{formatCurrency(
                                            sale.change_amount ?? 0,
                                        )}</span
                                    >
                                </div>
                            {/if}
                            {#if (sale.shortage_amount ?? 0) > 0}
                                <div class="flex justify-between">
                                    <span>Kekurangan</span>
                                    <span
                                        >{formatCurrency(
                                            sale.shortage_amount ?? 0,
                                        )}</span
                                    >
                                </div>
                            {/if}
                            {#if (sale.outstanding_amount ?? 0) > 0}
                                <div class="flex justify-between">
                                    <span>Piutang</span>
                                    <span
                                        >{formatCurrency(
                                            sale.outstanding_amount ?? 0,
                                        )}</span
                                    >
                                </div>
                            {/if}
                        </div>
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                        >
                            RINGKASAN
                        </p>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>{formatCurrency(sale.subtotal)}</span>
                            </div>
                            {#if (sale.item_discount_total ?? 0) > 0}
                                <div class="flex justify-between">
                                    <span>Total Diskon Item</span>
                                    <span
                                        >{formatCurrency(
                                            sale.item_discount_total ?? 0,
                                        )}</span
                                    >
                                </div>
                            {/if}
                            {#if (sale.extra_discount_total ?? 0) > 0}
                                <div class="flex justify-between">
                                    <span
                                        >Diskon Tambahan{sale.discount_percentage
                                            ? ` (${sale.discount_percentage}%)`
                                            : ""}</span
                                    >
                                    <span
                                        >{formatCurrency(
                                            sale.extra_discount_total ?? 0,
                                        )}</span
                                    >
                                </div>
                            {/if}
                            <div class="flex justify-between">
                                <span>Setelah Diskon</span>
                                <span
                                    >{formatCurrency(
                                        sale.total_after_discount,
                                    )}</span
                                >
                            </div>
                            {#if sale.is_value_added_tax_enabled}
                                <div class="flex justify-between">
                                    <span
                                        >PPN{sale.value_added_tax_percentage
                                            ? ` (${sale.value_added_tax_percentage}%)`
                                            : ""}</span
                                    >
                                    <span
                                        >{formatCurrency(
                                            sale.value_added_tax_amount,
                                        )}</span
                                    >
                                </div>
                            {/if}
                            {#if (sale.shipping_amount ?? 0) > 0}
                                <div class="flex justify-between">
                                    <span>Biaya Pengiriman</span>
                                    <span
                                        >{formatCurrency(
                                            sale.shipping_amount ?? 0,
                                        )}</span
                                    >
                                </div>
                            {/if}
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total</span>
                                <span>{formatCurrency(sale.grand_total)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/snippet}
    </Card>

    <Modal
        bind:isOpen={showCreateDeliveryModal}
        title="Buat Surat Jalan Manual"
        size="md"
        centered={true}
        backdrop="clickable"
        keyboard={true}
        lockScroll={true}
        onClose={() => {
            showCreateDeliveryModal = false;
        }}
        onShow={() => {
            recipientName = String(sale.customer?.name ?? "");
            recipientPhone = String(sale.customer?.phone ?? "");
            deliveryAddress = String(sale.customer?.address ?? "");
            shippingNote = String(sale.shipping_note ?? "");
        }}
    >
        {#snippet children()}
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <TextInput
                        id="recipient_name"
                        name="recipient_name"
                        label="Nama Penerima"
                        placeholder="Nama penerima"
                        bind:value={recipientName}
                    />
                    <TextInput
                        id="recipient_phone"
                        name="recipient_phone"
                        label="No. Whatsapp Penerima"
                        placeholder="08xxxxxxxxxx"
                        bind:value={recipientPhone}
                    />
                </div>
                <TextInput
                    id="delivery_address"
                    name="delivery_address"
                    label="Alamat Pengiriman"
                    placeholder="Alamat lengkap"
                    bind:value={deliveryAddress}
                />
                <TextInput
                    id="shipping_note"
                    name="shipping_note"
                    label="Catatan Pengiriman"
                    placeholder="Catatan tambahan"
                    bind:value={shippingNote}
                />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Checkbox
                        id="gratis_shipping"
                        name="gratis_shipping"
                        label="Gratis"
                        bind:checked={gratisShipping}
                    />
                </div>
                {#if !gratisShipping}
                    <TextInput
                        id="shipping_amount"
                        name="shipping_amount"
                        label="Nominal (Rp)"
                        placeholder="Misal: 50.000"
                        currency={true}
                        currencySymbol="Rp"
                        thousandSeparator="."
                        decimalSeparator=","
                        maxDecimals={0}
                        stripZeros={true}
                        bind:value={shippingAmountStr}
                        oninput={(ev) =>
                            (shippingAmountStr = String(
                                (ev as any).numericValue ?? 0,
                            ))}
                    />
                {/if}
            </div>
        {/snippet}
        {#snippet footerSlot()}
            <div class="flex items-center justify-end space-x-3">
                <Button
                    variant="secondary"
                    onclick={() => {
                        showCreateDeliveryModal = false;
                    }}
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    icon="fa-solid fa-check"
                    onclick={() => {
                        const payload: Record<string, any> = {
                            recipient_name:
                                (recipientName || "").trim() || null,
                            recipient_phone:
                                (recipientPhone || "").trim() || null,
                            delivery_address:
                                (deliveryAddress || "").trim() || null,
                            shipping_note: (shippingNote || "").trim() || null,
                            shipping_amount: gratisShipping
                                ? 0
                                : toInt(shippingAmountStr),
                        };
                        createManualDelivery(payload);
                        showCreateDeliveryModal = false;
                    }}
                >
                    Buat
                </Button>
            </div>
        {/snippet}
    </Modal>

    <Modal
        bind:isOpen={showCreateReturnModal}
        title="Buat Retur Penjualan"
        size="lg"
        centered={true}
        backdrop="clickable"
        keyboard={true}
        lockScroll={true}
        onClose={() => {
            showCreateReturnModal = false;
        }}
        onShow={() => {
            returnReason = String(
                (($page.props as any).returnReasonOptions?.[0]?.value ?? "") ||
                    "",
            );
            returnResolution = String(
                (($page.props as any).returnResolutionOptions?.[0]?.value ??
                    "") ||
                    "",
            );
        }}
    >
        {#snippet children()}
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Select
                        minimal
                        searchable={false}
                        placeholder="Alasan Retur"
                        options={reasonOptions()}
                        value={returnReason}
                        onchange={(v) => (returnReason = String(v))}
                    />
                    <Select
                        minimal
                        searchable={false}
                        placeholder="Penyelesaian"
                        options={resolutionOptions()}
                        value={returnResolution}
                        onchange={(v) => (returnResolution = String(v))}
                    />
                    <TextInput
                        id="return_notes"
                        name="return_notes"
                        placeholder="Catatan retur (opsional)"
                        bind:value={returnNotes}
                    />
                </div>
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-sm text-left custom-table border-collapse dark:text-gray-300"
                    >
                        <thead
                            class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-200 dark:bg-gray-800"
                        >
                            <tr>
                                <th scope="col" class="px-3 py-2">PRODUK</th>
                                <th
                                    scope="col"
                                    class="px-3 py-2 text-center w-24">QTY</th
                                >
                                <th
                                    scope="col"
                                    class="px-3 py-2 text-right w-32">HARGA</th
                                >
                                <th
                                    scope="col"
                                    class="px-3 py-2 text-center w-28">RETUR</th
                                >
                                <th scope="col" class="px-3 py-2 w-1/3"
                                    >CATATAN ITEM</th
                                >
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 dark:text-gray-300">
                            {#each sale.items ?? [] as item}
                                <tr>
                                    <td class="px-3 py-2">
                                        <span>{item.name || "-"}</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        {item.qty}
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    item.unit_price,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <TextInput
                                            id={"ret_qty_" +
                                                String(
                                                    item.sales_item_id || "",
                                                )}
                                            name={"ret_qty_" +
                                                String(
                                                    item.sales_item_id || "",
                                                )}
                                            placeholder="0"
                                            value={(retQtyMap[
                                                String(item.sales_item_id || "")
                                            ] ?? 0) + ""}
                                            oninput={(ev) =>
                                                setRetQty(
                                                    String(
                                                        item.sales_item_id ||
                                                            "",
                                                    ),
                                                    Number(
                                                        String(
                                                            (ev as any).target
                                                                ?.value || "",
                                                        ).replace(/\D+/g, ""),
                                                    ),
                                                    Number(item.qty || 0),
                                                )}
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <TextInput
                                            id={"ret_note_" +
                                                String(
                                                    item.sales_item_id || "",
                                                )}
                                            name={"ret_note_" +
                                                String(
                                                    item.sales_item_id || "",
                                                )}
                                            placeholder="Catatan"
                                            value={retItemNotesMap[
                                                String(item.sales_item_id || "")
                                            ] ?? ""}
                                            oninput={(ev) =>
                                                setRetItemNote(
                                                    String(
                                                        item.sales_item_id ||
                                                            "",
                                                    ),
                                                    String(
                                                        (ev as any).target
                                                            ?.value || "",
                                                    ),
                                                )}
                                        />
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600 dark:text-gray-400">
                        {returnResolution === "refund"
                            ? "Total Refund"
                            : returnResolution === "store_credit"
                              ? "Total Kredit Toko"
                              : "Retur tanpa dana"}
                    </div>
                    <div class="font-semibold">
                        {formatCurrency(
                            returnResolution === "exchange"
                                ? 0
                                : computeRefundTotal(),
                        )}
                    </div>
                </div>
            </div>
        {/snippet}
        {#snippet footerSlot()}
            <div class="flex items-center justify-end space-x-3">
                <Button
                    variant="secondary"
                    onclick={() => {
                        showCreateReturnModal = false;
                    }}
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    icon="fa-solid fa-check"
                    onclick={createSalesReturn}
                >
                    Buat
                </Button>
            </div>
        {/snippet}
    </Modal>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class={(sale.outstanding_amount ?? 0) > 0 ? "" : "col-span-2"}>
            <Card
                title="Riwayat Pembayaran"
                collapsible={false}
                bodyWithoutPadding={true}
            >
                {#snippet children()}
                    <div class="overflow-x-auto">
                        {#if (sale.payments ?? []).length > 0}
                            <table
                                class="w-full text-sm text-left custom-table border-collapse dark:text-gray-300"
                            >
                                <thead
                                    class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-200 dark:bg-gray-800"
                                >
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-3 py-2 w-40 whitespace-nowrap"
                                            >WAKTU</th
                                        >
                                        <th
                                            scope="col"
                                            class="px-3 py-2 whitespace-nowrap"
                                            >METODE</th
                                        >
                                        <th
                                            scope="col"
                                            class="px-3 py-2 text-right w-32 whitespace-nowrap"
                                            >NOMINAL</th
                                        >
                                        <th
                                            scope="col"
                                            class="px-3 py-2 w-1/3 whitespace-nowrap"
                                            >CATATAN</th
                                        >
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700 dark:text-gray-300">
                                    {#each sale.payments as p}
                                        <tr>
                                            <td
                                                class="px-3 py-2 whitespace-nowrap"
                                            >
                                                {formatDateTimeDisplay(
                                                    p.paid_at || p.payment_date,
                                                )}
                                            </td>
                                            <td
                                                class="px-3 py-2 whitespace-nowrap"
                                            >
                                                {p.payment_method_name ?? "-"}
                                            </td>
                                            <td
                                                class="px-3 py-2 whitespace-nowrap"
                                            >
                                                <div class="flex justify-end">
                                                    <span
                                                        >{formatCurrency(
                                                            p.amount ?? 0,
                                                        )}</span
                                                    >
                                                </div>
                                            </td>
                                            <td
                                                class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                            >
                                                {(p.notes ?? "").trim() || "-"}
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        {:else}
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Belum ada pembayaran.
                            </p>
                        {/if}
                    </div>
                {/snippet}
            </Card>
        </div>
        {#if (sale.outstanding_amount ?? 0) > 0}
            <Card collapsible={false} bodyWithoutPadding={true}>
                {#snippet children()}
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    Pelunasan Pembayaran
                                </h3>
                            </div>
                            <div class="space-y-2">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <Select
                                                minimal
                                                searchable={false}
                                                placeholder="Metode #1"
                                                value={selectedPayment1Id}
                                                options={paymentMethodOptions()}
                                                onchange={(v) =>
                                                    (selectedPayment1Id =
                                                        String(v))}
                                            />
                                            <div
                                                class="flex items-center justify-between text-xs"
                                            >
                                                <button
                                                    type="button"
                                                    class="underline text-[#0060B2]"
                                                    onclick={() =>
                                                        openPaymentNoteModal(1)}
                                                    aria-label="open payment note method 1"
                                                >
                                                    Catatan: {paymentNote1 ||
                                                        "......"}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="w-40">
                                            <TextInput
                                                id="pay1"
                                                name="pay1"
                                                placeholder="Nominal #1"
                                                currency={true}
                                                currencySymbol="Rp"
                                                thousandSeparator="."
                                                decimalSeparator=","
                                                maxDecimals={0}
                                                stripZeros={true}
                                                bind:value={payment1Str}
                                                disabled={selectedPayment1Id ===
                                                    ""}
                                                oninput={(ev) =>
                                                    (payment1Str = String(
                                                        (ev as any)
                                                            .numericValue ?? 0,
                                                    ))}
                                            />
                                        </div>
                                    </div>
                                    {#if useSecondPayment}
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1">
                                                <Select
                                                    minimal
                                                    searchable={false}
                                                    placeholder="Metode #2"
                                                    value={selectedPayment2Id}
                                                    options={paymentMethodOptions()}
                                                    onchange={(v) =>
                                                        (selectedPayment2Id =
                                                            String(v))}
                                                />
                                                <div
                                                    class="flex items-center justify-between text-xs"
                                                >
                                                    <button
                                                        type="button"
                                                        class="underline text-[#0060B2]"
                                                        onclick={() =>
                                                            openPaymentNoteModal(
                                                                2,
                                                            )}
                                                        aria-label="open payment note method 2"
                                                        disabled={selectedPayment2Id ===
                                                            ""}
                                                    >
                                                        Catatan: {paymentNote2 ||
                                                            "......"}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="w-40">
                                                <TextInput
                                                    id="pay2"
                                                    name="pay2"
                                                    placeholder="Nominal #2"
                                                    currency={true}
                                                    currencySymbol="Rp"
                                                    thousandSeparator="."
                                                    decimalSeparator=","
                                                    maxDecimals={0}
                                                    stripZeros={true}
                                                    bind:value={payment2Str}
                                                    disabled={selectedPayment2Id ===
                                                        ""}
                                                    oninput={(ev) =>
                                                        (payment2Str = String(
                                                            (ev as any)
                                                                .numericValue ??
                                                                0,
                                                        ))}
                                                />
                                            </div>
                                        </div>
                                    {/if}
                                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <button
                                            type="button"
                                            class="underline text-[#0060B2]"
                                            onclick={toggleSecondPayment}
                                            aria-label="toggle second payment"
                                        >
                                            {useSecondPayment
                                                ? "Hapus metode kedua"
                                                : "Tambah metode kedua"}
                                        </button>
                                    </div>
                                    {#if shortageAmount > 0}
                                        <div
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span
                                                class="text-gray-600 dark:text-gray-400"
                                                >Kurang bayar</span
                                            >
                                            <span class="text-red-600"
                                                >{formatCurrency(
                                                    shortageAmount,
                                                )}</span
                                            >
                                        </div>
                                    {:else if changeAmount > 0}
                                        <div
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span
                                                class="text-gray-600 dark:text-gray-400"
                                                >Kembalian</span
                                            >
                                            <span class="text-green-600"
                                                >{formatCurrency(
                                                    changeAmount,
                                                )}</span
                                            >
                                        </div>
                                    {/if}
                                    <div class="flex justify-end">
                                        <Button
                                            variant="success"
                                            icon="fa-solid fa-check"
                                            disabled={(selectedPayment1Id ===
                                                "" &&
                                                (!useSecondPayment ||
                                                    selectedPayment2Id ===
                                                        "")) ||
                                                toInt(payment1Str) +
                                                    (useSecondPayment
                                                        ? toInt(payment2Str)
                                                        : 0) <=
                                                    0}
                                            onclick={openSettleDialog}
                                            >Selesaikan Pembayaran</Button
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/snippet}
            </Card>
        {/if}
    </div>
</section>

{#if (sale.outstanding_amount ?? 0) > 0}
    <Dialog
        bind:isOpen={showPaymentNoteModal}
        type="info"
        title="Catatan Pembayaran"
        message="Tambahkan catatan pembayaran"
        confirmText="Simpan"
        cancelText="Batal"
        showCancel={true}
        form_fields={[
            {
                id: "payment_note",
                name: "note",
                type: "textarea",
                label: "Catatan",
                placeholder: "Tambahkan catatan pembayaran",
                rows: 3,
            },
        ]}
        form_data={{
            note: paymentNoteDraft,
        }}
        onConfirm={(formData) => {
            paymentNoteDraft = String(
                (formData as Record<string, any>)?.note ?? "",
            ).trim();
            savePaymentNote();
        }}
        onCancel={closePaymentNoteModal}
        onClose={closePaymentNoteModal}
    />
{/if}

{#if (sale.outstanding_amount ?? 0) > 0}
    <Dialog
        bind:isOpen={showSettleDialog}
        type="success"
        title="Konfirmasi Pembayaran"
        message={settleDialogMessage()}
        confirmText="Ya, Selesaikan"
        cancelText="Batal"
        showCancel={true}
        loading={settleProcessing}
        onConfirm={async () => {
            settleProcessing = true;
            await settlePayments();
            settleProcessing = false;
        }}
        onCancel={() => {
            settleProcessing = false;
        }}
        onClose={() => {
            settleProcessing = false;
        }}
    />
{/if}
