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

<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 border-b border-gray-100 bg-white sticky top-0 z-10"
    >
        <div class="flex items-center gap-3">
            <button
                onclick={goBack}
                class="w-8 h-8 flex items-center justify-center text-gray-800"
                aria-label="Kembali"
            >
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>
            <div>
                <h1 class="font-bold text-lg leading-tight">Tipe Pesanan</h1>
                <p class="text-xs text-gray-500">
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
            <h2 class="text-2xl font-black text-gray-900 leading-tight">
                Pilih Tipe Pesanan
            </h2>
            <p class="text-gray-500 text-sm">
                Tentukan bagaimana Anda ingin menerima pesanan
            </p>
        </div>

        <div class="w-full max-w-sm space-y-4">
            <!-- Instant Option -->
            <button
                onclick={() => isInstantAvailable && selectOrderType("instant")}
                class="w-full group block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 transition-all transform text-left {isInstantAvailable
                    ? 'hover:bg-yellow-50 hover:border-[#FFD700] active:scale-[0.98]'
                    : 'opacity-60 cursor-not-allowed hover:bg-white hover:border-gray-100 active:scale-100 group-hover:bg-white group-hover:border-gray-100'}"
                disabled={!isInstantAvailable}
            >
                <div class="flex items-center gap-4">
                    <div
                        class="{isInstantAvailable
                            ? 'bg-[#FFD700] text-slate-800'
                            : 'bg-gray-200 text-gray-400'} w-14 h-14 rounded-xl flex items-center justify-center text-2xl shadow-inner"
                    >
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-bold {isInstantAvailable
                                ? 'text-slate-900'
                                : 'text-gray-500'} text-lg leading-tight"
                        >
                            Instant Delivery
                        </h3>
                        <p class="text-gray-500 text-xs mt-1">
                            Pesanan langsung diproses dan dikirim segera.
                        </p>
                        {#if !isInstantAvailable}
                            <p
                                class="text-red-500 text-xs mt-1.5 font-semibold bg-red-50 inline-block px-2 py-1 rounded"
                            >
                                Hanya tersedia pukul {instantStartTime} - {instantEndTime}
                                WIB.
                            </p>
                        {/if}
                    </div>
                </div>
            </button>

            <!-- Pre-order Option -->
            <button
                onclick={() => selectOrderType("preorder")}
                class="w-full group block bg-white hover:bg-yellow-50 p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-[#FFD700] transition-all transform active:scale-[0.98] text-left"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="bg-gray-100 w-14 h-14 rounded-xl flex items-center justify-center text-gray-600 text-2xl group-hover:bg-[#FFD700] group-hover:text-slate-800 transition-colors"
                    >
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-bold text-gray-800 text-lg leading-tight group-hover:text-slate-900 transition-colors"
                        >
                            Pre-Order
                        </h3>
                        <p class="text-gray-500 text-xs mt-1">
                            Pesan sekarang untuk jadwal pengiriman nanti.
                        </p>
                    </div>
                </div>
            </button>
        </div>
    </main>
</div>
