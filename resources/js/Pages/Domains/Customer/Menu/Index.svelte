<script lang="ts">
    import { page, Link } from "@inertiajs/svelte";
    import { name, buildWaLink } from "@/Lib/Admin/Utils/settings";

    // Auth object holds user information
    $: auth = $page.props.auth;
    $: user = auth.user;

    $: activeOrdersCount = $page.props.activeOrdersCount || 0;
    $: unreadNotificationsCount = $page.props.unreadNotificationsCount || 0;

    // Function to extract initials (e.g. "Vino N" -> "VN")
    function getInitials(name: string | null | undefined) {
        if (!name) return "?";
        const parts = name.trim().split(" ").filter(Boolean);
        const first = parts[0];
        const second = parts[1];
        if (first && second) {
            return (first.charAt(0) + second.charAt(0)).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }
</script>

<svelte:head>
    <title>Menu | {name($page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-slate-950 sticky top-0 z-10 border-b border-slate-800"
    >
        <Link
            href="/"
            class="text-slate-100 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-slate-100">Menu</h1>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col items-center pb-5">
        <!-- User Profile Area -->
        <div class="w-full flex items-center p-6 gap-4 mb-2">
            {#if user}
                <div
                    class="size-14 rounded-full bg-slate-700 flex items-center justify-center text-lg font-bold text-slate-100"
                >
                    {getInitials(user.name)}
                </div>
                <div>
                    <h2
                        class="font-bold text-[17px] text-slate-100 leading-tight"
                    >
                        Hi, {user.name}
                    </h2>
                    <p class="text-[13px] text-slate-400 mt-0.5">
                        {user.phone || "-"}
                    </p>
                </div>
            {:else}
                <div
                    class="size-14 rounded-full bg-slate-700 flex items-center justify-center text-lg font-bold text-slate-100"
                >
                    ?
                </div>
                <div>
                    <h2
                        class="font-bold text-[17px] text-slate-100 leading-tight"
                    >
                        Anda belum login
                    </h2>
                </div>
            {/if}
        </div>

        <!-- Menu Links -->
        <div class="w-full px-4 space-y-3">
            {#if user}
                <Link
                    href="/profile"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Ubah Profil</span
                    >
                </Link>
                <Link
                    href="/notifications"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div
                        class="w-5 flex justify-center text-slate-100 text-lg relative"
                    >
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1">Notifikasi</span
                    >
                    {#if unreadNotificationsCount > 0}
                        <div
                            class="bg-red-500 text-white flex items-center justify-center text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[20px] h-[20px]"
                        >
                            {unreadNotificationsCount > 99
                                ? "99+"
                                : unreadNotificationsCount}
                        </div>
                    {/if}
                </Link>
                <Link
                    href="/orders"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div
                        class="w-5 flex justify-center text-slate-100 text-lg relative"
                    >
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Riwayat Pesanan</span
                    >
                    {#if activeOrdersCount > 0}
                        <div
                            class="bg-[#FFD700] text-slate-900 flex items-center justify-center text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[20px] h-[20px]"
                        >
                            {activeOrdersCount > 99 ? "99+" : activeOrdersCount}
                        </div>
                    {/if}
                </Link>
                <Link
                    href="/food-requests"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-[#FFD700] text-lg">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Ingin Request Makanan Baru?</span
                    >
                </Link>
                <Link
                    href="/feedback"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Kritik dan Saran</span
                    >
                </Link>
                <a
                    href={buildWaLink(
                        $page.props.settings?.whatsapp,
                        "Halo Admin, saya ingin bertanya...",
                    )}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-green-500 transition-colors"
                >
                    <div class="w-5 flex justify-center text-green-500 text-lg">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Hubungi Admin</span
                    >
                </a>
                <Link
                    href="/terms-and-conditions"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Syarat dan Ketentuan</span
                    >
                </Link>
                <Link
                    href="/privacy-policy"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Kebijakan Privasi</span
                    >
                </Link>
                <!-- Logout -> Use inertia link as button with post method -->
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-red-500 transition-colors"
                >
                    <div class="w-5 flex justify-center text-red-500 text-lg">
                        <i class="fa-solid fa-power-off"></i>
                    </div>
                    <span class="text-red-500 text-sm flex-1 text-left"
                        >Keluar</span
                    >
                </Link>
            {:else}
                <Link
                    href="/login"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1">Masuk</span>
                </Link>
                <Link
                    href="/register"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1">Daftar</span>
                </Link>
                <a
                    href={buildWaLink(
                        $page.props.settings?.whatsapp,
                        "Halo Admin, saya ingin bertanya...",
                    )}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-green-500 transition-colors"
                >
                    <div class="w-5 flex justify-center text-green-500 text-lg">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Hubungi Admin</span
                    >
                </a>
                <Link
                    href="/terms-and-conditions"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Syarat dan Ketentuan</span
                    >
                </Link>
                <Link
                    href="/privacy-policy"
                    class="flex items-center gap-4 bg-slate-800 px-4 py-3.5 rounded-xl border border-slate-700 hover:border-[#FFD700] transition-colors"
                >
                    <div class="w-5 flex justify-center text-slate-100 text-lg">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <span class="text-slate-100 text-sm flex-1"
                        >Kebijakan Privasi</span
                    >
                </Link>
            {/if}
        </div>
    </main>
</div>
