<script lang="ts">
    import { useForm, Link } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { page } from "@inertiajs/svelte";

    export let requests: any[] = [];

    const form = useForm({
        name: "",
        notes: "",
    });

    function handleSubmit() {
        form.post("/food-requests", {
            onSuccess: () => {
                form.reset();
            },
        });
    }

    function getStatusBadgeClass(status: string) {
        switch (status) {
            case "pending":
                return "bg-yellow-100 text-yellow-700";
            case "approved":
                return "bg-green-100 text-green-700";
            case "rejected":
                return "bg-red-100 text-red-700";
            default:
                return "bg-gray-100 text-gray-700";
        }
    }

    function formatStatus(status: string) {
        switch (status) {
            case "pending":
                return "Menunggu";
            case "approved":
                return "Disetujui";
            case "rejected":
                return "Ditolak";
            default:
                return status;
        }
    }

    function formatDate(dateString: string) {
        return new Date(dateString).toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    }
</script>

<svelte:head>
    <title>Request Makanan Baru | {name($page.props.settings)}</title>
</svelte:head>

<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-white sticky top-0 z-10 border-b border-gray-100"
    >
        <Link
            href="/menu"
            class="text-gray-800 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1
            class="font-bold text-lg leading-tight text-gray-900 flex-1 text-center mr-8"
        >
            Request Menu
        </h1>
    </header>

    <main class="flex-1 w-full max-w-md mx-auto p-4 space-y-6">
        <!-- Form Section -->
        <section
            class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm"
        >
            <h2 class="font-bold text-gray-900 mb-1">Ingin menu apa?</h2>
            <p class="text-sm text-gray-500 mb-6">
                Bantu kami menambah menu baru yang Anda inginkan.
            </p>

            <form on:submit|preventDefault={handleSubmit} class="space-y-4">
                <div>
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700 mb-1.5"
                    >
                        Nama Makanan / Minuman <span class="text-red-500"
                            >*</span
                        >
                    </label>
                    <input
                        type="text"
                        id="name"
                        bind:value={$form.name}
                        placeholder="Contoh: Nasi Goreng Gila"
                        class="w-full px-4 py-3 rounded-xl border {$form.errors
                            .name
                            ? 'border-red-300'
                            : 'border-gray-200'} focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm"
                        required
                    />
                    {#if $form.errors.name}
                        <p class="mt-1 text-xs text-red-500">
                            {$form.errors.name}
                        </p>
                    {/if}
                </div>

                <div>
                    <label
                        for="notes"
                        class="block text-sm font-medium text-gray-700 mb-1.5"
                    >
                        Catatan Tambahan (Opsional)
                    </label>
                    <textarea
                        id="notes"
                        bind:value={$form.notes}
                        rows="3"
                        placeholder="Misal: Porsinya banyakin ya.."
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm resize-none"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    disabled={$form.processing}
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition-colors shadow-lg shadow-blue-200 disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2"
                >
                    {#if $form.processing}
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Mengirim...
                    {:else}
                        Kirim Request
                    {/if}
                </button>
            </form>
        </section>

        <!-- List Section -->
        <section class="space-y-4">
            <h2 class="font-bold text-gray-900 px-1">Riwayat Request Anda</h2>

            {#if requests.length === 0}
                <div
                    class="bg-white p-8 rounded-2xl border border-dashed border-gray-200 flex flex-col items-center justify-center text-center"
                >
                    <div
                        class="size-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 mb-3"
                    >
                        <i class="fa-solid fa-utensils text-xl"></i>
                    </div>
                    <p class="text-sm text-gray-500">Belum ada request menu.</p>
                </div>
            {:else}
                <div class="space-y-3">
                    {#each requests as req}
                        <div
                            class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col gap-2"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <h3
                                    class="font-bold text-gray-900 text-[15px] leading-tight flex-1"
                                >
                                    {req.name}
                                </h3>
                                <span
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {getStatusBadgeClass(
                                        req.status,
                                    )}"
                                >
                                    {formatStatus(req.status)}
                                </span>
                            </div>
                            {#if req.notes}
                                <p
                                    class="text-[13px] text-gray-600 line-clamp-2 italic"
                                >
                                    "{req.notes}"
                                </p>
                            {/if}
                            <div
                                class="flex items-center gap-1.5 text-[11px] text-gray-400 mt-1"
                            >
                                <i class="fa-solid fa-calendar-day"></i>
                                {formatDate(req.created_at)}
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </section>
    </main>
</div>
