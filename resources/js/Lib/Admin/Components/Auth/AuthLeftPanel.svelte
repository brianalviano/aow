<script lang="ts">
    import backgroundImageDay from "@img/agriculture-day.png";
    import backgroundImageNight from "@img/agriculture-night.png";
    import { onMount } from "svelte";
    import { applyThemeClass } from "@/Lib/Admin/Hooks/sidebar";
    import icon from "@img/icon.png";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { page } from "@inertiajs/svelte";

    let backgroundImage = $state(backgroundImageDay);
    function updateBackground() {
        const isDark = document.documentElement.classList.contains("dark");
        backgroundImage = isDark ? backgroundImageNight : backgroundImageDay;
    }

    onMount(() => {
        const savedTheme = localStorage.getItem("theme");
        const initialDark = savedTheme
            ? savedTheme === "dark"
            : window.matchMedia("(prefers-color-scheme: dark)").matches;
        applyThemeClass(initialDark);
        updateBackground();
        const observer = new MutationObserver(() => updateBackground());
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });
        return () => observer.disconnect();
    });
</script>

<!-- Left Panel: Company Showcase - Hidden on mobile -->
<div class="group relative hidden w-0 overflow-hidden lg:block lg:w-3/5">
    <!-- Background with modern clip-path -->
    <div
        class="absolute inset-0 transition-all duration-1000 bg-cover bg-center bg-no-repeat"
        style="clip-path: polygon(0 0, 100% 0, 85% 100%, 0 100%); background-image: url({backgroundImage});"
    ></div>
    <div
        class="absolute inset-0 pointer-events-none transition-all duration-1000 bg-linear-to-b from-black/70 to-black/30 dark:from-black/55 dark:to-black/35"
        style="clip-path: polygon(0 0, 100% 0, 85% 100%, 0 100%);"
    ></div>

    <!-- Subtle pattern overlay -->
    <div class="absolute inset-0 opacity-10">
        <div
            class="h-full w-full"
            style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 40px 40px;"
        ></div>
    </div>

    <!-- Content Container -->
    <div class="relative z-10 flex h-full flex-col p-6 text-white xl:p-12">
        <!-- Header Section -->
        <div class="space-y-6">
            <!-- Company Logo & Brand -->
            <div class="group/brand flex items-center space-x-3">
                <div class="relative">
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-white/30 bg-white/20 p-2 backdrop-blur-sm transition-all duration-500 hover:rotate-6 hover:scale-110 hover:bg-white/30"
                    >
                        <img
                            alt="Logo"
                            class="w-full object-contain"
                            loading="lazy"
                            src={icon}
                        />
                    </div>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white">
                        {siteName($page.props.settings)}
                    </h1>
                    <p class="text-xs text-white/80">
                        Solusi Agrikultur Terpadu
                    </p>
                </div>
            </div>

            <!-- Main Value Proposition -->
            <div class="max-w-xl space-y-4">
                <h2
                    class="text-2xl font-bold leading-tight tracking-tight xl:text-3xl"
                >
                    <span
                        class="block cursor-default text-white transition-colors duration-300 hover:text-green-200"
                        >Mitra Terpercaya</span
                    >
                    <span
                        class="inline-block cursor-default text-white/80 transition-all duration-300 hover:text-white"
                        >Pertanian Modern Indonesia</span
                    >
                </h2>

                <p
                    class="cursor-default text-sm leading-relaxed text-white/90 transition-colors duration-300 hover:text-white xl:text-base"
                >
                    Penyedia lengkap kebutuhan pertanian modern sejak tahun
                    2020. Kami menyediakan solar industri, alat dan mesin
                    pertanian berkualitas, serta pupuk bersubsidi dan
                    non-subsidi untuk mendukung produktivitas petani Indonesia
                    mencapai hasil panen maksimal.
                </p>
            </div>

            <!-- Company Stats -->
            <div class="max-w-2xl space-y-4 mt-6">
                <div class="grid grid-cols-3 gap-4">
                    <!-- Stat 1 -->
                    <div
                        class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
                    >
                        <div class="flex items-start space-x-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-green-400"
                            >
                                <i class="fa-regular fa-clock text-white"></i>
                            </div>
                            <div>
                                <h3
                                    class="mb-1 text-xl font-bold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                                >
                                    5+
                                </h3>
                                <p
                                    class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                                >
                                    Tahun Pengalaman
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 2 -->
                    <div
                        class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
                    >
                        <div class="flex items-start space-x-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-amber-400"
                            >
                                <i class="fa-regular fa-users text-white"></i>
                            </div>
                            <div>
                                <h3
                                    class="mb-1 text-xl font-bold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                                >
                                    5000+
                                </h3>
                                <p
                                    class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                                >
                                    Petani Mitra
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 3 -->
                    <div
                        class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
                    >
                        <div class="flex items-start space-x-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-blue-400"
                            >
                                <i class="fa-regular fa-truck text-white"></i>
                            </div>
                            <div>
                                <h3
                                    class="mb-1 text-xl font-bold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                                >
                                    24/7
                                </h3>
                                <p
                                    class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                                >
                                    Layanan Pengiriman
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="my-6 grid grid-cols-1 gap-3 xl:grid-cols-2 xl:gap-4">
            <div
                class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
            >
                <div class="flex items-start space-x-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-red-400"
                    >
                        <i class="fa-regular fa-gas-pump text-white"></i>
                    </div>
                    <div>
                        <h3
                            class="mb-1 text-sm font-semibold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                        >
                            Solar Industri & Pertanian
                        </h3>
                        <p
                            class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                        >
                            Penyedia solar berkualitas untuk traktor dan mesin
                            pertanian dengan harga kompetitif dan pengiriman
                            tepat waktu
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:-rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
            >
                <div class="flex items-start space-x-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-orange-400"
                    >
                        <i class="fa-regular fa-tractor text-white"></i>
                    </div>
                    <div>
                        <h3
                            class="mb-1 text-sm font-semibold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                        >
                            Alat & Mesin Pertanian
                        </h3>
                        <p
                            class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                        >
                            Menyediakan traktor, mesin bajak, pompa air, dan
                            berbagai peralatan modern untuk efisiensi kerja
                            pertanian
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
            >
                <div class="flex items-start space-x-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-green-400"
                    >
                        <i class="fa-regular fa-seedling text-white"></i>
                    </div>
                    <div>
                        <h3
                            class="mb-1 text-sm font-semibold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                        >
                            Pupuk Bersubsidi & Non-Subsidi
                        </h3>
                        <p
                            class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                        >
                            Distributor resmi pupuk Urea, NPK, TSP, dan pupuk
                            organik untuk meningkatkan hasil panen
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="group/card cursor-pointer rounded-xl border border-white/20 bg-black/20 p-3 backdrop-blur-sm transition-all duration-500 hover:-rotate-1 hover:scale-105 hover:bg-white/20 xl:p-4"
            >
                <div class="flex items-start space-x-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 transition-all duration-300 group-hover/card:scale-110 group-hover/card:bg-emerald-400"
                    >
                        <i class="fa-regular fa-handshake text-white"></i>
                    </div>
                    <div>
                        <h3
                            class="mb-1 text-sm font-semibold text-white transition-transform duration-300 group-hover/card:translate-x-1"
                        >
                            Konsultasi Pertanian
                        </h3>
                        <p
                            class="text-xs text-white/80 transition-colors duration-300 group-hover/card:text-white"
                        >
                            Layanan konsultasi gratis seputar pemilihan alat,
                            penggunaan pupuk, dan solusi pertanian modern
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
