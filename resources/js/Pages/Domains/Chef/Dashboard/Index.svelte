<script lang="ts">
    import { page, Link, useForm } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";

    const form = useForm({});

    function handleLogout(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/chef/logout");
    }
</script>

<svelte:head>
    <title>Dashboard Chef | {appName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen">
    <header class="bg-white border-b border-gray-100 p-4 sticky top-0 z-10">
        <div class="flex justify-between items-center">
            <h1 class="text-lg font-bold text-gray-900">Chef Portal</h1>
            <form onsubmit={handleLogout}>
                <Button
                    type="submit"
                    variant="danger"
                    size="sm"
                    icon="fa-solid fa-right-from-bracket"
                >
                    Keluar
                </Button>
            </form>
        </div>
    </header>

    <main class="flex-1 p-4">
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <h2 class="text-lg font-semibold text-blue-800">
                Halo, {$page.props.auth?.user?.name || "Chef"}!
            </h2>
            <p class="text-sm text-blue-700 mt-1">
                Selamat datang di portal Anda.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-2">Ringkasan Pesanan</h3>
            <p class="text-sm text-gray-500">
                Data pesanan dan pengelolaan produk akan segera muncul di sini.
            </p>
        </div>
    </main>
</div>
