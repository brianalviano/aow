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

    interface Customer {
        id: string;
        name: string;
        email: string;
    }

    interface OrderItem {
        id: string;
        product?: {
            id: string;
            name: string;
        };
        quantity: number;
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
        items?: OrderItem[];
    }

    let orders = $derived(page.props.orders as { data: Order[]; meta?: any });

    let filters = $derived(
        page.props.filters as
            | {
                  drop_point_id?: string;
                  delivery_date?: string;
                  view?: string;
              }
            | undefined,
    );

    let dropPoints = $derived((page.props.dropPoints as DropPoint[]) ?? []);

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
    let deliveryDateFilter = $state(
        untrack(() => filters?.delivery_date || ""),
    );

    let hasActiveFilters = $derived(
        !!dropPointFilter || !!deliveryDateFilter,
    );

    $effect(() => {
        const _dp = dropPointFilter;
        const _dd = deliveryDateFilter;
        const _v = currentView;

        untrack(() => applyFilters(1));
    });

    function applyFilters(pageNumber = 1) {
        const params: Record<string, string> = { page: String(pageNumber) };
        if (dropPointFilter) params.drop_point_id = dropPointFilter;
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
    }

    function resetFilters() {
        dropPointFilter = "";
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

    interface ConfirmDialog {
        open: boolean;
        title: string;
        message: string;
        action: (() => void) | null;
        variant: "danger" | "primary" | "success" | "warning";
    }

    let confirmDialog = $state<ConfirmDialog>({
        open: false,
        title: "",
        message: "",
        action: null,
        variant: "primary",
    });

    let isProcessing = $state(false);

    function openConfirm(
        title: string,
        message: string,
        action: () => void,
        variant: ConfirmDialog["variant"] = "primary",
    ) {
        confirmDialog = { open: true, title, message, action, variant };
    }

    function closeConfirm() {
        confirmDialog = { ...confirmDialog, open: false, action: null };
    }

    function executeAction() {
        if (!confirmDialog.action) return;
        isProcessing = true;
        confirmDialog.action();
        closeConfirm();
    }

    function handleCookOrder(order: Order) {
        openConfirm(
            "Mulai Memasak",
            `Ubah status pesanan #${order.number} menjadi 'Sedang Dimasak'?`,
            () => {
                isProcessing = true;
                router.post(
                    `/admin/orders/${order.id}/cook`,
                    {},
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            isProcessing = false;
                        },
                    },
                );
            },
            "warning",
        );
    }

    function handleShipOrder(order: Order) {
        openConfirm(
            "Kirim Pesanan",
            `Ubah status pesanan #${order.number} menjadi 'Sedang Dikirim'?`,
            () => {
                isProcessing = true;
                router.post(
                    `/admin/orders/${order.id}/ship`,
                    {},
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            isProcessing = false;
                        },
                    },
                );
            },
            "primary",
        );
    }

    function handleDeliverOrder(order: Order) {
        openConfirm(
            "Selesaikan Pesanan",
            `Tandai pesanan #${order.number} sebagai 'Diterima' (Pesanan Selesai)?`,
            () => {
                isProcessing = true;
                router.post(
                    `/admin/orders/${order.id}/deliver`,
                    {},
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            isProcessing = false;
                        },
                    },
                );
            },
            "success",
        );
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
            case "cooking":
                return { variant: "warning", label: "Sedang Dimasak" };
            case "on_delivery":
                return { variant: "primary", label: "Sedang Dikirim" };
            case "arrived":
            case "delivered":
                return { variant: "success", label: "Diterima (Selesai)" };
            default:
                return { variant: "secondary", label: status };
        }
    }

    // Group orders by delivery_date
    let groupedItems = $derived(() => {
        const groups: Record<string, Order[]> = {};
        for (const order of items) {
            const key = order.delivery_date ?? "Tanpa Tanggal";
            if (!groups[key]) groups[key] = [];
            groups[key].push(order);
        }
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

    function formatDeliveryDate(dateStr: string) {
        if (dateStr === "Tanpa Tanggal") return dateStr;
        return new Date(dateStr).toLocaleDateString("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    }

    let dropPointOptions = $derived([
        { value: "", label: "Semua Drop Point" },
        ...dropPoints.map((dp) => ({ value: dp.id, label: dp.name })),
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
                    <th>Total</th>
                    <th>Status</th>
                    <th class="w-48 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {#each orderList as item}
                    {@const statusBadge = getStatusBadge(item.order_status)}
                    <tr
                        class={item.order_status === "cancelled"
                            ? "bg-gray-100 dark:bg-gray-800/60 opacity-60 hover:opacity-100 transition-opacity"
                            : "hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"}
                    >
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
                                            (i) =>
                                                `${i.product?.name ?? "Produk"} x${i.quantity}`,
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
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex gap-1.5 items-center justify-center">
                                <Button
                                    variant="primary"
                                    size="xs"
                                    icon="fa-solid fa-eye"
                                    href={`/admin/orders/${item.id}?from=processing`}
                                    title="Lihat Detail"
                                />

                                {#if item.order_status === "confirmed"}
                                    <Button
                                        variant="warning"
                                        size="xs"
                                        icon="fa-solid fa-fire-burner"
                                        disabled={isProcessing}
                                        onclick={() => handleCookOrder(item)}
                                        title="Mulai Memasak"
                                    />
                                    <Button
                                        variant="info"
                                        size="xs"
                                        icon="fa-solid fa-truck-fast"
                                        disabled={isProcessing}
                                        onclick={() => handleShipOrder(item)}
                                        title="Kirim Pesanan"
                                    />
                                {/if}

                                {#if item.order_status === "cooking"}
                                    <Button
                                        variant="info"
                                        size="xs"
                                        icon="fa-solid fa-truck-fast"
                                        disabled={isProcessing}
                                        onclick={() => handleShipOrder(item)}
                                        title="Kirim Pesanan"
                                    />
                                {/if}

                                {#if item.order_status === "on_delivery" || item.order_status === "arrived"}
                                    <Button
                                        variant="success"
                                        size="xs"
                                        icon="fa-solid fa-circle-check"
                                        disabled={isProcessing}
                                        onclick={() => handleDeliverOrder(item)}
                                        title="Tandai Diterima / Selesai"
                                    />
                                {/if}
                            </div>
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/snippet}

<svelte:head>
    <title>Pesanan Diproses | {name(page.props.settings)}</title>
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
    {#if currentView === "drop_point" && items.length > 0}
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
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
        </div>
    {/if}

    <!-- Table View -->
    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800"
    >
        {#if items.length > 0}
            {#if currentView === "list"}
                {#each groupedItems() as [dateKey, groupOrders]}
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

<!-- Confirm Dialog -->
{#if confirmDialog.open}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {confirmDialog.variant ===
                    'danger'
                        ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                        : confirmDialog.variant === 'warning'
                          ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400'
                          : confirmDialog.variant === 'success'
                            ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'}"
                >
                    <i
                        class="fa-solid {confirmDialog.variant === 'danger'
                            ? 'fa-triangle-exclamation'
                            : confirmDialog.variant === 'warning'
                              ? 'fa-fire-burner'
                              : confirmDialog.variant === 'success'
                                ? 'fa-circle-check'
                                : 'fa-circle-info'}"
                    ></i>
                </div>
                <div class="flex-1">
                    <h3
                        id="confirm-dialog-title"
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        {confirmDialog.title}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {confirmDialog.message}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Button
                    variant="secondary"
                    size="sm"
                    onclick={closeConfirm}
                    disabled={isProcessing}
                >
                    {#snippet children()}Batal{/snippet}
                </Button>
                <Button
                    variant={confirmDialog.variant}
                    size="sm"
                    disabled={isProcessing}
                    onclick={executeAction}
                >
                    {#snippet children()}
                        {#if isProcessing}
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memproses...
                        {:else}
                            Ya, Lanjutkan
                        {/if}
                    {/snippet}
                </Button>
            </div>
        </div>
    </div>
{/if}
