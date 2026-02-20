<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { isHighestRole } from "@/Lib/Admin/Utils/roles";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";

    type IdName = {
        id: string | null;
        name: string | null;
        address: string | null;
        phone: string | null;
    };
    type ReceivingRow = {
        sdo_number: string;
        sdo_delivery_date: string | null;
        product_name: string;
        sdo_quantity: number;
        received_quantity: number;
        remaining_quantity: number;
    };

    type SupplierDeliveryOrderItem = {
        product?: { name: string | null } | null;
        quantity: number;
        notes: string | null;
    };

    type SupplierDeliveryOrder = {
        id: string;
        number: string;
        delivery_date: string | null;
        status: string | null;
        status_label: string | null;
        items?: SupplierDeliveryOrderItem[] | null;
    };

    type PurchaseOrderDetail = {
        id: string;
        number: string;
        supplier: {
            id: string | null;
            name: string | null;
            phone: string | null;
            email: string | null;
            address: string | null;
        };
        warehouse: IdName;
        order_date: string | null;
        due_date: string | null;
        notes: string | null;
        status: string | null;
        status_label: string | null;
        subtotal: number;
        delivery_cost: number;
        total_before_discount: number;
        discount_percentage: string | null;
        discount_amount: number;
        total_after_discount: number;
        value_added_tax_included: boolean;
        is_value_added_tax_enabled: boolean;
        value_added_tax_id: string | null;
        value_added_tax_percentage: string | null;
        value_added_tax_amount: number;
        total_after_value_added_tax: number;
        is_income_tax_enabled: boolean;
        income_tax_id: string | null;
        income_tax_percentage: string | null;
        income_tax_amount: number;
        total_after_income_tax: number;
        grand_total: number;
        supplier_invoice_number: string | null;
        supplier_invoice_file: string | null;
        supplier_invoice_date: string | null;
        items: Array<{
            id: string;
            product: { id: string | null; name: string | null };
            quantity: number;
            price: number;
            subtotal: number;
            notes: string | null;
        }>;
    };

    let po = $derived($page.props.purchase_order as PurchaseOrderDetail);
    let sdos = $derived(
        ($page.props.supplier_delivery_orders ?? []) as SupplierDeliveryOrder[],
    );
    let receivings = $derived(($page.props.receivings ?? []) as ReceivingRow[]);

    type InvoiceFormData = {
        supplier_invoice_number: string;
        supplier_invoice_date: string;
        supplier_invoice_file: File | null;
    };
    const invoiceForm = useForm<InvoiceFormData>(() => ({
        supplier_invoice_number: String(po.supplier_invoice_number ?? ""),
        supplier_invoice_date: String(po.supplier_invoice_date ?? ""),
        supplier_invoice_file: null,
    }));
    function submitInvoice(e: SubmitEvent) {
        e.preventDefault();
        router.put(
            `/purchase-orders/${po.id}/supplier-invoice`,
            {
                supplier_invoice_number: String(
                    $invoiceForm.supplier_invoice_number || "",
                ),
                supplier_invoice_date: String(
                    $invoiceForm.supplier_invoice_date || "",
                ),
                supplier_invoice_file:
                    $invoiceForm.supplier_invoice_file ?? null,
            },
            { preserveScroll: true, preserveState: true },
        );
    }

    function backToList() {
        router.visit("/purchase-orders");
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

    function getNextStatus(
        current: string | null,
    ): { value: string; label: string } | null {
        const c = String(current ?? "");
        switch (c) {
            case "draft":
                return { value: "pending_ho_approval", label: "Ajukan ke HO" };
            case "pending_ho_approval":
                return isHighestRole($page.props.auth?.user?.role)
                    ? { value: "head_office_approved", label: "Setujui HO" }
                    : null;
            case "head_office_approved":
                return {
                    value: "pending_supplier_approval",
                    label: "Kirim ke Pemasok",
                };
            case "pending_supplier_approval":
                return {
                    value: "supplier_confirmed",
                    label: "Konfirmasi Pemasok",
                };
            default:
                return null;
        }
    }

    function advancePO() {
        router.post(
            `/purchase-orders/${po.id}/advance`,
            {},
            { preserveScroll: true, preserveState: true },
        );
    }
    let showAdvanceDialog = $state(false);
    function openAdvanceDialog() {
        showAdvanceDialog = true;
    }

    let showRejectModal = $state(false);
    let rejectReason = $state("");
    let rejectTarget: "ho" | "supplier" | null = $state(null);

    function canRejectHo(status: string | null): boolean {
        return (
            isHighestRole($page.props.auth?.user?.role) &&
            String(status ?? "") === "pending_ho_approval"
        );
    }
    function canRejectSupplier(status: string | null): boolean {
        return String(status ?? "") === "pending_supplier_approval";
    }
    function openRejectModalForCurrent() {
        rejectReason = "";
        if (canRejectHo(po.status)) {
            rejectTarget = "ho";
        } else if (canRejectSupplier(po.status)) {
            rejectTarget = "supplier";
        } else {
            rejectTarget = null;
        }
        if (rejectTarget) {
            showRejectModal = true;
        }
    }
    function submitReject(e: SubmitEvent) {
        e.preventDefault();
        if (!rejectTarget) return;
        const url =
            rejectTarget === "ho"
                ? `/purchase-orders/${po.id}/reject/ho`
                : `/purchase-orders/${po.id}/reject/supplier`;
        router.put(
            url,
            { reason: rejectReason },
            { preserveScroll: true, preserveState: true },
        );
        showRejectModal = false;
        rejectReason = "";
        rejectTarget = null;
    }
</script>

<svelte:head>
    <title>Detail Purchase Order | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Purchase Order
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {po.number}
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
                icon="fa-solid fa-print"
                onclick={() =>
                    openCenteredWindow(`/purchase-orders/${po.id}/print`, {
                        width: 960,
                        height: 700,
                        fallbackWhenBlocked: false,
                    })}>Cetak</Button
            >
            {#if getNextStatus(po.status)}
                <Button
                    variant="success"
                    icon="fa-solid fa-forward"
                    onclick={openAdvanceDialog}
                    >Lanjut: {getNextStatus(po.status)?.label}</Button
                >
            {/if}
            <Button
                variant="success"
                icon="fa-solid fa-rotate-left"
                onclick={() =>
                    router.visit(
                        `/purchase-returns/create?purchase_order_id=${po.id}`,
                    )}>Buat Retur Pembelian</Button
            >
            {#if canRejectHo(po.status)}
                <Button
                    variant="danger"
                    icon="fa-solid fa-xmark"
                    onclick={openRejectModalForCurrent}>Tolak HO</Button
                >
            {/if}
            {#if canRejectSupplier(po.status)}
                <Button
                    variant="danger"
                    icon="fa-solid fa-xmark"
                    onclick={openRejectModalForCurrent}>Tolak Pemasok</Button
                >
            {/if}
        </div>
    </header>

    <div class="space-y-4">
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
                                Gudang: {po.warehouse?.name || "-"}
                            </p>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                Alamat: {po.warehouse?.address ||
                                    ($page.props.settings as any)?.address ||
                                    "-"}
                            </p>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                No. Whatsapp: {po.warehouse?.phone ||
                                    ($page.props.settings as any)
                                        ?.whatsapp_number ||
                                    "-"}
                            </p>
                        </div>
                        <div
                            class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                        >
                            <h2
                                class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                            >
                                PURCHASE ORDER
                            </h2>
                            <div
                                class="w-full flex justify-end gap-12 text-right"
                            >
                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        No Surat
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {po.number ?? "-"}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        Tgl Buat
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {formatDateIndo(po.order_date)}
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
                                SUPPLIER
                            </p>
                            <div class="mb-2">
                                <h3
                                    class="text-lg font-medium text-gray-800 dark:text-white"
                                >
                                    {po.supplier?.name || "-"}
                                </h3>
                            </div>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mb-1"
                            >
                                No. Whatsapp : {po.supplier?.phone || "-"}
                            </p>
                            <div
                                class="flex text-sm text-gray-700 dark:text-gray-300 mb-1"
                            >
                                <span class="w-16">Email</span>
                                <span>: {po.supplier?.email || "-"}</span>
                            </div>
                            <div
                                class="flex text-sm text-gray-700 dark:text-gray-300"
                            >
                                <span class="w-16">Alamat</span>
                                <span>: {po.supplier?.address || "-"}</span>
                            </div>
                        </div>

                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                            >
                                CATATAN UNTUK SUPPLIER
                            </p>
                            <div
                                class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                            >
                                {(po.notes || "").trim() || "-"}
                            </div>
                        </div>
                    </div>

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
                                        class="px-3 py-2 text-center w-12"
                                        >NO</th
                                    >
                                    <th scope="col" class="px-3 py-2 w-1/4"
                                        >PRODUK</th
                                    >
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-20"
                                        >QTY</th
                                    >
                                    <th scope="col" class="px-3 py-2 text-right"
                                        >HARGA BELI</th
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
                                {#each po.items as item, index}
                                    <tr>
                                        <td class="px-3 py-2 text-center"
                                            >{index + 1}</td
                                        >
                                        <td class="px-3 py-2">
                                            <span
                                                >{item.product?.name ||
                                                    "-"}</span
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            {item.quantity}
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex justify-end">
                                                <span
                                                    >{formatCurrency(
                                                        item.price,
                                                    )}</span
                                                >
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex justify-end">
                                                <span
                                                    >{formatCurrency(
                                                        item.subtotal,
                                                    )}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="px-3 py-2 text-gray-600 dark:text-gray-400"
                                        >
                                            {item.notes || "-"}
                                        </td>
                                    </tr>
                                {/each}
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-2 bg-gray-50/50 dark:bg-gray-800/50"
                                    >
                                        <div
                                            class="text-end font-bold uppercase"
                                        >
                                            Total
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    po.subtotal,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="bg-gray-100 dark:bg-gray-800 border-none"
                                    ></td>
                                </tr>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                    >
                                        Biaya Pengiriman
                                    </td>
                                    <td
                                        class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                    >
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    po.delivery_cost,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="bg-gray-100 dark:bg-gray-800 border-none"
                                    ></td>
                                </tr>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                    >
                                        Total sebelum diskon
                                    </td>
                                    <td
                                        class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                    >
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    po.total_before_discount,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="bg-gray-100 dark:bg-gray-800 border-none"
                                    ></td>
                                </tr>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                    >
                                        Diskon ({po.discount_percentage ??
                                            "0"}%)
                                    </td>
                                    <td
                                        class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                    >
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    po.discount_amount,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="bg-gray-100 dark:bg-gray-800 border-none"
                                    ></td>
                                </tr>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                    >
                                        Setelah diskon
                                    </td>
                                    <td
                                        class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                    >
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    po.total_after_discount,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="bg-gray-100 dark:bg-gray-800 border-none"
                                    ></td>
                                </tr>
                                {#if po.is_value_added_tax_enabled}
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                        >
                                            PPN ({po.value_added_tax_percentage ??
                                                "0"}%)
                                        </td>
                                        <td
                                            class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                        >
                                            <div class="flex justify-end">
                                                <span
                                                    >{formatCurrency(
                                                        po.value_added_tax_amount,
                                                    )}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="bg-gray-100 dark:bg-gray-800 border-none"
                                        ></td>
                                    </tr>
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                        >
                                            Total dengan PPN
                                        </td>
                                        <td
                                            class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                        >
                                            <div class="flex justify-end">
                                                <span
                                                    >{formatCurrency(
                                                        po.total_after_value_added_tax,
                                                    )}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="bg-gray-100 dark:bg-gray-800 border-none"
                                        ></td>
                                    </tr>
                                {/if}
                                {#if po.is_income_tax_enabled}
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                        >
                                            PPh ({po.income_tax_percentage ??
                                                "0"}%)
                                        </td>
                                        <td
                                            class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                        >
                                            <div class="flex justify-end">
                                                <span
                                                    >{formatCurrency(
                                                        po.income_tax_amount,
                                                    )}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="bg-gray-100 dark:bg-gray-800 border-none"
                                        ></td>
                                    </tr>
                                {/if}
                                <tr class="font-bold">
                                    <td
                                        colspan="4"
                                        class="px-3 py-2 text-right bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700"
                                    >
                                        GRAND TOTAL
                                    </td>
                                    <td
                                        class="px-3 py-2 bg-white dark:bg-[#0a0a0a] border border-gray-300 dark:border-gray-700"
                                    >
                                        <div class="flex justify-end">
                                            <span
                                                >{formatCurrency(
                                                    po.grand_total,
                                                )}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="bg-gray-100 dark:bg-gray-800 border-none"
                                    ></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            {/snippet}
        </Card>

        <Card
            title="Surat Jalan Supplier"
            collapsible={false}
            bodyWithoutPadding={true}
        >
            {#snippet children()}
                <div class="overflow-x-auto">
                    {#if sdos.length > 0}
                        <table class="custom-table min-w-full">
                            <thead>
                                <tr>
                                    <th>Nomor SDO</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Jumlah Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each sdos as sdo}
                                    <tr
                                        class="border-b border-gray-200 dark:border-gray-700"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {sdo.number}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {sdo.delivery_date ?? "-"}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {sdo.status_label ??
                                                    sdo.status ??
                                                    "-"}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {sdo.items
                                                    ? sdo.items.length
                                                    : 0}
                                            </div>
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    {:else}
                        <div
                            class="px-4 py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                        >
                            Belum ada SDO pada PO ini.
                        </div>
                    {/if}
                </div>
            {/snippet}
        </Card>

        <Card
            title="Detail Penerimaan Barang"
            collapsible={false}
            bodyWithoutPadding={true}
        >
            {#snippet children()}
                <div class="overflow-x-auto">
                    {#if receivings.length > 0}
                        <table class="custom-table min-w-full">
                            <thead>
                                <tr>
                                    <th>Nomor SDO</th>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th>Qty SDO</th>
                                    <th>Sudah Diterima</th>
                                    <th>Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each receivings as row}
                                    <tr
                                        class="border-b border-gray-200 dark:border-gray-700"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {row.sdo_number}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {row.sdo_delivery_date ?? "-"}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {row.product_name}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {row.sdo_quantity}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {row.received_quantity}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {row.remaining_quantity}
                                            </div>
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    {:else}
                        <div
                            class="px-4 py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                        >
                            Belum ada penerimaan untuk PO ini.
                        </div>
                    {/if}
                </div>
            {/snippet}
        </Card>

        <Card title="Faktur Supplier" collapsible={false}>
            {#snippet children()}
                <form id="supplier-invoice-form" onsubmit={submitInvoice}>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <TextInput
                            id="supplier_invoice_number"
                            name="supplier_invoice_number"
                            label="Nomor Faktur (opsional)"
                            bind:value={$invoiceForm.supplier_invoice_number}
                            placeholder="Contoh: INV-00123"
                        />
                        <DateInput
                            id="supplier_invoice_date"
                            name="supplier_invoice_date"
                            label="Tanggal Faktur (opsional)"
                            bind:value={$invoiceForm.supplier_invoice_date}
                        />
                        <div class="md:col-span-1">
                            <FileUpload
                                id="supplier_invoice_file"
                                name="supplier_invoice_file"
                                label="File Faktur (opsional)"
                                accept=".pdf,image/*"
                                multiple={false}
                                maxSize={10 * 1024 * 1024}
                                showPreview={true}
                                bind:value={$invoiceForm.supplier_invoice_file}
                                uploadText="Pilih file faktur"
                                variant="box"
                            />
                        </div>
                        <div class="md:col-span-3 flex items-center gap-2">
                            <Button
                                variant="success"
                                icon="fa-solid fa-floppy-disk"
                                form="supplier-invoice-form"
                                type="submit">Simpan Faktur</Button
                            >
                            {#if po.supplier_invoice_file}
                                <a
                                    class="text-sm text-blue-600 underline dark:text-blue-400"
                                    href={po.supplier_invoice_file}
                                    target="_blank">Buka File Saat Ini</a
                                >
                            {/if}
                        </div>
                    </div>
                </form>
            {/snippet}
        </Card>
    </div>
</section>

<Dialog
    bind:isOpen={showAdvanceDialog}
    type="warning"
    title="Konfirmasi Perpindahan Status"
    message={`Status akan diubah dari "${po.status_label ?? po.status ?? "-"}" menjadi "${getNextStatus(po.status)?.label ?? "-"}". Lanjutkan?`}
    confirmText="Lanjutkan"
    cancelText="Batal"
    showCancel={true}
    onConfirm={async () => {
        advancePO();
    }}
    onClose={() => {
        showAdvanceDialog = false;
    }}
/>

<Modal
    bind:isOpen={showRejectModal}
    title={rejectTarget === "ho" ? "Tolak PO oleh HO" : "Tolak PO oleh Pemasok"}
    size="md"
>
    {#snippet children()}
        <form id="reject-po-form" onsubmit={submitReject}>
            <div class="space-y-4">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Alasan penolakan wajib diisi untuk catatan audit.
                </p>
                <TextArea
                    id="reject-reason"
                    name="reason"
                    label="Alasan Penolakan"
                    bind:value={rejectReason}
                    placeholder="Tuliskan alasan penolakan..."
                    required
                    rows={5}
                />
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showRejectModal = false)}
            >Batal</Button
        >
        <Button
            variant="danger"
            type="submit"
            form="reject-po-form"
            icon="fa-solid fa-xmark">Tolak</Button
        >
    {/snippet}
</Modal>
