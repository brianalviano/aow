<script lang="ts">
    import { page, Link } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    // Auth object holds user information
    $: auth = $page.props.auth;
    $: user = auth.user;

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
    <title>Menu - {name($page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-white sticky top-0 z-10 border-b border-gray-100"
    >
        <Link
            href="/"
            class="text-gray-800 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-gray-900">Menu</h1>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col items-center">
        <!-- User Profile Area -->
        <div class="w-full flex items-center p-6 gap-4 mb-4">
            {#if user}
                <div
                    class="size-14 rounded-full bg-gray-200 flex items-center justify-center text-lg font-bold text-gray-800"
                >
                    {getInitials(user.name)}
                </div>
                <div>
                    <h2
                        class="font-bold text-[17px] text-gray-900 leading-tight"
                    >
                        Hi, {user.name}
                    </h2>
                    <p class="text-[13px] text-gray-600 mt-0.5">
                        {user.phone || "-"}
                    </p>
                </div>
            {:else}
                <div
                    class="size-14 rounded-full bg-gray-200 flex items-center justify-center text-lg font-bold text-gray-800"
                >
                    ?
                </div>
                <div>
                    <h2
                        class="font-bold text-[17px] text-gray-900 leading-tight"
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
                    href="#"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1">Ubah Profil</span
                    >
                </Link>
                <Link
                    href="#"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1"
                        >Riwayat Pesanan</span
                    >
                </Link>
                <Link
                    href="#"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1"
                        >Syarat dan Ketentuan</span
                    >
                </Link>
                <Link
                    href="#"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1"
                        >Kebijakan Privasi</span
                    >
                </Link>
                <!-- Logout -> Use inertia link as button with post method -->
                <Link
                    href="/admin/logout"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-red-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-red-600 text-lg">
                        <i class="fa-solid fa-power-off"></i>
                    </div>
                    <span class="text-red-600 text-sm flex-1 text-left"
                        >Keluar</span
                    >
                </Link>
            {:else}
                <Link
                    href="/login"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1">Masuk</span>
                </Link>
                <Link
                    href="/register"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1">Daftar</span>
                </Link>
                <Link
                    href="#"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1"
                        >Syarat dan Ketentuan</span
                    >
                </Link>
                <Link
                    href="#"
                    class="flex items-center gap-4 bg-white px-4 py-3.5 rounded-xl border border-gray-200 hover:border-blue-300 transition-colors"
                >
                    <div class="w-5 flex justify-center text-gray-900 text-lg">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <span class="text-gray-900 text-sm flex-1"
                        >Kebijakan Privasi</span
                    >
                </Link>
            {/if}
        </div>
    </main>
</div>
