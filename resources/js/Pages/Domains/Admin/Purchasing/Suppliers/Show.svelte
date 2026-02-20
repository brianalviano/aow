<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatDateDisplay,
        formatDateTimeDisplay,
    } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type SupplierDetail = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        address: string | null;
        birth_date: string | null;
        gender: string | null;
        is_active: boolean;
        photo_url: string | null;
        created_at: string | null;
        updated_at: string | null;
    };

    let supplier = $derived($page.props.supplier as SupplierDetail);
    type PurchaseOrderSummary = {
        id: string;
        number: string | null;
        order_date: string | null;
        status: string | null;
        status_label: string | null;
        grand_total: number;
    };
    type PurchaseReturnSummary = {
        id: string;
        number: string | null;
        purchase_order: { id: string | null; number: string | null };
        return_date: string | null;
        status: string | null;
        status_label: string | null;
        credit_amount: number;
        refund_amount: number;
    };
    let purchaseOrders = $derived(
        ($page.props.purchase_orders ?? []) as PurchaseOrderSummary[],
    );
    let purchaseReturns = $derived(
        ($page.props.purchase_returns ?? []) as PurchaseReturnSummary[],
    );

    function backToList() {
        router.visit("/suppliers");
    }

    function editSupplier() {
        router.visit(`/suppliers/${supplier.id}/edit`);
    }

    function openPO(id: string) {
        router.visit(`/purchase-orders/${id}`);
    }
    function openPR(id: string) {
        router.visit(`/purchase-returns/${id}`);
    }
</script>

<svelte:head>
    <title>Detail Supplier | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Supplier
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {supplier.name} - {supplier.email}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                onclick={editSupplier}>Edit</Button
            >
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Supplier" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {supplier.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Email
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {supplier.email}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tanggal Lahir
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateDisplay(supplier.birth_date)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Telepon
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {supplier.phone || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Status
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {supplier.is_active ? "Aktif" : "Tidak Aktif"}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Alamat
                            </p>
                            {#if supplier.address}
                                <p
                                    class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {supplier.address}
                                </p>
                            {:else}
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada alamat.
                                </p>
                            {/if}
                        </div>
                    </div>
                {/snippet}
            </Card>

            <Card title="Informasi Sistem" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dibuat Pada
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(supplier.created_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(supplier.updated_at)}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>

            <Card title="Riwayat Purchase Order" bodyWithoutPadding={true}>
                {#snippet children()}
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-100 dark:bg-gray-800">
                                <tr
                                    class="text-sm text-left text-gray-600 dark:text-gray-400"
                                >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Nomor</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Tanggal</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Status</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Grand Total</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Aksi</th
                                    >
                                </tr>
                            </thead>
                            <tbody>
                                {#if purchaseOrders?.length}
                                    {#each purchaseOrders as po}
                                        <tr
                                            class="border-b border-gray-200 dark:border-gray-700"
                                        >
                                            <td class="px-4 py-3">
                                                <a
                                                    href={"/purchase-orders/" +
                                                        po.id}
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >{po.number || "-"}</a
                                                >
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {formatDateDisplay(
                                                        po.order_date,
                                                    )}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {po.status_label}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {formatCurrency(
                                                        po.grand_total,
                                                    )}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="flex gap-2 items-center"
                                                >
                                                    <Button
                                                        variant="primary"
                                                        size="sm"
                                                        icon="fa-solid fa-eye"
                                                        onclick={() =>
                                                            openPO(po.id)}
                                                        >Detail</Button
                                                    >
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else}
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                        >
                                            Tidak ada data PO.
                                        </td>
                                    </tr>
                                {/if}
                            </tbody>
                        </table>
                    </div>
                {/snippet}
            </Card>

            <Card title="Riwayat Retur Pembelian" bodyWithoutPadding={true}>
                {#snippet children()}
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-100 dark:bg-gray-800">
                                <tr
                                    class="text-sm text-left text-gray-600 dark:text-gray-400"
                                >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Nomor</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >No PO</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Tanggal</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Status</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Kredit</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Refund</th
                                    >
                                    <th
                                        class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                        >Aksi</th
                                    >
                                </tr>
                            </thead>
                            <tbody>
                                {#if purchaseReturns?.length}
                                    {#each purchaseReturns as r}
                                        <tr
                                            class="border-b border-gray-200 dark:border-gray-700"
                                        >
                                            <td class="px-4 py-3">
                                                <a
                                                    href={"/purchase-returns/" +
                                                        r.id}
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >{r.number || "-"}</a
                                                >
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {r.purchase_order?.number ||
                                                        "-"}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {formatDateDisplay(
                                                        r.return_date,
                                                    )}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {r.status_label}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {formatCurrency(
                                                        r.credit_amount,
                                                    )}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {formatCurrency(
                                                        r.refund_amount,
                                                    )}
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap"
                                            >
                                                <div
                                                    class="flex gap-2 items-center"
                                                >
                                                    <Button
                                                        variant="primary"
                                                        size="sm"
                                                        icon="fa-solid fa-eye"
                                                        onclick={() =>
                                                            openPR(r.id)}
                                                        >Detail</Button
                                                    >
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else}
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                        >
                                            Tidak ada data retur.
                                        </td>
                                    </tr>
                                {/if}
                            </tbody>
                        </table>
                    </div>
                {/snippet}
            </Card>
        </div>

        <div class="space-y-4">
            <Card title="Foto" collapsible={false}>
                {#snippet children()}
                    {#if supplier.photo_url}
                        <img
                            class="rounded-lg border border-gray-200 dark:border-gray-700"
                            src={supplier.photo_url}
                            alt="Foto Supplier"
                        />
                        <a
                            class="text-xs text-blue-600 underline dark:text-blue-400"
                            href={supplier.photo_url}
                            target="_blank">Buka di tab baru</a
                        >
                    {:else}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada foto.
                        </p>
                    {/if}
                {/snippet}
            </Card>
        </div>
    </div>
</section>
