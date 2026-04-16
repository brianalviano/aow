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
        address?: string;
    }

    interface Chef {
        id: string;
        name: string;
        business_name?: string;
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
                  view?: string;
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

    let currentView = $state(untrack(() => filters?.view || "list"));
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
        const _v = currentView;

        untrack(() => applyFilters(1));
    });

    function applyFilters(pageNumber = 1) {
        const params: Record<string, string> = { page: String(pageNumber) };
        if (dropPointFilter) params.drop_point_id = dropPointFilter;
        if (chefFilter) params.chef_id = chefFilter;
        if (deliveryDateFilter) params.delivery_date = deliveryDateFilter;
        if (currentView !== "list") params.view = currentView;

        router.get("/admin/orders/processing", params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function switchView(view: string) {
        currentView = view;
        // Effect will trigger applyFilters(1)
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

    // Group orders by delivery_date for better readability (Standard List view)
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

    // Group orders by Drop Point
    let groupedByDropPoint = $derived(() => {
        const groups: Record<
            string,
            { drop_point: DropPoint | undefined; orders: Order[] }
        > = {};
        for (const order of items) {
            const key = order.drop_point?.id ?? "unknown";
            if (!groups[key]) {
                groups[key] = {
                    drop_point: order.drop_point,
                    orders: [],
                };
            }
            groups[key].orders.push(order);
        }
        return Object.values(groups).sort((a, b) =>
            (a.drop_point?.name ?? "").localeCompare(b.drop_point?.name ?? ""),
        );
    });

    // Group orders by Kitchen/Chef
    let groupedByKitchen = $derived(() => {
        const groups: Record<
            string,
            { chef: Chef | undefined; orders: Order[] }
        > = {};
        for (const order of items) {
            // Get unique chefs by ID for this order
            const orderChefsMap = (order.items ?? [])
                .map((i: any) => i.chef)
                .filter(Boolean)
                .reduce((acc: Record<string, Chef>, chef: Chef) => {
                    acc[chef.id] = chef;
                    return acc;
                }, {});

            const orderChefs = Object.values(orderChefsMap) as Chef[];

            if (orderChefs.length === 0) {
                const key = "unknown";
                if (!groups[key]) groups[key] = { chef: undefined, orders: [] };
                groups[key].orders.push(order);
                continue;
            }

            for (const chef of orderChefs) {
                const key = chef.id;
                if (!groups[key]) {
                    groups[key] = {
                        chef: chef,
                        orders: [],
                    };
                }
                groups[key].orders.push(order);
            }
        }
        return Object.values(groups).sort((a, b) =>
            (a.chef?.name ?? "").localeCompare(b.chef?.name ?? ""),
        );
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

{#snippet orderTable(orderList: Order[])}
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
                {#each orderList as item}
                    {@const statusBadge = getStatusBadge(item.order_status)}
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
                        <td class="font-medium text-gray-900 dark:text-white">
                            {item.number}
                        </td>
                        <td
                            class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                        >
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
                            {#if item.items && item.items.length > 0}
                                <div
                                    class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium italic border-t border-gray-100 dark:border-gray-800 pt-1"
                                >
                                    {item.items
                                        .map(
                                            (i: any) =>
                                                `${i.product?.name ?? "Produk"} ${i.quantity}`,
                                        )
                                        .join(", ")}
                                </div>
                            {/if}
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
                                <span class="text-gray-400 text-sm">-</span>
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
                        <td class="px-4 py-3 whitespace-nowrap text-center">
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
{/snippet}

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

    <!-- Tabs View Switcher -->
    <div
        class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-lg w-full sm:w-fit"
    >
        <button
            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium rounded-md transition-all {currentView ===
            'list'
                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'}"
            onclick={() => switchView("list")}
        >
            <i class="fa-solid fa-list-ul mr-2"></i>
            Daftar Pesanan
        </button>
        <button
            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium rounded-md transition-all {currentView ===
            'drop_point'
                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'}"
            onclick={() => switchView("drop_point")}
        >
            <i class="fa-solid fa-location-dot mr-2"></i>
            Per Drop Point
        </button>
        <button
            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium rounded-md transition-all {currentView ===
            'kitchen'
                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'}"
            onclick={() => switchView("kitchen")}
        >
            <i class="fa-solid fa-fire-burner mr-2"></i>
            Per Dapur
        </button>
    </div>

    <!-- Filter Bar -->
    {#if currentView === "list"}
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
    {/if}

    <!-- Active filter summary -->
    {#if hasActiveFilters && currentView === "list"}
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

    <!-- Summary Cards -->
    {#if currentView !== "list" && items.length > 0}
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
            {#if currentView === "drop_point"}
                {#each groupedByDropPoint() as group}
                    <button
                        class="text-left bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 flex items-center gap-4 transition-all hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md group"
                        onclick={() => {
                            const el = document.getElementById(
                                `group-${group.drop_point?.id ?? "unknown"}`,
                            );
                            el?.scrollIntoView({
                                behavior: "smooth",
                                block: "start",
                            });
                        }}
                    >
                        <div
                            class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 transition-colors"
                        >
                            <i class="fa-solid fa-location-dot text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white truncate"
                            >
                                {group.drop_point?.name ?? "Tanpa Drop Point"}
                            </h3>
                            <p
                                class="text-xs text-blue-600 dark:text-blue-400 font-bold mt-1"
                            >
                                {group.orders.length} Pesanan
                            </p>
                        </div>
                    </button>
                {/each}
            {:else if currentView === "kitchen"}
                {#each groupedByKitchen() as group}
                    <button
                        class="text-left bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 flex items-center gap-4 transition-all hover:border-purple-500 dark:hover:border-purple-400 hover:shadow-md group"
                        onclick={() => {
                            const el = document.getElementById(
                                `group-${group.chef?.id ?? "unknown"}`,
                            );
                            el?.scrollIntoView({
                                behavior: "smooth",
                                block: "start",
                            });
                        }}
                    >
                        <div
                            class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/30 transition-colors"
                        >
                            <i class="fa-solid fa-fire-burner text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white truncate"
                            >
                                {group.chef?.name ?? "Tanpa Dapur/Chef"}
                            </h3>
                            <p
                                class="text-xs text-purple-600 dark:text-purple-400 font-bold mt-1"
                            >
                                {group.orders.length} Pesanan
                            </p>
                        </div>
                    </button>
                {/each}
            {/if}
        </div>
    {/if}

    <!-- Table View -->
    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800"
    >
        {#if items.length > 0}
            {#if currentView === "list"}
                {#each groupedItems() as [dateKey, groupOrders]}
                    <!-- Group header by Date -->
                    <div
                        class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2"
                    >
                        <i
                            class="fa-solid fa-calendar-day text-gray-400 text-xs"
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
                    {@render orderTable(groupOrders)}
                {/each}
            {:else if currentView === "drop_point"}
                {#each groupedByDropPoint() as group}
                    <!-- Group header by Drop Point -->
                    <div
                        id={`group-${group.drop_point?.id ?? "unknown"}`}
                        class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3 scroll-mt-20"
                    >
                        <div
                            class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400"
                        >
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <span
                                class="text-base font-bold text-gray-900 dark:text-white block"
                            >
                                {group.drop_point?.name ?? "Tanpa Drop Point"}
                            </span>
                            {#if group.drop_point?.address}
                                <span class="text-xs text-gray-500 line-clamp-1"
                                    >{group.drop_point.address}</span
                                >
                            {/if}
                        </div>
                        <span class="ml-auto text-xs text-gray-400 font-medium">
                            <span
                                class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300"
                            >
                                {group.orders.length} pesanan
                            </span>
                        </span>
                    </div>
                    {@render orderTable(group.orders)}
                {/each}
            {:else if currentView === "kitchen"}
                {#each groupedByKitchen() as group}
                    <!-- Group header by Kitchen -->
                    <div
                        id={`group-${group.chef?.id ?? "unknown"}`}
                        class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3 scroll-mt-20"
                    >
                        <div
                            class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400"
                        >
                            <i class="fa-solid fa-fire-burner"></i>
                        </div>
                        <div>
                            <span
                                class="text-base font-bold text-gray-900 dark:text-white block"
                            >
                                {group.chef?.name ?? "Tanpa Dapur/Chef"}
                            </span>
                            {#if group.chef?.business_name}
                                <span class="text-xs text-gray-500"
                                    >{group.chef.business_name}</span
                                >
                            {/if}
                        </div>
                        <span class="ml-auto text-xs text-gray-400 font-medium">
                            <span
                                class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300"
                            >
                                {group.orders.length} pesanan
                            </span>
                        </span>
                    </div>
                    {@render orderTable(group.orders)}
                {/each}
            {/if}
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
