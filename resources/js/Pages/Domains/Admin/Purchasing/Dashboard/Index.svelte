<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    let { role, dashboardData } = $props();
    let user = $derived($page.props.auth?.user);

    type PurchaseOrderItem = {
        id: string;
        number: string;
        supplier: { id: string | null; name: string | null };
        warehouse: { id: string | null; name: string | null };
        order_date?: string | null;
        status: string | null;
        status_label?: string | null;
        grand_total: number;
    };

    let summary = $derived(dashboardData?.summary ?? {});
    let recentPurchaseOrders = $derived(
        (dashboardData?.lists?.recent_purchase_orders ??
            []) as PurchaseOrderItem[],
    );

    function getPOStatusVariant(
        status: string | null,
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
        const s = String(status ?? "").toLowerCase();
        if (!s || s === "draft") return "secondary";
        if (s === "pending_ho_approval") return "warning";
        if (s === "head_office_approved") return "info";
        if (s === "rejected_by_ho") return "danger";
        if (s === "pending_supplier_approval") return "warning";
        if (s === "supplier_confirmed") return "success";
        if (s === "rejected_by_supplier") return "danger";
        if (s === "in_delivery") return "info";
        if (s === "partially_delivered") return "purple";
        if (s === "completed") return "success";
        if (s === "canceled") return "danger";
        return "secondary";
    }
    function canReceive(status: string | null): boolean {
        const s = String(status ?? "");
        return s === "in_delivery" || s === "partially_delivered";
    }
    function canEdit(status: string | null): boolean {
        const s = String(status ?? "");
        return (
            s === "draft" ||
            s === "rejected_by_ho" ||
            s === "rejected_by_supplier"
        );
    }
</script>

<svelte:head>
    <title>Dashboard Purchasing | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
    >
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Dashboard Purchasing
            </h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                Selamat datang kembali,
                <span class="font-semibold text-gray-900 dark:text-white">
                    {user?.name || "User"}
                </span>
                !
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2"
                >
                    {role}
                </span>
            </p>
        </div>
        <div class="text-sm text-gray-500">
            {new Date().toLocaleDateString("id-ID", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
            })}
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <StatCard
            label="PO Menunggu HO"
            value={Number(summary.purchase_orders_pending_ho ?? 0)}
            icon="fa-solid fa-building"
            color="blue"
        />
        <StatCard
            label="PO Menunggu Pemasok"
            value={Number(summary.purchase_orders_pending_supplier ?? 0)}
            icon="fa-solid fa-user-tie"
            color="amber"
        />
        <StatCard
            label="PO Dikonfirmasi"
            value={Number(summary.purchase_orders_supplier_confirmed ?? 0)}
            icon="fa-solid fa-file-invoice"
            color="green"
        />
        <StatCard
            label="PO Dalam Pengiriman"
            value={Number(summary.purchase_orders_in_delivery ?? 0)}
            icon="fa-solid fa-truck"
            color="indigo"
        />
        <StatCard
            label="PO Terkirim Sebagian"
            value={Number(summary.purchase_orders_partially_delivered ?? 0)}
            icon="fa-solid fa-box-open"
            color="purple"
        />
        <StatCard
            label="Supplier"
            value={Number(summary.suppliers_total ?? 0)}
            icon="fa-solid fa-people-group"
            color="teal"
        />
    </div>

    <Card title="PO Terbaru" collapsible={false} bodyWithoutPadding={true}>
        {#snippet actions()}
            <Button variant="link" href="/purchase-orders">
                Lihat Semua PO
            </Button>
        {/snippet}
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-max table-auto">
                    <thead>
                        <tr>
                            <th class="text-left w-40">Nomor</th>
                            <th class="text-left w-52">Pemasok</th>
                            <th class="text-left w-52">Gudang</th>
                            <th class="text-left w-40">Tanggal</th>
                            <th class="text-left w-44">Status</th>
                            <th class="text-right w-40">Grand Total</th>
                            <th class="text-left w-64">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if recentPurchaseOrders.length === 0}
                            <tr>
                                <td
                                    colspan="7"
                                    class="text-center py-4 text-gray-500"
                                    >Tidak ada data</td
                                >
                            </tr>
                        {:else}
                            {#each recentPurchaseOrders as po}
                                <tr>
                                    <td class="text-left">
                                        <a
                                            class="text-blue-600 hover:underline"
                                            href={`/purchase-orders/${po.id}`}
                                        >
                                            {po.number}
                                        </a>
                                    </td>
                                    <td class="text-left">
                                        {po.supplier?.name || "-"}
                                    </td>
                                    <td class="text-left">
                                        {po.warehouse?.name || "-"}
                                    </td>
                                    <td class="text-left">
                                        {formatDateDisplay(po.order_date)}
                                    </td>
                                    <td class="text-left">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getPOStatusVariant(
                                                po.status,
                                            )}
                                            title={po.status_label ??
                                                po.status ??
                                                "-"}
                                        >
                                            {#snippet children()}
                                                {po.status_label ??
                                                    po.status ??
                                                    "-"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="text-right">
                                        {formatCurrency(po.grand_total)}
                                    </td>
                                    <td class="text-left">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="xs"
                                                icon="fa-solid fa-eye"
                                                href={`/purchase-orders/${po.id}`}
                                                >Detail</Button
                                            >
                                            {#if canReceive(po.status)}
                                                <Button
                                                    variant="primary"
                                                    size="xs"
                                                    icon="fa-solid fa-box-open"
                                                    href={`/purchase-orders/${po.id}/receivings/create`}
                                                    >Penerimaan</Button
                                                >
                                            {/if}
                                            {#if canEdit(po.status)}
                                                <Button
                                                    variant="warning"
                                                    size="xs"
                                                    icon="fa-solid fa-edit"
                                                    href={`/purchase-orders/${po.id}/edit`}
                                                    >Edit</Button
                                                >
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
    </Card>
</section>
