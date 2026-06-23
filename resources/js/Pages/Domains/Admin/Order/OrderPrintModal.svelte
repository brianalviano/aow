<script lang="ts">
    import { onMount } from "svelte";
    import { page } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { bluetoothPrinterManager } from "@/Lib/Admin/Utils/bluetoothPrinter";
    import { toastStore } from "@/Lib/Admin/Stores/toast";

    interface Props {
        open: boolean;
        order: any;
        onClose: () => void;
    }

    let { open, order, onClose }: Props = $props();

    const companySettings = $derived(($page.props.settings as any) || {});

    // State Variables
    let printMethod = $state<'bluetooth' | 'pdf'>('pdf');
    let paperWidth = $state<'58mm' | '80mm'>('58mm');
    let receiptType = $state<'all' | 'checker' | 'invoice'>('all');
    
    let isBluetoothSupported = $state(false);
    let isConnected = $state(false);
    let printerName = $state("");
    let isConnecting = $state(false);
    let isPrinting = $state(false);

    function updatePrinterState() {
        const s = bluetoothPrinterManager.getState();
        isConnected = s.isConnected;
        printerName = s.name;
    }

    onMount(() => {
        isBluetoothSupported = typeof (navigator as any).bluetooth !== "undefined";

        // Load default choices from localStorage
        const savedMethod = localStorage.getItem("pos_printer_method");
        if (savedMethod === "bluetooth" || savedMethod === "pdf") {
            printMethod = savedMethod;
        }

        const savedWidth = localStorage.getItem("pos_printer_size");
        if (savedWidth === "58mm" || savedWidth === "80mm") {
            paperWidth = savedWidth;
        }

        updatePrinterState();

        // If Bluetooth was active previously, try to auto-connect
        if (printMethod === "bluetooth" && isBluetoothSupported) {
            isConnecting = true;
            bluetoothPrinterManager.autoConnectLast()
                .then(() => {
                    updatePrinterState();
                })
                .finally(() => {
                    isConnecting = false;
                });
        }
    });

    // Save print method preference
    $effect(() => {
        localStorage.setItem("pos_printer_method", printMethod);
    });

    // Save paper size preference
    $effect(() => {
        localStorage.setItem("pos_printer_size", paperWidth);
    });

    async function handleBluetoothConnect() {
        if (isConnecting) return;
        isConnecting = true;
        try {
            const success = await bluetoothPrinterManager.requestAndConnect();
            updatePrinterState();
            if (success) {
                toastStore.success("Koneksi Berhasil", `Terhubung ke printer: ${printerName}`);
            }
        } catch (e: any) {
            console.error(e);
            toastStore.error("Koneksi Gagal", e.message || "Gagal menghubungkan printer Bluetooth.");
        } finally {
            isConnecting = false;
        }
    }

    async function handleBluetoothDisconnect() {
        await bluetoothPrinterManager.disconnect();
        updatePrinterState();
        toastStore.info("Printer Terputus", "Koneksi printer Bluetooth telah diputuskan.");
    }

    async function handleTestPrint() {
        if (!isConnected) {
            toastStore.error("Kesalahan", "Hubungkan printer terlebih dahulu sebelum melakukan test print.");
            return;
        }
        isPrinting = true;
        try {
            await bluetoothPrinterManager.printTestReceipt(paperWidth);
            toastStore.success("Test Print Terkirim", "Receipt test berhasil dikirim ke printer.");
        } catch (e: any) {
            toastStore.error("Gagal Cetak", e.message || "Terjadi kesalahan saat mencetak.");
        } finally {
            isPrinting = false;
        }
    }

    async function handlePrintNow() {
        if (printMethod === "bluetooth") {
            if (!isConnected) {
                toastStore.error("Printer Belum Terhubung", "Silakan hubungkan printer Bluetooth Anda terlebih dahulu.");
                return;
            }
            isPrinting = true;
            try {
                await bluetoothPrinterManager.printOrder(order, companySettings, receiptType, paperWidth);
                toastStore.success("Cetak Berhasil", `Struk pesanan #${order.number} telah dikirim ke printer.`);
                onClose();
            } catch (e: any) {
                console.error(e);
                toastStore.error("Gagal Cetak", e.message || "Gagal mengirim data ke printer Bluetooth.");
            } finally {
                isPrinting = false;
            }
        } else {
            // PDF fallback/standard browser print
            const url = `/admin/orders/${order.id}/print?type=${receiptType}`;
            window.open(url, "_blank");
            onClose();
        }
    }
</script>

{#if open}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="print-modal-title"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-150 dark:border-gray-700 pb-3 mb-4">
                <h3
                    id="print-modal-title"
                    class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
                >
                    <i class="fa-solid fa-print text-indigo-600 dark:text-indigo-400"></i>
                    Cetak Struk Pesanan
                </h3>
                <button
                    onclick={onClose}
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    aria-label="Tutup"
                >
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-4">
                <!-- Info Struk -->
                <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-3 text-sm text-gray-600 dark:text-gray-400 border border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between">
                        <span>No. Pesanan:</span>
                        <span class="font-bold text-gray-900 dark:text-white">#{order.number}</span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span>Pelanggan:</span>
                        <span class="text-gray-900 dark:text-white">{order.customer?.name || '-'}</span>
                    </div>
                </div>

                <!-- Metode Cetak -->
                <div>
                    <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Metode Cetak / Tujuan
                    </span>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onclick={() => (printMethod = 'bluetooth')}
                            class="flex flex-col items-center justify-center p-3 rounded-lg border text-sm font-medium transition-all gap-1.5
                                {printMethod === 'bluetooth' 
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-600 dark:border-indigo-400 dark:bg-indigo-950/20 dark:text-indigo-400 ring-1 ring-indigo-500' 
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50'}"
                        >
                            <i class="fa-solid fa-bluetooth text-lg"></i>
                            Printer Bluetooth
                        </button>
                        <button
                            type="button"
                            onclick={() => (printMethod = 'pdf')}
                            class="flex flex-col items-center justify-center p-3 rounded-lg border text-sm font-medium transition-all gap-1.5
                                {printMethod === 'pdf' 
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-600 dark:border-indigo-400 dark:bg-indigo-950/20 dark:text-indigo-400 ring-1 ring-indigo-500' 
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50'}"
                        >
                            <i class="fa-solid fa-file-pdf text-lg"></i>
                            Cetak PDF / Browser
                        </button>
                    </div>
                </div>

                <!-- Section Details for Bluetooth -->
                {#if printMethod === 'bluetooth'}
                    <div class="p-3.5 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3 bg-gray-50/50 dark:bg-gray-900/10">
                        <!-- Connection Status -->
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                <span>Status Printer</span>
                                {#if isConnected}
                                    <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold normal-case">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Terhubung
                                    </span>
                                {:else}
                                    <span class="text-rose-500 dark:text-rose-400 font-bold normal-case">Tidak Terhubung</span>
                                {/if}
                            </div>

                            {#if isConnected}
                                <div class="flex items-center justify-between p-2 rounded bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 text-sm">
                                    <span class="font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                                        <i class="fa-solid fa-print text-gray-500"></i>
                                        {printerName}
                                    </span>
                                    <button
                                        type="button"
                                        onclick={handleBluetoothDisconnect}
                                        class="text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 text-xs font-bold"
                                    >
                                        Putuskan
                                    </button>
                                </div>
                            {/if}
                        </div>

                        <!-- Bluetooth Actions / Connect / Test -->
                        <div class="flex gap-2">
                            {#if !isBluetoothSupported}
                                <div class="w-full text-xs text-rose-500 dark:text-rose-400 text-center py-1">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i>
                                    Web Bluetooth API tidak didukung pada browser/koneksi ini. Gunakan HTTPS.
                                </div>
                            {:else}
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={isConnecting}
                                    onclick={handleBluetoothConnect}
                                    class="flex-1 text-xs"
                                >
                                    {#snippet children()}
                                        {#if isConnecting}
                                            <i class="fa-solid fa-spinner animate-spin mr-1"></i>Menghubungkan...
                                        {:else}
                                            <i class="fa-solid fa-bluetooth mr-1"></i>Connect Printer
                                        {/if}
                                    {/snippet}
                                </Button>

                                {#if isConnected}
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        disabled={isPrinting}
                                        onclick={handleTestPrint}
                                        class="flex-1 text-xs"
                                    >
                                        {#snippet children()}
                                            {#if isPrinting}
                                                <i class="fa-solid fa-spinner animate-spin mr-1"></i>Cetak...
                                            {:else}
                                                <i class="fa-solid fa-vial mr-1"></i>Cetak Test
                                            {/if}
                                        {/snippet}
                                    </Button>
                                {/if}
                            {/if}
                        </div>

                        <!-- Ukuran Kertas -->
                        <div class="pt-1">
                            <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Ukuran Kertas Printer
                            </span>
                            <div class="flex gap-4">
                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="paperWidth"
                                        value="58mm"
                                        bind:group={paperWidth}
                                        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                    <span class="ml-2 font-medium">58mm (POS Kecil)</span>
                                </label>
                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="paperWidth"
                                        value="80mm"
                                        bind:group={paperWidth}
                                        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                    <span class="ml-2 font-medium">80mm (POS Standar)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                {/if}

                <!-- Tipe Struk -->
                <div>
                    <label for="receipt-type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Bagian Struk Yang Dicetak
                    </label>
                    <select
                        id="receipt-type"
                        bind:value={receiptType}
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                            focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500
                            dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="all">Semua (Checker Dapur & Struk Customer)</option>
                        <option value="checker">Checker Dapur Saja</option>
                        <option value="invoice">Struk Pembayaran Customer Saja</option>
                    </select>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 flex justify-end gap-3 border-t border-gray-150 dark:border-gray-700 pt-4">
                <Button
                    variant="secondary"
                    onclick={onClose}
                    disabled={isPrinting}
                >
                    {#snippet children()}Batal{/snippet}
                </Button>
                
                <Button
                    variant="primary"
                    disabled={isPrinting || (printMethod === 'bluetooth' && !isConnected)}
                    onclick={handlePrintNow}
                    icon={printMethod === 'bluetooth' ? 'fa-solid fa-print' : 'fa-solid fa-file-pdf'}
                >
                    {#snippet children()}
                        {#if isPrinting}
                            <i class="fa-solid fa-spinner animate-spin mr-1.5"></i>Mencetak...
                        {:else}
                            Cetak Sekarang
                        {/if}
                    {/snippet}
                </Button>
            </div>
        </div>
    </div>
{/if}
