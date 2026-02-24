<script lang="ts">
    import { useForm } from "@inertiajs/svelte";
    import { router } from "@inertiajs/svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { untrack } from "svelte";

    interface Props {
        paymentMethods?: Record<string, any[]>;
        customer?: any;
        totalAmount?: number;
        order?: any;
    }

    let {
        paymentMethods = {},
        customer = null,
        totalAmount = 0,
        order = null,
    }: Props = $props();

    const form = useForm({
        name: untrack(() => customer?.name || ""),
        phone: untrack(() => customer?.phone || ""),
        email: untrack(() => customer?.email || ""),
        school_class: untrack(() => customer?.school_class || ""),
        payment_method_id: "",
    });

    let guideModalOpen = $state(false);
    let activeGuide = $state<any>(null);

    function showGuide(method: any) {
        activeGuide = method.payment_guide;
        guideModalOpen = true;
    }

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
        $form.post("/payment");
    }

    function copyToClipboard(text: string) {
        navigator.clipboard.writeText(text);
        // Show a temporary toast or something?
        // For now just alert or assume success
        alert("Berhasil disalin!");
    }

    function getMidtransData() {
        if (!order?.payment_details) return null;
        const details = order.payment_details;

        // Handle VA
        if (details.va_numbers?.[0]) {
            return {
                type: "va",
                bank: details.va_numbers[0].bank,
                number: details.va_numbers[0].va_number,
                expiry: details.expiry_time,
            };
        }

        // Handle Permata VA
        if (details.permata_va_number) {
            return {
                type: "va",
                bank: "permata",
                number: details.permata_va_number,
                expiry: details.expiry_time,
            };
        }

        // Handle Mandiri (E-channel)
        if (details.bill_key) {
            return {
                type: "bill",
                bank: "mandiri",
                bill_key: details.bill_key,
                biller_code: details.biller_code,
                expiry: details.expiry_time,
            };
        }

        // Handle QRIS/GoPay
        if (details.actions) {
            const qrisAction = details.actions.find(
                (a: any) => a.name === "generate-qr-code",
            );
            if (qrisAction) {
                return {
                    type: "qris",
                    url: qrisAction.url,
                    expiry: details.expiry_time,
                };
            }
        }

        return null;
    }

    const midtransData = $derived(getMidtransData());
</script>

<svelte:head>
    <title>Pembayaran</title>
</svelte:head>

<div>
    <!-- Header -->
    <header class="flex items-center p-4 bg-white sticky top-0 z-30 shadow-sm">
        <button
            onclick={goBack}
            class="w-10 h-10 flex items-center justify-center text-gray-900 hover:bg-gray-50 rounded-full transition-colors"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </button>
        <h1 class="flex-1 text-center font-bold text-xl text-gray-900 mr-10">
            Pembayaran
        </h1>
    </header>

    <main class="space-y-8 mt-6 mb-30">
        {#if order}
            <!-- Success / Instruction State -->
            <section class="px-6 space-y-6">
                <div class="text-center space-y-3 py-6">
                    <div
                        class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-4xl animate-bounce"
                    >
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2
                        class="text-2xl font-black text-gray-900 tracking-tight"
                    >
                        Pesanan Berhasil!
                    </h2>
                    <p class="text-gray-500 text-sm max-w-[280px] mx-auto">
                        Silakan selesaikan pembayaran sesuai instruksi di bawah
                        ini.
                    </p>
                </div>

                {#if midtransData}
                    <div
                        class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-gray-200/50 border border-gray-100 space-y-8"
                    >
                        <!-- VA / Bill Section -->
                        {#if midtransData.type === "va"}
                            <div class="space-y-4 text-center">
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                >
                                    Nomor Virtual Account
                                </p>
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <span
                                        class="text-3xl font-black text-gray-900 tracking-tighter"
                                        >{midtransData.number}</span
                                    >
                                    <button
                                        onclick={() =>
                                            copyToClipboard(
                                                midtransData.number,
                                            )}
                                        class="w-10 h-10 bg-gray-50 hover:bg-gray-100 text-gray-400 hover:text-gray-900 rounded-2xl transition-all flex items-center justify-center"
                                        title="Salin"
                                        aria-label="Salin nomor VA"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                <div
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500 uppercase"
                                        >{midtransData.bank}</span
                                    >
                                </div>
                            </div>
                        {:else if midtransData.type === "bill"}
                            <div class="space-y-6">
                                <div class="text-center space-y-2">
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                    >
                                        Biller Code
                                    </p>
                                    <div
                                        class="flex items-center justify-center gap-3"
                                    >
                                        <span
                                            class="text-2xl font-black text-gray-900"
                                            >{midtransData.biller_code}</span
                                        >
                                        <button
                                            onclick={() =>
                                                copyToClipboard(
                                                    midtransData.biller_code,
                                                )}
                                            class="text-gray-400 hover:text-gray-900"
                                            aria-label="Salin biller code"
                                            ><i class="fa-solid fa-copy"
                                            ></i></button
                                        >
                                    </div>
                                </div>
                                <div class="text-center space-y-2">
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                    >
                                        Bill Key
                                    </p>
                                    <div
                                        class="flex items-center justify-center gap-3"
                                    >
                                        <span
                                            class="text-2xl font-black text-gray-900"
                                            >{midtransData.bill_key}</span
                                        >
                                        <button
                                            onclick={() =>
                                                copyToClipboard(
                                                    midtransData.bill_key,
                                                )}
                                            class="text-gray-400 hover:text-gray-900"
                                            aria-label="Salin bill key"
                                            ><i class="fa-solid fa-copy"
                                            ></i></button
                                        >
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full"
                                    >
                                        <span
                                            class="text-xs font-bold text-gray-500 uppercase"
                                            >MANDIRI BILL</span
                                        >
                                    </div>
                                </div>
                            </div>
                        {:else if midtransData.type === "qris"}
                            <div class="space-y-6 text-center">
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                >
                                    Scan QRIS
                                </p>
                                <div
                                    class="bg-white p-4 rounded-3xl inline-block shadow-lg border border-gray-100"
                                >
                                    <img
                                        src={midtransData.url}
                                        alt="QRIS Code"
                                        class="w-64 h-64 object-contain mx-auto"
                                    />
                                </div>
                                <p class="text-xs text-gray-500">
                                    Buka aplikasi e-wallet kamu dan scan kode di
                                    atas.
                                </p>
                            </div>
                        {/if}

                        <hr class="border-gray-100" />

                        <!-- Detail Section -->
                        <div class="space-y-4">
                            <div
                                class="flex justify-between items-center text-sm"
                            >
                                <span class="text-gray-500">Total Tagihan</span>
                                <span class="font-black text-gray-900 text-lg"
                                    >{formatRupiah(order.total_amount)}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-sm"
                            >
                                <span class="text-gray-500">ID Pesanan</span>
                                <span class="font-bold text-gray-900"
                                    >{order.number}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-sm"
                            >
                                <span class="text-gray-500">Batas Waktu</span>
                                <span class="font-bold text-red-500"
                                    >{midtransData.expiry || "1x24 Jam"}</span
                                >
                            </div>
                        </div>
                    </div>
                {:else}
                    <!-- Manual Payment State -->
                    <div
                        class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-gray-200/50 border border-gray-100 space-y-6"
                    >
                        <div class="text-center space-y-2">
                            <p
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Metode Pembayaran
                            </p>
                            <p class="text-lg font-black text-gray-900">
                                {order.payment_method?.name}
                            </p>
                        </div>

                        {#if order.payment_method?.account_number}
                            <div
                                class="bg-gray-50 rounded-3xl p-6 text-center space-y-3"
                            >
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase"
                                >
                                    Nomor Rekening
                                </p>
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <span
                                        class="text-2xl font-black text-gray-900"
                                        >{order.payment_method
                                            .account_number}</span
                                    >
                                    <button
                                        onclick={() =>
                                            copyToClipboard(
                                                order.payment_method
                                                    .account_number,
                                            )}
                                        class="text-gray-400 hover:text-gray-900"
                                        aria-label="Salin nomor rekening"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                <p class="text-sm font-bold text-gray-600">
                                    a/n {order.payment_method.account_name}
                                </p>
                            </div>
                        {/if}

                        <div class="pt-4">
                            <button
                                onclick={() => showGuide(order.payment_method)}
                                class="w-full py-4 text-sm font-bold text-[#0060B2] bg-blue-50 rounded-2xl flex items-center justify-center gap-2 hover:bg-blue-100 transition-colors"
                            >
                                <i class="fa-solid fa-circle-question"></i>
                                Lihat Instruksi Pembayaran
                            </button>
                        </div>
                    </div>
                {/if}

                <div class="pt-6">
                    <button
                        onclick={() => router.visit("/")}
                        class="w-full py-5 bg-[#CCFF33] text-gray-900 font-black rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all text-base"
                    >
                        Selesai, Kembali ke Home
                    </button>
                </div>
            </section>
        {:else}
            <!-- Form State (Existing) -->
            <!-- Order Information Section -->
            <section class="px-6 space-y-4">
                <h2 class="font-bold text-lg text-gray-900">
                    Informasi Pesanan
                </h2>

                <div class="space-y-5">
                    <!-- Nama Lengkap -->
                    <TextInput
                        id="name"
                        name="name"
                        label="Nama Lengkap"
                        bind:value={$form.name}
                        placeholder="Nama Lengkap"
                        required
                        error={$form.errors.name}
                        class="rounded-2xl! focus:ring-[#CCFF33]!"
                    />

                    <!-- Nomor Whatsapp -->
                    <TextInput
                        id="phone"
                        name="phone"
                        label="Nomor Whatsapp"
                        type="tel"
                        bind:value={$form.phone}
                        placeholder="Nomor Whatsapp"
                        required
                        error={$form.errors.phone}
                        class="rounded-2xl! focus:ring-[#CCFF33]!"
                    />

                    <!-- Email -->
                    <TextInput
                        id="email"
                        name="email"
                        label="Email"
                        type="email"
                        bind:value={$form.email}
                        placeholder="Email"
                        required
                        error={$form.errors.email}
                        class="rounded-2xl! focus:ring-[#CCFF33]!"
                    />

                    <!-- Kelas Sekolah -->
                    <TextInput
                        id="school_class"
                        name="school_class"
                        label="Kelas Sekolah"
                        bind:value={$form.school_class}
                        placeholder="Cth: XII IPA 1"
                        required
                        error={$form.errors.school_class}
                        class="rounded-2xl! focus:ring-[#CCFF33]!"
                    />
                </div>
            </section>

            <!-- Payment Methods Section -->
            <section class="space-y-4">
                <h2 class="font-bold text-lg text-gray-900 px-6">
                    Metode Pembayaran
                </h2>

                <div class="border-t border-gray-100">
                    {#each Object.entries(paymentMethods) as [category, methods]}
                        <div
                            class="bg-gray-50/50 px-6 py-2 border-b border-gray-100"
                        >
                            <span
                                class="text-xs font-bold text-gray-500 uppercase tracking-wider"
                            >
                                {category}
                            </span>
                        </div>
                        {#each methods as method}
                            <div
                                class="w-full px-6 py-5 flex items-center justify-between hover:bg-gray-50 transition-colors border-b border-gray-50 bg-white cursor-pointer"
                                onclick={() =>
                                    ($form.payment_method_id = method.id)}
                                role="button"
                                tabindex="0"
                                onkeydown={(e) =>
                                    e.key === "Enter" &&
                                    ($form.payment_method_id = method.id)}
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
                                            <p
                                                class="text-xs text-gray-500 mt-0.5"
                                            >
                                                {method.description}
                                            </p>
                                        {/if}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    {#if method.payment_guide}
                                        <button
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-bold text-[#0060B2] bg-blue-50 hover:bg-blue-100 rounded-full transition-colors flex items-center gap-1 z-10"
                                            onclick={(e) => {
                                                e.stopPropagation();
                                                showGuide(method);
                                            }}
                                        >
                                            <i
                                                class="fa-solid fa-circle-question"
                                            ></i>
                                            Instruksi
                                        </button>
                                    {/if}
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
                                </div>
                            </div>
                        {/each}
                    {/each}
                    {#if $form.errors.payment_method_id}
                        <p class="text-xs text-red-500 px-6 mt-2">
                            {$form.errors.payment_method_id}
                        </p>
                    {/if}
                </div>
            </section>
        {/if}
    </main>

    <!-- Bottom Action Bar -->
    {#if !order}
        <div
            class="fixed bottom-0 left-0 right-0 p-6 bg-white shadow-[0_-10px_40px_rgba(0,0,0,0.08)] rounded-t-[2.5rem] z-40 border-t border-gray-50"
        >
            <div
                class="max-w-7xl mx-auto flex items-center justify-between gap-6"
            >
                <div>
                    <p
                        class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1"
                    >
                        Total Pembayaran
                    </p>
                    <p
                        class="text-gray-900 font-extrabold text-2xl tracking-tight"
                    >
                        {formatRupiah(totalAmount)}
                    </p>
                </div>
                <button
                    onclick={handleSubmit}
                    disabled={$form.processing}
                    class="bg-[#CCFF33] text-gray-900 font-black py-4 px-10 rounded-2xl shadow-[0_8px_20px_rgba(204,255,51,0.3)] hover:shadow-[0_12px_25px_rgba(204,255,51,0.4)] hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0 disabled:shadow-none active:scale-95 text-base"
                >
                    {$form.processing ? "Memproses..." : "Bayar"}
                </button>
            </div>
        </div>
    {/if}

    <!-- Guide Dialog -->
    <Dialog
        bind:isOpen={guideModalOpen}
        title={activeGuide?.name || "Instruksi Pembayaran"}
        message=""
        showCancel={false}
        confirmText="Dimengerti"
    >
        {#snippet children()}
            <div
                class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar"
            >
                {#if activeGuide?.content}
                    {#each activeGuide.content as section}
                        <div class="space-y-3">
                            <h4
                                class="font-bold text-gray-900 border-l-4 border-[#CCFF33] pl-3"
                            >
                                {section.title}
                            </h4>
                            <ul class="space-y-2">
                                {#each section.items as item, index}
                                    <li
                                        class="flex gap-3 text-sm text-gray-600"
                                    >
                                        <span
                                            class="shrink-0 w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-500"
                                        >
                                            {index + 1}
                                        </span>
                                        <span>{item}</span>
                                    </li>
                                {/each}
                            </ul>
                        </div>
                    {/each}
                {:else}
                    <p class="text-sm text-gray-500 italic">
                        Belum ada instruksi untuk metode ini.
                    </p>
                {/if}
            </div>
        {/snippet}
    </Dialog>
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
