<script lang="ts">
    import { useForm, Link } from "@inertiajs/svelte";

    // Initialize the form with empty strings
    const form = useForm({
        name: "",
        phone: "",
        address: "",
        note: "",
        latitude: null,
        longitude: null,
    });

    // Handle form submission
    const submit = () => {
        $form.post("/custom-address", {
            preserveScroll: true,
            onError: (errors: Record<string, string>) => {
                console.error("Validation errors:", errors);
            },
        });
    };
</script>

<svelte:head>
    <title>Gunakan Alamat Lain</title>
</svelte:head>

<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header
        class="bg-white border-b border-gray-100 p-4 sticky top-0 z-10 flex items-center justify-between"
    >
        <div class="flex items-center gap-3">
            <Link
                href="/"
                class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                aria-label="Kembali ke Beranda"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </Link>
            <div>
                <h1 class="font-bold text-gray-900 text-lg leading-tight">
                    Alamat Pengiriman
                </h1>
                <p class="text-xs text-gray-500">Isi detail alamat Anda</p>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 p-4 w-full max-w-lg mx-auto">
        <form
            on:submit|preventDefault={submit}
            class="space-y-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mt-4"
        >
            <!-- Name Input -->
            <div class="space-y-1.5">
                <label
                    for="name"
                    class="block text-sm font-semibold text-gray-700"
                >
                    Nama Penerima <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    bind:value={$form.name}
                    class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-[#CCFF33] focus:ring focus:ring-[#CCFF33] focus:ring-opacity-50 transition-colors"
                    placeholder="Contoh: Budi Susanto"
                    required
                />
                {#if $form.errors.name}
                    <p class="text-xs text-red-500 mt-1">{$form.errors.name}</p>
                {/if}
            </div>

            <!-- Phone Input -->
            <div class="space-y-1.5">
                <label
                    for="phone"
                    class="block text-sm font-semibold text-gray-700"
                >
                    Nomor Telepon/WhatsApp <span class="text-red-500">*</span>
                </label>
                <input
                    type="tel"
                    id="phone"
                    bind:value={$form.phone}
                    class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-[#CCFF33] focus:ring focus:ring-[#CCFF33] focus:ring-opacity-50 transition-colors"
                    placeholder="Contoh: 081234567890"
                    required
                />
                {#if $form.errors.phone}
                    <p class="text-xs text-red-500 mt-1">
                        {$form.errors.phone}
                    </p>
                {/if}
            </div>

            <!-- Address Input -->
            <div class="space-y-1.5">
                <label
                    for="address"
                    class="block text-sm font-semibold text-gray-700"
                >
                    Alamat Lengkap <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="address"
                    bind:value={$form.address}
                    rows="3"
                    class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-[#CCFF33] focus:ring focus:ring-[#CCFF33] focus:ring-opacity-50 transition-colors resize-none"
                    placeholder="Contoh: Jl. Sudirman No. 123, RT 01/RW 02, Patokan: Pagar Hitam"
                    required
                ></textarea>
                {#if $form.errors.address}
                    <p class="text-xs text-red-500 mt-1">
                        {$form.errors.address}
                    </p>
                {/if}
            </div>

            <!-- Note Input (Optional) -->
            <div class="space-y-1.5">
                <label
                    for="note"
                    class="block text-sm font-semibold text-gray-700"
                >
                    Catatan untuk Kurir <span
                        class="text-gray-400 font-normal text-xs"
                        >(Opsional)</span
                    >
                </label>
                <textarea
                    id="note"
                    bind:value={$form.note}
                    rows="2"
                    class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-[#CCFF33] focus:ring focus:ring-[#CCFF33] focus:ring-opacity-50 transition-colors resize-none"
                    placeholder="Contoh: Titip di pos satpam saja"
                ></textarea>
                {#if $form.errors.note}
                    <p class="text-xs text-red-500 mt-1">{$form.errors.note}</p>
                {/if}
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button
                    type="submit"
                    disabled={$form.processing}
                    class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-slate-900 bg-[#CCFF33] hover:bg-[#bdf33c] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#CCFF33] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {#if $form.processing}
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i> Memproses...
                    {:else}
                        Lanjut Pilih Menu <i
                            class="fa-solid fa-arrow-right ml-2"
                        ></i>
                    {/if}
                </button>
            </div>
        </form>
    </main>
</div>
