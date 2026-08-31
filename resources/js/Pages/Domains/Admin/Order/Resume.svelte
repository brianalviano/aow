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
        size_option?: string | null;
        spicy_option?: string | null;
        other_options?: string[];
        quantity: number;
        paid_quantity?: number;
        unpaid_quantity?: number;
    }

    interface ResumeGroup {
        drop_point_name: string;
        items: ResumeItem[];
    }

    interface ActiveDate {
        date: string;
        total_orders: number;
    }

    let resumeData = $derived(
        (page.props.resumeData as ResumeGroup[]) ?? [],
    );

    let filters = $derived(
        page.props.filters as { delivery_date: string; payment_filter?: string },
    );

    let activeDates = $derived(
        (page.props.activeDates as ActiveDate[]) ?? [],
    );

    let deliveryDate = $state<string>(untrack(() => {
        const defaultDate = new Date().toISOString().split("T")[0] ?? "";
        return filters?.delivery_date || defaultDate;
    }));

    let paymentFilter = $state<string>(untrack(() => {
        return filters?.payment_filter || "all";
    }));

    $effect(() => {
        if (
            deliveryDate !== filters?.delivery_date ||
            paymentFilter !== (filters?.payment_filter || "all")
        ) {
            router.get(
                "/admin/orders/resume",
                {
                    delivery_date: deliveryDate,
                    payment_filter: paymentFilter,
                },
                { preserveState: true, replace: true },
            );
        }
    });

    function handlePrint() {
        window.print();
    }

    function formatDateDisplay(dateStr: string): string {
        try {
            return new Date(dateStr + "T00:00:00").toLocaleDateString("id-ID", {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        } catch (e) {
            return dateStr;
        }
    }

    function formatShortDate(dateStr: string): string {
        try {
            return new Date(dateStr + "T00:00:00").toLocaleDateString("id-ID", {
                weekday: "short",
                day: "numeric",
                month: "short",
            });
        } catch (e) {
            return dateStr;
        }
    }

    function setDate(dateStr: string) {
        deliveryDate = dateStr;
    }

    const todayStr = new Date().toISOString().split("T")[0] ?? "";
    const tomorrowDate = new Date();
    tomorrowDate.setDate(tomorrowDate.getDate() + 1);
    const tomorrowStr = tomorrowDate.toISOString().split("T")[0] ?? "";
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

    <!-- Filters & Quick Date Selector (Hidden on print) -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5 print:hidden space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Date Input -->
            <div class="w-full sm:w-72">
                <DateInput
                    label="Tanggal Pengiriman"
                    bind:value={deliveryDate}
                    placeholder="Pilih Tanggal Pengiriman"
                />
            </div>

            <!-- Payment Filter Toggle -->
            <div class="flex flex-col gap-1.5">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">Status Pembayaran:</span>
                <div class="inline-flex rounded-lg p-1 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {paymentFilter === 'all' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'}"
                        onclick={() => paymentFilter = 'all'}
                    >
                        Semua Pesanan
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {paymentFilter === 'paid_only' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'}"
                        onclick={() => paymentFilter = 'paid_only'}
                    >
                        Hanya Lunas / Cash
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Date Pills -->
        <div class="border-t border-gray-100 dark:border-gray-800 pt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1 flex items-center gap-1.5">
                <i class="fa-regular fa-calendar-check text-indigo-500"></i>
                Jadwal Cepat:
            </span>

            <!-- Hari Ini -->
            <button
                type="button"
                class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors {deliveryDate === todayStr ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100'}"
                onclick={() => setDate(todayStr)}
            >
                Hari Ini
            </button>

            <!-- Besok -->
            <button
                type="button"
                class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors {deliveryDate === tomorrowStr ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100'}"
                onclick={() => setDate(tomorrowStr)}
            >
                Besok
            </button>

            <!-- Active Dates from DB -->
            {#each activeDates as item}
                {#if item.date !== todayStr && item.date !== tomorrowStr}
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors flex items-center gap-1.5 {deliveryDate === item.date ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100'}"
                        onclick={() => setDate(item.date)}
                    >
                        <span>{formatShortDate(item.date)}</span>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-bold {deliveryDate === item.date ? 'bg-white/20 text-white' : 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300'}">
                            {item.total_orders}
                        </span>
                    </button>
                {/if}
            {/each}
        </div>
    </div>

    <!-- Print Title Header (Visible only on print) -->
    <div class="hidden print:block text-center border-b-2 border-gray-800 pb-3 mb-6">
        <h1 class="text-2xl font-bold text-black uppercase">{name(page.props.settings) || "AOWENAK"}</h1>
        <h2 class="text-lg font-bold text-black mt-1">RESUME ORDER DAPUR</h2>
        <p class="text-sm text-gray-600 mt-1">
            Tanggal Pengiriman: <strong>{formatDateDisplay(deliveryDate)}</strong>
        </p>
        <p class="text-[10px] text-gray-500 mt-0.5">
            Filter: {paymentFilter === 'paid_only' ? 'Pesanan Lunas/Cash' : 'Semua Pesanan Aktif'} | Dicetak pada: {new Date().toLocaleString('id-ID')}
        </p>
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
                                    <tr class="print:border-b print:border-gray-300 bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                                        <th class="text-left font-bold py-2.5 px-3 print:py-1">Nama Menu</th>
                                        <th class="text-left font-bold py-2.5 px-3 print:py-1">Varian / Pilihan Ukuran</th>
                                        <th class="text-center font-bold py-2.5 px-3 print:py-1 w-32">Jumlah Porsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each group.items as item}
                                        <tr class="border-b border-gray-100 dark:border-gray-800 print:border-b print:border-gray-200 hover:bg-gray-50/30 dark:hover:bg-gray-800/30 transition-colors">
                                            <td class="py-3 px-3 print:py-1.5 font-bold text-gray-900 dark:text-white print:text-black text-sm">
                                                {item.product_name}
                                            </td>
                                            <td class="py-3 px-3 print:py-1.5 text-xs">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    {#if item.size_option}
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-bold text-[11px] {item.size_option.toLowerCase().includes('besar') ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700'}">
                                                            <i class="fa-solid fa-cube text-[9px] mr-1 opacity-70"></i>
                                                            {item.size_option}
                                                        </span>
                                                    {/if}

                                                    {#if item.spicy_option}
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-bold text-[11px] bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border border-orange-200 dark:border-orange-800">
                                                            <i class="fa-solid fa-pepper-hot text-[9px] mr-1 opacity-70"></i>
                                                            {item.spicy_option}
                                                        </span>
                                                    {/if}

                                                    {#if item.other_options && item.other_options.length > 0}
                                                        {#each item.other_options as otherOpt}
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded font-medium text-[11px] bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                                                {otherOpt}
                                                            </span>
                                                        {/each}
                                                    {/if}

                                                    {#if !item.size_option && !item.spicy_option && (!item.other_options || item.other_options.length === 0)}
                                                        <span class="text-gray-400 italic">{item.options_label || "-"}</span>
                                                    {/if}
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 print:py-1.5 text-center">
                                                <div class="font-black text-indigo-600 dark:text-indigo-400 print:text-black text-base">
                                                    {item.quantity} porsi
                                                </div>
                                                {#if paymentFilter === 'all' && item.unpaid_quantity && item.unpaid_quantity > 0}
                                                    <div class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold print:hidden mt-0.5">
                                                        ({item.paid_quantity} Lunas, {item.unpaid_quantity} Blm Bayar)
                                                    </div>
                                                {/if}
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-between items-center text-sm font-bold text-gray-800 dark:text-gray-200 print:text-black pt-3 border-t border-gray-100 dark:border-gray-800 print:border-t-2 print:border-gray-300">
                            <span class="text-gray-500 dark:text-gray-400">TOTAL PORSI DROP POINT INI:</span>
                            <span class="text-xl text-indigo-600 dark:text-indigo-400 print:text-black font-black">
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
                <div class="flex flex-col items-center justify-center py-12 text-center text-gray-500 dark:text-gray-400 space-y-3">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 mb-1">
                        <i class="fa-solid fa-calendar-xmark text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Tidak Ada Pesanan di Tanggal Ini</h3>
                    <p class="max-w-md text-sm text-gray-500">
                        Belum ada pesanan {paymentFilter === 'paid_only' ? 'lunas' : 'aktif'} untuk jadwal pengiriman <strong class="text-gray-800 dark:text-gray-200">{formatDateDisplay(deliveryDate)}</strong>.
                    </p>

                    {#if activeDates.length > 0}
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 max-w-lg w-full">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-3">
                                <i class="fa-solid fa-bolt text-amber-500 mr-1"></i>
                                Rekomendasi Jadwal Pengiriman dengan Pesanan Aktif:
                            </p>
                            <div class="flex flex-wrap justify-center gap-2">
                                {#each activeDates as item}
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/70 dark:text-indigo-300 dark:hover:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1.5 transition-colors"
                                        onclick={() => setDate(item.date)}
                                    >
                                        <i class="fa-regular fa-calendar text-indigo-500"></i>
                                        <span>{formatDateDisplay(item.date)}</span>
                                        <span class="px-1.5 py-0.2 rounded-full bg-indigo-600 text-white text-[10px]">
                                            {item.total_orders} pesanan
                                        </span>
                                    </button>
                                {/each}
                            </div>
                        </div>
                    {/if}
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
