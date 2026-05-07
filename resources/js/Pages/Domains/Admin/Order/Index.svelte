<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface Customer {
        id: string;
        name: string;
        email: string;
    }

    interface Order {
        id: string;
        number: string;
        customer_id: string;
        delivery_date: string;
        delivery_time?: string;
        order_status: string;
        payment_status: string;
        total_amount: number;
        created_at: string;
        customer?: Customer;
    }

    let orders = $derived(
        $page.props.orders as {
            data: Order[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as
            | {
                  search?: string;
                  status?: string;
                  date_range?: string;
                  start_date?: string;
                  end_date?: string;
                  drop_point_id?: string;
                  chef_id?: string;
                  delivery_date?: string;
              }
            | undefined,
    );

    let dropPoints = $derived(($page.props.dropPoints as any[]) || []);
    let chefs = $derived(($page.props.chefs as any[]) || []);

    let statusCounts = $derived(
        ($page.props.status_counts as Record<string, number>) || {},
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));
    let statusFilter = $state(untrack(() => filters?.status || "all"));
    let dateRange = $state(untrack(() => filters?.date_range || "all"));
    let startDate = $state(untrack(() => filters?.start_date || ""));
    let endDate = $state(untrack(() => filters?.end_date || ""));
    let dropPointId = $state(untrack(() => filters?.drop_point_id || ""));
    let chefId = $state(untrack(() => filters?.chef_id || ""));
    let deliveryDate = $state(untrack(() => filters?.delivery_date || ""));

    let isFilterExpanded = $state(false);

    let meta = $derived(
        orders?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(orders?.data ?? []);

    let orderTabs = $derived([
        { id: "all", label: "Semua", badge: statusCounts.all || 0 },
        {
            id: "unpaid",
            label: "Belum Bayar",
            badge: statusCounts.unpaid || 0,
            badgeVariant: "warning" as const,
        },
        {
            id: "process",
            label: "Diproses",
            badge: statusCounts.process || 0,
            badgeVariant: "primary" as const,
        },
        {
            id: "shipped",
            label: "Dikirim",
            badge: statusCounts.shipped || 0,
            badgeVariant: "info" as const,
        },
        {
            id: "completed",
            label: "Selesai",
            badge: statusCounts.completed || 0,
            badgeVariant: "success" as const,
        },
        {
            id: "cancelled",
            label: "Dibatalkan",
            badge: statusCounts.cancelled || 0,
            badgeVariant: "danger" as const,
        },
    ]);

    function goToPage(pageNumber: number) {
        const params = new URLSearchParams();
        const limit = meta.per_page || 15;
        params.set("page", String(pageNumber));
        params.set("limit", String(limit));

        if (searchQuery) {
            params.set("search", searchQuery);
        }
        if (statusFilter && statusFilter !== "all") {
            params.set("status", statusFilter);
        }
        if (dateRange && dateRange !== "all") {
            params.set("date_range", dateRange);
            if (dateRange === "custom") {
                if (startDate) params.set("start_date", startDate);
                if (endDate) params.set("end_date", endDate);
            }
        }
        if (dropPointId) {
            params.set("drop_point_id", dropPointId);
        }
        if (chefId) {
            params.set("chef_id", chefId);
        }
        if (deliveryDate) {
            params.set("delivery_date", deliveryDate);
        }

        router.get(
            "/admin/orders?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const handleSearch = debounce(() => {
        goToPage(1);
    }, 500);

    $effect(() => {
        if (
            searchQuery !== (filters?.search || "") ||
            statusFilter !== (filters?.status || "all") ||
            dateRange !== (filters?.date_range || "all") ||
            startDate !== (filters?.start_date || "") ||
            endDate !== (filters?.end_date || "") ||
            dropPointId !== (filters?.drop_point_id || "") ||
            chefId !== (filters?.chef_id || "") ||
            deliveryDate !== (filters?.delivery_date || "")
        ) {
            handleSearch();
        }
    });

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
            case "arrived":
                return { variant: "info", label: "Tiba di Tujuan" };
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

    function approveOrder(orderId: string) {
        router.post(
            `/admin/orders/${orderId}/confirm`,
            {},
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }

    function rejectOrder(orderId: string) {
        router.post(
            `/admin/orders/${orderId}/cancel`,
            { cancellation_note: "Ditolak oleh Admin" },
            {
                onFinish: () => {
                    isProcessing = false;
                },
            },
        );
    }
</script>

<svelte:head>
    <title>Pesanan | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Pesanan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola pesanan pelanggan dari berbagai dapur
            </p>
        </div>
    </header>
    <Tab
        tabs={orderTabs}
        bind:activeTab={statusFilter}
        variant="underline"
        onTabChange={() => goToPage(1)}
    />

    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800"
    >
        <div class="p-4 flex flex-col sm:flex-row items-center gap-4">
            <div class="flex-1 w-full">
                <TextInput
                    id="search"
                    name="search"
                    placeholder="Cari nomor pesanan atau customer..."
                    bind:value={searchQuery}
                    class="mb-0!"
                    icon="fa-solid fa-search"
                />
            </div>
            <div class="flex gap-2 shrink-0">
                <Button
                    variant="light"
                    size="sm"
                    icon="fa-solid fa-filter"
                    onclick={() => (isFilterExpanded = !isFilterExpanded)}
                >
                    Filter Lanjutan
                </Button>
                {#if searchQuery || statusFilter !== "all" || dateRange !== "all" || dropPointId || chefId || deliveryDate}
                    <Button
                        variant="secondary"
                        size="sm"
                        onclick={() => {
                            searchQuery = "";
                            statusFilter = "all";
                            dateRange = "all";
                            startDate = "";
                            endDate = "";
                            dropPointId = "";
                            chefId = "";
                            deliveryDate = "";
                            handleSearch();
                        }}
                    >
                        Reset Filter
                    </Button>
                {/if}
            </div>
        </div>

        {#if isFilterExpanded}
            <div
                class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 border-t border-gray-100 dark:border-gray-800 pt-4"
            >
                <Select
                    label="Rentang Tanggal"
                    bind:value={dateRange}
                    options={[
                        { value: "all", label: "Semua Waktu" },
                        { value: "30_days", label: "30 Hari Terakhir" },
                        { value: "90_days", label: "90 Hari Terakhir" },
                        { value: "custom", label: "Rentang Custom" },
                    ]}
                    placeholder="Pilih rentang..."
                />

                {#if dateRange === "custom"}
                    <DateInput
                        label="Tgl. Mulai"
                        bind:value={startDate}
                        placeholder="Mulai"
                    />
                    <DateInput
                        label="Tgl. Selesai"
                        bind:value={endDate}
                        placeholder="Selesai"
                    />
                {/if}

                <Select
                    label="Drop Point"
                    bind:value={dropPointId}
                    options={[
                        { value: "", label: "Semua Drop Point" },
                        ...dropPoints.map((dp) => ({
                            value: dp.id,
                            label: dp.name,
                        })),
                    ]}
                    placeholder="Pilih drop point..."
                    searchable={true}
                />

                <Select
                    label="Chef / Dapur"
                    bind:value={chefId}
                    options={[
                        { value: "", label: "Semua Chef" },
                        ...chefs.map((c) => ({
                            value: c.id,
                            label: c.name,
                        })),
                    ]}
                    placeholder="Pilih chef..."
                    searchable={true}
                />

                <DateInput
                    label="Tgl. Pengiriman"
                    bind:value={deliveryDate}
                    placeholder="Pilih tgl pengiriman"
                />
            </div>
        {/if}

        <div class="overflow-x-auto">
            <table class="custom-table min-w-full">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Tgl. Kirim</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status Pesanan</th>
                        <th>Status Dapur</th>
                        <th>Status Bayar</th>
                        <th>Bukti</th>
                        <th class="w-48 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#if items.length > 0}
                        {#each items as item}
                            {@const statusBadge = getStatusBadge(
                                item.order_status,
                            )}
                            {@const paymentBadge = getPaymentBadge(
                                item.payment_status,
                            )}
                            {@const chefStatus = (item as any)
                                .chef_status_summary}
                            <tr>
                                <td
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    <div>{item.number}</div>
                                    <div
                                        class="text-[10px] text-gray-500 font-normal mt-0.5"
                                    >
                                        {new Date(
                                            item.created_at,
                                        ).toLocaleDateString("id-ID", {
                                            day: "numeric",
                                            month: "short",
                                            year: "numeric",
                                        })}
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
                                    {#if (item as any).items && (item as any).items.length > 0}
                                        <div
                                            class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium italic border-t border-gray-100 dark:border-gray-800 pt-1"
                                        >
                                            {(item as any).items
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
                                        variant={chefStatus === "rejected" ||
                                        chefStatus === "rejected_partial"
                                            ? "danger"
                                            : chefStatus === "delivered"
                                              ? "success"
                                              : chefStatus === "shipped"
                                                ? "primary"
                                                : chefStatus === "accepted"
                                                  ? "info"
                                                  : chefStatus === "partial"
                                                    ? "purple"
                                                    : "warning"}
                                        dot={true}
                                    >
                                        {#snippet children()}
                                            {chefStatus === "rejected"
                                                ? "Ditolak"
                                                : chefStatus === "rejected_partial"
                                                  ? "Ditolak Sebagian"
                                                  : chefStatus === "delivered"
                                                    ? "Selesai"
                                                    : chefStatus === "shipped"
                                                      ? "Dikirim"
                                                      : chefStatus === "accepted"
                                                        ? "Diproses"
                                                        : chefStatus === "partial"
                                                          ? "Sebagian"
                                                          : "Menunggu"}
                                        {/snippet}
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
                                <td class="text-center">
                                    {#if (item as any).payment_proof_url}
                                        <div
                                            class="flex items-center justify-center"
                                        >
                                            <img
                                                src={(item as any).payment_proof_url}
                                                alt="Bukti"
                                                class="w-8 h-8 object-cover rounded border border-gray-200 dark:border-gray-700"
                                            />
                                        </div>
                                    {:else}
                                        <span
                                            class="text-[10px] text-gray-400 italic"
                                            >Belum ada</span
                                        >
                                    {/if}
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-center"
                                >
                                    <div
                                        class="flex gap-2 items-center justify-center"
                                    >
                                        <Button
                                            variant="primary"
                                            size="xs"
                                            icon="fa-solid fa-eye"
                                            href={`/admin/orders/${item.id}`}
                                            title="Detail"
                                        />
                                        {#if item.order_status === "pending"}
                                            <Button
                                                variant="success"
                                                size="xs"
                                                icon="fa-solid fa-check"
                                                disabled={isProcessing}
                                                onclick={() =>
                                                    openConfirm(
                                                        "Konfirmasi Pesanan",
                                                        `Apakah Anda yakin ingin menyetujui pesanan #${item.number}?`,
                                                        () =>
                                                            approveOrder(
                                                                item.id,
                                                            ),
                                                        "success",
                                                    )}
                                                title="Setujui"
                                            />
                                            <Button
                                                variant="danger"
                                                size="xs"
                                                icon="fa-solid fa-xmark"
                                                disabled={isProcessing}
                                                onclick={() =>
                                                    openConfirm(
                                                        "Tolak Pesanan",
                                                        `Apakah Anda yakin ingin menolak pesanan #${item.number}?`,
                                                        () =>
                                                            rejectOrder(
                                                                item.id,
                                                            ),
                                                        "danger",
                                                    )}
                                                title="Tolak"
                                            />
                                        {/if}
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    {:else}
                        <tr>
                            <td
                                colspan="9"
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
                    {/if}
                </tbody>
            </table>
        </div>

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

<!-- Confirmation Dialog Modal -->
{#if confirmDialog.open}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
                        {confirmDialog.variant === 'danger'
                        ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                        : confirmDialog.variant === 'success'
                          ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                          : confirmDialog.variant === 'warning'
                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                            : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'}"
                >
                    <i
                        class="fa-solid
                            {confirmDialog.variant === 'danger'
                            ? 'fa-triangle-exclamation'
                            : confirmDialog.variant === 'success'
                              ? 'fa-circle-check'
                              : confirmDialog.variant === 'warning'
                                ? 'fa-circle-exclamation'
                                : 'fa-circle-question'}"
                    ></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {confirmDialog.title}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {confirmDialog.message}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" size="sm" onclick={closeConfirm}>
                    Batal
                </Button>
                <Button
                    variant={confirmDialog.variant === "danger"
                        ? "danger"
                        : confirmDialog.variant === "success"
                          ? "success"
                          : "primary"}
                    size="sm"
                    disabled={isProcessing}
                    onclick={executeAction}
                >
                    Ya, Lanjutkan
                </Button>
            </div>
        </div>
    </div>
{/if}

