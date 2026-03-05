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

<div>
    <section
        class="my-5 px-6 space-y-6 w-full {isPaid ||
        isCash ||
        isExpired ||
        (isManualTransfer && hasProof)
            ? 'flex flex-col items-center justify-center min-h-[70vh]'
            : ''}"
    >
        {#if isExpired}
            <div class="text-center space-y-3">
                <div
                    class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto text-4xl"
                >
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                    Waktu Habis
                </h2>
                <p class="text-gray-500 text-sm max-w-[280px] mx-auto">
                    Batas waktu pembayaran telah kedaluwarsa. Silakan buat
                    pesanan baru.
                </p>
            </div>
        {:else if isPaid || isCash}
            <div class="text-center space-y-3">
                <div
                    class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-4xl animate-bounce"
                >
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                    Pesanan Berhasil!
                </h2>
                <p class="text-gray-500 text-sm max-w-[280px] mx-auto">
                    Pesanan anda akan dikirim tanggal <strong
                        >{formatDateStr(order.delivery_date)}</strong
                    >
                    dan jam
                    <strong>{formatTimeStr(order.delivery_time)}</strong>.
                    {#if isCash}
                        <span
                            class="block mt-4 mb-1 text-xs font-bold text-gray-400 uppercase tracking-widest"
                            >Total yang harus disiapkan</span
                        >
                        <span
                            class="block text-3xl font-black text-gray-900 mb-2"
                            >{formatRupiah(order.total_amount)}</span
                        >
                        <span class="text-red-500 font-bold block"
                            >Harap siapkan uang pas ya kak!</span
                        >
                    {/if}
                </p>
            </div>
        {:else if isManualTransfer && hasProof}
            <div class="text-center space-y-3">
                <div
                    class="w-20 h-20 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto text-4xl animate-pulse"
                >
                    <i class="fa-solid fa-clock"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                    Menunggu Verifikasi
                </h2>
                <p class="text-gray-500 text-sm max-w-[280px] mx-auto">
                    Pesanan Anda sedang diproses. Silakan tunggu konfirmasi dari
                    admin.
                </p>
            </div>
        {:else}
            <div class="text-center space-y-3 pt-4">
                <div
                    class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto text-4xl"
                >
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                    Selesaikan Pembayaran
                </h2>
                <p class="text-gray-500 text-sm max-w-[280px] mx-auto">
                    Silakan selesaikan pembayaran sesuai instruksi di bawah ini.
                </p>
            </div>
        {/if}

        {#if !isPaid && !isCash && !(isManualTransfer && hasProof) && !isExpired}
            {#if midtransData}
                <div
                    class="bg-white rounded-[2.5rem] p-6 shadow-2xl shadow-gray-200/50 border border-gray-100 space-y-8"
                >
                    <!-- VA / Bill Section -->
                    {#if midtransData.type === "va"}
                        <div class="space-y-4 text-center">
                            <p
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Nomor Virtual Account
                            </p>
                            <div class="flex items-center justify-center gap-3">
                                <span
                                    class="text-3xl font-black text-gray-900 tracking-tighter"
                                    >{midtransData.number}</span
                                >
                                <button
                                    onclick={() =>
                                        copyToClipboard(midtransData.number)}
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
                            <a
                                href={midtransData.url}
                                download={`QRIS-${order.number}.png`}
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full py-3 bg-blue-50 text-[#0060B2] font-bold rounded-2xl hover:bg-blue-100 transition-colors flex items-center justify-center gap-2"
                            >
                                <i class="fa-solid fa-download"></i> Simpan QRIS
                            </a>
                        </div>
                    {/if}

                    <hr class="border-gray-100" />

                    <!-- Detail Section -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Total Tagihan</span>
                            <span class="font-black text-gray-900 text-lg"
                                >{formatRupiah(order.total_amount)}</span
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">ID Pesanan</span>
                            <span class="font-bold text-gray-900"
                                >{order.number}</span
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Batas Waktu</span>
                            <span class="font-bold text-red-500"
                                >{midtransData.expiry || "1x24 Jam"}</span
                            >
                        </div>
                    </div>

                    <div>
                        <button
                            onclick={() => showGuide(order.payment_method)}
                            class="w-full py-4 text-sm font-bold text-[#0060B2] bg-blue-50 rounded-2xl flex items-center justify-center gap-2 hover:bg-blue-100 transition-colors"
                        >
                            <i class="fa-solid fa-circle-question"></i>
                            Lihat Instruksi Pembayaran
                        </button>
                    </div>
                </div>
            {:else}
                <!-- Manual Payment State -->
                <div
                    class="bg-white rounded-[2.5rem] p-6 shadow-2xl shadow-gray-200/50 border border-gray-100 space-y-4"
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

                    <div
                        class="bg-blue-50 rounded-3xl p-6 text-center space-y-2 border border-blue-100"
                    >
                        <p
                            class="text-xs font-bold text-blue-400 uppercase tracking-widest"
                        >
                            Jumlah yang harus ditransfer
                        </p>
                        <p class="text-3xl font-black text-blue-900">
                            {formatRupiah(order.total_amount)}
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
                            <div class="flex items-center justify-center gap-3">
                                <span class="text-2xl font-black text-gray-900"
                                    >{order.payment_method.account_number}</span
                                >
                                <button
                                    onclick={() =>
                                        copyToClipboard(
                                            order.payment_method.account_number,
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

                    <div>
                        <button
                            onclick={() => showGuide(order.payment_method)}
                            class="w-full py-4 text-sm font-bold text-[#0060B2] bg-blue-50 rounded-2xl flex items-center justify-center gap-2 hover:bg-blue-100 transition-colors"
                        >
                            <i class="fa-solid fa-circle-question"></i>
                            Lihat Instruksi Pembayaran
                        </button>
                    </div>

                    <div class="space-y-3 pt-6 border-t border-gray-100">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase tracking-widest text-center"
                        >
                            Upload Bukti Pembayaran
                        </p>
                        <form onsubmit={submitProof} class="space-y-4">
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
                            <button
                                type="submit"
                                disabled={$form.processing || !$form.proof}
                                class="w-full py-4 bg-[#FFD700] text-gray-900 font-black rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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
            class="pt-2 w-70 flex items-center justify-center text-center mx-auto"
        >
            <button
                onclick={() => router.visit(backUrl)}
                class="w-full py-3 bg-white text-gray-900 border-2 border-gray-100 font-bold rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-gray-200 transition-all text-base"
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
    >
        {#snippet children()}
            <div
                class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar"
            >
                {#if activeGuide?.content}
                    {#each activeGuide.content as section}
                        <div class="space-y-3">
                            <h4
                                class="font-bold text-gray-900 border-l-4 border-[#FFD700] pl-3"
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
