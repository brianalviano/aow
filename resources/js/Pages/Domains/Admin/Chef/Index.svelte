<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface Chef {
        id: string;
        name: string;
        business_name: string | null;
        email: string;
        phone: string | null;
        fee_percentage: number;
        is_active: boolean;
        order_types: ("instant" | "preorder")[];
        total_sales: number;
        total_transferred: number;
        outstanding_balance: number;
        created_at: string;
    }

    let chefs = $derived(
        $page.props.chefs as {
            data: Chef[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        chefs?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(chefs?.data ?? []);

    let deleteModalOpen = $state(false);
    let itemToDelete = $state<Chef | null>(null);

    function confirmDelete(item: Chef) {
        itemToDelete = item;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!itemToDelete) return;

        router.delete(`/admin/chefs/${itemToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteModalOpen = false;
                itemToDelete = null;
            },
        });
    }

    function goToPage(pageNumber: number) {
        const params = new URLSearchParams();
        const limit = meta.per_page || 15;
        params.set("page", String(pageNumber));
        params.set("limit", String(limit));

        if (searchQuery) {
            params.set("search", searchQuery);
        }

        router.get(
            "/admin/chefs?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    const handleSearch = debounce(() => {
        goToPage(1);
    }, 500);

    $effect(() => {
        if (searchQuery !== (filters?.search || "")) {
            handleSearch();
        }
    });

    function formatCurrency(amount: number): string {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }
</script>

<svelte:head>
    <title>Chef Mitra | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Chef Mitra
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola daftar chef mitra dan assignment produk
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/chefs/create"
            >
                Tambah Chef
            </Button>
        </div>
    </header>

    <Card title="Daftar Chef" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-lg gap-2"
            >
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama, email, telepon..."
                        bind:value={searchQuery}
                        class="mb-0!"
                    />
                </div>
                <div class="flex gap-2">
                    {#if searchQuery}
                        <Button
                            variant="secondary"
                            size="sm"
                            onclick={() => {
                                searchQuery = "";
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
                            <th>Nama & Usaha</th>
                            <th>Kontak</th>
                            <th>Fee (%)</th>
                            <th>Total Penjualan</th>
                            <th>Sudah Ditransfer</th>
                            <th>Belum Ditransfer</th>
                            <th>Status</th>
                            <th class="w-40 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-bold text-gray-900 dark:text-white"
                                        >
                                            {item.name}
                                        </div>
                                        {#if item.business_name}
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                            >
                                                <i class="fa-solid fa-shop mr-1"
                                                ></i>
                                                {item.business_name}
                                            </div>
                                        {/if}
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            <i
                                                class="fa-solid fa-envelope text-gray-400 mr-1"
                                            ></i>
                                            {item.email}
                                        </div>
                                        {#if item.phone}
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                            >
                                                <i
                                                    class="fa-solid fa-phone text-gray-400 mr-1"
                                                ></i>
                                                {item.phone}
                                            </div>
                                        {/if}
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.fee_percentage}%
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-blue-600 dark:text-blue-400"
                                        >
                                            {formatCurrency(item.total_sales)}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-teal-600 dark:text-teal-400"
                                        >
                                            {formatCurrency(
                                                item.total_transferred,
                                            )}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-bold {item.outstanding_balance >
                                            0
                                                ? 'text-orange-600 dark:text-orange-400'
                                                : 'text-green-600 dark:text-green-400'}"
                                        >
                                            {formatCurrency(
                                                item.outstanding_balance,
                                            )}
                                        </div>
                                    </td>
                                    <td>
                                        {#if item.is_active}
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant="success"
                                                title="Aktif"
                                            >
                                                {#snippet children()}Aktif{/snippet}
                                            </Badge>
                                        {:else}
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant="danger"
                                                title="Nonaktif"
                                            >
                                                {#snippet children()}Nonaktif{/snippet}
                                            </Badge>
                                        {/if}
                                    </td>

                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-center"
                                    >
                                        <div
                                            class="flex gap-2 items-center justify-center"
                                        >
                                            <Button
                                                variant="info"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/admin/chefs/${item.id}`}
                                            >
                                                Detail
                                            </Button>
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/admin/chefs/${item.id}/edit`}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    confirmDelete(item)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data chef
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

<!-- Delete Confirmation Dialog -->
<Dialog
    bind:isOpen={deleteModalOpen}
    title="Konfirmasi Hapus"
    type="danger"
    message={`Apakah Anda yakin ingin menghapus Chef ${itemToDelete?.name}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
