<script lang="ts">
    import { useForm } from "@inertiajs/svelte";
    import { router } from "@inertiajs/svelte";

    export let paymentMethods: any[] = [];
    export let customer: any = null;
    export let totalAmount: number = 0;

    const form = useForm({
        name: customer?.name || "",
        phone: customer?.phone || "",
        email: customer?.email || "",
        school_class: customer?.school_class || "",
        payment_method_id: "",
    });

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit("/checkout");
        }
    }

    function handleSubmit() {
        form.post("/payment");
    }
</script>

<svelte:head>
    <title>Pembayaran</title>
</svelte:head>

<div class="bg-white min-h-screen pb-32 font-sans overflow-x-hidden">
    <!-- Header -->
    <header class="flex items-center p-4 bg-white sticky top-0 z-30 shadow-sm">
        <button
            on:click={goBack}
            class="w-10 h-10 flex items-center justify-center text-gray-900 hover:bg-gray-50 rounded-full transition-colors"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </button>
        <h1 class="flex-1 text-center font-bold text-xl text-gray-900 mr-10">
            Pembayaran
        </h1>
    </header>

    <main class="space-y-8 mt-6">
        <!-- Order Information Section -->
        <section class="px-6 space-y-6">
            <h2 class="font-bold text-lg text-gray-900">Informasi Pesanan</h2>

            <div class="space-y-5">
                <!-- Nama Lengkap -->
                <div class="space-y-2">
                    <label
                        for="name"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        bind:value={$form.name}
                        placeholder="Nama Lengkap"
                        class="w-full px-4 py-3.5 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#CCFF33] focus:border-transparent outline-none transition-all placeholder:text-gray-400 text-gray-900"
                        class:border-red-500={$form.errors.name}
                    />
                    {#if $form.errors.name}
                        <p class="text-xs text-red-500 mt-1">
                            {$form.errors.name}
                        </p>
                    {/if}
                </div>

                <!-- Nomor Whatsapp -->
                <div class="space-y-2">
                    <label
                        for="phone"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Nomor Whatsapp <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="phone"
                        type="tel"
                        bind:value={$form.phone}
                        placeholder="Nomor Whatsapp"
                        class="w-full px-4 py-3.5 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#CCFF33] focus:border-transparent outline-none transition-all placeholder:text-gray-400 text-gray-900"
                        class:border-red-500={$form.errors.phone}
                    />
                    {#if $form.errors.phone}
                        <p class="text-xs text-red-500 mt-1">
                            {$form.errors.phone}
                        </p>
                    {/if}
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label
                        for="email"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        bind:value={$form.email}
                        placeholder="Email"
                        class="w-full px-4 py-3.5 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#CCFF33] focus:border-transparent outline-none transition-all placeholder:text-gray-400 text-gray-900"
                        class:border-red-500={$form.errors.email}
                    />
                    {#if $form.errors.email}
                        <p class="text-xs text-red-500 mt-1">
                            {$form.errors.email}
                        </p>
                    {/if}
                </div>

                <!-- Kelas Sekolah -->
                <div class="space-y-2">
                    <label
                        for="school_class"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Kelas Sekolah <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="school_class"
                        type="text"
                        bind:value={$form.school_class}
                        placeholder="Cth: XII IPA 1"
                        class="w-full px-4 py-3.5 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#CCFF33] focus:border-transparent outline-none transition-all placeholder:text-gray-400 text-gray-900"
                        class:border-red-500={$form.errors.school_class}
                    />
                    {#if $form.errors.school_class}
                        <p class="text-xs text-red-500 mt-1">
                            {$form.errors.school_class}
                        </p>
                    {/if}
                </div>
            </div>
        </section>

        <!-- Payment Methods Section -->
        <section class="space-y-4">
            <h2 class="font-bold text-lg text-gray-900 px-6">
                Metode Pembayaran
            </h2>

            <div class="border-t border-gray-100">
                {#each paymentMethods as method}
                    <button
                        type="button"
                        class="w-full px-6 py-5 flex items-center justify-between hover:bg-gray-50 transition-colors border-b border-gray-50"
                        on:click={() => ($form.payment_method_id = method.id)}
                    >
                        <div class="flex items-center gap-4">
                            {#if method.photo}
                                <img
                                    src={method.photo}
                                    alt={method.name}
                                    class="w-10 h-10 object-contain rounded-lg shadow-sm bg-white p-1"
                                />
                            {:else}
                                <div
                                    class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400"
                                >
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                            {/if}
                            <div class="text-left">
                                <p class="font-semibold text-gray-900">
                                    {method.name}
                                </p>
                                {#if method.description}
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {method.description}
                                    </p>
                                {/if}
                            </div>
                        </div>
                        <div
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                            class:border-[#CCFF33]={$form.payment_method_id ===
                                method.id}
                            class:border-gray-300={$form.payment_method_id !==
                                method.id}
                        >
                            {#if $form.payment_method_id === method.id}
                                <div
                                    class="w-3 h-3 rounded-full bg-[#CCFF33] shadow-sm animate-in zoom-in duration-200"
                                ></div>
                            {/if}
                        </div>
                    </button>
                {/each}
                {#if $form.errors.payment_method_id}
                    <p class="text-xs text-red-500 px-6 mt-2">
                        {$form.errors.payment_method_id}
                    </p>
                {/if}
            </div>
        </section>
    </main>

    <!-- Bottom Action Bar -->
    <div
        class="fixed bottom-0 left-0 right-0 p-6 bg-white shadow-[0_-10px_40px_rgba(0,0,0,0.08)] rounded-t-[2.5rem] z-40 border-t border-gray-50"
    >
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-6">
            <div>
                <p
                    class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1"
                >
                    Total Pembayaran
                </p>
                <p class="text-gray-900 font-extrabold text-2xl tracking-tight">
                    {formatRupiah(totalAmount)}
                </p>
            </div>
            <button
                on:click={handleSubmit}
                disabled={$form.processing}
                class="bg-[#CCFF33] text-gray-900 font-black py-4 px-10 rounded-2xl shadow-[0_8px_20px_rgba(204,255,51,0.3)] hover:shadow-[0_12px_25px_rgba(204,255,51,0.4)] hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0 disabled:shadow-none active:scale-95 text-base"
            >
                {$form.processing ? "Memproses..." : "Bayar"}
            </button>
        </div>
    </div>
</div>

<style>
    /* Custom styles for radio effect */
    .animate-in {
        animation: zoom-in 0.2s ease-out;
    }
    @keyframes zoom-in {
        from {
            transform: scale(0);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>
