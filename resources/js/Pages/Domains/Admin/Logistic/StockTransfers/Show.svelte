<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";

    type StockTransfer = {
        id: string;
        number: string;
        transfer_date: string | null;
        status: string | null;
        status_label: string | null;
        notes: string | null;
        from_warehouse: {
            id: string | null;
            name: string | null;
            address: string | null;
            whatsapp: string | null;
        };
        to_warehouse: {
            id: string | null;
            name: string | null;
            address: string | null;
            whatsapp: string | null;
        };
        to_owner_user: {
            id: string | null;
            name: string | null;
            address: string | null;
            whatsapp: string | null;
        } | null;
        items: Array<{
            id: string;
            product: { id: string | null; name: string | null };
            quantity: number;
        }>;
    };

    let tr = $derived($page.props.stock_transfer as StockTransfer);

    function toBadgeVariant(s: string | null) {
        switch (String(s ?? "")) {
            case "draft":
                return "secondary";
            case "in_transit":
                return "warning";
            case "received":
                return "success";
            case "canceled":
                return "danger";
            default:
                return "secondary";
        }
    }

    function getNextStatus(
        current: string | null,
    ): { value: string; label: string } | null {
        const s = String(current ?? "");
        switch (s) {
            case "draft":
                return { value: "in_transit", label: "Mulai Pengiriman" };
            case "in_transit":
                return { value: "received", label: "Terima di Gudang Tujuan" };
            default:
                return null;
        }
    }
    function canDelete(status: string | null): boolean {
        return String(status ?? "") === "draft";
    }
    function canEdit(status: string | null): boolean {
        return String(status ?? "") === "draft";
    }

    let showAdvanceDialog = $state(false);
    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
</script>

<svelte:head>
    <title>{siteName($page.props.settings)} - Detail Mutasi Stok</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Mutasi Stok
            </h1>
            <div>
                <Badge variant={toBadgeVariant(tr.status)}
                    >{tr.status_label ?? tr.status ?? "-"}</Badge
                >
            </div>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                href="/stock-transfers">Kembali</Button
            >
            <Button
                variant="primary"
                icon="fa-solid fa-print"
                onclick={() =>
                    openCenteredWindow(`/stock-transfers/${tr.id}/print`, {
                        width: 960,
                        height: 700,
                        fallbackWhenBlocked: false,
                    })}>Cetak</Button
            >
            {#if getNextStatus(tr.status)}
                <Button
                    variant="success"
                    icon="fa-solid fa-forward"
                    onclick={() => (showAdvanceDialog = true)}
                    >Lanjut: {getNextStatus(tr.status)?.label}</Button
                >
            {/if}
            {#if canEdit(tr.status)}
                <Button
                    variant="warning"
                    icon="fa-solid fa-edit"
                    href={`/stock-transfers/${tr.id}/edit`}>Edit</Button
                >
            {/if}
            {#if canDelete(tr.status)}
                <Button
                    variant="danger"
                    icon="fa-solid fa-trash"
                    onclick={() => (showDeleteDialog = true)}>Hapus</Button
                >
            {/if}
        </div>
    </header>

    <Card collapsible={false} bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="p-6">
                <div
                    class="w-full flex flex-col md:flex-row justify-between items-start gap-6"
                >
                    <div class="w-full md:w-1/2">
                        <div class="mb-2">
                            <img
                                src={logo}
                                alt="Logo"
                                class="w-90 object-contain"
                                loading="lazy"
                            />
                        </div>
                        <div class="mt-1">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Alamat Asal: {tr.from_warehouse?.address || "-"}
                            </p>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                WhatsApp Asal: {tr.from_warehouse?.whatsapp ||
                                    "-"}
                            </p>
                        </div>
                    </div>
                    <div
                        class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                    >
                        <h2
                            class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                        >
                            MUTASI STOK
                        </h2>
                        <div class="w-full flex justify-end gap-12 text-right">
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    No Surat
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {tr.number ?? "-"}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    Tanggal
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {tr.transfer_date ?? "-"}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-[#212121] my-4" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            {tr.to_owner_user?.id
                                ? "MARKETING TUJUAN"
                                : "GUDANG TUJUAN"}
                        </p>
                        <div class="mb-2">
                            <h3
                                class="text-lg font-medium text-gray-800 dark:text-white"
                            >
                                {tr.to_owner_user?.id
                                    ? tr.to_owner_user?.name || "-"
                                    : tr.to_warehouse?.name || "-"}
                            </h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Alamat Tujuan:
                            {tr.to_owner_user?.id
                                ? tr.to_owner_user?.address || "-"
                                : tr.to_warehouse?.address || "-"}
                        </p>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                        >
                            WhatsApp Tujuan:
                            {tr.to_owner_user?.id
                                ? tr.to_owner_user?.whatsapp || "-"
                                : tr.to_warehouse?.whatsapp || "-"}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                        >
                            CATATAN
                        </p>
                        <div
                            class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                        >
                            {(tr.notes || "").trim() || "-"}
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-[#212121] my-4" />

                <div class="overflow-x-auto mb-6">
                    <table class="custom-table w-full">
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    class="text-center w-12">NO</th
                                >
                                <th scope="col" class="w-1/2"
                                    >PRODUK</th
                                >
                                <th
                                    scope="col"
                                    class="text-center w-24">QTY</th
                                >
                            </tr>
                        </thead>
                        <tbody>
                            {#each tr.items as item, index}
                                <tr>
                                    <td class="text-center"
                                        >{index + 1}</td
                                    >
                                    <td>
                                        <span>{item.product?.name || "-"}</span>
                                    </td>
                                    <td class="text-center"
                                        >{item.quantity}</td
                                    >
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            </div>
        {/snippet}
    </Card>
</section>

<Dialog
    isOpen={showAdvanceDialog}
    onClose={() => (showAdvanceDialog = false)}
    title="Lanjutkan Status"
    message="Konfirmasi perubahan status mutasi stok"
    showDefaultActions={false}
>
    {#if getNextStatus(tr.status)}
        <p class="mb-4 text-sm">
            Lanjutkan status mutasi stok <b>{tr.number}</b> menjadi
            <b>{getNextStatus(tr.status)?.label}</b>?
        </p>
        <div class="flex justify-end gap-2">
            <Button
                variant="secondary"
                onclick={() => (showAdvanceDialog = false)}>Batal</Button
            >
            <Button
                variant="primary"
                onclick={() =>
                    router.post(
                        `/stock-transfers/${tr.id}/advance`,
                        {},
                        {
                            preserveScroll: true,
                            onSuccess: () => (showAdvanceDialog = false),
                        },
                    )}>Lanjut</Button
            >
        </div>
    {:else}
        <p>Status tidak dapat dilanjutkan.</p>
    {/if}
</Dialog>

<Dialog
    isOpen={showDeleteDialog}
    onClose={() => (showDeleteDialog = false)}
    title="Hapus Mutasi Stok"
    message="Konfirmasi penghapusan mutasi stok"
    showDefaultActions={false}
>
    <p class="mb-4 text-sm">
        Anda yakin ingin menghapus mutasi stok <b>{tr.number}</b>?
    </p>
    <div class="flex justify-end gap-2">
        <Button variant="secondary" onclick={() => (showDeleteDialog = false)}
            >Batal</Button
        >
        <Button
            variant="danger"
            onclick={() => {
                deleteProcessing = true;
                router.delete(`/stock-transfers/${tr.id}`, {
                    preserveScroll: true,
                    onFinish: () => {
                        deleteProcessing = false;
                        showDeleteDialog = false;
                        router.visit("/stock-transfers");
                    },
                });
            }}
            disabled={deleteProcessing}
            >{deleteProcessing ? "Menghapus..." : "Hapus"}</Button
        >
    </div>
</Dialog>
