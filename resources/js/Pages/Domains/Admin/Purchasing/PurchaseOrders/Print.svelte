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

    type Rel = {
        id: string | null;
        name: string | null;
        address: string | null;
        phone: string | null;
    };
    type PurchaseOrderItem = {
        id: string;
        product: Rel;
        quantity: number;
        price: number;
        subtotal: number;
        notes: string | null;
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
        warehouse: Rel;
        order_date: string | null;
        notes: string | null;
        status: string | null;
        subtotal: number;
        delivery_cost: number;
        total_before_discount: number;
        discount_percentage: string | null;
        discount_amount: number;
        total_after_discount: number;
        is_value_added_tax_enabled: boolean;
        value_added_tax_percentage: string | null;
        value_added_tax_amount: number;
        total_after_value_added_tax: number;
        is_income_tax_enabled: boolean;
        income_tax_percentage: string | null;
        income_tax_amount: number;
        total_after_income_tax: number;
        grand_total: number;
        items: PurchaseOrderItem[];
    };

    let po = $derived($page.props.purchase_order as PurchaseOrderDetail);
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
    <title>Cetak Purchase Order | {siteName($page.props.settings)}</title>
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
                        <span>Nama Gudang : {po.warehouse?.name ?? "-"}</span>
                    </div>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        Alamat: {po.warehouse?.address ??
                            ($page.props.settings as any)?.address ??
                            "-"}
                    </p>
                    <p class="text-[7pt] text-gray-700 dark:text-gray-300 mt-1">
                        No. Whatsapp : {po.warehouse?.phone ??
                            ($page.props.settings as any)?.whatsapp_number ??
                            "-"}
                    </p>
                </td>
                <td class="border-none! align-top w-[60%] text-right">
                    <h2
                        class="title text-3xl font-bold text-gray-900 dark:text-white mb-4 print:text-xl print:mb-2"
                    >
                        PURCHASE ORDER
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
                                        {po.number}
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
                                        {formatDateIndo(po.order_date)}
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

    <!-- Supplier & Notes Section dengan Table -->
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
                            {po.supplier?.name ?? "-"}
                        </h3>
                    </div>
                    <table class="border-none!">
                        <tbody>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0] w-20"
                                >
                                    No. Whatsapp
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none!"
                                >
                                    : {po.supplier?.phone ?? "-"}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] border-none! p-[2px_0] w-20"
                                >
                                    Email
                                </td>
                                <td
                                    class="text-[7pt] text-gray-700 dark:text-gray-300 print:text-[7pt] p-[2px_0] border-none!"
                                >
                                    : {po.supplier?.email ?? "-"}
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
                                    : {po.supplier?.address ?? "-"}
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
                        {(po.notes ?? "").trim() || "-"}
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
                        Nama Produk
                    </th>
                    <th
                        class="px-3 py-2 text-center w-24 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        Kuantitas
                    </th>
                    <th
                        class="px-3 py-2 text-right w-32 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        Harga
                    </th>
                    <th
                        class="px-3 py-2 text-right w-32 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        Subtotal
                    </th>
                    <th
                        class="px-3 py-2 w-48 print:px-2 print:py-1 whitespace-nowrap"
                    >
                        Catatan
                    </th>
                </tr>
            </thead>
            <tbody>
                {#each po.items as item, idx}
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
                            {item.product?.name ?? "-"}
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {item.quantity}
                        </td>
                        <td
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <table class="w-full border-none!">
                                <tbody>
                                    <tr>
                                        <td class="border-none! text-left"
                                            >{currencySymbol}</td
                                        >
                                        <td class="border-none! text-right">
                                            {formatCurrencyWithoutSymbol(
                                                item.price,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td
                            class="px-3 py-2 text-right print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <table class="w-full border-none!">
                                <tbody>
                                    <tr>
                                        <td class="border-none! text-left"
                                            >{currencySymbol}</td
                                        >
                                        <td class="border-none! text-right">
                                            {formatCurrencyWithoutSymbol(
                                                item.subtotal,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {item.notes || "-"}
                        </td>
                    </tr>
                {/each}
                <tr>
                    <td
                        colspan="4"
                        class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                    >
                        Subtotal
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
                                        {formatCurrencyWithoutSymbol(
                                            po.subtotal,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
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
                        <table class="w-full border-none!">
                            <tbody>
                                <tr class="border-none! text-left">
                                    <td class="border-none! text-left"
                                        >{currencySymbol}</td
                                    >
                                    <td class="border-none! text-right">
                                        {formatCurrencyWithoutSymbol(
                                            po.delivery_cost,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
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
                        <table class="w-full border-none!">
                            <tbody>
                                <tr>
                                    <td class="border-none! text-left"
                                        >{currencySymbol}</td
                                    >
                                    <td class="border-none! text-right">
                                        {formatCurrencyWithoutSymbol(
                                            po.total_before_discount,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
                </tr>
                <tr>
                    <td
                        colspan="4"
                        class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                    >
                        Diskon ({po.discount_percentage ?? "0"}%)
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
                                        {formatCurrencyWithoutSymbol(
                                            po.discount_amount,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
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
                        <table class="w-full border-none!">
                            <tbody>
                                <tr>
                                    <td class="border-none! text-left"
                                        >{currencySymbol}</td
                                    >
                                    <td class="border-none! text-right">
                                        {formatCurrencyWithoutSymbol(
                                            po.total_after_discount,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
                </tr>
                {#if po.is_value_added_tax_enabled}
                    <tr>
                        <td
                            colspan="4"
                            class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                        >
                            PPN ({po.value_added_tax_percentage ?? "0"}%)
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
                                            {formatCurrencyWithoutSymbol(
                                                po.value_added_tax_amount,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800"></td>
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
                            <table class="w-full border-none!">
                                <tbody>
                                    <tr>
                                        <td class="border-none! text-left"
                                            >{currencySymbol}</td
                                        >
                                        <td class="border-none! text-right">
                                            {formatCurrencyWithoutSymbol(
                                                po.total_after_value_added_tax,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800"></td>
                    </tr>
                {/if}
                {#if po.is_income_tax_enabled}
                    <tr>
                        <td
                            colspan="4"
                            class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                        >
                            PPh ({po.income_tax_percentage ?? "0"}%)
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
                                            {formatCurrencyWithoutSymbol(
                                                po.income_tax_amount,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800"></td>
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
                        <table class="w-full border-none!">
                            <tbody>
                                <tr>
                                    <td class="border-none! text-left"
                                        >{currencySymbol}</td
                                    >
                                    <td class="border-none! text-right">
                                        {formatCurrencyWithoutSymbol(
                                            po.grand_total,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
