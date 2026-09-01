<script lang="ts">
    import icon from "@img/icon.png";

    import { name as getAppName } from "@/Lib/Admin/Utils/settings";
    import { page, Link } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import { register } from "swiper/element/bundle";

    let loadingTarget = $state<string | null>(null);

    onMount(() => {
        register();

        const handleStart = (event: any) => {
            if (event.detail.visit.url.pathname === "/drop-points") {
                loadingTarget = "drop-points";
            } else if (event.detail.visit.url.pathname === "/custom-address") {
                loadingTarget = "custom-address";
            } else {
                loadingTarget = "other";
            }
        };

        const handleFinish = () => {
            loadingTarget = null;
        };

        document.addEventListener("inertia:start", handleStart);
        document.addEventListener("inertia:finish", handleFinish);

        return () => {
            document.removeEventListener("inertia:start", handleStart);
            document.removeEventListener("inertia:finish", handleFinish);
        };
    });

    interface Props {
        sliders?: { data: any[] };
        activeOrdersCount?: number;
        unreadNotificationsCount?: number;
        lastDropPoint?: {
            id: string;
            name: string;
            address?: string;
            category_label?: string;
        } | null;
    }

    // Props from controller
    let {
        sliders = { data: [] },
        activeOrdersCount = 0,
        unreadNotificationsCount = 0,
        lastDropPoint = null,
    }: Props = $props();

    let totalBadgeCount = $derived(
        activeOrdersCount + unreadNotificationsCount,
    );

    const APP_NAME = getAppName(page.props.settings);

    let displayItems = $derived(sliders.data.length > 0 ? sliders.data : []);

    let swiperEl: any = $state(null);

    $effect(() => {
        if (swiperEl && displayItems.length > 0) {
            // Memberikan sedikit delay agar Swiper Web Component tahu child node (slides) sudah dirender penuh oleh Svelte
            setTimeout(() => {
                if (swiperEl && !swiperEl.swiper) {
                    swiperEl.initialize();
                } else if (swiperEl && swiperEl.swiper) {
                    swiperEl.swiper.update();
                }
            }, 50);
        }
    });
</script>

<svelte:head>
    <title>{APP_NAME}</title>
</svelte:head>

<div class="min-h-screen">
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-[#FFD700] sticky top-0 z-10 shadow-sm"
    >
        <div class="flex items-center gap-3">
            <div
                class="bg-white/40 p-1.5 rounded-xl flex items-center justify-center shadow-inner"
            >
                <img
                    src={icon}
                    alt="Logo Utama"
                    loading="lazy"
                    class="object-contain size-7"
                />
            </div>
            <div>
                <h1 class="font-bold text-lg leading-tight text-slate-900">
                    {APP_NAME}
                </h1>
                <p
                    class="text-[10px] text-slate-800/70 font-medium uppercase tracking-tight"
                >
                    The Best Choice For Your Food
                </p>
            </div>
        </div>
        <Link
            href="/menu"
            class="relative text-slate-900 p-2 focus:outline-none hover:bg-black/5 rounded-full transition-colors"
            aria-label="Menu"
        >
            <i class="fa-solid fa-bars text-xl"></i>
            {#if totalBadgeCount > 0}
                <span
                    class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[9px] font-bold text-white ring-2 ring-[#FFD700]"
                >
                    {totalBadgeCount > 99 ? "99+" : totalBadgeCount}
                </span>
            {/if}
        </Link>
    </header>

    <!-- Main Content -->
    <main class="pb-4 flex flex-col items-center justify-center space-y-6">
        <!-- Promotional Section -->
        {#if displayItems.length > 0}
            <section class="w-full overflow-hidden py-8 shadow-inner relative">
                <div
                    class="absolute inset-0 bg-linear-to-b from-black/40 to-transparent pointer-events-none"
                ></div>
                <swiper-container
                    bind:this={swiperEl}
                    init="false"
                    slides-per-view="auto"
                    space-between="16"
                    class="w-full"
                    loop="true"
                    autoplay-delay="3000"
                    autoplay-disable-on-interaction="false"
                    centered-slides="true"
                >
                    {#each displayItems as item}
                        <swiper-slide style="width: 85%; max-width: 320px;">
                            <div
                                class="relative w-full aspect-video rounded-2xl overflow-hidden shadow-lg transform transition-transform duration-300"
                            >
                                <img
                                    src={item.photo}
                                    alt={item.name}
                                    class="absolute inset-0 w-full h-full object-cover"
                                    loading="lazy"
                                />
                                <div
                                    class="absolute inset-0 bg-linear-to-t from-slate-900/50 flex flex-col justify-end p-4"
                                >
                                    <p
                                        class="text-white font-bold text-lg leading-tight drop-shadow-md"
                                    >
                                        {item.name}
                                    </p>
                                </div>
                            </div>
                        </swiper-slide>
                    {/each}
                </swiper-container>
            </section>
        {/if}

        <div class="px-4 text-center space-y-2 mb-4">
            <h2 class="text-2xl font-black text-slate-100 leading-tight">
                Mau Pesan Ke Mana?
            </h2>
            <p class="text-slate-400 text-sm">
                Silakan pilih opsi pengiriman Anda
            </p>
        </div>

        <div class="px-4 w-full max-w-sm space-y-4">
            <!-- Quick Re-order at Last Drop Point if exists -->
            {#if lastDropPoint}
                <Link
                    href={`/drop-points/${lastDropPoint.id}/products`}
                    class="group block bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 p-5 rounded-2xl shadow-lg border border-blue-400/30 transition-all transform active:scale-[0.98] text-left"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-white/20 w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow-inner shrink-0"
                        >
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider bg-white/20 text-blue-100 px-2 py-0.5 rounded-full"
                                >
                                    Terakhir Dipilih
                                </span>
                            </div>
                            <h3 class="font-bold text-white text-base truncate">
                                {lastDropPoint.name}
                            </h3>
                            <p class="text-blue-100/80 text-xs truncate mt-0.5">
                                Pesan cepat langsung di lokasi ini
                            </p>
                        </div>
                        <i
                            class="fa-solid fa-chevron-right text-white/80 group-hover:translate-x-1 transition-transform"
                        ></i>
                    </div>
                </Link>
            {/if}

            <!-- Option 1: Choose Drop Point -->
            <Link
                href="/drop-points"
                class="group block bg-[#FFD700] hover:bg-[#FFC700] p-6 rounded-2xl shadow-sm border border-[#FFC700] transition-all transform active:scale-[0.98] {loadingTarget !==
                null
                    ? 'opacity-75 pointer-events-none'
                    : ''}"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="bg-black/20 w-14 h-14 rounded-xl flex items-center justify-center text-slate-900 text-2xl shadow-inner"
                    >
                        {#if loadingTarget === "drop-points"}
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        {:else}
                            <i class="fa-solid fa-location-dot"></i>
                        {/if}
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-bold text-slate-900 text-lg leading-tight"
                        >
                            Pilih Drop Point
                        </h3>
                        <p class="text-slate-800 text-xs mt-1">
                            Tersedia di lokasi terdekat Anda
                        </p>
                    </div>
                    <i
                        class="fa-solid fa-chevron-right text-slate-700 group-hover:translate-x-1 transition-transform"
                    ></i>
                </div>
            </Link>

            <!-- Option 2: Use Other Address -->
            <Link
                href="/custom-address"
                class="group block bg-[#FFD700] hover:bg-[#FFC700] p-6 rounded-2xl shadow-sm border border-[#FFC700] transition-all transform active:scale-[0.98] {loadingTarget !==
                null
                    ? 'opacity-75 pointer-events-none'
                    : ''}"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="bg-black/20 w-14 h-14 rounded-xl flex items-center justify-center text-slate-900 text-2xl shadow-inner"
                    >
                        {#if loadingTarget === "custom-address"}
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        {:else}
                            <i class="fa-solid fa-map-location-dot"></i>
                        {/if}
                    </div>
                    <div class="flex-1">
                        <h3
                            class="font-bold text-slate-900 text-lg leading-tight"
                        >
                            Gunakan Alamat Lain
                        </h3>
                        <p class="text-slate-800 text-xs mt-1">
                            Pesan dari lokasi Anda saat ini
                        </p>
                    </div>
                    <i
                        class="fa-solid fa-chevron-right text-slate-700 group-hover:translate-x-1 transition-transform"
                    ></i>
                </div>
            </Link>
        </div>
    </main>
</div>
