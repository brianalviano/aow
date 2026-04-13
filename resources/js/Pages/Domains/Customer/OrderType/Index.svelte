<script lang="ts">
    import icon from "@img/icon.png";
    import { page, router } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    interface Props {
        dropPointId?: string;
        dropPointName?: string;
        isInstantAvailable?: boolean;
        instantStartTime?: string;
        instantEndTime?: string;
    }

    let {
        dropPointId,
        dropPointName,
        isInstantAvailable = true,
        instantStartTime = "08:00",
        instantEndTime = "21:00",
    }: Props = $props();

    const APP_NAME = name($page.props.settings);

    function selectOrderType(type: "instant" | "preorder") {
        router.post("/order-type", {
            order_type: type,
            drop_point_id: dropPointId,
        });
    }

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit("/");
        }
    }
</script>

<svelte:head>
    <title>Pilih Tipe Pesanan | {APP_NAME}</title>
</svelte:head>

<div class="min-h-screen bg-slate-950 flex flex-col">
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 border-b border-slate-800 bg-slate-950 sticky top-0 z-10"
    >
        <div class="flex items-center gap-3">
            <button
                onclick={goBack}
                class="w-8 h-8 flex items-center justify-center text-slate-300"
                aria-label="Kembali"
            >
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>
            <div>
                <h1 class="font-bold text-lg leading-tight text-slate-100">
                    Tipe Pesanan
                </h1>
                <p class="text-xs text-slate-400">
                    {dropPointName
                        ? `Kirim ke ${dropPointName}`
                        : "Kirim ke Alamat Anda"}
                </p>
            </div>
        </div>
    </header>

    <main
        class="flex-1 p-6 flex flex-col items-center justify-center space-y-8"
    >
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-black text-slate-100 leading-tight">
                Pilih Tipe Pesanan
            </h2>
            <p class="text-slate-400 text-sm">
                Tentukan bagaimana Anda ingin menerima pesanan
            </p>
        </div>

        <div class="w-full max-w-sm space-y-6">
            <!-- Instant Option -->
            <button
                onclick={() => isInstantAvailable && selectOrderType("instant")}
                class="w-full group block bg-slate-950 p-8 rounded-4xl shadow-2xl border border-slate-800 transition-all transform text-left {isInstantAvailable
                    ? 'hover:border-[#FFD700]/50 active:scale-[0.98]'
                    : 'opacity-50 cursor-not-allowed'}"
                disabled={!isInstantAvailable}
            >
                <div class="flex items-center gap-6">
                    <div
                        class="{isInstantAvailable
                            ? 'bg-[#FFD700] text-slate-900 shadow-lg shadow-[#FFD700]/20'
                            : 'bg-slate-800 text-slate-600'} w-16 h-16 rounded-2xl flex items-center justify-center text-2xl"
                    >
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-black {isInstantAvailable
                                ? 'text-slate-100'
                                : 'text-slate-500'} text-xl leading-tight"
                        >
                            Instant Delivery
                        </h3>
                        <p class="text-slate-400 text-sm mt-1 font-medium">
                            Pesanan langsung diproses dan dikirim segera.
                        </p>
                        {#if !isInstantAvailable}
                            <p
                                class="text-red-400 text-[10px] mt-3 font-black uppercase tracking-widest bg-red-900/20 inline-block px-3 py-1 rounded-full border border-red-500/20"
                            >
                                Tersedia pukul {instantStartTime} - {instantEndTime}
                                WIB
                            </p>
                        {/if}
                    </div>
                </div>
            </button>

            <!-- Pre-order Option -->
            <button
                onclick={() => selectOrderType("preorder")}
                class="w-full group block bg-slate-950 hover:border-[#FFD700]/50 p-8 rounded-4xl shadow-2xl border border-slate-800 transition-all transform active:scale-[0.98] text-left"
            >
                <div class="flex items-center gap-6">
                    <div
                        class="bg-slate-800 w-16 h-16 rounded-2xl flex items-center justify-center text-slate-400 text-2xl group-hover:bg-[#FFD700] group-hover:text-slate-900 transition-all duration-300"
                    >
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-black text-slate-100 text-xl leading-tight"
                        >
                            Pre-Order
                        </h3>
                        <p class="text-slate-400 text-sm mt-1 font-medium">
                            Pesan sekarang untuk jadwal pengiriman nanti.
                        </p>
                    </div>
                </div>
            </button>
        </div>
    </main>
</div>
