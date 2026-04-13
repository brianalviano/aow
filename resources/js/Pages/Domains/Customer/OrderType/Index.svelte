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
        class="flex items-center justify-between p-4 bg-[#FFD700] sticky top-0 z-10 shadow-sm"
    >
        <div class="flex items-center gap-3">
            <button
                onclick={goBack}
                class="w-8 h-8 flex items-center justify-center text-slate-800"
                aria-label="Kembali"
            >
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>
            <div>
                <h1 class="font-black text-lg leading-tight text-slate-900">
                    Tipe Pesanan
                </h1>
                <p class="text-xs text-slate-800/70 font-medium">
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
                class="w-full group block bg-[#FFD700] p-8 rounded-[2.5rem] shadow-xl border border-[#FFC700] transition-all transform text-left {isInstantAvailable
                    ? 'hover:bg-[#FFC700] active:scale-[0.98]'
                    : 'opacity-50 cursor-not-allowed'}"
                disabled={!isInstantAvailable}
            >
                <div class="flex items-center gap-6">
                    <div
                        class="{isInstantAvailable
                            ? 'bg-slate-950 text-[#FFD700] shadow-lg shadow-black/20'
                            : 'bg-slate-800 text-slate-600'} w-16 h-16 rounded-2xl flex items-center justify-center text-2xl"
                    >
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-black {isInstantAvailable
                                ? 'text-slate-900'
                                : 'text-slate-600'} text-xl leading-tight"
                        >
                            Instant Delivery
                        </h3>
                        <p class="text-slate-800/80 text-sm mt-1 font-bold">
                            Pesanan langsung diproses dan dikirim segera.
                        </p>
                        {#if !isInstantAvailable}
                            <p
                                class="text-red-600 text-[10px] mt-3 font-black uppercase tracking-widest bg-white/40 inline-block px-3 py-1 rounded-full border border-red-500/20"
                            >
                                Coming Soon / Belum Tersedia
                            </p>
                        {/if}
                    </div>
                </div>
            </button>

            <!-- Pre-order Option -->
            <button
                onclick={() => selectOrderType("preorder")}
                class="w-full group block bg-[#FFD700] hover:bg-[#FFC700] p-8 rounded-[2.5rem] shadow-xl border border-[#FFC700] transition-all transform active:scale-[0.98] text-left"
            >
                <div class="flex items-center gap-6">
                    <div
                        class="bg-slate-950 w-16 h-16 rounded-2xl flex items-center justify-center text-[#FFD700] text-2xl shadow-lg shadow-black/20"
                    >
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-black text-slate-900 text-xl leading-tight"
                        >
                            Pre-Order
                        </h3>
                        <p class="text-slate-800/80 text-sm mt-1 font-bold">
                            Pesan sekarang untuk jadwal pengiriman nanti.
                        </p>
                    </div>
                </div>
            </button>
        </div>
    </main>
</div>
