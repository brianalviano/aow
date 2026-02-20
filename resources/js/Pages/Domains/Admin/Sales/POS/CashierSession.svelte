<script lang="ts">
    import { router } from "@inertiajs/svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type ActiveShift = {
        id: string;
        number: string;
        opened_at: string;
        opening_balance: number;
        expected_closing_balance: number;
        total_sales: number;
        total_cash_in: number;
    } | null;

    const { activeShift, canOpenShiftToday } = $props<{
        activeShift: ActiveShift;
        canOpenShiftToday: boolean;
    }>();

    let showOpenShiftModal = $state<boolean>(false);
    let openingBalanceStr = $state<string>("");
    let openShiftError = $state<string>("");
    $effect(() => {
        showOpenShiftModal = !activeShift && !!canOpenShiftToday;
        openShiftError = "";
    });
    function submitOpenShift(): void {
        openShiftError = "";
        const payload = {
            opening_balance: Math.max(0, Number(openingBalanceStr || 0)),
        };
        router.post("/pos/open-shift", payload, {
            preserveScroll: true,
            onSuccess: () => {
                showOpenShiftModal = false;
            },
            onError: (errors: Record<string, unknown>) => {
                const eb = (errors as any)?.opening_balance;
                if (eb) {
                    openShiftError = String(eb);
                } else {
                    openShiftError =
                        "Gagal membuka kasir. Periksa saldo dan konfigurasi kasir.";
                }
            },
        });
    }
    let showCloseShiftModal = $state<boolean>(false);
    let closingBalanceStr = $state<string>("");
    function openCloseShiftModal(): void {
        if (!activeShift) return;
        closingBalanceStr = "";
        showCloseShiftModal = true;
    }
    function submitCloseShift(): void {
        if (!activeShift) return;
        const payload = {
            session_id: (activeShift as any).id,
            actual_closing_balance: Math.max(0, Number(closingBalanceStr || 0)),
        };
        router.post("/pos/close-shift", payload, {
            preserveScroll: true,
            onSuccess: () => {
                showCloseShiftModal = false;
            },
        });
    }
    function closeCloseShiftModal(): void {
        showCloseShiftModal = false;
    }

    let showClosedWarningModal = $state<boolean>(false);
    $effect(() => {
        showClosedWarningModal = !activeShift && !canOpenShiftToday;
    });
    function gotoHome(): void {
        router.get("/dashboard/sales", { preserveScroll: true });
    }
</script>

{#if activeShift}
    <Button
        variant="danger"
        onclick={openCloseShiftModal}
        icon="fa-solid fa-xmark">Tutup Kasir</Button
    >
{/if}

<Modal
    bind:isOpen={showOpenShiftModal}
    title="Buka Kasir"
    showCloseButton={false}
>
    {#snippet children()}
        <div class="space-y-3">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Masukkan nominal "Uang Yang Ada di Laci/Kasir" untuk memulai
                POS.
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-1">
                <div>
                    <TextInput
                        id="opening_balance"
                        name="opening_balance"
                        label="Saldo Awal Laci/Kasir"
                        placeholder="Contoh: 500000"
                        bind:value={openingBalanceStr}
                        currency={true}
                        currencySymbol="Rp"
                        thousandSeparator="."
                        decimalSeparator=","
                        maxDecimals={0}
                        stripZeros={true}
                        oninput={(ev) =>
                            (openingBalanceStr = String(
                                (ev as any).numericValue ?? 0,
                            ))}
                    />
                </div>
                {#if openShiftError}
                    <div class="text-sm text-red-600 dark:text-red-400">
                        {openShiftError}
                    </div>
                {/if}
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button
            variant="primary"
            onclick={submitOpenShift}
            disabled={!canOpenShiftToday}>Buka Kasir</Button
        >
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showCloseShiftModal}
    title="Tutup Kasir"
    onClose={closeCloseShiftModal}
>
    {#snippet children()}
        <div class="space-y-3">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Anda sudah tidak bisa melakukan penjualan. Silahkan pindah ke
                beranda.
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="primary" onclick={gotoHome}>Ke Beranda</Button>
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showCloseShiftModal}
    title="Tutup Kasir"
    onClose={closeCloseShiftModal}
>
    {#snippet children()}
        <div class="space-y-3">
            {#if activeShift}
                <div class="text-sm text-gray-900 dark:text-white">
                    Ekspektasi: {formatCurrency(
                        (activeShift as any).expected_closing_balance,
                    )}
                    <div
                        class="text-xs text-gray-600 dark:text-gray-400 mt-0.5"
                    >
                        Saldo awal {formatCurrency(
                            (activeShift as any).opening_balance,
                        )} + kas masuk {formatCurrency(
                            (activeShift as any).total_cash_in,
                        )}
                    </div>
                </div>
            {/if}
            <div>
                <TextInput
                    id="actual_closing_balance"
                    name="actual_closing_balance"
                    label="Tunai Diterima"
                    placeholder="Contoh: 1500000"
                    bind:value={closingBalanceStr}
                    currency={true}
                    currencySymbol="Rp"
                    thousandSeparator="."
                    decimalSeparator=","
                    maxDecimals={0}
                    stripZeros={true}
                    oninput={(ev) =>
                        (closingBalanceStr = String(
                            (ev as any).numericValue ?? 0,
                        ))}
                />
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={closeCloseShiftModal}>Batal</Button
        >
        <Button
            variant="primary"
            onclick={submitCloseShift}
            disabled={!activeShift}>Tutup Kasir</Button
        >
    {/snippet}
</Modal>
