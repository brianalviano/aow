<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    interface Customer {
        id: string;
        name: string;
        email: string;
    }

    const customer = $page.props.auth?.user as Customer | null;

    function logout() {
        router.post("/customer/logout");
    }
</script>

<svelte:head>
    <title>Dashboard | {siteName($page.props.settings)}</title>
</svelte:head>

<div class="min-h-screen bg-slate-100 dark:bg-neutral-900">
    <!-- Top bar -->
    <header
        class="flex items-center justify-between bg-white px-6 py-4 shadow-sm dark:bg-neutral-800"
    >
        <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
            Portal Pelanggan
        </h1>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600 dark:text-gray-300">
                {customer?.name ?? ""}
            </span>
            <button
                onclick={logout}
                class="rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40"
            >
                <i class="fa-solid fa-right-from-bracket mr-1"></i>
                Logout
            </button>
        </div>
    </header>

    <!-- Content -->
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="rounded-xl bg-white p-8 shadow-sm dark:bg-neutral-800">
            <div class="mb-2 flex items-center gap-3">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30"
                >
                    <i
                        class="fa-solid fa-house text-blue-600 dark:text-blue-400"
                    ></i>
                </div>
                <div>
                    <h2
                        class="text-xl font-bold text-gray-800 dark:text-gray-100"
                    >
                        Selamat datang, {customer?.name ?? ""}!
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {customer?.email ?? ""}
                    </p>
                </div>
            </div>
        </div>
    </main>
</div>
