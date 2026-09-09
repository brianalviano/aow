<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

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

    interface OrderOption {
        id: string;
        product_option_item?: {
            id: string;
            name: string;
        };
    }

    interface OrderItem {
        id: string;
        product?: {
            id: string;
            name: string;
        };
        quantity: number;
        options?: OrderOption[];
    }

    interface Order {
        id: string;
        number: string;
        delivery_date: string | null;
        delivery_time: string | null;
        order_status: string;
        payment_status: string;
        payment_proof_url?: string | null;
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
                  search?: string;
                  drop_point_id?: string;
                  delivery_date?: string;
                  status?: string;
                  payment_status?: string;
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
    let searchFilter = $state(untrack(() => filters?.search || ""));
    let dropPointFilter = $state(untrack(() => filters?.drop_point_id || ""));
    let deliveryDateFilter = $state(
        untrack(() => filters?.delivery_date || ""),
    );
    let statusFilter = $state(untrack(() => filters?.status || "all"));
    let paymentStatusFilter = $state(
        untrack(() => filters?.payment_status || "all"),
    );

    let hasActiveFilters = $derived(
        !!searchFilter.trim() ||
            !!dropPointFilter ||
            !!deliveryDateFilter ||
            statusFilter !== "all" ||
            paymentStatusFilter !== "all",
    );

    function applyFilters(pageNumber = 1) {
        const params: Record<string, string> = { page: String(pageNumber) };
        if (searchFilter.trim()) params.search = searchFilter.trim();
        if (dropPointFilter) params.drop_point_id = dropPointFilter;
        if (deliveryDateFilter) params.delivery_date = deliveryDateFilter;
        if (statusFilter && statusFilter !== "all") params.status = statusFilter;
        if (paymentStatusFilter && paymentStatusFilter !== "all") {
            params.payment_status = paymentStatusFilter;
        }
        if (currentView !== "list") params.view = currentView;

        router.get("/admin/orders/processing", params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const debouncedApplyFilters = debounce(() => {
        applyFilters(1);
    }, 400);

    $effect(() => {
        const _s = searchFilter;
        const _dp = dropPointFilter;
        const _dd = deliveryDateFilter;
        const _st = statusFilter;
        const _ps = paymentStatusFilter;
        const _v = currentView;

        untrack(() => {
            debouncedApplyFilters();
        });
    });

    function switchView(view: string) {
        currentView = view;
    }

    function resetFilters() {
        searchFilter = "";
        dropPointFilter = "";
        deliveryDateFilter = "";
        statusFilter = "all";
        paymentStatusFilter = "all";
        applyFilters(1);
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

    let isMediaViewerOpen = $state(false);
    let mediaViewerItems = $state<string | string[]>([]);
    let mediaViewerInitialIndex = $state(0);

    function openMediaViewer(items: string | string[], index: number = 0) {
        mediaViewerItems = items;
        mediaViewerInitialIndex = index;
        isMediaViewerOpen = true;
    }

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

    function handleConfirmOrder(order: Order) {
        if (order.order_status === "confirmed") return;
        openConfirm(
            "Konfirmasi Pesanan",
            `Ubah status pesanan #${order.number} menjadi 'Dikonfirmasi'?`,
            () => {
                isProcessing = true;
                router.post(
                    `/admin/orders/${order.id}/confirm`,
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

    function handleCookOrder(order: Order) {
        if (order.order_status === "cooking") return;
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
        if (order.order_status === "on_delivery") return;
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
        if (order.order_status === "delivered") return;
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

    function handleCancelOrder(order: Order) {
        if (order.order_status === "cancelled" || order.order_status === "delivered") return;
        openConfirm(
            "Batalkan Pesanan",
            `Apakah Anda yakin ingin membatalkan pesanan #${order.number}?`,
            () => {
                isProcessing = true;
                router.post(
                    `/admin/orders/${order.id}/cancel`,
                    { cancellation_note: "Dibatalkan oleh Admin di Pesanan Diproses" },
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            isProcessing = false;
                        },
                    },
                );
            },
            "danger",
        );
    }

    const ORDER_LIFECYCLE_STEPS = [
        {
            key: "pending",
            label: "1. Menunggu",
            title: "Verifikasi Pembayaran",
            icon: "fa-solid fa-clock",
        },
        {
            key: "confirmed",
            label: "2. Dikonfirmasi",
            title: "Masuk Antrean Dapur",
            icon: "fa-solid fa-clipboard-check",
        },
        {
            key: "cooking",
            label: "3. Sedang Dimasak",
            title: "Proses Dapur Sentral",
            icon: "fa-solid fa-fire-burner",
        },
        {
            key: "on_delivery",
            label: "4. Sedang Dikirim",
            title: "Pengantaran Kurir",
            icon: "fa-solid fa-truck-fast",
        },
        {
            key: "delivered",
            label: "5. Diterima",
            title: "Pesanan Selesai",
            icon: "fa-solid fa-circle-check",
        },
    ] as const;

    const stepOrderKeys = [
        "pending",
        "confirmed",
        "cooking",
        "on_delivery",
        "delivered",
    ];

    function getStepIndex(status: string): number {
        if (status === "cancelled") return -1;
        if (status === "arrived" || status === "delivered") return 4;
        return stepOrderKeys.indexOf(status);
    }

    function handleStepClick(order: Order, stepKey: string) {
        if (order.order_status === "cancelled" || isProcessing) return;
        if (order.order_status === stepKey) return;

        switch (stepKey) {
            case "confirmed":
                handleConfirmOrder(order);
                break;
            case "cooking":
                handleCookOrder(order);
                break;
            case "on_delivery":
                handleShipOrder(order);
                break;
            case "delivered":
                handleDeliverOrder(order);
                break;
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
            return b.localeCompare(a);
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

    function getTodayString(): string {
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, "0");
        const d = String(today.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    }

    function getTomorrowString(): string {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const y = tomorrow.getFullYear();
        const m = String(tomorrow.getMonth() + 1).padStart(2, "0");
        const d = String(tomorrow.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    }

    let isTodaySelected = $derived(deliveryDateFilter === getTodayString());
    let isTomorrowSelected = $derived(deliveryDateFilter === getTomorrowString());
    let isAllDatesSelected = $derived(!deliveryDateFilter);

    const statusOptions = [
        { value: "all", label: "Semua Tahap Alur" },
        { value: "pending", label: "1. Menunggu (Verifikasi Bayar)" },
        { value: "confirmed", label: "2. Dikonfirmasi (Antrean Dapur)" },
        { value: "cooking", label: "3. Sedang Dimasak" },
        { value: "on_delivery", label: "4. Sedang Dikirim" },
    ];

    const paymentStatusOptions = [
        { value: "all", label: "Semua Pembayaran" },
        { value: "paid", label: "Lunas" },
        { value: "pending", label: "Belum Bayar" },
    ];

    function getPaymentBadge(status: string): {
        variant: "warning" | "success" | "danger" | "info" | "secondary";
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

{#snippet orderTable(orderList: Order[])}
    <div class="overflow-x-auto">
        <table class="custom-table min-w-full">
            <thead>
                <tr>
                    <th class="whitespace-nowrap">No. Pesanan</th>
                    <th class="whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5" title="Jam Pengantaran / Minta Dikirim">
                            <i class="fa-regular fa-clock text-xs text-amber-500"></i>
                            Jam Kirim
                        </span>
                    </th>
                    <th>Customer</th>
                    <th>Drop Point</th>
                    <th>Total</th>
                    <th class="text-center whitespace-nowrap">Bukti Bayar</th>
                    <th class="min-w-[620px] text-center">
                        <span class="inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-arrows-split-up-and-left text-xs text-blue-500"></i>
                            Alur Proses Pesanan (Klik Tombol untuk Ubah Status)
                        </span>
                    </th>
                    <th class="w-28 text-center whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {#each orderList as item}
                    {@const currentStepIdx = getStepIndex(item.order_status)}
                    {@const paymentBadge = getPaymentBadge(item.payment_status)}
                    <tr
                        class={item.order_status === "cancelled"
                            ? "bg-gray-100 dark:bg-gray-800/60 opacity-60 hover:opacity-100 transition-opacity"
                            : "hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"}
                    >
                        <td class="font-medium text-gray-900 dark:text-white whitespace-nowrap">
                            <a
                                href={`/admin/orders/${item.id}?from=processing`}
                                class="hover:text-blue-600 hover:underline inline-flex items-center gap-1"
                                title="Lihat Detail Pesanan"
                            >
                                {item.number}
                            </a>
                        </td>
                        <td
                            class="text-sm font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap"
                        >
                            {#if item.delivery_time}
                                <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold">
                                    <i class="fa-regular fa-clock text-xs"></i>
                                    {item.delivery_time.substring(0, 5)} WIB
                                </span>
                            {:else}
                                <span class="text-xs text-gray-400 italic">Secepatnya</span>
                            {/if}
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
                                class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap"
                            >
                                {item.drop_point?.name ?? "-"}
                            </div>
                        </td>
                        <td>
                            <div
                                class="text-sm font-bold text-gray-900 dark:text-white whitespace-nowrap"
                            >
                                {formatCurrency(item.total_amount)}
                            </div>
                            <div class="mt-1">
                                <Badge
                                    size="xs"
                                    rounded="pill"
                                    variant={paymentBadge.variant}
                                >
                                    {#snippet children()}{paymentBadge.label}{/snippet}
                                </Badge>
                            </div>
                        </td>
                        <td class="text-center px-3 py-2 whitespace-nowrap">
                            {#if item.payment_proof_url}
                                <button
                                    type="button"
                                    class="group relative inline-flex items-center justify-center cursor-pointer overflow-hidden rounded-lg border-2 border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-400 transition-all shadow-2xs hover:shadow-md"
                                    onclick={() => openMediaViewer(item.payment_proof_url!)}
                                    title="Klik untuk melihat bukti pembayaran"
                                >
                                    <img
                                        src={item.payment_proof_url}
                                        alt="Bukti Bayar #{item.number}"
                                        class="w-10 h-10 object-cover group-hover:scale-110 transition-transform duration-200"
                                    />
                                    <div
                                        class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity"
                                    >
                                        <i class="fa-solid fa-magnifying-glass-plus text-white text-xs"></i>
                                    </div>
                                </button>
                            {:else}
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">
                                    Belum ada
                                </span>
                            {/if}
                        </td>
                        <td class="py-2.5 px-3">
                            {#if item.order_status === "cancelled"}
                                <div class="flex items-center justify-center gap-2 py-2 px-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40 rounded-xl text-red-600 dark:text-red-400">
                                    <i class="fa-solid fa-ban text-sm"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Pesanan Dibatalkan</span>
                                </div>
                            {:else}
                                <div class="relative py-1 px-1 min-w-[620px]">
                                    <div class="grid grid-cols-5 gap-1.5 relative items-center">
                                        {#each ORDER_LIFECYCLE_STEPS as step, index}
                                            {@const isPassed = currentStepIdx > index}
                                            {@const isCurrent = currentStepIdx === index}
                                            {@const isUpcoming = currentStepIdx < index}
                                            {@const isClickable = !isCurrent && step.key !== "pending" && item.order_status !== "delivered"}

                                            <div class="relative flex flex-col items-center">
                                                <!-- Connecting line to next step -->
                                                {#if index < ORDER_LIFECYCLE_STEPS.length - 1}
                                                    <div
                                                        class="hidden sm:block absolute top-[24px] left-1/2 w-full h-[2px] z-0 pointer-events-none {isPassed
                                                            ? 'bg-emerald-500'
                                                            : isCurrent
                                                              ? 'bg-gradient-to-r from-amber-500 to-gray-200 dark:to-gray-700'
                                                              : 'bg-gray-200 dark:bg-gray-700'}"
                                                    ></div>
                                                {/if}

                                                <!-- Button / Card -->
                                                <button
                                                    type="button"
                                                    disabled={!isClickable || isProcessing}
                                                    onclick={() => handleStepClick(item, step.key)}
                                                    class="w-full flex flex-col items-center text-center relative z-10 p-2 rounded-xl transition-all duration-200 {isCurrent
                                                        ? 'bg-amber-500/10 dark:bg-amber-500/20 border-2 border-amber-500 shadow-sm ring-2 ring-amber-500/20 cursor-default'
                                                        : isClickable
                                                          ? 'hover:bg-blue-50/80 dark:hover:bg-blue-950/40 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-sm cursor-pointer border border-transparent group'
                                                          : 'opacity-70 cursor-default border border-transparent'}"
                                                    title={isCurrent
                                                        ? `Tahap saat ini: ${step.label} (${step.title})`
                                                        : isClickable
                                                          ? `Klik untuk ubah status ke: ${step.label} (${step.title})`
                                                          : `${step.label} (${step.title})`}
                                                >
                                                    <!-- Node Circle -->
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full transition-all duration-200 {isPassed
                                                            ? 'bg-emerald-500 text-white shadow-sm ring-2 ring-emerald-500/20'
                                                            : isCurrent
                                                              ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/30 ring-4 ring-amber-500/30 scale-105'
                                                              : 'border border-gray-300 bg-gray-100 text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 group-hover:border-blue-400 group-hover:text-blue-500 group-hover:bg-blue-50'}"
                                                    >
                                                        {#if isPassed}
                                                            <i class="fa-solid fa-check text-xs"></i>
                                                        {:else}
                                                            <i class="{step.icon} text-xs"></i>
                                                        {/if}
                                                    </div>

                                                    <!-- Step Label & Subtitle -->
                                                    <div class="mt-1.5 space-y-0.5 pointer-events-none">
                                                        <div
                                                            class="text-[11px] font-bold leading-tight whitespace-nowrap transition-colors {isPassed
                                                                ? 'text-emerald-700 dark:text-emerald-400'
                                                                : isCurrent
                                                                  ? 'text-amber-700 dark:text-amber-400 font-extrabold'
                                                                  : 'text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400'}"
                                                        >
                                                            {step.label}
                                                        </div>
                                                        <div
                                                            class="text-[9px] text-gray-500 dark:text-gray-400 leading-tight whitespace-nowrap"
                                                        >
                                                            {step.title}
                                                        </div>
                                                        {#if isCurrent}
                                                            <div class="inline-flex items-center gap-1 text-[8px] font-bold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5 rounded-full mt-0.5">
                                                                <span class="w-1 h-1 rounded-full bg-amber-500 animate-ping"></span>
                                                                Aktif
                                                            </div>
                                                        {:else if isUpcoming && isClickable}
                                                            <div class="text-[8px] text-blue-600 dark:text-blue-400 font-semibold opacity-0 group-hover:opacity-100 transition-opacity mt-0.5">
                                                                Pilih &rarr;
                                                            </div>
                                                        {/if}
                                                    </div>
                                                </button>
                                            </div>
                                        {/each}
                                    </div>
                                </div>
                            {/if}
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap text-center">
                            <div class="flex gap-1.5 items-center justify-center">
                                <!-- Detail Button -->
                                <Button
                                    variant="secondary"
                                    size="xs"
                                    icon="fa-solid fa-eye"
                                    href={`/admin/orders/${item.id}?from=processing`}
                                    title="Lihat Detail Pesanan"
                                >
                                    {#snippet children()}Detail{/snippet}
                                </Button>

                                <!-- Batalkan Button -->
                                {#if item.order_status !== "cancelled" && item.order_status !== "delivered"}
                                    <Button
                                        variant="outline-danger"
                                        size="xs"
                                        icon="fa-solid fa-xmark"
                                        disabled={isProcessing}
                                        onclick={() => handleCancelOrder(item)}
                                        title="Batalkan Pesanan"
                                    />
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {#if item.items && item.items.length > 0}
                        <tr
                            class="{item.order_status === 'cancelled'
                                ? 'bg-gray-100/90 dark:bg-gray-800/40 opacity-60 hover:opacity-100 transition-opacity'
                                : 'bg-slate-50/75 dark:bg-slate-900/50'} border-b border-gray-200/90 dark:border-gray-800"
                        >
                            <td colspan="8" class="py-2.5 px-4">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <div
                                        class="flex items-center gap-1.5 text-xs font-bold shrink-0 {item.order_status ===
                                        'cancelled'
                                            ? 'text-gray-400 dark:text-gray-500'
                                            : 'text-slate-500 dark:text-slate-400'}"
                                    >
                                        <i
                                            class="fa-solid fa-utensils {item.order_status ===
                                            'cancelled'
                                                ? 'text-gray-400 dark:text-gray-500'
                                                : 'text-indigo-500'} text-xs"
                                        ></i>
                                        <span>Menu Dipesan:</span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap flex-1">
                                        {#each item.items as orderItem}
                                            <div
                                                class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg {item.order_status ===
                                                'cancelled'
                                                    ? 'bg-gray-200/60 dark:bg-gray-800/80 border border-gray-300 dark:border-gray-700 text-gray-500'
                                                    : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700'} shadow-2xs text-xs"
                                            >
                                                <span
                                                    class="font-bold {item.order_status ===
                                                    'cancelled'
                                                        ? 'text-gray-500 dark:text-gray-400 line-through'
                                                        : 'text-gray-800 dark:text-gray-100'}"
                                                >
                                                    {orderItem.product?.name ?? "Produk"}
                                                </span>
                                                {#if orderItem.options && orderItem.options.length > 0}
                                                    <div class="flex items-center gap-1">
                                                        {#each orderItem.options as opt}
                                                            {#if opt.product_option_item?.name}
                                                                <span
                                                                    class="inline-flex items-center px-1.5 py-0.2 rounded font-bold text-[10px] {item.order_status ===
                                                                    'cancelled'
                                                                        ? 'bg-gray-200/90 text-gray-500 dark:bg-gray-700 dark:text-gray-400 border border-gray-300 dark:border-gray-600'
                                                                        : opt.product_option_item.name
                                                                                .toLowerCase()
                                                                                .includes('besar')
                                                                          ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800'
                                                                          : opt.product_option_item.name
                                                                                  .toLowerCase()
                                                                                  .includes('pedas')
                                                                            ? 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border border-orange-200 dark:border-orange-800'
                                                                            : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700'}"
                                                                >
                                                                    {opt.product_option_item.name}
                                                                </span>
                                                            {/if}
                                                        {/each}
                                                    </div>
                                                {/if}
                                                <span
                                                    class="font-black {item.order_status ===
                                                    'cancelled'
                                                        ? 'text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-700/80'
                                                        : 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80'} px-1.5 py-0.2 rounded text-[11px]"
                                                >
                                                    {orderItem.quantity}x
                                                </span>
                                            </div>
                                        {/each}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    {/if}
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
            class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 space-y-3"
        >
            <!-- Baris 1: Pencarian & Shortcut Tanggal Kirim -->
            <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <div class="flex-1 max-w-md">
                    <TextInput
                        id="searchFilter"
                        name="searchFilter"
                        bind:value={searchFilter}
                        placeholder="Cari no. pesanan, customer, telepon, menu..."
                        icon="fa-solid fa-magnifying-glass"
                    />
                </div>

                <!-- Shortcut Tanggal Kirim -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">
                        <i class="fa-regular fa-calendar-days mr-1 text-blue-500"></i>
                        Target Kirim:
                    </span>
                    <button
                        type="button"
                        onclick={() => (deliveryDateFilter = "")}
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all cursor-pointer {isAllDatesSelected
                            ? 'bg-blue-600 text-white shadow-xs font-semibold'
                            : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'}"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        onclick={() => (deliveryDateFilter = getTodayString())}
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all cursor-pointer {isTodaySelected
                            ? 'bg-blue-600 text-white shadow-xs font-semibold'
                            : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'}"
                    >
                        Hari Ini
                    </button>
                    <button
                        type="button"
                        onclick={() => (deliveryDateFilter = getTomorrowString())}
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all cursor-pointer {isTomorrowSelected
                            ? 'bg-blue-600 text-white shadow-xs font-semibold'
                            : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'}"
                    >
                        Besok
                    </button>
                </div>
            </div>

            <!-- Baris 2: Dropdown Filter Spesifik -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end pt-2 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <DateInput
                        label="Pilih Tanggal Spesifik"
                        id="delivery_date"
                        name="delivery_date"
                        bind:value={deliveryDateFilter}
                        placeholder="Pilih tanggal kirim"
                    />
                </div>

                <div>
                    <Select
                        label="Drop Point"
                        id="drop_point_id"
                        name="drop_point_id"
                        options={dropPointOptions}
                        bind:value={dropPointFilter}
                    />
                </div>

                <div>
                    <Select
                        label="Tahap Alur Proses"
                        id="order_status"
                        name="order_status"
                        options={statusOptions}
                        bind:value={statusFilter}
                    />
                </div>

                <div>
                    <Select
                        label="Status Pembayaran"
                        id="payment_status"
                        name="payment_status"
                        options={paymentStatusOptions}
                        bind:value={paymentStatusFilter}
                    />
                </div>
            </div>

            <!-- Reset Filter Button -->
            {#if hasActiveFilters}
                <div class="flex justify-end pt-1">
                    <Button
                        variant="secondary"
                        size="xs"
                        icon="fa-solid fa-rotate-left"
                        onclick={resetFilters}
                    >
                        {#snippet children()}Reset Filter{/snippet}
                    </Button>
                </div>
            {/if}
        </div>
    {/if}

    <!-- Active filter summary -->
    {#if hasActiveFilters && currentView === "list"}
        <div
            class="flex flex-wrap gap-2 items-center text-sm text-gray-600 dark:text-gray-400"
        >
            <span class="font-medium text-xs">Filter aktif:</span>
            {#if searchFilter.trim()}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-xs font-medium"
                >
                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                    "{searchFilter.trim()}"
                    <button
                        type="button"
                        class="hover:text-blue-900 dark:hover:text-blue-100 ml-1 cursor-pointer font-bold"
                        onclick={() => (searchFilter = "")}
                    >
                        &times;
                    </button>
                </span>
            {/if}
            {#if deliveryDateFilter}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-xs font-medium"
                >
                    <i class="fa-solid fa-calendar text-[10px]"></i>
                    {isTodaySelected
                        ? "Hari Ini"
                        : isTomorrowSelected
                          ? "Besok"
                          : new Date(deliveryDateFilter).toLocaleDateString(
                                "id-ID",
                                {
                                    day: "numeric",
                                    month: "short",
                                    year: "numeric",
                                },
                            )}
                    <button
                        type="button"
                        class="hover:text-green-900 dark:hover:text-green-100 ml-1 cursor-pointer font-bold"
                        onclick={() => (deliveryDateFilter = "")}
                    >
                        &times;
                    </button>
                </span>
            {/if}
            {#if dropPointFilter}
                {@const dp = dropPoints.find((d) => d.id === dropPointFilter)}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-xs font-medium"
                >
                    <i class="fa-solid fa-location-dot text-[10px]"></i>
                    {dp?.name ?? dropPointFilter}
                    <button
                        type="button"
                        class="hover:text-purple-900 dark:hover:text-purple-100 ml-1 cursor-pointer font-bold"
                        onclick={() => (dropPointFilter = "")}
                    >
                        &times;
                    </button>
                </span>
            {/if}
            {#if statusFilter !== "all"}
                {@const st = statusOptions.find((s) => s.value === statusFilter)}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-xs font-medium"
                >
                    <i class="fa-solid fa-arrows-split-up-and-left text-[10px]"></i>
                    {st?.label ?? statusFilter}
                    <button
                        type="button"
                        class="hover:text-amber-900 dark:hover:text-amber-100 ml-1 cursor-pointer font-bold"
                        onclick={() => (statusFilter = "all")}
                    >
                        &times;
                    </button>
                </span>
            {/if}
            {#if paymentStatusFilter !== "all"}
                {@const ps = paymentStatusOptions.find(
                    (p) => p.value === paymentStatusFilter,
                )}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full text-xs font-medium"
                >
                    <i class="fa-solid fa-wallet text-[10px]"></i>
                    {ps?.label ?? paymentStatusFilter}
                    <button
                        type="button"
                        class="hover:text-emerald-900 dark:hover:text-emerald-100 ml-1 cursor-pointer font-bold"
                        onclick={() => (paymentStatusFilter = "all")}
                    >
                        &times;
                    </button>
                </span>
            {/if}
            <Button
                variant="light"
                size="xs"
                onclick={resetFilters}
            >
                {#snippet children()}Hapus Semua{/snippet}
            </Button>
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

<MediaViewer
    bind:isOpen={isMediaViewerOpen}
    items={mediaViewerItems}
    initialIndex={mediaViewerInitialIndex}
/>
