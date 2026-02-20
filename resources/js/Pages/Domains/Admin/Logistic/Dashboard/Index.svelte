<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import Chart from "chart.js/auto";
    let { role, dashboardData } = $props();
    let user = $derived($page.props.auth?.user);
    type IdNumberStatus = {
        id: string;
        number: string;
        status: string | null;
        status_label?: string | null;
        transfer_date?: string | null;
    };
    type LowStockItem = {
        product: { id: string; name: string };
        min_stock: number;
        total_stock: number;
        shortage: number;
    };
    type ReceivingItem = {
        id: string;
        product: { name: string | null };
        warehouse: { name: string | null };
        quantity: number;
        supplier_name: string | null;
        invoice_number: string | null;
        received_at: string | null;
    };
    type StockUpdateItem = {
        id: string;
        product: { name: string | null };
        warehouse: { name: string | null };
        quantity: number;
        type: string | null;
        type_label?: string | null;
        time: string | null;
    };
    let summary = $derived(dashboardData?.summary ?? {});
    let pos = $derived(
        (dashboardData?.lists?.purchase_orders ?? []) as IdNumberStatus[],
    );
    let transfers = $derived(
        (dashboardData?.lists?.stock_transfers ?? []) as IdNumberStatus[],
    );
    let opnames = $derived(
        (dashboardData?.lists?.stock_opnames ?? []) as IdNumberStatus[],
    );
    let lowStocks = $derived(
        (dashboardData?.lists?.low_stocks ?? []) as LowStockItem[],
    );
    let recentReceivings = $derived(
        (dashboardData?.lists?.recent_receivings ?? []) as ReceivingItem[],
    );
    let recentStockUpdates = $derived(
        (dashboardData?.lists?.recent_stock_updates ?? []) as StockUpdateItem[],
    );
    let poCanvas = $state<HTMLCanvasElement>();
    let transferCanvas = $state<HTMLCanvasElement>();
    let opnameCanvas = $state<HTMLCanvasElement>();
    let receivingCanvas = $state<HTMLCanvasElement>();
    let lowStockChart = $state<Chart | undefined>();
    let poChartInst = $state<Chart | undefined>();
    let transferChartInst = $state<Chart | undefined>();
    let opnameChartInst = $state<Chart | undefined>();
    let receivingChartInst = $state<Chart | undefined>();
    $effect(() => {
        if (!poCanvas) return;
        const statusMap: Record<string, number> = {};
        for (const it of pos) {
            const s = (it.status || "").toString();
            statusMap[s] = (statusMap[s] || 0) + 1;
        }
        const labels = Object.keys(statusMap);
        const data = Object.values(statusMap);
        if (!poChartInst) {
            poChartInst = new Chart(poCanvas, {
                type: "doughnut",
                data: {
                    labels: [],
                    datasets: [
                        {
                            data: [],
                            backgroundColor: [
                                "#2563eb",
                                "#22c55e",
                                "#f59e0b",
                                "#ef4444",
                                "#8b5cf6",
                                "#0ea5e9",
                            ],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: "bottom" } },
                },
            });
        }
        const chart = poChartInst;
        if (!chart) return;
        const d = chart.data as any;
        if (!d) return;
        d.labels = labels as any;
        const ds = d.datasets as any[];
        if (!ds || ds.length === 0) return;
        ds[0].data = data as any;
        chart.update();
    });
    $effect(() => {
        if (!transferCanvas) return;
        const dateMap: Record<string, number> = {};
        for (const t of transfers) {
            const d = t.transfer_date || "-";
            dateMap[d] = (dateMap[d] || 0) + 1;
        }
        const labels = Object.keys(dateMap);
        const data = Object.values(dateMap);
        if (!transferChartInst) {
            transferChartInst = new Chart(transferCanvas, {
                type: "bar",
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: "Transfers",
                            data: [],
                            backgroundColor: "#f59e0b",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                },
            });
        }
        const chart = transferChartInst;
        if (!chart) return;
        const d = chart.data as any;
        if (!d) return;
        d.labels = labels as any;
        const ds = d.datasets as any[];
        if (!ds || ds.length === 0) return;
        ds[0].data = data as any;
        chart.update();
    });
    $effect(() => {
        if (!opnameCanvas) return;
        const statusMap: Record<string, number> = {};
        for (const o of opnames) {
            const s = (o.status || "").toString();
            statusMap[s] = (statusMap[s] || 0) + 1;
        }
        const labels = Object.keys(statusMap);
        const data = Object.values(statusMap);
        if (!opnameChartInst) {
            opnameChartInst = new Chart(opnameCanvas, {
                type: "doughnut",
                data: {
                    labels: [],
                    datasets: [
                        {
                            data: [],
                            backgroundColor: [
                                "#10b981",
                                "#f59e0b",
                                "#6366f1",
                                "#ef4444",
                            ],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: "bottom" } },
                },
            });
        }
        const chart = opnameChartInst;
        if (!chart) return;
        const d = chart.data as any;
        if (!d) return;
        d.labels = labels as any;
        const ds = d.datasets as any[];
        if (!ds || ds.length === 0) return;
        ds[0].data = data as any;
        chart.update();
    });
    $effect(() => {
        if (!receivingCanvas) return;
        const whMap: Record<string, number> = {};
        for (const r of recentReceivings) {
            const w = r.warehouse?.name || "Gudang";
            whMap[w] = (whMap[w] || 0) + (r.quantity || 0);
        }
        const labels = Object.keys(whMap);
        const data = Object.values(whMap);
        if (!receivingChartInst) {
            receivingChartInst = new Chart(receivingCanvas, {
                type: "bar",
                data: {
                    labels: [],
                    datasets: [
                        { label: "Qty", data: [], backgroundColor: "#14b8a6" },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                },
            });
        }
        const chart = receivingChartInst;
        if (!chart) return;
        const d = chart.data as any;
        if (!d) return;
        d.labels = labels as any;
        const ds = d.datasets as any[];
        if (!ds || ds.length === 0) return;
        ds[0].data = data as any;
        chart.update();
    });
    onMount(() => {
        return () => {
            poChartInst?.destroy();
            transferChartInst?.destroy();
            opnameChartInst?.destroy();
            receivingChartInst?.destroy();
        };
    });
</script>

<svelte:head>
    <title>Dashboard Logistik | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <!-- Header -->
    <header
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
    >
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Dashboard Logistik
            </h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                Selamat datang kembali, <span
                    class="font-semibold text-gray-900 dark:text-white"
                    >{user?.name || "User"}</span
                >!
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

    <div class="space-y-6">
        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Produk, Gudang & Stok
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-4">
                    <StatCard
                        label="Total Produk"
                        value={summary?.products_total ?? 0}
                        unit="Produk"
                        icon="fa-solid fa-boxes-stacked"
                        color="indigo"
                    />
                </div>
                <div class="md:col-span-4">
                    <StatCard
                        label="Total Gudang"
                        value={summary?.warehouses_total ?? 0}
                        unit="Gudang"
                        icon="fa-solid fa-warehouse"
                        color="cyan"
                    />
                </div>
                <div class="md:col-span-4">
                    <StatCard
                        label="Stok Rendah"
                        value={summary?.low_stock_items ?? 0}
                        icon="fa-solid fa-triangle-exclamation"
                        color="red"
                    />
                </div>
                <div class="md:col-span-6">
                    <Card title="Stok Rendah (Top 5)">
                        {#if lowStocks.length === 0}
                            <div class="text-gray-500 dark:text-gray-400">
                                Tidak ada peringatan stok rendah
                            </div>
                        {:else}
                            <ul
                                class="divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                {#each lowStocks as it}
                                    <li
                                        class="flex items-center justify-between py-2"
                                    >
                                        <div class="flex items-center gap-3">
                                            <i
                                                class="fa-solid fa-box-open text-red-600"
                                            ></i>
                                            <div class="flex flex-col">
                                                <span class="font-semibold"
                                                    >{it.product.name}</span
                                                >
                                                <span
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {it.total_stock} / {it.min_stock}
                                                    unit
                                                </span>
                                            </div>
                                        </div>
                                        <Badge variant="danger"
                                            >-{it.shortage}</Badge
                                        >
                                    </li>
                                {/each}
                            </ul>
                        {/if}
                        {#snippet actions()}
                            <Button variant="link" href="/product-stocks"
                                >Lihat stok</Button
                            >
                        {/snippet}
                    </Card>
                </div>
                <div class="md:col-span-6">
                    <Card title="Stok Terbaru">
                        {#if recentStockUpdates.length === 0}
                            <div class="text-gray-500 dark:text-gray-400">
                                Tidak ada data hari ini
                            </div>
                        {:else}
                            <div class="overflow-x-auto">
                                <table class="custom-table min-w-full">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Gudang</th>
                                            <th class="text-center w-24">Qty</th
                                            >
                                            <th>Jenis</th>
                                            <th class="text-center w-24">Jam</th
                                            >
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {#each recentStockUpdates as r}
                                            <tr>
                                                <td>
                                                    <div
                                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                                    >
                                                        {r.product?.name || "-"}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-sm text-gray-900 dark:text-white"
                                                    >
                                                        {r.warehouse?.name ||
                                                            "-"}
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    {r.quantity}
                                                </td>
                                                <td>
                                                    <Badge
                                                        variant={r.type === "in"
                                                            ? "success"
                                                            : "danger"}
                                                    >
                                                        {r.type_label ||
                                                            r.type ||
                                                            "-"}
                                                    </Badge>
                                                </td>
                                                <td class="text-center">
                                                    {r.time || "-"}
                                                </td>
                                            </tr>
                                        {/each}
                                    </tbody>
                                </table>
                            </div>
                        {/if}
                    </Card>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Mutasi Stok
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-12">
                    <StatCard
                        label="Dalam Perjalanan"
                        value={summary?.stock_transfers_in_transit ?? null}
                        icon="fa-solid fa-right-left"
                        color="orange"
                    />
                </div>

                <div class="md:col-span-6">
                    <Card title="Dalam Perjalanan">
                        {#if transfers.length === 0}
                            <div class="text-gray-500 dark:text-gray-400">
                                Tidak ada data
                            </div>
                        {:else}
                            <ul
                                class="divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                {#each transfers as t}
                                    <li
                                        class="flex items-center justify-between py-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <i
                                                class="fa-solid fa-right-left text-orange-500"
                                            ></i>
                                            <span class="font-semibold"
                                                >{t.number}</span
                                            >
                                        </div>
                                    </li>
                                {/each}
                            </ul>
                        {/if}
                        {#snippet actions()}
                            <Button variant="link" href="/stock-transfers"
                                >Lihat semua</Button
                            >
                        {/snippet}
                    </Card>
                </div>
                <div class="md:col-span-6">
                    <Card title="Chart Mutasi per Tanggal">
                        {#snippet children()}
                            <div class="h-56">
                                <canvas bind:this={transferCanvas}></canvas>
                            </div>
                        {/snippet}
                    </Card>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Stok Opname
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-12">
                    <StatCard
                        label="Stok Opname Aktif"
                        value={summary?.stock_opnames_active ?? null}
                        icon="fa-solid fa-clipboard-check"
                        color="green"
                    />
                </div>
                <div class="md:col-span-8">
                    <Card title="Stok Opname Aktif">
                        {#if opnames.length === 0}
                            <div class="text-gray-500 dark:text-gray-400">
                                Tidak ada data
                            </div>
                        {:else}
                            <ul
                                class="divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                {#each opnames as op}
                                    <li
                                        class="flex items-center justify-between py-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <i
                                                class="fa-solid fa-clipboard-check text-green-600"
                                            ></i>
                                            <span class="font-semibold"
                                                >{op.number}</span
                                            >
                                        </div>
                                        <Badge variant="primary"
                                            >{op.status_label ||
                                                op.status}</Badge
                                        >
                                    </li>
                                {/each}
                            </ul>
                        {/if}
                        {#snippet actions()}
                            <Button variant="link" href="/stock-opnames"
                                >Lihat semua</Button
                            >
                        {/snippet}
                    </Card>
                </div>
                <div class="md:col-span-4">
                    <Card title="Chart Status Opname">
                        {#snippet children()}
                            <div class="h-56 flex items-center justify-center">
                                <canvas bind:this={opnameCanvas}></canvas>
                            </div>
                        {/snippet}
                    </Card>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Penerimaan
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="col-span-12">
                    <StatCard
                        label="Penerimaan Hari Ini"
                        value={summary?.receivings_today ?? null}
                        icon="fa-solid fa-inbox"
                        color="teal"
                    />
                </div>
                <div class="col-span-6">
                    <Card title="Penerimaan Hari Ini">
                        {#if recentReceivings.length === 0}
                            <div class="text-gray-500 dark:text-gray-400">
                                Belum ada penerimaan
                            </div>
                        {:else}
                            <ul
                                class="divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                {#each recentReceivings as rc}
                                    <li
                                        class="flex items-center justify-between py-2"
                                    >
                                        <div class="flex items-center gap-3">
                                            <i
                                                class="fa-solid fa-inbox text-teal-600"
                                            ></i>
                                            <div class="flex flex-col">
                                                <span class="font-semibold"
                                                    >{rc.product?.name ||
                                                        "Produk"}</span
                                                >
                                                <span
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {rc.warehouse?.name ||
                                                        "Gudang"}
                                                    • {rc.supplier_name ||
                                                        "Pemasok"}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Badge variant="success"
                                                >{rc.quantity}</Badge
                                            >
                                            <span
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                                >{rc.received_at || ""}</span
                                            >
                                        </div>
                                    </li>
                                {/each}
                            </ul>
                        {/if}
                        {#snippet actions()}
                            <Button variant="link" href="/purchase-orders"
                                >Form Penerimaan</Button
                            >
                        {/snippet}
                    </Card>
                </div>
                <div class="col-span-6">
                    <Card title="Chart Qty per Gudang">
                        {#snippet children()}
                            <div class="h-56">
                                <canvas bind:this={receivingCanvas}></canvas>
                            </div>
                        {/snippet}
                    </Card>
                </div>
            </div>
        </div>
    </div>
</section>
