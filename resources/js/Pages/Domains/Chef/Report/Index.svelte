<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { buildUrl } from "@/Lib/Admin/Utils/navigation";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";

    interface Transfer {
        id: string;
        amount: number;
        gross_amount: number;
        fee_amount: number;
        fee_percentage: number;
        note?: string;
        transfer_proof?: string;
        transferred_at: string;
    }

    interface ReportSummary {
        total_income: number;
        fee_amount: number;
        net_income: number;
        withdrawn: number;
        unwithdrawn: number;
    }

    let {
        transfers = [],
        summary,
        filters = {},
    } = $props<{
        transfers: Transfer[];
        summary: ReportSummary;
        filters: {
            date_range?: string;
            start_date?: string;
            end_date?: string;
        };
    }>();

    let dateRange = $state("all");
    let startDate = $state("");
    let endDate = $state("");

    $effect(() => {
        if (filters?.date_range) dateRange = filters.date_range;
        if (filters?.start_date) startDate = filters.start_date;
        if (filters?.end_date) endDate = filters.end_date;
    });

    function handleFilter() {
        const url = buildUrl("/chef/report", {
            date_range: dateRange !== "all" ? dateRange : undefined,
            start_date: dateRange === "custom" ? startDate : undefined,
            end_date: dateRange === "custom" ? endDate : undefined,
        });

        router.visit(url.toString(), {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function handleDateRangeChange(range: string) {
        dateRange = range;
        if (range !== "custom") {
            handleFilter();
        }
    }

    function clearFilter() {
        startDate = "";
        endDate = "";

        const url = buildUrl("/chef/report", {});
        router.visit(url.toString(), {
            preserveState: true,
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Laporan | {appName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen bg-gray-50 pb-24">
    <header
        class="bg-white border-b border-gray-100 p-4 sticky top-0 z-10 shadow-sm"
    >
        <div class="max-w-7xl mx-auto flex justify-between items-center w-full">
            <div class="flex items-center gap-2">
                <div class="bg-orange-500 text-white p-1.5 rounded-lg">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">Laporan</h1>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 max-w-7xl mx-auto w-full space-y-6">
        <!-- Date Filters -->
        <div class="px-1 flex gap-2 overflow-x-auto hide-scrollbar">
            <button
                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                'all'
                    ? 'bg-orange-600 border-orange-600 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                onclick={() => handleDateRangeChange("all")}
            >
                Semua Waktu
            </button>
            <button
                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-medium border transition-colors {dateRange ===
                'custom'
                    ? 'bg-orange-600 border-orange-600 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'}"
                onclick={() => handleDateRangeChange("custom")}
            >
                Pilih Tanggal
            </button>
        </div>

        {#if dateRange === "custom"}
            <div
                class="flex flex-col gap-2 animate-in fade-in slide-in-from-top-1 bg-white p-4 rounded-xl border border-gray-100 shadow-sm"
            >
                <div class="grid grid-cols-2 gap-3">
                    <DateInput
                        bind:value={startDate}
                        format="yyyy-mm-dd"
                        placeholder="Tanggal Mulai"
                    />
                    <DateInput
                        bind:value={endDate}
                        format="yyyy-mm-dd"
                        placeholder="Tanggal Selesai"
                    />
                </div>
                <button
                    onclick={handleFilter}
                    class="w-full mt-1 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-bold hover:bg-orange-700 transition-all shadow-sm active:scale-[0.98]"
                >
                    Terapkan Rentang Tanggal
                </button>
            </div>
        {/if}

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-4">
            <div
                class="bg-linear-to-br from-orange-500 to-orange-600 rounded-2xl p-4 shadow-lg text-white col-span-2"
            >
                <h2
                    class="text-xs font-medium opacity-80 uppercase tracking-wider"
                >
                    Total Pendapatan Bersih
                </h2>
                <p class="text-2xl font-black mt-1">
                    {formatCurrency(summary.net_income)}
                </p>
                <div
                    class="mt-3 flex items-center gap-2 text-[10px] opacity-70"
                >
                    <i class="fa-solid fa-circle-info"></i>
                    <span
                        >Telah dipotong fee aplikasi sebesar {formatCurrency(
                            summary.fee_amount,
                        )}</span
                    >
                </div>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col justify-center"
            >
                <div class="flex items-center gap-2 text-red-500 mb-1">
                    <i class="fa-solid fa-wallet text-sm"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider">
                        Belum Diambil
                    </h3>
                </div>
                <p class="text-lg font-black text-gray-900">
                    {formatCurrency(summary.unwithdrawn)}
                </p>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col justify-center"
            >
                <div class="flex items-center gap-2 text-green-500 mb-1">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider">
                        Sudah Diambil
                    </h3>
                </div>
                <p class="text-lg font-black text-gray-900">
                    {formatCurrency(summary.withdrawn)}
                </p>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm col-span-2 flex flex-col justify-center"
            >
                <div class="flex items-center gap-2 text-gray-500 mb-1">
                    <i class="fa-solid fa-money-bill-wave text-sm"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider">
                        Total Penjualan
                    </h3>
                </div>
                <p class="text-lg font-black text-gray-900">
                    {formatCurrency(summary.total_income)}
                </p>
            </div>
        </div>

        <!-- Transfer History -->
        <h3 class="text-lg font-bold text-gray-900 mb-2 mt-4 px-1">
            Riwayat Transfer
        </h3>

        {#if transfers.length === 0}
            <div
                class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-sm"
            >
                <div
                    class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                    <i
                        class="fa-solid fa-money-bill-transfer text-2xl text-gray-300"
                    ></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">
                    Belum ada riwayat transfer
                </h3>
                <p class="text-xs text-gray-500">
                    Data transfer dana dari Admin akan muncul di sini.
                </p>
            </div>
        {:else}
            <div class="space-y-4">
                {#each transfers as transfer (transfer.id)}
                    <div
                        class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shrink-0"
                                >
                                    <i class="fa-solid fa-arrow-down-long"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">
                                        {formatCurrency(transfer.amount)}
                                    </h4>
                                    <p class="text-[10px] text-gray-500">
                                        {new Date(
                                            transfer.transferred_at,
                                        ).toLocaleDateString("id-ID", {
                                            day: "numeric",
                                            month: "short",
                                            year: "numeric",
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge variant="success" size="sm"
                                    >Berhasil</Badge
                                >
                            </div>
                        </div>

                        {#if transfer.note || transfer.transfer_proof}
                            <div class="border-t border-gray-100 pt-3 mt-3">
                                {#if transfer.note}
                                    <p
                                        class="text-xs text-gray-600 mb-2 italic"
                                    >
                                        "{transfer.note}"
                                    </p>
                                {/if}
                                {#if transfer.transfer_proof}
                                    <a
                                        href={transfer.transfer_proof}
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-orange-600 font-semibold hover:text-orange-700 hover:underline bg-orange-50 px-3 py-1.5 rounded-lg transition-colors border border-orange-100"
                                    >
                                        <i class="fa-solid fa-file-invoice"></i>
                                        Lihat Bukti Transfer
                                    </a>
                                {/if}
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        {/if}
    </main>
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
