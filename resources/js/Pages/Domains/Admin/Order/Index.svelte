<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
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

    const statusOptions = [
        { value: "all", label: "Semua Status" },
        { value: "unpaid", label: "Belum Bayar" },
        { value: "process", label: "Diproses" },
        { value: "shipped", label: "Dikirim" },
        { value: "completed", label: "Selesai" },
        { value: "cancelled", label: "Dibatalkan" },
    ];

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
            { preserveState: true, preserveScroll: true },
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
                Kelola pesanan pelanggan
            </p>
        </div>
    </header>

    <Card title="Daftar Pesanan" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-2xl gap-2"
            >
                <div class="w-full sm:w-48">
                    <Select
                        id="status_filter"
                        options={statusOptions}
                        bind:value={statusFilter}
                    />
                </div>
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nomor pesanan atau customer..."
                        bind:value={searchQuery}
                        class="mb-0!"
                    />
                </div>
                <div class="flex gap-2">
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
                            Reset
                        </Button>
                    {/if}
                </div>
            </div>
        {/snippet}

        {#snippet children()}
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
                        {#if items.length > 0}
                            {#each items as item}
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
                                                  ).toLocaleDateString(
                                                      "id-ID",
                                                      {
                                                          weekday: "short",
                                                          day: "numeric",
                                                          month: "short",
                                                          year: "numeric",
                                                      },
                                                  )
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
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="8"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}

        {#snippet footer()}
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        {/snippet}
    </Card>
</section>
