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

    function formatDateTime(s?: string | null): string {
        const v = String(s ?? "");
        const d = v ? new Date(v) : new Date();
        const date = new Intl.DateTimeFormat("id-ID", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        }).format(d);
        const time = new Intl.DateTimeFormat("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
        }).format(d);
        return `${date} ${time}`;
    }

    onMount(() => {
        const cleanupLight = printWithLightMode(100);
        const cleanupPage = setPrintPage({
            size: "58mm",
            orientation: "portrait",
            margin: "0mm",
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
    <title>Cetak Struk POS | {siteName(($page.props as any).settings)}</title>
</svelte:head>

<section class="receipt">
    <div class="text-center">
        <div class="mb-1">
            <img src={logo} alt="Logo" class="logo" />
        </div>
        <div class="text-[8pt] text-gray-700 dark:text-gray-300 mt-0.5">
            {($page.props as any)?.settings?.address ?? ""}
        </div>
        <div class="text-[8pt] text-gray-700 dark:text-gray-300">
            WA: {($page.props as any)?.settings?.whatsapp_number ?? ""}
        </div>
    </div>

    <div class="divider"></div>

    <div class="text-[8pt] space-y-0.5">
        <div>Struk: {sale.receipt_number || "-"}</div>
        <div>Tanggal: {formatDateTime(sale.sale_datetime)}</div>
        <div>
            Customer: {sale.customer?.name || "Tanpa Customer"}
            {#if sale.customer?.phone}
                <span class="text-gray-600 dark:text-gray-400"
                    >({sale.customer?.phone})</span
                >
            {/if}
        </div>
    </div>

    <div class="divider"></div>

    {#if (sale.items ?? []).length > 0}
        <div class="space-y-1">
            {#each sale.items as it (it.id)}
                <div class="item">
                    <div class="name">
                        {it.name}
                    </div>
                    <div class="meta">
                        {formatCurrencyWithoutSymbol(it.unit_price)} × {it.qty}
                        <span class="currency">{currencySymbol}</span>
                        <span class="total">
                            {formatCurrencyWithoutSymbol(it.subtotal)}
                        </span>
                    </div>
                    {#if it.discount_text}
                        <div class="note">Diskon: {it.discount_text}</div>
                    {/if}
                    {#if it.note}
                        <div class="note">Note: {it.note}</div>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}

    <div class="divider"></div>

    <div class="totals">
        <div class="row">
            <span>Subtotal</span>
            <span class="amount">
                <span class="currency">{currencySymbol}</span>
                {formatCurrencyWithoutSymbol(sale.subtotal)}
            </span>
        </div>
        {#if (sale.item_discount_total ?? 0) > 0}
            <div class="row">
                <span>Total Diskon Item</span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.item_discount_total ?? 0)}
                </span>
            </div>
        {/if}
        {#if (sale.extra_discount_total ?? 0) > 0}
            <div class="row">
                <span>
                    Diskon Tambahan{sale.discount_percentage
                        ? ` (${sale.discount_percentage}%)`
                        : ""}
                </span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(
                        sale.extra_discount_total ?? 0,
                    )}
                </span>
            </div>
        {/if}
        {#if (sale.voucher_amount ?? 0) > 0}
            <div class="row">
                <span
                    >Voucher{sale.voucher_code
                        ? ` (${sale.voucher_code})`
                        : ""}</span
                >
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.voucher_amount ?? 0)}
                </span>
            </div>
        {/if}
        <div class="row">
            <span>Setelah Diskon</span>
            <span class="amount">
                <span class="currency">{currencySymbol}</span>
                {formatCurrencyWithoutSymbol(sale.total_after_discount)}
            </span>
        </div>
        {#if sale.is_value_added_tax_enabled}
            <div class="row">
                <span>
                    PPN{sale.value_added_tax_percentage
                        ? ` (${sale.value_added_tax_percentage}%)`
                        : ""}
                </span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.value_added_tax_amount)}
                </span>
            </div>
        {/if}
        {#if (sale.shipping_amount ?? 0) > 0}
            <div class="row">
                <span>Biaya Pengiriman</span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.shipping_amount ?? 0)}
                </span>
            </div>
        {/if}
        <div class="row total">
            <span>Total</span>
            <span class="amount">
                <span class="currency">{currencySymbol}</span>
                {formatCurrencyWithoutSymbol(sale.grand_total)}
            </span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="payments">
        {#if sale.payment_status_label != "Lunas"}
            <div class="row">
                <span>Status</span>
                <span>{sale.payment_status_label || "-"}</span>
            </div>
        {/if}
        {#if (sale.outstanding_amount ?? 0) > 0}
            <div class="row">
                <span>Piutang</span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.outstanding_amount)}
                </span>
            </div>
        {/if}
        {#if (sale.payments ?? []).length > 0}
            {#each sale.payments as p, idx}
                <div class="row">
                    <span
                        >Metode{(sale.payments ?? []).length > 1
                            ? ` #${idx + 1}`
                            : ""}</span
                    >
                    <span>{p.payment_method_name ?? "-"}</span>
                </div>
                {#if (sale.payments ?? []).length > 1}
                    <div class="row">
                        <span
                            >Nominal{(sale.payments ?? []).length > 1
                                ? ` #${idx + 1}`
                                : ""}</span
                        >
                        <span class="amount">
                            <span class="currency">{currencySymbol}</span>
                            {formatCurrencyWithoutSymbol(p.amount ?? 0)}
                        </span>
                    </div>
                {/if}
                {#if p.notes}
                    <div class="row">
                        <span>Catatan</span>
                        <span>{p.notes}</span>
                    </div>
                {/if}
            {/each}
        {/if}
        <div class="row">
            <span>Total Bayar</span>
            <span class="amount">
                <span class="currency">{currencySymbol}</span>
                {formatCurrencyWithoutSymbol(sale.payment_total ?? 0)}
            </span>
        </div>
        {#if (sale.change_amount ?? 0) > 0}
            <div class="row">
                <span>Kembalian</span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.change_amount ?? 0)}
                </span>
            </div>
        {/if}
        {#if (sale.shortage_amount ?? 0) > 0}
            <div class="row">
                <span>Kekurangan</span>
                <span class="amount">
                    <span class="currency">{currencySymbol}</span>
                    {formatCurrencyWithoutSymbol(sale.shortage_amount ?? 0)}
                </span>
            </div>
        {/if}
    </div>

    <div class="divider"></div>

    <div class="text-center text-[8pt]">
        <div>Terima kasih</div>
        {#if sale.requires_delivery}
            <div class="mt-0.5">
                Pengiriman: {sale.shipping_recipient_name || "-"} •
                {sale.shipping_recipient_phone || "-"}
            </div>
            <div class="mt-0.5">
                {sale.shipping_address || "-"}
            </div>
            {#if sale.shipping_note}
                <div class="mt-0.5">
                    Catatan: {sale.shipping_note}
                </div>
            {/if}
        {/if}
    </div>
</section>

<style>
    .logo {
        width: 40mm;
        max-width: 46mm;
        height: auto;
        margin: 0 auto;
        display: block;
    }
    .receipt {
        width: 58mm;
        margin: 0 auto;
        padding: 2mm 1mm 3mm 1mm;
        line-height: 1.25;
    }
    .divider {
        border-top: 1px solid #d1d5db;
        margin: 2mm 0;
    }
    .item .name {
        font-size: 9pt;
        font-weight: 600;
        color: #111827;
    }
    :global(.dark) .item .name {
        color: #ffffff;
    }
    .item .meta {
        font-size: 8pt;
        color: #374151;
        display: flex;
        justify-content: space-between;
    }
    :global(.dark) .item .meta {
        color: #d1d5db;
    }
    .item .note {
        font-size: 7pt;
        color: #6b7280;
        font-style: italic;
        margin-top: 0.5mm;
    }
    .totals .row,
    .payments .row {
        display: flex;
        justify-content: space-between;
        font-size: 8pt;
        margin: 0.5mm 0;
    }
    .totals .row.total span {
        font-weight: 700;
        font-size: 10pt;
    }
    .amount {
        display: inline-flex;
        gap: 1mm;
        align-items: baseline;
    }
    .currency {
        font-weight: 600;
    }
    @media print {
        .receipt {
            width: 54mm;
            margin: 0 auto;
        }
    }
</style>
