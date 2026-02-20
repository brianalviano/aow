<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatCurrencyWithoutSymbol,
        getCurrencySymbol,
    } from "@/Lib/Admin/Utils/currency";
    import { onMount } from "svelte";
    import logo from "@img/logo.png";
    import {
        printWithLightMode,
        setPrintPage,
        enablePrintView,
    } from "@/Lib/Admin/Utils/print";

    type IdName = {
        id: string | null;
        name: string | null;
        address?: string | null;
        phone?: string | null;
    };
    type SupplierDetail = {
        id: string | null;
        name: string | null;
        phone: string | null;
        email: string | null;
        address: string | null;
    };
    type PurchaseOrderInfo = {
        id: string | null;
        number: string | null;
        order_date: string | null;
        supplier: IdName;
        warehouse: IdName;
    };
    type PurchaseReturnItem = {
        product: { id: string | null; name: string | null; sku: string | null };
        quantity: number;
        price: number;
        subtotal: number;
        notes: string | null;
    };
    type PurchaseReturnDetail = {
        id: string;
        number: string | null;
        supplier: SupplierDetail;
        warehouse: IdName;
        purchase_order: PurchaseOrderInfo;
        return_date: string | null;
        reason: string | null;
        reason_label: string | null;
        resolution: string | null;
        resolution_label: string | null;
        status: string | null;
        status_label: string | null;
        credit_amount: number;
        refund_amount: number;
        notes: string | null;
        items: PurchaseReturnItem[];
    };

    let pr = $derived($page.props.purchase_return as PurchaseReturnDetail);
    const currencySymbol = getCurrencySymbol();

    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(d);
    }

    const total = $derived(
        pr.items.reduce((sum, it) => sum + (it.subtotal ?? 0), 0),
    );

    onMount(() => {
        const cleanupLight = printWithLightMode(100);
        const cleanupPage = setPrintPage({
            size: "A5",
            orientation: "landscape",
            margin: "10mm",
        });
        const cleanupView = enablePrintView();
        return () => {
            cleanupLight();
            cleanupPage();
            cleanupView();
        };
    });
</script>

<svelte:head>
    <title>Cetak Retur Pembelian | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="p-0.1 print-a5">
    <table class="w-full border-collapse border-none!">
        <tbody>
            <tr>
                <td class="border-none! align-top w-[40%]">
                    <div class="mb-2">
                        <img
                            src={logo}
                            alt="Logo"
                            class="w-90 object-contain"
                            loading="lazy"
                        />
                    </div>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        Gudang: {pr.warehouse?.name || "-"}
                    </p>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        Alamat: {pr.warehouse?.address ||
                            ($page.props.settings as any)?.address ||
                            "-"}
                    </p>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        No. Whatsapp: {pr.warehouse?.phone ||
                            ($page.props.settings as any)?.whatsapp_number ||
                            "-"}
                    </p>
                </td>
                <td class="border-none! align-top w-[60%] text-right">
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-2 print:text-xl"
                    >
                        RETUR PEMBELIAN
                    </h2>
                    <table class="ml-auto border-none!">
                        <tbody>
                            <tr>
                                <td
                                    class="border-none! p-[4px_20px_4px_0] text-right"
                                >
                                    <p
                                        class="text-[7pt] font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        No Surat
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {pr.number || "-"}
                                    </p>
                                </td>
                                <td class="border-none! p-[4px_0] text-right">
                                    <p
                                        class="text-[7pt] font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        Tgl Buat
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {formatDateIndo(pr.return_date)}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <hr class="border-gray-200 dark:border-[#212121] my-4" />

    <table class="w-full border-collapse mb-6 border-none!">
        <tbody>
            <tr>
                <td class="border-none! align-top w-[50%] pr-8">
                    <p
                        class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                    >
                        SUPPLIER
                    </p>
                    <div class="mb-1">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white print:text-xs"
                        >
                            {pr.supplier?.name || "-"}
                        </h3>
                    </div>
                    <table class="border-none!">
                        <tbody>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none! align-top w-20"
                                >
                                    No. Whatsapp
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0]"
                                >
                                    : {pr.supplier?.phone || "-"}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none! align-top w-20"
                                >
                                    Email
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0]"
                                >
                                    : {pr.supplier?.email || "-"}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none! align-top w-20"
                                >
                                    Alamat
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0]"
                                >
                                    : {pr.supplier?.address || "-"}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="border-none! align-top w-[50%] pl-8">
                    <p
                        class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                    >
                        CATATAN UNTUK SUPPLIER
                    </p>
                    <div
                        class="text-[7pt] text-gray-800 dark:text-gray-300 leading-relaxed print:text-[7pt]"
                    >
                        {(pr.notes || "").trim() || "-"}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <div>
        <table
            class="w-full text-[6pt] text-left text-gray-800 dark:text-gray-200 custom-table"
        >
            <thead
                class="text-[6pt] uppercase bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400"
            >
                <tr>
                    <th
                        class="px-3 py-2 text-center w-12 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        NO
                    </th>
                    <th
                        class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        PRODUK
                    </th>
                    <th
                        class="px-3 py-2 text-center w-20 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        QTY
                    </th>
                    <th
                        class="px-3 py-2 text-right w-32 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        HARGA RETUR
                    </th>
                    <th
                        class="px-3 py-2 text-right w-32 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        SUBTOTAL
                    </th>
                    <th
                        class="px-3 py-2 w-48 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        CATATAN
                    </th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                {#each pr.items as item, index}
                    <tr>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                            >{index + 1}</td
                        >
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <div class="flex items-center justify-between">
                                <span>
                                    {item.product?.name && item.product?.sku
                                        ? `${item.product.name} (${item.product.sku})`
                                        : item.product?.name || "-"}
                                </span>
                            </div>
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {item.quantity}
                        </td>
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <div class="flex justify-between">
                                <span>{currencySymbol}</span>
                                <span
                                    >{formatCurrencyWithoutSymbol(
                                        item.price,
                                    )}</span
                                >
                            </div>
                        </td>
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <div class="flex justify-between">
                                <span>{currencySymbol}</span>
                                <span
                                    >{formatCurrencyWithoutSymbol(
                                        item.subtotal,
                                    )}</span
                                >
                            </div>
                        </td>
                        <td
                            class="px-3 py-2 text-gray-600 dark:text-gray-400 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {item.notes || "-"}
                        </td>
                    </tr>
                {/each}
                <tr>
                    <td
                        colspan="4"
                        class="px-3 py-1 text-right bg-gray-100 dark:bg-gray-800 uppercase font-bold border-l border-b border-gray-300 dark:border-gray-700"
                    >
                        Total
                    </td>
                    <td
                        class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                    >
                        <table class="w-full border-none!">
                            <tbody>
                                <tr>
                                    <td class="border-none! text-left"
                                        >{currencySymbol}</td
                                    >
                                    <td class="border-none! text-right">
                                        {formatCurrencyWithoutSymbol(total)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td
                        class="px-3 py-2 w-48 print:px-2 print:py-1 whitespace-nowrap bg-gray-100 dark:bg-gray-800"
                    ></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
