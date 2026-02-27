<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    interface Customer {
        id: number;
        name: string;
        phone: string | null;
    }

    interface FoodRequest {
        id: string;
        customer_id: number;
        name: string;
        notes: string | null;
        status: "pending" | "approved" | "rejected" | "completed";
        created_at: string;
        customer: Customer;
    }

    let foodRequests = $derived(
        $page.props.requests as {
            data: FoodRequest[];
            meta?: any;
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );

    let meta = $derived({
        total: foodRequests?.total ?? 0,
        per_page: foodRequests?.per_page ?? 10,
        current_page: foodRequests?.current_page ?? 1,
        last_page: foodRequests?.last_page ?? 1,
    });

    let items = $derived(foodRequests?.data ?? []);

    function updateStatus(id: string, status: string) {
        router.patch(
            `/admin/food-requests/${id}`,
            { status },
            {
                preserveScroll: true,
            },
        );
    }

    function goToPage(pageNumber: number) {
        router.get(
            `/admin/food-requests?page=${pageNumber}`,
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function formatStatus(status: string) {
        switch (status) {
            case "pending":
                return "Menunggu";
            case "approved":
                return "Disetujui";
            case "rejected":
                return "Ditolak";
            case "completed":
                return "Selesai";
            default:
                return status;
        }
    }

    function getStatusVariant(status: string) {
        switch (status) {
            case "pending":
                return "warning";
            case "approved":
                return "success";
            case "rejected":
                return "danger";
            case "completed":
                return "primary";
            default:
                return "secondary";
        }
    }
</script>

<svelte:head>
    <title>Permintaan Menu Baru | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Permintaan Menu Baru
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola makanan dan minuman yang direquest oleh customer
            </p>
        </div>
    </header>

    <Card title="Daftar Permintaan" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th class="w-16">No</th>
                            <th>Customer</th>
                            <th>Nama Menu</th>
                            <th>Catatan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item, i}
                                <tr>
                                    <td
                                        >{(meta.current_page - 1) *
                                            meta.per_page +
                                            i +
                                            1}</td
                                    >
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.customer.name}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                        >
                                            {item.customer.phone || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >
                                            {item.name}
                                        </div>
                                    </td>
                                    <td>
                                        <p
                                            class="text-xs italic text-gray-600 dark:text-gray-400 max-w-xs truncate"
                                        >
                                            {item.notes || "-"}
                                        </p>
                                    </td>
                                    <td>
                                        <div
                                            class="text-xs text-gray-900 dark:text-white"
                                        >
                                            {new Date(
                                                item.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "numeric",
                                                month: "short",
                                                year: "numeric",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getStatusVariant(
                                                item.status,
                                            )}
                                            title={formatStatus(item.status)}
                                        >
                                            {#snippet children()}{formatStatus(
                                                    item.status,
                                                )}{/snippet}
                                        </Badge>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-center"
                                    >
                                        <div
                                            class="flex gap-2 items-center justify-center"
                                        >
                                            {#if item.status !== "approved"}
                                                <Button
                                                    variant="success"
                                                    size="sm"
                                                    icon="fa-solid fa-check"
                                                    onclick={() =>
                                                        updateStatus(
                                                            item.id,
                                                            "approved",
                                                        )}
                                                >
                                                    Setujui
                                                </Button>
                                            {/if}
                                            {#if item.status !== "rejected"}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-xmark"
                                                    onclick={() =>
                                                        updateStatus(
                                                            item.id,
                                                            "rejected",
                                                        )}
                                                >
                                                    Tolak
                                                </Button>
                                            {/if}
                                            {#if item.status !== "pending"}
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    icon="fa-solid fa-clock"
                                                    onclick={() =>
                                                        updateStatus(
                                                            item.id,
                                                            "pending",
                                                        )}
                                                >
                                                    Pending
                                                </Button>
                                            {/if}
                                            {#if item.status === "approved"}
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fa-solid fa-flag-checkered"
                                                    onclick={() =>
                                                        updateStatus(
                                                            item.id,
                                                            "completed",
                                                        )}
                                                >
                                                    Selesai
                                                </Button>
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="7"
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
