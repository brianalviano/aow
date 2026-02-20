<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatCurrencyWithoutSymbol,
        getCurrencySymbol,
    } from "@/Lib/Admin/Utils/currency";
    import { onMount } from "svelte";
    import {
        printWithLightMode,
        setPrintPage,
        enablePrintView,
    } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";

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

    let sale = $derived(($page.props as any).sale as SaleCompleted);
    const currencySymbol = getCurrencySymbol();

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

    const shippingAmount = $derived<number>(Number(sale.shipping_amount ?? 0));
    const totalBeforeDiscount = $derived<number>(
        Number(sale.subtotal) + shippingAmount,
    );
    const isFullyPaid = $derived<boolean>(
        String(sale.payment_status_label ?? "")
            .trim()
            .toLowerCase() === "lunas" ||
            String(sale.payment_status ?? "")
                .trim()
                .toLowerCase() === "paid" ||
            Math.round(Number(sale.outstanding_amount ?? 0)) === 0,
    );
    const hasOutstanding = $derived<boolean>(
        Math.round(Number(sale.outstanding_amount ?? 0)) > 0,
    );
    const hasDiscount = $derived<boolean>(
        Math.round(Number(sale.discount_amount ?? 0)) > 0,
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
    <title>Cetak Invoice | {siteName(($page.props as any).settings)}</title>
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
                        INVOICE
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
                                        No Invoice
                                    </p>
                                    <p
                                        class="text-[7pt] font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {sale.invoice_number || "-"}
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
                        CUSTOMER
                    </p>
                    <div class="mb-1">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white print:text-xs"
                        >
                            {sale.customer?.name ?? "Tanpa Customer"}
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
                                    : {sale.customer?.phone ?? "-"}
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
                                    : {sale.customer?.address ?? "-"}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="border-none! align-top w-[50%] pl-8">
                    <p
                        class="text-[7pt] font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                    >
                        PEMBAYARAN
                    </p>
                    <div
                        class="space-y-1 text-[7pt] text-gray-800 dark:text-gray-300 leading-relaxed print:text-[7pt]"
                    >
                        {#if !isFullyPaid}
                            <div>
                                Status: {sale.payment_status_label || "-"}
                            </div>
                        {/if}
                        {#if hasOutstanding}
                            <div>
                                Piutang:
                                <span class="inline-block">
                                    <span class="font-semibold"
                                        >{currencySymbol}</span
                                    >
                                    {formatCurrencyWithoutSymbol(
                                        sale.outstanding_amount,
                                    )}
                                </span>
                            </div>
                        {/if}
                        {#if (sale.payments ?? []).length > 0}
                            {#each sale.payments as p, idx}
                                <div>
                                    Metode{(sale.payments ?? []).length > 1
                                        ? ` #${idx + 1}`
                                        : ""}:
                                    {p.payment_method_name ?? "-"}
                                </div>
                                {#if (sale.payments ?? []).length > 1}
                                    <div>
                                        Nominal{(sale.payments ?? []).length > 1
                                            ? ` #${idx + 1}`
                                            : ""}:
                                        <span class="inline-block">
                                            <span class="font-semibold"
                                                >{currencySymbol}</span
                                            >
                                            {formatCurrencyWithoutSymbol(
                                                p.amount ?? 0,
                                            )}
                                        </span>
                                    </div>
                                {/if}
                                {#if p.notes}
                                    <div>Catatan: {p.notes}</div>
                                {/if}
                            {/each}
                        {/if}
                        <div>
                            Total Bayar:
                            <span class="inline-block">
                                <span class="font-semibold"
                                    >{currencySymbol}</span
                                >
                                {formatCurrencyWithoutSymbol(
                                    sale.payment_total ?? 0,
                                )}
                            </span>
                        </div>
                        {#if (sale.change_amount ?? 0) > 0}
                            <div>
                                Kembalian:
                                <span class="inline-block">
                                    <span class="font-semibold"
                                        >{currencySymbol}</span
                                    >
                                    {formatCurrencyWithoutSymbol(
                                        sale.change_amount ?? 0,
                                    )}
                                </span>
                            </div>
                        {/if}
                        {#if (sale.shortage_amount ?? 0) > 0}
                            <div>
                                Kekurangan:
                                <span class="inline-block">
                                    <span class="font-semibold"
                                        >{currencySymbol}</span
                                    >
                                    {formatCurrencyWithoutSymbol(
                                        sale.shortage_amount ?? 0,
                                    )}
                                </span>
                            </div>
                        {/if}
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
                        class="px-3 py-2 text-right w-32 print:px-2 print:py-1 whitespace-nowrap"
                        >Harga</th
                    >
                    <th
                        class="px-3 py-2 text-right w-32 print:px-2 print:py-1 whitespace-nowrap"
                        >Subtotal</th
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
                            class="px-3 py-2 text-center print:px-2 print:py-1 whitespace-nowrap"
                        >
                            <table class="w-full border-none!">
                                <tbody>
                                    <tr>
                                        <td class="border-none! text-left"
                                            >{currencySymbol}</td
                                        >
                                        <td class="border-none! text-right">
                                            {formatCurrencyWithoutSymbol(item.unit_price)}
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
                                            {formatCurrencyWithoutSymbol(item.subtotal)}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td
                            class="px-3 py-2 print:px-2 print:py-1 whitespace-nowrap"
                        >
                            {#if item.discount_text}
                                <div>Diskon: {item.discount_text}</div>
                            {/if}
                            <div>{item.note || "-"}</div>
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
                                            sale.subtotal,
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
                                <tr>
                                    <td class="border-none! text-left"
                                        >{currencySymbol}</td
                                    >
                                    <td class="border-none! text-right">
                                        {formatCurrencyWithoutSymbol(
                                            sale.shipping_amount ?? 0,
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="bg-gray-100 dark:bg-gray-800"></td>
                </tr>
                {#if hasDiscount}
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
                                                totalBeforeDiscount,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800"></td>
                    </tr>
                    {#if (sale.item_discount_total ?? 0) > 0}
                        <tr>
                            <td
                                colspan="4"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                            >
                                Total Diskon Item
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
                                                    sale.item_discount_total ??
                                                        0,
                                                )}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800"></td>
                        </tr>
                    {/if}
                    {#if (sale.extra_discount_total ?? 0) > 0}
                        <tr>
                            <td
                                colspan="4"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                            >
                                Diskon Tambahan{sale.discount_percentage
                                    ? ` (${sale.discount_percentage}%)`
                                    : ""}
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
                                                    sale.extra_discount_total ??
                                                        0,
                                                )}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800"></td>
                        </tr>
                    {/if}
                    {#if (sale.voucher_amount ?? 0) > 0}
                        <tr>
                            <td
                                colspan="4"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                            >
                                Voucher{sale.voucher_code
                                    ? ` (${sale.voucher_code})`
                                    : ""}
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
                                                    sale.voucher_amount ?? 0,
                                                )}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800"></td>
                        </tr>
                    {/if}
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
                                                sale.total_after_discount,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800"></td>
                    </tr>
                {/if}
                {#if sale.is_value_added_tax_enabled}
                    <tr>
                        <td
                            colspan="4"
                            class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                        >
                            PPN{sale.value_added_tax_percentage
                                ? ` (${sale.value_added_tax_percentage}%)`
                                : ""}
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
                                                sale.value_added_tax_amount,
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
                                            sale.grand_total,
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
