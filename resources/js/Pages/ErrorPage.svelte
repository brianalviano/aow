<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import AdminLayout from "@/Lib/Admin/Layouts/Default.svelte";

    export const layout = AdminLayout;

    let { status }: { status: 403 | 404 | 419 | 500 | 503 } = $props();

    const titles: Record<number, string> = {
        503: "Recharging Energy...",
        500: "System Overheat",
        404: "Off The Map",
        403: "Access Denied",
        419: "Session Expired",
    };

    const descriptions: Record<number, string> = {
        503: "Sistem sedang istirahat sebentar untuk pemeliharaan. Kembali lagi nanti ya!",
        500: "Mesin kami sedikit 'kepanasan'. Tim teknis sedang mendinginkannya.",
        404: "Halaman yang kamu cari tidak ditemukan di peta kami.",
        403: "Area ini tertutup. Kamu memerlukan kunci akses khusus.",
        419: "Sesi kamu telah berakhir. Silakan muat ulang atau login kembali.",
    };

    const isAuthenticated = $derived(
        !!(
            ($page.props as any)?.auth?.guard ||
            ($page.props as any)?.auth?.user
        ),
    );

    const primaryHref = $derived(
        (status === 404 && isAuthenticated) || isAuthenticated
            ? "/dashboard"
            : "/login",
    );

    const primaryLabel = $derived(
        isAuthenticated ? "Back to Dashboard" : "Login Now",
    );
</script>

<section
    class="relative min-h-screen w-full flex items-center justify-center overflow-hidden bg-[#1c1917] text-white selection:bg-orange-500 selection:text-white"
>
    <div
        class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none"
    >
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 font-black text-[30vw] leading-none text-white/3 select-none blur-sm tracking-tighter z-0"
        >
            {status}
        </div>

        <div
            class="absolute top-[-10%] left-[20%] size-125 bg-orange-600/20 rounded-full blur-[120px] mix-blend-screen animate-pulse duration-4000"
        ></div>
        <div
            class="absolute bottom-[-10%] right-[20%] size-150 bg-red-700/20 rounded-full blur-[120px] mix-blend-screen animate-pulse duration-7000"
        ></div>
        <div
            class="absolute top-[30%] right-[10%] size-75 bg-amber-500/10 rounded-full blur-[100px] mix-blend-screen animate-pulse duration-5000"
        ></div>
    </div>

    <div
        class="relative z-10 w-full max-w-6xl px-6 py-12 flex flex-col items-center"
    >
        <div class="text-center max-w-2xl mx-auto mb-16 animate-fade-in-up">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-orange-500/20 bg-orange-900/10 backdrop-blur-md text-sm font-mono tracking-widest text-orange-300 mb-6 shadow-xl shadow-orange-900/20"
            >
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"
                    ></span>
                    <span
                        class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"
                    ></span>
                </span>
                ERROR CODE: {status}
            </div>

            <h1
                class="text-5xl md:text-7xl font-bold bg-clip-text text-transparent bg-linear-to-r from-white via-orange-100 to-red-200 drop-shadow-sm mb-6 tracking-tight"
            >
                {titles[status]}
            </h1>

            <p class="text-lg md:text-xl text-stone-400 leading-relaxed">
                {descriptions[status]}
            </p>

            <div class="mt-8">
                <Button href={primaryHref} variant="warning" size="lg">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    {primaryLabel}
                </Button>
            </div>
        </div>

        {#if isAuthenticated}
            <div class="w-full max-w-4xl mx-auto">
                <div
                    class="flex items-center justify-center gap-4 mb-8 opacity-60"
                >
                    <div
                        class="h-px w-12 bg-linear-to-r from-transparent to-stone-500"
                    ></div>
                    <span
                        class="text-xs font-bold uppercase tracking-[0.2em] text-stone-500"
                        >Quick Access</span
                    >
                    <div
                        class="h-px w-12 bg-linear-to-l from-transparent to-stone-500"
                    ></div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    <a
                        href="/absents"
                        class="group relative overflow-hidden p-6 rounded-3xl bg-white/3 border border-white/10 hover:bg-white/8 hover:border-orange-500/30 transition-all duration-500 flex flex-col items-center justify-center gap-4 text-center cursor-pointer hover:-translate-y-2 hover:shadow-2xl hover:shadow-orange-500/20"
                    >
                        <div
                            class="absolute inset-0 bg-linear-to-br from-orange-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                        ></div>
                        <div
                            class="h-12 w-12 rounded-2xl bg-linear-to-br from-orange-500 to-red-500 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-500 z-10"
                        >
                            <i class="fa-solid fa-camera text-xl"></i>
                        </div>
                        <span
                            class="text-sm font-semibold text-stone-300 group-hover:text-white z-10"
                            >Absensi</span
                        >
                    </a>

                    <a
                        href="/my-schedule"
                        class="group relative overflow-hidden p-6 rounded-3xl bg-white/3 border border-white/10 hover:bg-white/8 hover:border-rose-500/30 transition-all duration-500 flex flex-col items-center justify-center gap-4 text-center cursor-pointer hover:-translate-y-2 hover:shadow-2xl hover:shadow-rose-500/20"
                    >
                        <div
                            class="absolute inset-0 bg-linear-to-br from-rose-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                        ></div>
                        <div
                            class="h-12 w-12 rounded-2xl bg-linear-to-br from-rose-500 to-pink-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-500 z-10"
                        >
                            <i class="fa-solid fa-calendar-check text-xl"></i>
                        </div>
                        <span
                            class="text-sm font-semibold text-stone-300 group-hover:text-white z-10"
                            >Jadwal Saya</span
                        >
                    </a>

                    <a
                        href="/leaves"
                        class="group relative overflow-hidden p-6 rounded-3xl bg-white/3 border border-white/10 hover:bg-white/8 hover:border-amber-500/30 transition-all duration-500 flex flex-col items-center justify-center gap-4 text-center cursor-pointer hover:-translate-y-2 hover:shadow-2xl hover:shadow-amber-500/20"
                    >
                        <div
                            class="absolute inset-0 bg-linear-to-br from-amber-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                        ></div>
                        <div
                            class="h-12 w-12 rounded-2xl bg-linear-to-br from-amber-500 to-yellow-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-500 z-10"
                        >
                            <i class="fa-solid fa-file-signature text-xl"></i>
                        </div>
                        <span
                            class="text-sm font-semibold text-stone-300 group-hover:text-white z-10"
                            >Pengajuan Izin</span
                        >
                    </a>

                    <a
                        href="/organization-structure"
                        class="group relative overflow-hidden p-6 rounded-3xl bg-white/3 border border-white/10 hover:bg-white/8 hover:border-emerald-500/30 transition-all duration-500 flex flex-col items-center justify-center gap-4 text-center cursor-pointer hover:-translate-y-2 hover:shadow-2xl hover:shadow-emerald-500/20"
                    >
                        <div
                            class="absolute inset-0 bg-linear-to-br from-emerald-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                        ></div>
                        <div
                            class="h-12 w-12 rounded-2xl bg-linear-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-500 z-10"
                        >
                            <i class="fa-solid fa-sitemap text-xl"></i>
                        </div>
                        <span
                            class="text-sm font-semibold text-stone-300 group-hover:text-white z-10"
                            >Struktur</span
                        >
                    </a>
                </div>
            </div>
        {/if}
    </div>
</section>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.8s ease-out forwards;
    }
</style>
