<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { onMount } from "svelte";
    import logo from "@img/logo.png";
    import {
        printWithLightMode,
        setPrintPage,
        enablePrintView,
    } from "@/Lib/Admin/Utils/print";

    type PurchaseOrderInfo = {
        id: string;
        number: string;
        supplier: { name: string | null };
        warehouse: {
            name: string | null;
            address?: string | null;
            phone?: string | null;
        };
        status: string | null;
        status_label: string | null;
        supplier_invoice_number?: string | null;
        supplier_invoice_date?: string | null;
        supplier_invoice_file?: string | null;
    };
    type PurchaseReturnInfo = {
        id: string;
        number: string | null;
        return_date: string | null;
        supplier: { name: string | null };
        warehouse: {
            name: string | null;
            address?: string | null;
            phone?: string | null;
        };
        purchase_order: { id: string | null; number: string | null };
        status: string | null;
        status_label: string | null;
        resolution: string | null;
    };
    type ItemRow = {
        product_id: string;
        product_name: string;
        sku?: string | null;
        sdo_quantity?: number;
        return_quantity?: number;
        received_quantity: number;
        remaining_quantity: number;
    };
    type SdoInfo = {
        id: string;
        number: string | null;
        delivery_date: string | null;
    };
    type ReceivingInfo = {
        sender_name?: string | null;
        vehicle_plate_number?: string | null;
    };

    let po = $derived(
        ($page.props.purchase_order ?? null) as PurchaseOrderInfo | null,
    );
    let pr = $derived(
        ($page.props.purchase_return ?? null) as PurchaseReturnInfo | null,
    );
    let isReturn = $derived<boolean>(!!pr);
    let sdo = $derived(
        ($page.props.supplier_delivery_order ?? null) as SdoInfo | null,
    );
    let items = $derived(($page.props.items ?? []) as ItemRow[]);
    let receiving = $derived(
        ($page.props.receiving ?? null) as ReceivingInfo | null,
    );

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

    let warehouseData = $derived(() => {
        if (isReturn && pr?.warehouse) {
            return {
                name: pr.warehouse.name ?? "",
                address:
                    pr.warehouse.address ??
                    ($page.props.settings as any)?.address ??
                    "",
                phone:
                    pr.warehouse.phone ??
                    ($page.props.settings as any)?.whatsapp_number ??
                    "",
            };
        }
        if (po?.warehouse) {
            return {
                name: po.warehouse.name ?? "",
                address:
                    po.warehouse.address ??
                    ($page.props.settings as any)?.address ??
                    "",
                phone:
                    po.warehouse.phone ??
                    ($page.props.settings as any)?.whatsapp_number ??
                    "",
            };
        }
        return null;
    });

    let supplierData = $derived(() => {
        if (isReturn && pr?.supplier) {
            return { name: pr.supplier.name ?? "" };
        }
        if (po?.supplier) {
            return { name: po.supplier.name ?? "" };
        }
        return null;
    });

    let documentDate = $derived(() => {
        if (isReturn) return pr?.return_date ?? "";
        return sdo?.delivery_date ?? "";
    });

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
    <title>Cetak Tanda Terima Barang | {siteName($page.props.settings)}</title>
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
                            class="logo w-90 object-contain print:w-35"
                        />
                    </div>
                    {#if warehouseData()}
                        <div
                            class="mt-1 text-[7pt] text-gray-700 dark:text-gray-300"
                        >
                            <span>Nama Gudang : {warehouseData()?.name}</span>
                        </div>
                        <p
                            class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1"
                        >
                            Alamat: {warehouseData()?.address || "-"}
                        </p>
                        <p
                            class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1"
                        >
                            No. Whatsapp : {warehouseData()?.phone || "-"}
                        </p>
                    {/if}
                </td>
                <td class="border-none! align-top w-[60%] text-right">
                    <h2
                        class="title text-3xl font-bold text-gray-900 dark:text-white mb-4 print:text-xl print:mb-2"
                    >
                        TANDA TERIMA BARANG
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
                                        Tgl {isReturn ? "Retur" : "PO"}
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {formatDateIndo(documentDate())}
                                    </p>
                                </td>
                                <td class="border-none! p-[4px_0] text-right">
                                    <p
                                        class="text-[7pt] font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        No {isReturn ? "Retur" : "SJ"}
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {sdo?.number ?? pr?.number ?? "-"}
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
                    {#if supplierData()}
                        <p
                            class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            SUPPLIER
                        </p>
                        <div class="mb-1">
                            <h3
                                class="text-lg font-medium text-gray-800 dark:text-white print:text-xs"
                            >
                                {supplierData()?.name}
                            </h3>
                        </div>
                    {/if}
                </td>
                <td class="border-none! align-top w-[50%] pl-8">
                    {#if receiving}
                        <p
                            class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            DETAIL PENGIRIMAN
                        </p>
                        <div
                            class="text-[7pt] text-gray-800 dark:text-gray-300 leading-relaxed print:text-[7pt]"
                        >
                            <div class="flex">
                                <span class="w-32">Nama Pengirim</span>
                                <span
                                    >: {(receiving?.sender_name || "").trim() ||
                                        "-"}</span
                                >
                            </div>
                            <div class="flex">
                                <span class="w-32">Plat Nomor</span>
                                <span
                                    >: {(
                                        receiving?.vehicle_plate_number || ""
                                    ).trim() || "-"}</span
                                >
                            </div>
                        </div>
                    {/if}
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
                        >NO</th
                    >
                    <th
                        class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >PRODUK</th
                    >
                    <th
                        class="px-3 py-2 text-center w-24 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        {isReturn ? "QTY RETUR" : "QTY PO"}
                    </th>
                    <th
                        class="px-3 py-2 text-center w-24 print:px-2 print:py-1 whitespace-nowrap"
                        >DITERIMA</th
                    >
                    <th
                        class="px-3 py-2 text-center w-24 print:px-2 print:py-1 whitespace-nowrap"
                        >SISA</th
                    >
                </tr>
            </thead>
            <tbody>
                {#each items as row, idx}
                    <tr
                        class="bg-white dark:bg-[#0a0a0a] border-b dark:border-gray-700"
                    >
                        <td
                            class="px-3 py-2 font-medium text-gray-900 dark:text-white text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {idx + 1}
                        </td>
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <div class="flex items-center justify-between">
                                <span>{row.product_name}</span>
                            </div>
                            <div
                                class="text-[6pt] text-gray-500 dark:text-gray-400"
                            >
                                {row.sku || "-"}
                            </div>
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {isReturn
                                ? (row.return_quantity ?? 0)
                                : (row.sdo_quantity ?? 0)}
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {row.received_quantity}
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {row.remaining_quantity}
                        </td>
                    </tr>
                {/each}
                {#if items.length === 0}
                    <tr>
                        <td
                            colspan="5"
                            class="px-3 py-6 text-center text-[7pt] text-gray-500 dark:text-gray-400"
                        >
                            Tidak ada item.
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</section>
