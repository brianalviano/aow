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

    type Rel = {
        id: string | null;
        name: string | null;
        address: string | null;
        phone: string | null;
    };
    type StockRow = {
        warehouse_id: string;
        product_id: string;
        quantity: number;
    };
    type StockDocumentItem = {
        id: string;
        product: {
            id: string | null;
            name: string | null;
            sku?: string | null;
        };
        quantity: number;
        notes: string | null;
    };
    type StockDocumentDetail = {
        id: string;
        number: string;
        warehouse: Rel;
        document_date: string | null;
        type: string | null;
        type_label: string | null;
        reason: string | null;
        reason_label: string | null;
        bucket: string | null;
        notes: string | null;
        status: string | null;
        status_label: string | null;
        items: StockDocumentItem[];
    };

    let sd = $derived($page.props.stock_document as StockDocumentDetail);
    let stocks = $derived(($page.props.stocks ?? []) as StockRow[]);

    function getProductStock(productId: string | null): number {
        const wid = String(sd.warehouse?.id ?? "");
        const pid = String(productId ?? "");
        if (!wid || !pid) return 0;
        const row = stocks.find(
            (s) =>
                String(s.warehouse_id) === wid && String(s.product_id) === pid,
        );
        return row ? row.quantity : 0;
    }

    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(d);
    }

    function bucketLabel(input?: string | null): string {
        const s = String(input ?? "");
        if (s === "vat") return "PPN";
        if (s === "non_vat") return "Non PPN";
        return "-";
    }

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
    <title>Cetak Dokumen Stok | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="p-0.1 print-a5">
    <!-- Header Section dengan Table -->
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
                    <div
                        class="mt-1 text-[7pt] text-gray-700 dark:text-gray-300"
                    >
                        <span>Gudang : {sd.warehouse?.name ?? "-"}</span>
                    </div>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        Alamat: {sd.warehouse?.address ?? "-"}
                    </p>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        No. Whatsapp : {sd.warehouse?.phone ?? "-"}
                    </p>
                </td>
                <td class="border-none! align-top w-[60%] text-right">
                    <h2
                        class="title text-3xl font-bold text-gray-900 dark:text-white mb-4 print:text-xl print:mb-2"
                    >
                        DOKUMEN STOK
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
                                        No Dokumen
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {sd.number}
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
                                        {formatDateIndo(sd.document_date)}
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

    <!-- Document Info Section dengan Table -->
    <table class="w-full border-collapse mb-6 border-none!">
        <tbody>
            <tr>
                <td class="border-none! align-top w-[50%] pr-8">
                    <div
                        class="grid grid-cols-2 gap-x-10 gap-y-3 print:gap-x-6 print:gap-y-2"
                    >
                        <div>
                            <p
                                class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                JENIS DOKUMEN
                            </p>
                            <h3
                                class="text-lg font-medium text-gray-800 dark:text-white print:text-xs"
                            >
                                {sd.type_label ?? sd.type ?? "-"}
                            </h3>
                        </div>
                        <div>
                            <p
                                class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                ALASAN
                            </p>
                            <p
                                class="text-xs text-gray-800 dark:text-white print:text-[7pt]"
                            >
                                {sd.reason_label ?? sd.reason ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                STATUS
                            </p>
                            <p
                                class="text-xs text-gray-800 dark:text-white print:text-[7pt]"
                            >
                                {sd.status_label ?? sd.status ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                BUCKET
                            </p>
                            <p
                                class="text-xs text-gray-800 dark:text-white print:text-[7pt]"
                            >
                                {bucketLabel(sd.bucket)}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="border-none! align-top w-[50%]">
                    {#if sd.notes}
                        <p
                            class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            CATATAN
                        </p>
                        <p
                            class="whitespace-pre-wrap text-xs text-gray-600 dark:text-gray-300 print:text-[7pt]"
                        >
                            {sd.notes}
                        </p>
                    {/if}
                </td>
            </tr>
        </tbody>
    </table>

    <hr class="border-gray-200 dark:border-[#212121] my-4" />

    <!-- Items Section -->
    <div class="mb-6">
        <table
            class="w-full border-collapse border border-gray-300 dark:border-gray-700"
        >
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800">
                    <th
                        class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
                    >
                        NO
                    </th>
                    <th
                        class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
                    >
                        PRODUK
                    </th>
                    <th
                        class="px-3 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
                    >
                        STOK
                    </th>
                    <th
                        class="px-3 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
                    >
                        QTY
                    </th>
                    <th
                        class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
                    >
                        CATATAN
                    </th>
                </tr>
            </thead>
            <tbody>
                {#each sd.items as item, idx}
                    <tr
                        class="border-b border-gray-300 bg-white dark:border-gray-700 dark:bg-[#0a0a0a]"
                    >
                        <td
                            class="px-3 py-2 text-center text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700"
                        >
                            {idx + 1}
                        </td>
                        <td
                            class="px-3 py-2 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700"
                        >
                            {item.product?.name ?? "-"}
                        </td>
                        <td
                            class="px-3 py-2 text-center text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700"
                        >
                            {getProductStock(item.product?.id ?? "")}
                        </td>
                        <td
                            class="px-3 py-2 text-center text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700"
                        >
                            {item.quantity}
                        </td>
                        <td
                            class="px-3 py-2 text-xs text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
                        >
                            {item.notes || "-"}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</section>
