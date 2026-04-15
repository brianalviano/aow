<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface DropPoint {
        id: string;
        name: string;
    }

    interface Chef {
        id: string;
        name: string;
    }

    interface Customer {
        id: string;
        name: string;
        email: string;
    }

    interface ChefItem {
        id: string;
        name: string;
        chef_status?: string;
    }

    interface Order {
        id: string;
        number: string;
        delivery_date: string | null;
        delivery_time: string | null;
        order_status: string;
        payment_status: string;
        total_amount: number;
        created_at: string;
        customer?: Customer;
        drop_point?: DropPoint;
        items?: ChefItem[];
        chef_status_summary?: string;
    }

    let orders = $derived($page.props.orders as { data: Order[]; meta?: any });

    let filters = $derived(
        $page.props.filters as
            | {
                  drop_point_id?: string;
                  chef_id?: string;
                  delivery_date?: string;
              }
            | undefined,
    );

    let dropPoints = $derived(($page.props.dropPoints as DropPoint[]) ?? []);

    let chefs = $derived(($page.props.chefs as Chef[]) ?? []);

    let meta = $derived(
        orders?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(orders?.data ?? []);

    let dropPointFilter = $state(untrack(() => filters?.drop_point_id || ""));
    let chefFilter = $state(untrack(() => filters?.chef_id || ""));
    let deliveryDateFilter = $state(
        untrack(() => filters?.delivery_date || ""),
    );

    let hasActiveFilters = $derived(
        !!dropPointFilter || !!chefFilter || !!deliveryDateFilter,
    );

    $effect(() => {
        // Trigger applyFilters whenever any filter state changes
        const _dp = dropPointFilter;
        const _cf = chefFilter;
        const _dd = deliveryDateFilter;

        untrack(() => applyFilters(1));
    });

    function applyFilters(pageNumber = 1) {
        const params: Record<string, string> = { page: String(pageNumber) };
        if (dropPointFilter) params.drop_point_id = dropPointFilter;
        if (chefFilter) params.chef_id = chefFilter;
        if (deliveryDateFilter) params.delivery_date = deliveryDateFilter;

        router.get("/admin/orders/processing", params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function resetFilters() {
        dropPointFilter = "";
        chefFilter = "";
        deliveryDateFilter = "";
        applyFilters();
    }

    function goToPage(pageNumber: number) {
        applyFilters(pageNumber);
    }

    function formatCurrency(amount: number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }

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
            default:
                return { variant: "secondary", label: status };
        }
    }

    function getChefStatusBadge(status: string | undefined): {
        variant: BadgeVariant;
        label: string;
    } {
        switch (status) {
            case "accepted":
                return { variant: "success", label: "Selesai" };
            case "rejected":
                return { variant: "danger", label: "Ditolak" };
            case "partial":
                return { variant: "purple", label: "Sebagian" };
            default:
                return { variant: "warning", label: "Menunggu" };
        }
    }

    // Group orders by delivery_date for better readability
    let groupedItems = $derived(() => {
        const groups: Record<string, Order[]> = {};
        for (const order of items) {
            const key = order.delivery_date ?? "Tanpa Tanggal";
            if (!groups[key]) groups[key] = [];
            groups[key].push(order);
        }
        // Sort keys: dated entries ascending, undated last
        return Object.entries(groups).sort(([a], [b]) => {
            if (a === "Tanpa Tanggal") return 1;
            if (b === "Tanpa Tanggal") return -1;
            return a.localeCompare(b);
        });
    });

    function formatDeliveryDate(dateStr: string) {
        if (dateStr === "Tanpa Tanggal") return dateStr;
        return new Date(dateStr).toLocaleDateString("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    }

    // Build select options
    let dropPointOptions = $derived([
        { value: "", label: "Semua Drop Point" },
        ...dropPoints.map((dp) => ({ value: dp.id, label: dp.name })),
    ]);

    let chefOptions = $derived([
        { value: "", label: "Semua Dapur/Chef" },
        ...chefs.map((c) => ({ value: c.id, label: c.name })),
    ]);
</script>

<svelte:head>
    <title>Pesanan Diproses | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Pesanan Diproses
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Pesanan yang sedang diproses dan dikirim
            </p>
        </div>
    </header>

    <!-- Filter Bar -->
    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4"
    >
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <Select
                    label="Drop Point"
                    id="drop_point_id"
                    name="drop_point_id"
                    options={dropPointOptions}
                    bind:value={dropPointFilter}
                />
            </div>
            <div class="flex-1 min-w-[180px]">
                <Select
                    label="Dapur / Chef"
                    id="chef_id"
                    name="chef_id"
                    options={chefOptions}
                    bind:value={chefFilter}
                />
            </div>
            <div class="flex gap-2 shrink-0">
                {#if hasActiveFilters}
                    <Button
                        variant="secondary"
                        size="sm"
                        onclick={resetFilters}
                    >
                        Reset
                    </Button>
                {/if}
            </div>
        </div>
    </div>

    <!-- Active filter summary -->
    {#if hasActiveFilters}
        <div
            class="flex flex-wrap gap-2 items-center text-sm text-gray-600 dark:text-gray-400"
        >
            <span class="font-medium">Filter aktif:</span>
            {#if dropPointFilter}
                {@const dp = dropPoints.find((d) => d.id === dropPointFilter)}
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-xs"
                >
                    <i class="fa-solid fa-location-dot"></i>
                    {dp?.name ?? dropPointFilter}
                </span>
            {/if}
            {#if chefFilter}
                {@const chef = chefs.find((c) => c.id === chefFilter)}
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-xs"
                >
                    <i class="fa-solid fa-user-chef"></i>
                    {chef?.name ?? chefFilter}
                </span>
            {/if}
            {#if deliveryDateFilter}
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-xs"
                >
                    <i class="fa-solid fa-calendar"></i>
                    {new Date(deliveryDateFilter).toLocaleDateString("id-ID", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                    })}
                </span>
            {/if}
        </div>
    {/if}

    <!-- Table grouped by delivery date -->
    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800"
    >
        {#if items.length > 0}
            {#each groupedItems() as [dateKey, groupOrders]}
                <!-- Group header -->
                <div
                    class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2"
                >
                    <i class="fa-solid fa-calendar-day text-gray-400 text-xs"
                    ></i>
                    <span
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        {formatDeliveryDate(dateKey)}
                    </span>
                    <span class="ml-auto text-xs text-gray-400">
                        {groupOrders.length} pesanan
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="custom-table min-w-full">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Jam</th>
                                <th>Customer</th>
                                <th>Drop Point</th>
                                <th>Dapur / Chef</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Status Dapur</th>
                                <th class="w-28 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each groupOrders as item}
                                {@const statusBadge = getStatusBadge(
                                    item.order_status,
                                )}
                                {@const chefBadge = getChefStatusBadge(
                                    item.chef_status_summary,
                                )}
                                {@const chefNames = [
                                    ...new Set(
                                        (item.items ?? [])
                                            .map((i: any) => i.chef?.name)
                                            .filter(Boolean),
                                    ),
                                ]}
                                <tr>
                                    <td
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.number}
                                    </td>
                                    <td class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {item.delivery_time ?? "-"}
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
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {item.drop_point?.name ?? "-"}
                                        </div>
                                    </td>
                                    <td>
                                        {#if chefNames.length > 0}
                                            <div class="flex flex-wrap gap-1">
                                                {#each chefNames as chefName}
                                                    <span
                                                        class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded"
                                                    >
                                                        {chefName}
                                                    </span>
                                                {/each}
                                            </div>
                                        {:else}
                                            <span class="text-gray-400 text-sm"
                                                >-</span
                                            >
                                        {/if}
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
                                            variant={chefBadge.variant}
                                            dot={true}
                                        >
                                            {#snippet children()}{chefBadge.label}{/snippet}
                                        </Badge>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-center"
                                    >
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            icon="fa-solid fa-eye"
                                            href={`/admin/orders/${item.id}`}
                                        >
                                            Detail
                                        </Button>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/each}
        {:else}
            <div
                class="py-12 text-sm text-center text-gray-500 dark:text-gray-400"
            >
                <div
                    class="flex flex-col items-center justify-center space-y-2"
                >
                    <i class="fa-solid fa-inbox text-4xl text-gray-300"></i>
                    <p>Tidak ada pesanan yang sedang diproses</p>
                    {#if hasActiveFilters}
                        <p class="text-xs text-gray-400">
                            Coba ubah atau hapus filter
                        </p>
                    {/if}
                </div>
            </div>
        {/if}

        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        </div>
    </div>
</section>
