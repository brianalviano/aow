<script lang="ts">
    import { router } from "@inertiajs/svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";

    interface Props {
        order: any;
    }

    let { order }: Props = $props();

    let guideModalOpen = $state(false);
    let activeGuide = $state<any>(null);

    function showGuide(method: any) {
        activeGuide = method.payment_guide;
        guideModalOpen = true;
    }

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function copyToClipboard(text: string) {
        navigator.clipboard.writeText(text);
        alert("Berhasil disalin!");
    }

    function getMidtransData() {
        if (!order?.payment_details) return null;
        const details = order.payment_details;

        // Handle VA
        if (details.va_numbers?.[0]) {
            return {
                type: "va",
                bank: details.va_numbers[0].bank,
                number: details.va_numbers[0].va_number,
                expiry: details.expiry_time,
            };
        }

        // Handle Permata VA
        if (details.permata_va_number) {
            return {
                type: "va",
                bank: "permata",
                number: details.permata_va_number,
                expiry: details.expiry_time,
            };
        }

        // Handle Mandiri (E-channel)
        if (details.bill_key) {
            return {
                type: "bill",
                bank: "mandiri",
                bill_key: details.bill_key,
                biller_code: details.biller_code,
                expiry: details.expiry_time,
            };
        }

        // Handle QRIS/GoPay
        if (details.actions) {
            const qrisAction = details.actions.find(
                (a: any) => a.name === "generate-qr-code",
            );
            if (qrisAction) {
                return {
                    type: "qris",
                    url: qrisAction.url,
                    expiry: details.expiry_time,
                };
            }
        }

        return null;
    }

    const midtransData = $derived(getMidtransData());
</script>

<svelte:head>
    <title>Selesaikan Pembayaran</title>
</svelte:head>

<div>
    <!-- Header -->
    <header class="flex items-center p-4 bg-white sticky top-0 z-30 shadow-sm">
        <button
            onclick={() => router.visit("/")}
            class="w-10 h-10 flex items-center justify-center text-gray-900 hover:bg-gray-50 rounded-full transition-colors"
            aria-label="Home"
        >
            <i class="fa-solid fa-house text-xl"></i>
        </button>
        <h1 class="flex-1 text-center font-bold text-xl text-gray-900 mr-10">
            Bayar
        </h1>
    </header>

    <main class="space-y-8 mt-6 mb-30 max-w-lg mx-auto">
        <!-- Success / Instruction State -->
        <section class="px-6 space-y-6">
            <div class="text-center space-y-3 py-6">
                <div
                    class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-4xl animate-bounce"
                >
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                    Pesanan Berhasil!
                </h2>
                <p class="text-gray-500 text-sm max-w-[280px] mx-auto">
                    Silakan selesaikan pembayaran sesuai instruksi di bawah ini.
                </p>
            </div>

            {#if midtransData}
                <div
                    class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-gray-200/50 border border-gray-100 space-y-8"
                >
                    <!-- VA / Bill Section -->
                    {#if midtransData.type === "va"}
                        <div class="space-y-4 text-center">
                            <p
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Nomor Virtual Account
                            </p>
                            <div class="flex items-center justify-center gap-3">
                                <span
                                    class="text-3xl font-black text-gray-900 tracking-tighter"
                                    >{midtransData.number}</span
                                >
                                <button
                                    onclick={() =>
                                        copyToClipboard(midtransData.number)}
                                    class="w-10 h-10 bg-gray-50 hover:bg-gray-100 text-gray-400 hover:text-gray-900 rounded-2xl transition-all flex items-center justify-center"
                                    title="Salin"
                                    aria-label="Salin nomor VA"
                                >
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <div
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full"
                            >
                                <span
                                    class="text-xs font-bold text-gray-500 uppercase"
                                    >{midtransData.bank}</span
                                >
                            </div>
                        </div>
                    {:else if midtransData.type === "bill"}
                        <div class="space-y-6">
                            <div class="text-center space-y-2">
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                >
                                    Biller Code
                                </p>
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <span
                                        class="text-2xl font-black text-gray-900"
                                        >{midtransData.biller_code}</span
                                    >
                                    <button
                                        onclick={() =>
                                            copyToClipboard(
                                                midtransData.biller_code,
                                            )}
                                        class="text-gray-400 hover:text-gray-900"
                                        aria-label="Salin biller code"
                                        ><i class="fa-solid fa-copy"
                                        ></i></button
                                    >
                                </div>
                            </div>
                            <div class="text-center space-y-2">
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                >
                                    Bill Key
                                </p>
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <span
                                        class="text-2xl font-black text-gray-900"
                                        >{midtransData.bill_key}</span
                                    >
                                    <button
                                        onclick={() =>
                                            copyToClipboard(
                                                midtransData.bill_key,
                                            )}
                                        class="text-gray-400 hover:text-gray-900"
                                        aria-label="Salin bill key"
                                        ><i class="fa-solid fa-copy"
                                        ></i></button
                                    >
                                </div>
                            </div>
                            <div class="text-center">
                                <div
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500 uppercase"
                                        >MANDIRI BILL</span
                                    >
                                </div>
                            </div>
                        </div>
                    {:else if midtransData.type === "qris"}
                        <div class="space-y-6 text-center">
                            <p
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Scan QRIS
                            </p>
                            <div
                                class="bg-white p-4 rounded-3xl inline-block shadow-lg border border-gray-100"
                            >
                                <img
                                    src={midtransData.url}
                                    alt="QRIS Code"
                                    class="w-64 h-64 object-contain mx-auto"
                                />
                            </div>
                            <p class="text-xs text-gray-500">
                                Buka aplikasi e-wallet kamu dan scan kode di
                                atas.
                            </p>
                        </div>
                    {/if}

                    <hr class="border-gray-100" />

                    <!-- Detail Section -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Total Tagihan</span>
                            <span class="font-black text-gray-900 text-lg"
                                >{formatRupiah(order.total_amount)}</span
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">ID Pesanan</span>
                            <span class="font-bold text-gray-900"
                                >{order.number}</span
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Batas Waktu</span>
                            <span class="font-bold text-red-500"
                                >{midtransData.expiry || "1x24 Jam"}</span
                            >
                        </div>
                    </div>
                </div>
            {:else}
                <!-- Manual Payment State -->
                <div
                    class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-gray-200/50 border border-gray-100 space-y-6"
                >
                    <div class="text-center space-y-2">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                        >
                            Metode Pembayaran
                        </p>
                        <p class="text-lg font-black text-gray-900">
                            {order.payment_method?.name}
                        </p>
                    </div>

                    {#if order.payment_method?.account_number}
                        <div
                            class="bg-gray-50 rounded-3xl p-6 text-center space-y-3"
                        >
                            <p
                                class="text-xs font-bold text-gray-400 uppercase"
                            >
                                Nomor Rekening
                            </p>
                            <div class="flex items-center justify-center gap-3">
                                <span class="text-2xl font-black text-gray-900"
                                    >{order.payment_method.account_number}</span
                                >
                                <button
                                    onclick={() =>
                                        copyToClipboard(
                                            order.payment_method.account_number,
                                        )}
                                    class="text-gray-400 hover:text-gray-900"
                                    aria-label="Salin nomor rekening"
                                >
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <p class="text-sm font-bold text-gray-600">
                                a/n {order.payment_method.account_name}
                            </p>
                        </div>
                    {/if}

                    <div class="pt-4">
                        <button
                            onclick={() => showGuide(order.payment_method)}
                            class="w-full py-4 text-sm font-bold text-[#0060B2] bg-blue-50 rounded-2xl flex items-center justify-center gap-2 hover:bg-blue-100 transition-colors"
                        >
                            <i class="fa-solid fa-circle-question"></i>
                            Lihat Instruksi Pembayaran
                        </button>
                    </div>
                </div>
            {/if}

            <div class="pt-6">
                <button
                    onclick={() => router.visit("/")}
                    class="w-full py-5 bg-[#CCFF33] text-gray-900 font-black rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all text-base"
                >
                    Selesai, Kembali ke Home
                </button>
            </div>
        </section>
    </main>

    <!-- Guide Dialog -->
    <Dialog
        bind:isOpen={guideModalOpen}
        title={activeGuide?.name || "Instruksi Pembayaran"}
        message=""
        showCancel={false}
        confirmText="Dimengerti"
    >
        {#snippet children()}
            <div
                class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar"
            >
                {#if activeGuide?.content}
                    {#each activeGuide.content as section}
                        <div class="space-y-3">
                            <h4
                                class="font-bold text-gray-900 border-l-4 border-[#CCFF33] pl-3"
                            >
                                {section.title}
                            </h4>
                            <ul class="space-y-2">
                                {#each section.items as item, index}
                                    <li
                                        class="flex gap-3 text-sm text-gray-600"
                                    >
                                        <span
                                            class="shrink-0 w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-500"
                                        >
                                            {index + 1}
                                        </span>
                                        <span>{item}</span>
                                    </li>
                                {/each}
                            </ul>
                        </div>
                    {/each}
                {:else}
                    <p class="text-sm text-gray-500 italic">
                        Belum ada instruksi untuk metode ini.
                    </p>
                {/if}
            </div>
        {/snippet}
    </Dialog>
</div>

<style>
    .animate-bounce {
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-20px);
        }
        60% {
            transform: translateY(-10px);
        }
    }
</style>
