<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface ResumeItem {
        product_name: string;
        options_label: string | null;
        quantity: number;
    }

    interface ResumeGroup {
        drop_point_name: string;
        items: ResumeItem[];
    }

    let resumeData = $derived(
        (page.props.resumeData as ResumeGroup[]) ?? [],
    );

    let filters = $derived(
        page.props.filters as { delivery_date: string },
    );

    let deliveryDate = $state<string>(untrack(() => {
        const defaultDate = new Date().toISOString().split("T")[0] ?? "";
        return filters?.delivery_date || defaultDate;
    }));

    $effect(() => {
        if (deliveryDate !== filters?.delivery_date) {
            router.get(
                "/admin/orders/resume",
                { delivery_date: deliveryDate },
                { preserveState: true, replace: true },
            );
        }
    });

    function handlePrint() {
        window.print();
    }

    function formatDateDisplay(dateStr: string): string {
        try {
            return new Date(dateStr).toLocaleDateString("id-ID", {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        } catch (e) {
            return dateStr;
        }
    }
</script>

<svelte:head>
    <title>Resume Order | {name(page.props.settings)}</title>
</svelte:head>

<section class="space-y-6 print:space-y-4 print:p-0">
    <!-- Header (Hidden on print) -->
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Resume Order
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Total pesanan per menu per drop point untuk mempermudah checker dapur
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-print"
                onclick={handlePrint}
            >
                Cetak Resume
            </Button>
        </div>
    </header>

    <!-- Filters Section (Hidden on print) -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 print:hidden">
        <div class="max-w-xs">
            <DateInput
                label="Tanggal Pengiriman"
                bind:value={deliveryDate}
                placeholder="Pilih Tanggal Pengiriman"
            />
        </div>
    </div>

    <!-- Print Title Header (Visible only on print) -->
    <div class="hidden print:block text-center border-b-2 border-gray-800 pb-3 mb-6">
        <h1 class="text-2xl font-bold text-black uppercase">{name(page.props.settings) || "AOWENAK"}</h1>
        <h2 class="text-lg font-bold text-black mt-1">RESUME ORDER</h2>
        <p class="text-sm text-gray-600 mt-1">
            Tanggal Pengiriman: <strong>{formatDateDisplay(deliveryDate)}</strong>
        </p>
        <p class="text-[10px] text-gray-500 mt-0.5">Dicetak pada: {new Date().toLocaleString('id-ID')}</p>
    </div>

    <!-- Resume Data Display -->
    {#if resumeData.length > 0}
        <div class="grid grid-cols-1 gap-6 print:gap-4">
            {#each resumeData as group}
                <Card title={group.drop_point_name} collapsible={false} class="print:border print:border-gray-300 print:shadow-none">
                    {#snippet children()}
                        <div class="overflow-x-auto">
                            <table class="custom-table min-w-full print:text-black">
                                <thead>
                                    <tr class="print:border-b print:border-gray-300">
                                        <th class="text-left font-bold py-2 print:py-1">Nama Menu</th>
                                        <th class="text-left font-bold py-2 print:py-1">Varian / Pilihan</th>
                                        <th class="text-center font-bold py-2 print:py-1 w-24">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each group.items as item}
                                        <tr class="border-b border-gray-100 dark:border-gray-800 print:border-b print:border-gray-200">
                                            <td class="py-3 print:py-1.5 font-semibold text-gray-900 dark:text-white print:text-black">
                                                {item.product_name}
                                            </td>
                                            <td class="py-3 print:py-1.5 text-sm text-gray-600 dark:text-gray-400 print:text-gray-700">
                                                {item.options_label || "-"}
                                            </td>
                                            <td class="py-3 print:py-1.5 text-center font-bold text-indigo-600 dark:text-indigo-400 print:text-black text-base">
                                                {item.quantity} porsi
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-between items-center text-sm font-bold text-gray-800 dark:text-gray-200 print:text-black pt-2 border-t border-gray-100 dark:border-gray-800 print:border-t-2 print:border-gray-300">
                            <span>TOTAL PORSI:</span>
                            <span class="text-lg text-indigo-600 dark:text-indigo-400 print:text-black">
                                {group.items.reduce((sum, item) => sum + item.quantity, 0)} porsi
                            </span>
                        </div>
                    {/snippet}
                </Card>
            {/each}
        </div>
    {:else}
        <Card title="" collapsible={false}>
            {#snippet children()}
                <div class="flex flex-col items-center justify-center py-12 text-center text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-inbox text-5xl text-gray-300 mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-1">Tidak Ada Pesanan</h3>
                    <p>Belum ada pesanan yang terkonfirmasi/lunas untuk tanggal <strong>{formatDateDisplay(deliveryDate)}</strong></p>
                </div>
            {/snippet}
        </Card>
    {/if}
</section>

<style>
    @media print {
        :global(body *) {
            visibility: hidden;
        }
        :global(main), :global(.content-area) {
            margin: 0 !important;
            padding: 0 !important;
        }
        section, section * {
            visibility: visible;
        }
        section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        :global(.print\:hidden) {
            display: none !important;
        }
        :global(.custom-table) {
            table-layout: fixed !important;
            width: 100% !important;
        }
        :global(.custom-table th), :global(.custom-table td) {
            white-space: normal !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
        }
        :global(.overflow-x-auto) {
            overflow: visible !important;
        }
    }
</style>
