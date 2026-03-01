<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";

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

    let { transfers = [] } = $props<{ transfers: Transfer[] }>();
</script>

<svelte:head>
    <title>Pendapatan & Transfer | {appName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen bg-gray-50">
    <header
        class="bg-white border-b border-gray-100 p-4 sticky top-0 z-10 shadow-sm"
    >
        <div class="max-w-7xl mx-auto flex justify-between items-center w-full">
            <div class="flex items-center gap-2">
                <div class="bg-orange-500 text-white p-1.5 rounded-lg">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">Pendapatan</h1>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 max-w-7xl mx-auto w-full">
        <div
            class="bg-linear-to-br from-orange-500 to-orange-600 rounded-2xl p-6 mb-8 shadow-lg text-white"
        >
            <h2 class="text-sm font-medium opacity-80 uppercase tracking-wider">
                Total Pendapatan Diterima
            </h2>
            <p class="text-3xl font-black mt-1">
                {formatCurrency(
                    transfers.reduce(
                        (acc: number, t: Transfer) => acc + Number(t.amount),
                        0,
                    ),
                )}
            </p>
            <div class="mt-4 flex items-center gap-2 text-xs opacity-70">
                <i class="fa-solid fa-circle-info"></i>
                <span
                    >Jumlah ini adalah total dari semua transfer yang telah
                    dikirim ke rekening Anda.</span
                >
            </div>
        </div>

        <h3 class="text-lg font-bold text-gray-900 mb-4 px-1">
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
                <p class="text-gray-500">
                    Data transfer dana akan muncul di sini setelah Admin
                    memproses pembayaran Anda.
                </p>
            </div>
        {:else}
            <div class="space-y-4">
                {#each transfers as transfer (transfer.id)}
                    <div
                        class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm flex items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center"
                            >
                                <i class="fa-solid fa-arrow-down-long"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">
                                    {formatCurrency(transfer.amount)}
                                </h4>
                                <p class="text-xs text-gray-500">
                                    {new Date(
                                        transfer.transferred_at,
                                    ).toLocaleDateString("id-ID", {
                                        day: "numeric",
                                        month: "long",
                                        year: "numeric",
                                        hour: "2-digit",
                                        minute: "2-digit",
                                    })}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <Badge variant="success" size="sm">Berhasil</Badge>
                            {#if transfer.transfer_proof}
                                <a
                                    href={transfer.transfer_proof}
                                    target="_blank"
                                    class="block text-[10px] text-orange-600 font-bold mt-1 hover:underline"
                                >
                                    Lihat Bukti
                                </a>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </main>
</div>
