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

    type StockTransfer = {
        id: string;
        number: string;
        transfer_date: string | null;
        status_label: string | null;
        notes: string | null;
        from_warehouse: {
            id: string | null;
            name: string | null;
            address: string | null;
            whatsapp: string | null;
        };
        to_warehouse: {
            id: string | null;
            name: string | null;
            address: string | null;
            whatsapp: string | null;
        };
        to_owner_user: {
            id: string | null;
            name: string | null;
            address: string | null;
            whatsapp: string | null;
        } | null;
        items: Array<{
            id: string;
            product: { id: string | null; name: string | null };
            quantity: number;
        }>;
    };

    let tr = $derived($page.props.stock_transfer as StockTransfer);

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
    <title>{siteName($page.props.settings)} - Cetak Mutasi Stok</title>
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
                        <p>{tr.from_warehouse?.address ?? "-"}</p>
                        <p class="mt-1">
                            WhatsApp: {tr.from_warehouse?.whatsapp ?? "-"}
                        </p>
                    </div>
                </td>
                <td class="border-none! align-top w-[60%] text-right">
                    <h2
                        class="title text-3xl font-bold text-gray-900 dark:text-white mb-4 print:text-xl print:mb-2"
                    >
                        MUTASI STOK
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
                                        {tr.number}
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
                                        {tr.transfer_date ?? "-"}
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
                        {tr.to_owner_user?.id
                            ? "MARKETING TUJUAN"
                            : "GUDANG TUJUAN"}
                    </p>
                    <div class="mb-1">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white print:text-xs"
                        >
                            {tr.to_owner_user?.id
                                ? (tr.to_owner_user?.name ?? "-")
                                : (tr.to_warehouse?.name ?? "-")}
                        </h3>
                    </div>
                    <div
                        class="text-[7pt] text-gray-800 dark:text-gray-300 leading-relaxed print:text-[7pt]"
                    >
                        <p>
                            Alamat Tujuan:
                            {tr.to_owner_user?.id
                                ? (tr.to_owner_user?.address ?? "-")
                                : (tr.to_warehouse?.address ?? "-")}
                        </p>
                        <p class="mt-1">
                            WhatsApp Tujuan:
                            {tr.to_owner_user?.id
                                ? (tr.to_owner_user?.whatsapp ?? "-")
                                : (tr.to_warehouse?.whatsapp ?? "-")}
                        </p>
                    </div>
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
                        {(tr.notes ?? "").trim() || "-"}
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
                </tr>
            </thead>
            <tbody>
                {#each tr.items as item, idx}
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
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</section>
