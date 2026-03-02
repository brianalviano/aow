<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
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
        $page.props.filters as { search?: string; status?: string } | undefined,
    );

    let statusCounts = $derived(
        ($page.props.status_counts as Record<string, number>) || {},
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));
    let statusFilter = $state(untrack(() => filters?.status || "all"));

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
            statusFilter !== (filters?.status || "all")
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
                {#if searchQuery || (statusFilter && statusFilter !== "all")}
                    <Button
                        variant="secondary"
                        size="sm"
                        onclick={() => {
                            searchQuery = "";
                            statusFilter = "all";
                            handleSearch();
                        }}
                    >
                        Reset Filter
                    </Button>
                {/if}
            </div>
        </div>

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
                        <th>Status Dapur</th>
                        <th>Status Bayar</th>
                        <th class="w-32 text-center">Aksi</th>
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
                                        variant={chefStatus === "rejected"
                                            ? "danger"
                                            : chefStatus === "accepted"
                                              ? "success"
                                              : chefStatus === "partial"
                                                ? "purple"
                                                : "warning"}
                                        dot={true}
                                    >
                                        {#snippet children()}
                                            {chefStatus === "rejected"
                                                ? "Ditolak"
                                                : chefStatus === "accepted"
                                                  ? "Selesai"
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
                        {/each}
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
