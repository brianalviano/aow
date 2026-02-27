<script lang="ts">
    import icon from "@img/icon.png";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { page, Link } from "@inertiajs/svelte";

    // Props from controller
    export let activeOrdersCount: number = 0;
    export let unreadNotificationsCount: number = 0;

    $: totalBadgeCount = activeOrdersCount + unreadNotificationsCount;

    const APP_NAME = name($page.props.settings);
</script>

<svelte:head>
    <title>{APP_NAME}</title>
</svelte:head>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 border-b border-gray-100 bg-white sticky top-0 z-10"
    >
        <div class="flex items-center gap-3">
            <div>
                <img
                    src={icon}
                    alt="Logo Utama"
                    loading="lazy"
                    class="object-contain rounded-lg size-8"
                />
            </div>
            <div>
                <h1 class="font-bold text-lg leading-tight">
                    {APP_NAME}
                </h1>
                <p class="text-xs text-gray-500">Pesan Makanan & Minuman</p>
            </div>
        </div>
        <Link
            href="/menu"
            class="relative text-gray-800 p-2 focus:outline-none"
            aria-label="Menu"
        >
            <i class="fa-solid fa-bars text-xl"></i>
            {#if totalBadgeCount > 0}
                <span
                    class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-2 ring-white"
                >
                    {totalBadgeCount > 99 ? "99+" : totalBadgeCount}
                </span>
            {/if}
        </Link>
    </header>

    <!-- Main Content -->
    <main class="p-4 flex flex-col items-center justify-center space-y-6 pt-12">
        <div class="text-center space-y-2 mb-4">
            <h2 class="text-2xl font-black text-gray-900 leading-tight">
                Mau Pesan Ke Mana?
            </h2>
            <p class="text-gray-500 text-sm">
                Silakan pilih opsi pengiriman Anda
            </p>
        </div>

        <div class="w-full max-w-sm space-y-4">
            <!-- Option 1: Choose Drop Point -->
            <Link
                href="/drop-points"
                class="group block bg-[#CCFF33] hover:bg-[#bdf33c] p-6 rounded-2xl shadow-sm border border-[#bdf33c] transition-all transform active:scale-[0.98]"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="bg-white/50 w-14 h-14 rounded-xl flex items-center justify-center text-slate-800 text-2xl shadow-inner"
                    >
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-bold text-slate-900 text-lg leading-tight"
                        >
                            Pilih Drop Point
                        </h3>
                        <p class="text-slate-700 text-xs mt-1">
                            Tersedia di lokasi terdekat Anda
                        </p>
                    </div>
                    <i
                        class="fa-solid fa-chevron-right text-slate-500 group-hover:translate-x-1 transition-transform"
                    ></i>
                </div>
            </Link>

            <!-- Option 2: Use Other Address -->
            <div
                class="group block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 opacity-80 cursor-not-allowed"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="bg-gray-100 w-14 h-14 rounded-xl flex items-center justify-center text-gray-400 text-2xl"
                    >
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-bold text-gray-400 text-lg leading-tight"
                        >
                            Gunakan Alamat Lain
                        </h3>
                        <p class="text-gray-400 text-xs mt-1">
                            Segera hadir untuk lokasi Anda
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Decoration -->
    <div
        class="fixed bottom-0 left-0 right-0 p-8 flex justify-center opacity-10 pointer-events-none"
    >
        <img src={icon} alt="" class="w-32 h-32 grayscale" />
    </div>
</div>
