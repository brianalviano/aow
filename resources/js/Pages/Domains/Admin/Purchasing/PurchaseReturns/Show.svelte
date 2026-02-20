<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        getCurrencySymbol,
        formatCurrencyWithoutSymbol,
    } from "@/Lib/Admin/Utils/currency";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";

    type IdName = {
        id: string | null;
        name: string | null;
        address?: string | null;
        phone?: string | null;
    };
    type SupplierDetail = {
        id: string | null;
        name: string | null;
        phone: string | null;
        email: string | null;
        address: string | null;
    };
    type PurchaseOrderInfo = {
        id: string | null;
        number: string | null;
        order_date: string | null;
        supplier: IdName;
        warehouse: IdName;
    };
    type PurchaseReturnItem = {
        product: { id: string | null; name: string | null; sku: string | null };
        quantity: number;
        price: number;
        subtotal: number;
        notes: string | null;
        stock_quantity: number;
    };
    type PurchaseReturnDetail = {
        id: string;
        number: string | null;
        supplier: SupplierDetail;
        warehouse: IdName;
        purchase_order: PurchaseOrderInfo;
        return_date: string | null;
        reason: string | null;
        reason_label: string | null;
        resolution: string | null;
        resolution_label: string | null;
        status: string | null;
        status_label: string | null;
        credit_amount: number;
        refund_amount: number;
        notes: string | null;
        items: PurchaseReturnItem[];
    };

    let pr = $derived($page.props.purchase_return as PurchaseReturnDetail);
    const currencySymbol = getCurrencySymbol();
    const total = $derived(
        pr.items.reduce((sum, it) => sum + (it.subtotal ?? 0), 0),
    );
    const poSummary = $derived(() => {
        const po = pr.purchase_order;
        return `${po.number || "-"}`;
    });

    function backToList() {
        router.visit("/purchase-returns");
    }
    let showCreateDeliveryModal = $state(false);
    let createDeliveryNumber = $state("");
    let createDeliveryDate = $state("");
    let createDeliveryNotes = $state("");
    let createDeliveryLoading = $state(false);
    function canCreateDelivery(status: string | null): boolean {
        const s = String(status ?? "");
        return (
            s === "draft" || s === "in_delivery" || s === "partially_delivered"
        );
    }
    function openCreateDeliveryModal() {
        createDeliveryNumber = "";
        createDeliveryDate =
            new Date().toISOString().split("T")[0] ||
            ($page.props.default_delivery_date as any) ||
            "";
        createDeliveryNotes = "";
        showCreateDeliveryModal = true;
    }
    function submitCreateDelivery(e: SubmitEvent) {
        e.preventDefault();
        createDeliveryLoading = true;
        router.post(
            `/purchase-returns/${pr.id}/deliveries`,
            {
                delivery_date: String(createDeliveryDate),
                number: String(createDeliveryNumber),
                notes: String(createDeliveryNotes || ""),
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => {
                    createDeliveryLoading = true;
                },
                onError: () => {
                    createDeliveryLoading = false;
                },
                onSuccess: () => {
                    createDeliveryLoading = false;
                    showCreateDeliveryModal = false;
                    createDeliveryNumber = "";
                    createDeliveryDate = "";
                    createDeliveryNotes = "";
                },
            },
        );
    }
</script>

<svelte:head>
    <title>Detail Retur Pembelian | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Retur Pembelian
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {pr.number || "-"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="primary"
                icon="fa-solid fa-print"
                onclick={() =>
                    openCenteredWindow(`/purchase-returns/${pr.id}/print`, {
                        width: 960,
                        height: 700,
                        fallbackWhenBlocked: false,
                    })}>Cetak</Button
            >
            {#if canCreateDelivery(pr.status)}
                <Button
                    variant="primary"
                    icon="fa-solid fa-truck-fast"
                    onclick={openCreateDeliveryModal}>Buat Pengiriman</Button
                >
            {/if}
        </div>
    </header>
    <Card collapsible={false}>
        <div class="flex flex-col md:flex-row justify-between items-start">
            <div class="w-full md:w-1/2">
                <div class="mb-2">
                    <img
                        src={logo}
                        alt="Logo"
                        class="w-90 object-contain"
                        loading="lazy"
                    />
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                    Gudang: {pr.warehouse?.name || "-"}
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                    Alamat: {pr.warehouse?.address ||
                        ($page.props.settings as any)?.address ||
                        "-"}
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                    No. Whatsapp: {pr.warehouse?.phone ||
                        ($page.props.settings as any)?.whatsapp_number ||
                        "-"}
                </p>
            </div>
            <div class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0">
                <h2
                    class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                >
                    RETUR PEMBELIAN
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
                            {pr.number || "-"}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            Tgl Buat
                        </p>
                        <div class="flex items-center justify-end gap-2">
                            <p
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {pr.return_date
                                    ? new Intl.DateTimeFormat("id-ID", {
                                          weekday: "long",
                                          year: "numeric",
                                          month: "long",
                                          day: "numeric",
                                      }).format(new Date(pr.return_date))
                                    : "-"}
                            </p>
                        </div>
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
                    No Purchase Order
                </p>
                <div
                    class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed mb-4"
                >
                    {poSummary()}
                </div>
                <p
                    class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                >
                    SUPPLIER
                </p>
                <div class="flex items-center gap-2 mb-2">
                    <h3
                        class="text-lg font-medium text-gray-800 dark:text-white"
                    >
                        {pr.supplier?.name || "-"}
                    </h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                    No. Whatsapp : {pr.supplier?.phone || "-"}
                </p>
                <div class="flex text-sm text-gray-700 dark:text-gray-300 mb-1">
                    <span class="w-16">Email</span>
                    <span>: {pr.supplier?.email || "-"}</span>
                </div>
                <div class="flex text-sm text-gray-700 dark:text-gray-300">
                    <span class="w-16">Alamat</span>
                    <span>: {pr.supplier?.address || "-"}</span>
                </div>
            </div>
            <div>
                <p
                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                >
                    Alasan Retur
                </p>
                <div
                    class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed mb-4"
                >
                    {pr.reason_label || pr.reason || "-"}
                </div>
                <p
                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                >
                    Resolusi
                </p>
                <div
                    class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed mb-4"
                >
                    {pr.resolution_label || pr.resolution || "-"}
                </div>
                <p
                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                >
                    CATATAN UNTUK SUPPLIER
                </p>
                <div
                    class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                >
                    {(pr.notes || "").trim() || "-"}
                </div>
            </div>
        </div>

        <hr class="border-gray-200 dark:border-[#212121] my-4" />

        <div class="overflow-x-auto mb-6">
            <table
                class="w-full text-sm text-left custom-table border-collapse dark:text-gray-300"
            >
                <thead
                    class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-200 dark:bg-gray-800"
                >
                    <tr>
                        <th scope="col" class="px-3 py-2 text-center w-12"
                            >NO</th
                        >
                        <th scope="col" class="px-3 py-2 w-1/4">PRODUK</th>
                        <th scope="col" class="px-3 py-2 text-center w-20"
                            >STOK</th
                        >
                        <th scope="col" class="px-3 py-2 text-center w-20"
                            >QTY</th
                        >
                        <th scope="col" class="px-3 py-2 text-right"
                            >HARGA RETUR</th
                        >
                        <th scope="col" class="px-3 py-2 text-right"
                            >SUBTOTAL</th
                        >
                        <th scope="col" class="px-3 py-2 w-1/4">CATATAN</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 dark:text-gray-300">
                    {#each pr.items as item, index}
                        <tr>
                            <td class="px-3 py-2 text-center">{index + 1}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <span>
                                        {item.product?.name && item.product?.sku
                                            ? `${item.product.name} (${item.product.sku})`
                                            : item.product?.name || "-"}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center">
                                {item.stock_quantity}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {item.quantity}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            item.price,
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            item.subtotal,
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td
                                class="px-3 py-2 text-gray-600 dark:text-gray-400"
                            >
                                {item.notes || "-"}
                            </td>
                        </tr>
                    {/each}
                    <tr>
                        <td
                            colspan="5"
                            class="px-3 py-2 bg-gray-50/50 dark:bg-gray-800/50"
                        >
                            <div class="flex justify-between">
                                <div class="font-bold uppercase">Total</div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex justify-between">
                                <span>{currencySymbol}</span>
                                <span>{formatCurrencyWithoutSymbol(total)}</span
                                >
                            </div>
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800 border-none"
                        ></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
</section>

{#if showCreateDeliveryModal}
    <Modal
        title="Buat Supplier Delivery Order (Retur)"
        bind:isOpen={showCreateDeliveryModal}
        onClose={() => (showCreateDeliveryModal = false)}
    >
        {#snippet children()}
            <form onsubmit={submitCreateDelivery} class="space-y-4">
                <TextInput
                    id="number"
                    name="number"
                    label="Nomor Pengiriman"
                    placeholder="SJ/202601/0001"
                    bind:value={createDeliveryNumber}
                    required={true}
                />
                <DateInput
                    id="delivery_date"
                    name="delivery_date"
                    label="Tanggal Pengiriman"
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                    bind:value={createDeliveryDate}
                    required={true}
                />
                <TextArea
                    id="notes"
                    name="notes"
                    label="Catatan"
                    placeholder="Catatan pengiriman"
                    bind:value={createDeliveryNotes}
                />
                <div class="flex gap-2 justify-end">
                    <Button
                        variant="secondary"
                        onclick={() => (showCreateDeliveryModal = false)}
                        type="button">Batal</Button
                    >
                    <Button
                        variant="primary"
                        type="submit"
                        disabled={createDeliveryLoading}
                        icon="fa-solid fa-truck-fast"
                        >{createDeliveryLoading
                            ? "Menyimpan..."
                            : "Buat Pengiriman"}</Button
                    >
                </div>
            </form>
        {/snippet}
    </Modal>
{/if}
