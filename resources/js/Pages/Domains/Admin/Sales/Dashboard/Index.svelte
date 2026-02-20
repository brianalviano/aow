<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    let { role, dashboardData } = $props();
    let user = $derived($page.props.auth?.user);

    type RecentSale = {
        id: string;
        number: string;
        sale_date: string | null;
        customer: {
            id: string | null;
            name: string | null;
            phone: string | null;
        };
        warehouse: { id: string | null; name: string | null };
        payment_status: string | null;
        payment_status_label?: string | null;
        grand_total: number;
    };

    let summary = $derived(dashboardData?.summary ?? {});
    let recentSales = $derived(
        (dashboardData?.lists?.recent_sales ?? []) as RecentSale[],
    );

    function getPaymentStatusVariant(
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
        switch (status) {
            case "paid":
                return "success";
            case "partially_paid":
                return "warning";
            case "unpaid":
                return "danger";
            default:
                return "secondary";
        }
    }

    function openSale(id: string) {
        router.visit(`/sales/${id}`);
    }
</script>

<svelte:head>
    <title>Dashboard Sales | {siteName(($page.props as any).settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header class="space-y-2">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Dashboard Sales
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span>
                    {user?.name ?? "Pengguna"}
                </span>
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
            label="Penjualan Hari Ini"
            value={Number(summary.sales_today ?? 0)}
            icon="fa-solid fa-cash-register"
            color="blue"
        />
        <StatCard
            label="Lunas"
            value={Number(summary.sales_paid ?? 0)}
            icon="fa-solid fa-check"
            color="green"
        />
        <StatCard
            label="Terbayar Sebagian"
            value={Number(summary.sales_partially_paid ?? 0)}
            icon="fa-solid fa-money-bill-wave"
            color="amber"
        />
        <StatCard
            label="Belum Dibayar"
            value={Number(summary.sales_unpaid ?? 0)}
            icon="fa-solid fa-exclamation-triangle"
            color="red"
        />
        <StatCard
            label="Dalam Pengiriman"
            value={Number(summary.deliveries_in_delivery ?? 0)}
            icon="fa-solid fa-truck"
            color="orange"
        />
        <StatCard
            label="Total Piutang"
            value={formatCurrency(Number(summary.total_outstanding ?? 0))}
            icon="fa-solid fa-hand-holding-dollar"
            color="purple"
        />
    </div>

    <Card title="Penjualan Terbaru" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Gudang</th>
                            <th>Status Pembayaran</th>
                            <th class="text-right">Total</th>
                            <th class="w-28"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if recentSales.length > 0}
                            {#each recentSales as s}
                                <tr>
                                    <td>{s.number}</td>
                                    <td>{formatDateDisplay(s.sale_date)}</td>
                                    <td>
                                        {#if s.customer?.name}
                                            <div class="font-medium">
                                                {s.customer?.name}
                                            </div>
                                            {#if s.customer?.phone}
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {s.customer?.phone}
                                                </div>
                                            {/if}
                                        {:else}
                                            <span
                                                class="text-gray-500 dark:text-gray-400"
                                                >—</span
                                            >
                                        {/if}
                                    </td>
                                    <td>{s.warehouse?.name ?? "—"}</td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getPaymentStatusVariant(
                                                s.payment_status,
                                            )}
                                        >
                                            {s.payment_status_label ??
                                                "Tidak Diketahui"}
                                        </Badge>
                                    </td>
                                    <td class="text-right"
                                        >{formatCurrency(s.grand_total)}</td
                                    >
                                    <td>
                                        <Button
                                            variant="secondary"
                                            icon="fa-solid fa-eye"
                                            size="sm"
                                            onclick={() => openSale(s.id)}
                                        >
                                            Detail
                                        </Button>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="7"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Tidak ada data</td
                                >
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
    </Card>
</section>
