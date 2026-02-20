<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";

    type PurchaseOrderInfo = {
        id: string;
        number: string;
        supplier: { name: string | null };
        warehouse: { name: string | null };
        status: string | null;
        status_label: string | null;
        supplier_invoice_number?: string | null;
        supplier_invoice_date?: string | null;
        supplier_invoice_file?: string | null;
    };
    type PurchaseReturnInfo = {
        id: string;
        number: string | null;
        return_date: string | null;
        supplier: { name: string | null };
        warehouse: { name: string | null };
        purchase_order: { id: string | null; number: string | null };
        status: string | null;
        status_label: string | null;
        resolution: string | null;
    };
    type ItemRow = {
        product_id: string;
        product_name: string;
        sku?: string | null;
        sdo_quantity?: number;
        return_quantity?: number;
        received_quantity: number;
        remaining_quantity: number;
    };
    type SdoInfo = {
        id: string;
        number: string | null;
        delivery_date: string | null;
    };
    type ReceivingInfo = {
        sender_name?: string | null;
        vehicle_plate_number?: string | null;
    };

    let po = $derived(
        ($page.props.purchase_order ?? null) as PurchaseOrderInfo | null,
    );
    let pr = $derived(
        ($page.props.purchase_return ?? null) as PurchaseReturnInfo | null,
    );
    let isReturn = $derived<boolean>(!!pr);
    let sdo = $derived(
        ($page.props.supplier_delivery_order ?? null) as SdoInfo | null,
    );
    let items = $derived(($page.props.items ?? []) as ItemRow[]);
    let receiving = $derived(
        ($page.props.receiving ?? null) as ReceivingInfo | null,
    );

    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(d);
    }

    let warehouseData = $derived(() => {
        if (isReturn && pr?.warehouse) {
            return {
                name: pr.warehouse.name ?? "",
                address: "",
                phone: "",
            };
        }
        if (po?.warehouse) {
            return {
                name: po.warehouse.name ?? "",
                address: "",
                phone: "",
            };
        }
        return null;
    });

    let supplierData = $derived(() => {
        if (isReturn && pr?.supplier) {
            return { name: pr.supplier.name ?? "" };
        }
        if (po?.supplier) {
            return { name: po.supplier.name ?? "" };
        }
        return null;
    });

    let documentDate = $derived(() => {
        if (isReturn) return pr?.return_date ?? "";
        return sdo?.delivery_date ?? "";
    });

    let totalOrdered = $derived(() => {
        return items.reduce((acc, row) => {
            const qty = isReturn
                ? Number(row.return_quantity ?? 0)
                : Number(row.sdo_quantity ?? 0);
            return acc + qty;
        }, 0);
    });
    let totalReceived = $derived(() => {
        return items.reduce(
            (acc, row) => acc + Number(row.received_quantity),
            0,
        );
    });
    let totalRemaining = $derived(() => {
        return items.reduce(
            (acc, row) => acc + Number(row.remaining_quantity),
            0,
        );
    });
</script>

<svelte:head>
    <title>Detail Penerimaan Barang - {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                TANDA TERIMA BARANG
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isReturn
                    ? "Detail penerimaan penggantian retur"
                    : "Detail penerimaan dari purchase order"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={() => router.visit("/goods-receipts")}
            >
                Kembali
            </Button>
            {#if isReturn || sdo}
                <Button
                    variant="primary"
                    icon="fa-solid fa-print"
                    onclick={() => {
                        const url = `/goods-receipts/${sdo?.id}/print`;
                        openCenteredWindow(url, {
                            width: 960,
                            height: 700,
                            fallbackWhenBlocked: false,
                        });
                    }}
                >
                    Cetak
                </Button>
            {/if}
        </div>
    </header>

    <div class="space-y-6">
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
                    {#if warehouseData()}
                        <div
                            class="flex items-center gap-2 mt-1 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <span>Nama Gudang : {warehouseData()?.name}</span>
                        </div>
                    {/if}
                </div>

                <div
                    class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                >
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                    >
                        TANDA TERIMA BARANG
                    </h2>
                    <div class="w-full flex justify-end gap-12 text-right">
                        <div class="flex gap-12">
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    Tgl {isReturn ? "Retur" : "PO"}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {formatDateIndo(documentDate())}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    No {isReturn ? "Retur" : "SJ"}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {isReturn
                                        ? (pr?.number ?? "-")
                                        : (sdo?.number ?? "-")}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                <div>
                    {#if supplierData()}
                        <p
                            class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            SUPPLIER
                        </p>
                        <div class="flex items-center gap-2 mb-2">
                            <h3
                                class="text-lg font-medium text-gray-800 dark:text-white"
                            >
                                {supplierData()?.name}
                            </h3>
                        </div>
                    {/if}
                </div>
                <div>
                    {#if receiving}
                        <p
                            class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            DETAIL PENGIRIMAN
                        </p>
                        <div
                            class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed space-y-1"
                        >
                            <div class="flex">
                                <span class="w-40">Nama Pengirim</span>
                                <span
                                    >:
                                    {(receiving?.sender_name || "").trim() ||
                                        "-"}</span
                                >
                            </div>
                            <div class="flex">
                                <span class="w-40">Plat Nomor</span>
                                <span
                                    >:
                                    {(
                                        receiving?.vehicle_plate_number || ""
                                    ).trim() || "-"}</span
                                >
                            </div>
                        </div>
                    {/if}
                </div>
            </div>

            <div class="overflow-x-auto mb-6">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center w-12">NO</th>
                            <th scope="col" class="w-1/4">PRODUK</th>
                            <th scope="col" class="text-center w-20">
                                {isReturn ? "QTY RETUR" : "QTY PO"}
                            </th>
                            <th scope="col" class="text-center w-20">
                                DITERIMA
                            </th>
                            <th scope="col" class="text-center w-20">SISA</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each items as row, idx}
                            <tr>
                                <td class="text-center">{idx + 1}</td>
                                <td>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span>
                                            {row.product_name}
                                        </span>
                                    </div>
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {row.sku || "-"}
                                    </div>
                                </td>
                                <td class="text-center">
                                    {isReturn
                                        ? (row.return_quantity ?? 0)
                                        : (row.sdo_quantity ?? 0)}
                                </td>
                                <td class="text-center">
                                    {row.received_quantity}
                                </td>
                                <td class="text-center font-semibold">
                                    {row.remaining_quantity}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col md:flex-row justify-between items-center gap-4"
            >
                <div class="flex gap-6">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Total Pesanan:
                        <span class="font-semibold">{totalOrdered()}</span>
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Total Diterima:
                        <span class="font-semibold">{totalReceived()}</span>
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Total Sisa: <span class="font-semibold"
                            >{totalRemaining()}</span
                        >
                    </div>
                </div>
            </div>
        </Card>

        {#if po}
            <Card title="Faktur Supplier" collapsible={false}>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <TextInput
                        id="supplier_invoice_number"
                        name="supplier_invoice_number"
                        label="Nomor Invoice Supplier"
                        value={String(po?.supplier_invoice_number ?? "")}
                        readonly
                    />
                    <TextInput
                        id="supplier_invoice_date"
                        name="supplier_invoice_date"
                        label="Tanggal Invoice"
                        value={String(po?.supplier_invoice_date ?? "")}
                        readonly
                    />
                    <div class="md:col-span-3 flex items-center gap-2">
                        {#if po?.supplier_invoice_file}
                            <a
                                class="text-sm text-blue-600 underline dark:text-blue-400"
                                href={String(po?.supplier_invoice_file)}
                                target="_blank">Buka File Saat Ini</a
                            >
                        {:else}
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Tidak ada file faktur</span
                            >
                        {/if}
                    </div>
                </div>
            </Card>
        {/if}
    </div>
</section>
