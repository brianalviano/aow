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
        price: number;
        qty: number;
        note?: string | null;
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
        delivery_number: string;
        sale_datetime: string | null;
        warehouse: IdName | null;
        customer: Customer;
        payment_status: string | null;
        payment_status_label: string | null;
        subtotal: number;
        discount_percentage: string | null;
        discount_amount: number;
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

    function formatDateIndo(s?: string | null): string {
        const v = String(s ?? "");
        const d = v ? new Date(v) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(d);
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
    <title>Cetak Surat Jalan | {siteName(($page.props as any).settings)}</title>
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
                    <div
                        class="mt-1 text-[7pt] text-gray-700 dark:text-gray-300"
                    >
                        <span>Nama Gudang : {sale.warehouse?.name ?? "-"}</span>
                    </div>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        Alamat: {($page.props as any)?.settings?.address ?? "-"}
                    </p>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        No. Whatsapp : {($page.props as any)?.settings
                            ?.whatsapp_number ?? "-"}
                    </p>
                </td>
                <td class="border-none! align-top w-[60%] text-right">
                    <h2
                        class="title text-3xl font-bold text-gray-900 dark:text-white mb-4 print:text-xl print:mb-2"
                    >
                        SURAT JALAN
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
                                        No Surat Jalan
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {sale.delivery_number || "-"}
                                    </p>
                                </td>
                                <td class="border-none! p-[4px_0] text-right">
                                    <p
                                        class="text-[7pt] font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        Tanggal
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {formatDateIndo(sale.sale_datetime)}
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
                        TUJUAN PENGIRIMAN
                    </p>
                    <div class="mb-1">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white print:text-xs"
                        >
                            {sale.shipping_recipient_name ||
                                sale.customer?.name ||
                                "-"}
                        </h3>
                    </div>
                    <table class="border-none!">
                        <tbody>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0] w-24"
                                >
                                    No. Whatsapp
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none!"
                                >
                                    : {sale.shipping_recipient_phone ||
                                        sale.customer?.phone ||
                                        "-"}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none! align-top w-24"
                                >
                                    Alamat
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0]"
                                >
                                    : {sale.shipping_address ||
                                        sale.customer?.address ||
                                        "-"}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="border-none! align-top w-[50%] pl-8">
                    <p
                        class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                    >
                        CATATAN
                    </p>
                    <div
                        class="text-[7pt] text-gray-800 dark:text-gray-300 leading-relaxed print:text-[7pt]"
                    >
                        {(sale.shipping_note ?? "").trim() || "-"}
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
                        >NO</th
                    >
                    <th
                        class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >Nama Produk</th
                    >
                    <th
                        class="px-3 py-2 text-center w-24 print:px-2 print:py-1 whitespace-nowrap"
                        >Kuantitas</th
                    >
                    <th
                        class="px-3 py-2 w-48 print:px-2 print:py-1 whitespace-nowrap"
                        >Catatan</th
                    >
                </tr>
            </thead>
            <tbody>
                {#each sale.items ?? [] as item, idx}
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
                            {item.name ?? "-"}
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {item.qty}
                        </td>
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {item.note || "-"}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</section>
