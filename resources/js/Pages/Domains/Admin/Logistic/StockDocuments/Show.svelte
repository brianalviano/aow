<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import logo from "@img/logo.png";

    type WarehouseInfo = {
        id: string | null;
        name: string | null;
        address: string | null;
        phone: string | null;
    };
    type StockRow = {
        warehouse_id: string;
        product_id: string;
        quantity: number;
    };
    type StockDocumentDetail = {
        id: string;
        number: string;
        warehouse: WarehouseInfo;
        document_date: string | null;
        type: string | null;
        type_label: string | null;
        reason: string | null;
        reason_label: string | null;
        status: string | null;
        status_label: string | null;
        bucket: string | null;
        notes: string | null;
        items: Array<{
            id: string;
            product: {
                id: string | null;
                name: string | null;
                sku?: string | null;
            };
            quantity: number;
            notes: string | null;
        }>;
    };

    let sd = $derived($page.props.stock_document as StockDocumentDetail);
    let stocks = $derived(($page.props.stocks ?? []) as StockRow[]);

    function backToList() {
        router.visit("/stock-documents");
    }

    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        }).format(d);
    }
    function bucketLabel(input?: string | null): string {
        const s = String(input ?? "");
        if (s === "vat") return "PPN";
        if (s === "non_vat") return "Non PPN";
        return "-";
    }
    function statusBadgeVariant(
        s: string,
    ):
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple"
        | "white" {
        if (s === "completed") return "success";
        if (s === "pending_ho_approval") return "warning";
        if (s === "rejected_by_ho") return "danger";
        if (s === "canceled") return "dark";
        return "secondary";
    }

    function getProductStock(productId: string | null): number {
        const wid = String(sd.warehouse?.id ?? "");
        const pid = String(productId ?? "");
        if (!wid || !pid) return 0;
        const row = stocks.find(
            (s) =>
                String(s.warehouse_id) === wid && String(s.product_id) === pid,
        );
        return row ? row.quantity : 0;
    }

    function printDoc() {
        openCenteredWindow(`/stock-documents/${sd.id}/print`, {
            width: 800,
            height: 600,
        });
    }
</script>

<svelte:head>
    <title>Detail Dokumen Stok | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Dokumen Stok
            </h1>
            <p class="text-gray-600 dark:text-gray-400">{sd.number}</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button variant="info" icon="fa-solid fa-print" onclick={printDoc}
                >Cetak</Button
            >
        </div>
    </header>

    <div class="space-y-4">
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
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                Gudang: {sd.warehouse?.name || "-"}
                            </p>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                Alamat: {sd.warehouse?.address || "-"}
                            </p>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                No. Whatsapp: {sd.warehouse?.phone || "-"}
                            </p>
                        </div>
                        <div
                            class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                        >
                            <h2
                                class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                            >
                                DOKUMEN STOK
                            </h2>
                            <div class="w-full flex justify-end mb-2">
                                <Badge
                                    size="sm"
                                    rounded="pill"
                                    variant={statusBadgeVariant(String(sd.status ?? ""))}
                                    title={sd.status_label ?? sd.status ?? ""}
                                >
                                    {#snippet children()}
                                        {sd.status_label ?? sd.status ?? ""}
                                    {/snippet}
                                </Badge>
                            </div>
                            <div
                                class="w-full flex justify-end gap-12 text-right"
                            >
                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        No Dokumen
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {sd.number ?? "-"}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        Tgl Buat
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {formatDateIndo(sd.document_date)}
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
                                JENIS DOKUMEN
                            </p>
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {sd.type_label ?? sd.type ?? "-"}
                            </div>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                ALASAN
                            </p>
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {sd.reason_label ?? sd.reason ?? "-"}
                            </div>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                STATUS
                            </p>
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {sd.status_label ?? sd.status ?? "-"}
                            </div>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                            >
                                BUCKET
                            </p>
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {bucketLabel(sd.bucket)}
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <p
                                class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                            >
                                CATATAN
                            </p>
                            <div
                                class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                            >
                                {(sd.notes || "").trim() || "-"}
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
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-12"
                                    >
                                        NO
                                    </th>
                                    <th scope="col" class="px-3 py-2 w-1/4"
                                        >PRODUK</th
                                    >
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-20"
                                    >
                                        STOK
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-20"
                                    >
                                        QTY
                                    </th>
                                    <th scope="col" class="px-3 py-2 w-1/4"
                                        >CATATAN</th
                                    >
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 dark:text-gray-300">
                                {#each sd.items as item, index}
                                    <tr>
                                        <td class="px-3 py-2 text-center"
                                            >{index + 1}</td
                                        >
                                        <td class="px-3 py-2">
                                            <span
                                                >{item.product?.name ||
                                                    "-"}</span
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            {getProductStock(
                                                item.product?.id ?? "",
                                            )}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            {item.quantity}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-gray-600 dark:text-gray-400"
                                        >
                                            {item.notes || "-"}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </div>
            {/snippet}
        </Card>
    </div>
</section>
