<script lang="ts">
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { onMount } from "svelte";
    import { Chart, registerables } from "chart.js";

    Chart.register(...registerables);

    let { stats, chartData, recentOrders, topProducts } = $props();

    let chartCanvas: HTMLCanvasElement;
    let chart: Chart;

    onMount(() => {
        if (chartCanvas) {
            chart = new Chart(chartCanvas, {
                type: "line",
                data: {
                    labels: chartData.map((d: any) => d.label),
                    datasets: [
                        {
                            label: "Revenue",
                            data: chartData.map((d: any) => d.value),
                            borderColor: "#6366f1",
                            backgroundColor: "rgba(99, 102, 241, 0.1)",
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: "#fff",
                            pointBorderColor: "#6366f1",
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: "#6366f1",
                            pointHoverBorderColor: "#fff",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)",
                            },
                            ticks: {
                                callback: (value: any) =>
                                    "Rp " + value.toLocaleString("id-ID"),
                            },
                        },
                        x: {
                            grid: { display: false },
                        },
                    },
                },
            });
        }
    });

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    type BadgeVariant =
        | "dark"
        | "light"
        | "success"
        | "warning"
        | "info"
        | "primary"
        | "danger"
        | "white"
        | "secondary"
        | "purple";

    function getStatusBadge(status: string): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Menunggu" };
            case "confirmed":
                return { variant: "info", label: "Dikonfirmasi" };
            case "shipped":
                return { variant: "primary", label: "Dikirim" };
            case "delivered":
                return { variant: "success", label: "Selesai" };
            case "cancelled":
                return { variant: "danger", label: "Dibatalkan" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    function getPaymentBadge(status: string): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "pending":
                return { variant: "warning", label: "Belum Bayar" };
            case "paid":
                return { variant: "success", label: "Lunas" };
            case "failed":
                return { variant: "danger", label: "Gagal" };
            case "refunded":
                return { variant: "info", label: "Dikembalikan" };
            default:
                return { variant: "secondary", label: status };
        }
    }
</script>

<div class="space-y-6">
    <!-- Header -->
    <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4"
    >
        <div class="flex flex-col gap-1">
            <h1
                class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight"
            >
                Dashboard Overview
            </h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium">
                Welcome back! Here's a snapshot of your business performance.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span
                class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest"
            >
                Last Sync: {new Date().toLocaleTimeString("id-ID")}
            </span>
        </div>
    </div>

    <!-- Statistics Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
            label="Total Revenue"
            value={formatCurrency(stats.total_revenue)}
            icon="fa-solid fa-money-bill-trend-up"
            color="indigo"
        />
        <StatCard
            label="Total Orders"
            value={stats.total_orders}
            icon="fa-solid fa-cart-shopping"
            color="blue"
        />
        <StatCard
            label="Total Customers"
            value={stats.total_customers}
            icon="fa-solid fa-users"
            color="purple"
        />
        <StatCard
            label="Today's Orders"
            value={stats.today_orders}
            icon="fa-solid fa-calendar-check"
            color="orange"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sales Trend Chart -->
        <div class="lg:col-span-8">
            <Card
                title="Revenue Growth"
                subtitle="Sales performance over the last 6 months"
                collapsible={false}
                class="h-full shadow-sm"
            >
                <div class="h-100 w-full pt-4">
                    <canvas bind:this={chartCanvas}></canvas>
                </div>
            </Card>
        </div>

        <!-- Top Products -->
        <div class="lg:col-span-4">
            <Card
                title="Top Selling Products"
                subtitle="Most popular items by volume"
                collapsible={false}
                class="h-full shadow-sm"
            >
                <div class="space-y-5 pt-2">
                    {#each topProducts as product}
                        <div
                            class="flex items-center gap-4 group cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 p-2 rounded-xl transition-all duration-300"
                        >
                            <div
                                class="size-12 rounded-xl bg-slate-100 dark:bg-slate-800 shrink-0 overflow-hidden shadow-sm border border-slate-200 dark:border-slate-700"
                            >
                                {#if product.image}
                                    <img
                                        src={product.image}
                                        alt={product.name}
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                    />
                                {:else}
                                    <div
                                        class="w-full h-full flex items-center justify-center text-slate-400"
                                    >
                                        <i class="fa-solid fa-box text-xl"></i>
                                    </div>
                                {/if}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4
                                    class="font-bold text-slate-800 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"
                                >
                                    {product.name}
                                </h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span
                                        class="text-[10px] font-extrabold uppercase px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-md tracking-wider"
                                    >
                                        {product.total_sold} units
                                    </span>
                                </div>
                            </div>
                        </div>
                    {:else}
                        <div
                            class="py-12 text-center text-slate-400 dark:text-slate-600"
                        >
                            <div class="mb-2">
                                <i
                                    class="fa-solid fa-box-open text-3xl opacity-20"
                                ></i>
                            </div>
                            <p class="italic text-sm font-medium">
                                No sales data available
                            </p>
                        </div>
                    {/each}
                </div>
            </Card>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="grid grid-cols-1">
        <Card
            title="Recent Transactions"
            subtitle="Latest 5 orders processed"
            collapsible={false}
            class="overflow-hidden shadow-sm"
            bodyWithoutPadding={true}
        >
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th>Tgl. Kirim</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status Pesanan</th>
                            <th>Status Bayar</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each recentOrders as item}
                            {@const statusBadge = getStatusBadge(
                                item.order_status,
                            )}
                            {@const paymentBadge = getPaymentBadge(
                                item.payment_status,
                            )}
                            <tr>
                                <td
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {item.number}
                                </td>
                                <td>
                                    <div
                                        class="text-sm text-gray-900 dark:text-white"
                                    >
                                        {new Date(
                                            item.created_at,
                                        ).toLocaleDateString("id-ID")}
                                    </div>
                                </td>
                                <td>
                                    <div
                                        class="text-sm text-gray-900 dark:text-white"
                                    >
                                        {item.delivery_date
                                            ? new Date(
                                                  item.delivery_date,
                                              ).toLocaleDateString("id-ID", {
                                                  weekday: "short",
                                                  day: "numeric",
                                                  month: "short",
                                                  year: "numeric",
                                              })
                                            : "-"}
                                        {#if item.delivery_time}
                                            <div
                                                class="text-[10px] text-gray-500 mt-0.5"
                                            >
                                                Pukul {item.delivery_time} WIB
                                            </div>
                                        {/if}
                                    </div>
                                </td>
                                <td>
                                    <div
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.customer?.name ?? "-"}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {item.customer?.email ?? ""}
                                    </div>
                                </td>
                                <td>
                                    <div
                                        class="text-sm font-bold text-gray-900 dark:text-white"
                                    >
                                        {formatCurrency(item.total_amount)}
                                    </div>
                                </td>
                                <td>
                                    <Badge
                                        size="sm"
                                        rounded="pill"
                                        variant={statusBadge.variant}
                                    >
                                        {#snippet children()}{statusBadge.label}{/snippet}
                                    </Badge>
                                </td>
                                <td>
                                    <Badge
                                        size="sm"
                                        rounded="pill"
                                        variant={paymentBadge.variant}
                                    >
                                        {#snippet children()}{paymentBadge.label}{/snippet}
                                    </Badge>
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-center"
                                >
                                    <div
                                        class="flex gap-2 items-center justify-center"
                                    >
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            icon="fa-solid fa-eye"
                                            href={`/admin/orders/${item.id}`}
                                        >
                                            Detail
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        {:else}
                            <tr>
                                <td
                                    colspan="8"
                                    class="py-12 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    <div
                                        class="flex flex-col items-center justify-center space-y-2"
                                    >
                                        <i
                                            class="fa-solid fa-inbox text-4xl text-gray-300"
                                        ></i>
                                        <p>Tidak ada data pesanan</p>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</div>
