<script lang="ts">
    import { router, page, useForm } from "@inertiajs/svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";

    interface Props {
        order: any;
        from?: string;
    }

    let { order, from }: Props = $props();

    let guideModalOpen = $state(false);
    let activeGuide = $state<any>(null);

    let form = useForm({
        proof: null as File | null,
    });

    function showGuide(method: any) {
        activeGuide = method.payment_guide;
        guideModalOpen = true;
    }

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function formatDateStr(dateStr: string) {
        if (!dateStr) return "";
        const options: Intl.DateTimeFormatOptions = {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        };
        return new Date(dateStr).toLocaleDateString("id-ID", options);
    }

    function formatTimeStr(timeStr: string) {
        if (!timeStr) return "";
        return timeStr.substring(0, 5);
    }

    function copyToClipboard(text: string) {
        navigator.clipboard.writeText(text);
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

    function submitProof(e: Event) {
        e.preventDefault();
        $form.post(`/payment/${order.id}/proof${from ? `?from=${from}` : ""}`, {
            preserveScroll: true,
        });
    }

    let currentTime = $state(Date.now());

    $effect(() => {
        const interval = setInterval(() => {
            currentTime = Date.now();
        }, 1000);
        return () => clearInterval(interval);
    });

    const midtransData = $derived(getMidtransData());
    const isPaid = $derived(
        ["paid", "settlement", "capture"].includes(order.payment_status),
    );
    const isExpiredStatus = $derived(
        ["expire", "cancel"].includes(order.payment_status),
    );
    const isExpired = $derived(
        isExpiredStatus ||
            (!isPaid &&
                midtransData?.expiry &&
                new Date(
                    midtransData.expiry.replace(" ", "T") + "+07:00",
                ).getTime() <= currentTime),
    );
    const isCash = $derived(order.payment_method?.category === "cash");
    const isManualTransfer = $derived(!isCash && !midtransData);
    const hasProof = $derived(!!order.payment_proof);

    const backUrl = $derived(from === "detail" ? `/orders/${order.id}` : "/");
    const backText = $derived(
        from === "detail" ? "Kembali ke Detail Pesanan" : "Kembali ke Beranda",
    );
</script>

<svelte:head>
    <title>Status Pesanan | {getSettingName($page.props.settings)}</title>
</svelte:head>

<div class="min-h-screen bg-slate-950 text-slate-100">
    <section
        class="py-8 px-6 space-y-8 w-full {isPaid ||
        isCash ||
        isExpired ||
        (isManualTransfer && hasProof)
            ? 'flex flex-col items-center justify-center min-h-[80vh]'
            : ''}"
    >
        {#if isExpired}
            <div class="text-center space-y-4">
                <div
                    class="w-24 h-24 bg-red-900/20 text-red-500 rounded-full flex items-center justify-center mx-auto text-5xl border border-red-500/20"
                >
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-100 tracking-tight">
                    Waktu Habis
                </h2>
                <p class="text-slate-400 text-sm max-w-[280px] mx-auto">
                    Batas waktu pembayaran telah kedaluwarsa. Silakan buat
                    pesanan baru.
                </p>
            </div>
        {:else if isPaid || isCash}
            <div class="text-center space-y-4">
                <div
                    class="w-24 h-24 bg-green-900/20 text-green-500 rounded-full flex items-center justify-center mx-auto text-5xl animate-bounce border border-green-500/20"
                >
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-100 tracking-tight">
                    Pesanan Berhasil!
                </h2>
                <div
                    class="text-slate-400 text-sm max-w-[280px] mx-auto space-y-4"
                >
                    <p>
                        Pesanan anda akan dikirim tanggal <strong
                            class="text-slate-100"
                            >{formatDateStr(order.delivery_date)}</strong
                        >
                        dan jam
                        <strong class="text-slate-100"
                            >{formatTimeStr(order.delivery_time)}</strong
                        >.
                    </p>
                    {#if isCash}
                        <div class="pt-4 border-t border-slate-800">
                            <span
                                class="block mb-1 text-xs font-bold text-slate-500 uppercase tracking-widest"
                                >Total yang harus disiapkan</span
                            >
                            <span
                                class="block text-4xl font-black text-[#FFD700] mb-2"
                                >{formatRupiah(order.total_amount)}</span
                            >
                            <span class="text-red-400 font-bold block"
                                >Harap siapkan uang pas ya kak!</span
                            >
                        </div>
                    {/if}
                </div>
            </div>
        {:else if isManualTransfer && hasProof}
            <div class="text-center space-y-4">
                <div
                    class="w-24 h-24 bg-yellow-900/20 text-[#FFD700] rounded-full flex items-center justify-center mx-auto text-5xl animate-pulse border border-[#FFD700]/20"
                >
                    <i class="fa-solid fa-clock"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-100 tracking-tight">
                    Menunggu Verifikasi
                </h2>
                <p class="text-slate-400 text-sm max-w-[280px] mx-auto">
                    Pesanan Anda sedang diproses. Silakan tunggu konfirmasi dari
                    admin.
                </p>
            </div>
        {:else}
            <div class="text-center space-y-4 pt-4">
                <div
                    class="w-24 h-24 bg-blue-900/20 text-blue-400 rounded-full flex items-center justify-center mx-auto text-5xl border border-blue-500/20"
                >
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-100 tracking-tight">
                    Selesaikan Pembayaran
                </h2>
                <p class="text-slate-400 text-sm max-w-[280px] mx-auto">
                    Silakan selesaikan pembayaran sesuai instruksi di bawah ini.
                </p>
            </div>
        {/if}

        {#if !isPaid && !isCash && !(isManualTransfer && hasProof) && !isExpired}
            {#if midtransData}
                <div
                    class="bg-slate-950 rounded-[2.5rem] p-8 shadow-2xl border border-slate-800 space-y-8"
                >
                    <!-- VA / Bill Section -->
                    {#if midtransData.type === "va"}
                        <div class="space-y-4 text-center">
                            <p
                                class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                            >
                                Nomor Virtual Account
                            </p>
                            <div class="flex items-center justify-center gap-3">
                                <span
                                    class="text-4xl font-black text-slate-100 tracking-tighter"
                                    >{midtransData.number}</span
                                >
                                <button
                                    onclick={() =>
                                        copyToClipboard(midtransData.number)}
                                    class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-[#FFD700] rounded-2xl transition-all flex items-center justify-center border border-slate-700"
                                    title="Salin"
                                    aria-label="Salin nomor VA"
                                >
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <div
                                class="inline-flex items-center gap-2 px-5 py-2 bg-slate-800 rounded-full border border-slate-700"
                            >
                                <span
                                    class="text-xs font-black text-[#FFD700] uppercase tracking-wider"
                                    >{midtransData.bank}</span
                                >
                            </div>
                        </div>
                    {:else if midtransData.type === "bill"}
                        <div class="space-y-8">
                            <div class="text-center space-y-3">
                                <p
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                                >
                                    Biller Code
                                </p>
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <span
                                        class="text-3xl font-black text-slate-100"
                                        >{midtransData.biller_code}</span
                                    >
                                    <button
                                        onclick={() =>
                                            copyToClipboard(
                                                midtransData.biller_code,
                                            )}
                                        class="text-slate-400 hover:text-[#FFD700] transition-colors"
                                        aria-label="Salin biller code"
                                        ><i class="fa-solid fa-copy text-xl"
                                        ></i></button
                                    >
                                </div>
                            </div>
                            <div class="text-center space-y-3">
                                <p
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                                >
                                    Bill Key
                                </p>
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <span
                                        class="text-3xl font-black text-slate-100"
                                        >{midtransData.bill_key}</span
                                    >
                                    <button
                                        onclick={() =>
                                            copyToClipboard(
                                                midtransData.bill_key,
                                            )}
                                        class="text-slate-400 hover:text-[#FFD700] transition-colors"
                                        aria-label="Salin bill key"
                                        ><i class="fa-solid fa-copy text-xl"
                                        ></i></button
                                    >
                                </div>
                            </div>
                            <div class="text-center">
                                <div
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-slate-800 rounded-full border border-slate-700"
                                >
                                    <span
                                        class="text-xs font-black text-[#FFD700] uppercase tracking-wider"
                                        >MANDIRI BILL</span
                                    >
                                </div>
                            </div>
                        </div>
                    {:else if midtransData.type === "qris"}
                        <div class="space-y-8 text-center">
                            <p
                                class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                            >
                                Scan QRIS
                            </p>
                            <div
                                class="bg-white p-6 rounded-4xl inline-block shadow-2xl border-4 border-[#FFD700]"
                            >
                                <img
                                    src={midtransData.url}
                                    alt="QRIS Code"
                                    class="w-64 h-64 object-contain mx-auto"
                                />
                            </div>
                            <p
                                class="text-xs text-slate-400 max-w-[200px] mx-auto"
                            >
                                Buka aplikasi e-wallet kamu dan scan kode di
                                atas.
                            </p>
                            <a
                                href={`/payment/${order.id}/qris-download`}
                                download={`QRIS-${order.number}.png`}
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full py-4 bg-slate-800 text-slate-100 font-bold rounded-2xl border border-slate-700 hover:bg-slate-700 hover:border-[#FFD700] transition-all flex items-center justify-center gap-2"
                            >
                                <i class="fa-solid fa-download"></i> Simpan QRIS
                            </a>
                        </div>
                    {/if}

                    <hr class="border-slate-800" />

                    <!-- Detail Section -->
                    <div class="space-y-6">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 text-sm"
                                >Total Tagihan</span
                            >
                            <span class="font-black text-[#FFD700] text-2xl"
                                >{formatRupiah(order.total_amount)}</span
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400">ID Pesanan</span>
                            <span class="font-bold text-slate-100"
                                >{order.number}</span
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400">Batas Waktu</span>
                            <span class="font-black text-red-500"
                                >{midtransData.expiry || "1x24 Jam"}</span
                            >
                        </div>
                    </div>

                    <div>
                        <button
                            onclick={() => showGuide(order.payment_method)}
                            class="w-full py-4 text-sm font-bold text-slate-100 bg-slate-800 border border-slate-700 rounded-2xl flex items-center justify-center gap-2 hover:bg-slate-700 hover:border-[#FFD700] transition-all"
                        >
                            <i
                                class="fa-solid fa-circle-question text-[#FFD700]"
                            ></i>
                            Lihat Instruksi Pembayaran
                        </button>
                    </div>
                </div>
            {:else}
                <!-- Manual Payment State -->
                <div
                    class="bg-slate-950 rounded-[2.5rem] p-8 shadow-2xl border border-slate-800 space-y-8"
                >
                    <div class="text-center space-y-3">
                        <p
                            class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                        >
                            Metode Pembayaran
                        </p>
                        <p class="text-2xl font-black text-slate-100">
                            {order.payment_method?.name}
                        </p>
                    </div>

                    <div
                        class="bg-blue-900/20 rounded-3xl p-6 text-center space-y-3 border border-blue-500/20"
                    >
                        <p
                            class="text-xs font-bold text-blue-400 uppercase tracking-widest"
                        >
                            Jumlah yang harus ditransfer
                        </p>
                        <p class="text-4xl font-black text-slate-100">
                            {formatRupiah(order.total_amount)}
                        </p>
                    </div>

                    {#if order.payment_method?.account_number}
                        <div
                            class="bg-slate-800 rounded-3xl p-6 text-center space-y-4 border border-slate-700"
                        >
                            <p
                                class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                            >
                                Nomor Rekening
                            </p>
                            <div class="flex items-center justify-center gap-3">
                                <span class="text-3xl font-black text-slate-100"
                                    >{order.payment_method.account_number}</span
                                >
                                <button
                                    onclick={() =>
                                        copyToClipboard(
                                            order.payment_method.account_number,
                                        )}
                                    class="text-slate-400 hover:text-[#FFD700] transition-colors"
                                    aria-label="Salin nomor rekening"
                                >
                                    <i class="fa-solid fa-copy text-xl"></i>
                                </button>
                            </div>
                            <p class="text-sm font-bold text-slate-300">
                                a/n {order.payment_method.account_name}
                            </p>
                        </div>
                    {/if}

                    <div>
                        <button
                            onclick={() => showGuide(order.payment_method)}
                            class="w-full py-4 text-sm font-bold text-slate-100 bg-slate-800 border border-slate-700 rounded-2xl flex items-center justify-center gap-2 hover:bg-slate-700 hover:border-[#FFD700] transition-all"
                        >
                            <i
                                class="fa-solid fa-circle-question text-[#FFD700]"
                            ></i>
                            Lihat Instruksi Pembayaran
                        </button>
                    </div>

                    <div class="space-y-4 pt-8 border-t border-slate-800">
                        <p
                            class="text-xs font-bold text-slate-500 uppercase tracking-widest text-center"
                        >
                            Upload Bukti Pembayaran
                        </p>
                        <form onsubmit={submitProof} class="space-y-6">
                            <div
                                class="bg-slate-950 rounded-2xl p-4 border border-slate-800"
                            >
                                <FileUpload
                                    id="payment-proof"
                                    name="proof"
                                    accept="image/*"
                                    required={true}
                                    variant="box"
                                    uploadText="Pilih atau seret gambar ke sini"
                                    uploadSubtext="Format: JPG, PNG. Maks: 10MB"
                                    bind:value={$form.proof}
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={$form.processing || !$form.proof}
                                class="w-full py-5 bg-[#FFD700] text-slate-900 font-black text-lg rounded-2xl shadow-xl shadow-[#FFD700]/20 hover:shadow-2xl hover:shadow-[#FFD700]/30 hover:-translate-y-1 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                            >
                                {#if $form.processing}
                                    <i class="fa-solid fa-spinner fa-spin mr-2"
                                    ></i> Mengunggah...
                                {:else}
                                    Konfirmasi Pembayaran
                                {/if}
                            </button>
                        </form>
                    </div>
                </div>
            {/if}
        {/if}

        <div
            class="pt-6 w-full flex items-center justify-center text-center mx-auto"
        >
            <button
                onclick={() => router.visit(backUrl)}
                class="w-full py-4 bg-slate-800 text-slate-100 border border-slate-700 font-bold rounded-2xl shadow-lg hover:bg-slate-700 hover:border-[#FFD700] hover:-translate-y-1 transition-all text-base"
            >
                {backText}
            </button>
        </div>
    </section>

    <!-- Guide Dialog -->
    <Dialog
        bind:isOpen={guideModalOpen}
        title={activeGuide?.name || "Instruksi Pembayaran"}
        message=""
        showCancel={false}
        confirmText="Dimengerti"
        type="info"
    >
        {#snippet children()}
            <div
                class="space-y-8 max-h-[60vh] overflow-y-auto pr-4 custom-scrollbar"
            >
                {#if activeGuide?.content}
                    {#each activeGuide.content as section}
                        <div class="space-y-4">
                            <h4
                                class="font-black text-slate-100 border-l-4 border-[#FFD700] pl-4 text-lg"
                            >
                                {section.title}
                            </h4>
                            <ul class="space-y-3">
                                {#each section.items as item, index}
                                    <li
                                        class="flex gap-4 text-sm text-slate-400 leading-relaxed"
                                    >
                                        <span
                                            class="shrink-0 w-6 h-6 bg-slate-800 border border-slate-700 rounded-full flex items-center justify-center text-[10px] font-black text-[#FFD700]"
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
                    <div class="py-10 text-center">
                        <i
                            class="fa-solid fa-info-circle text-4xl text-slate-700 mb-4 block"
                        ></i>
                        <p class="text-sm text-slate-500 italic">
                            Belum ada instruksi untuk metode ini.
                        </p>
                    </div>
                {/if}
            </div>
        {/snippet}
    </Dialog>
</div>

<style>
    .animate-bounce {
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-20px);
        }
        60% {
            transform: translateY(-10px);
        }
    }
</style>
