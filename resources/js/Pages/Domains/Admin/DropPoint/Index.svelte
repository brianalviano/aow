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

    interface DropPoint {
        id: string;
        name: string;
        address: string;
        phone: string;
        latitude: number;
        longitude: number;
        pic_name: string;
        pic_phone: string;
        is_active: boolean;
        delivery_fee: number;
        created_at: string;
        updated_at: string;
    }

    let dropPoints = $derived(
        $page.props.dropPoints as {
            data: DropPoint[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        dropPoints?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(dropPoints?.data ?? []);

    let deleteModalOpen = $state(false);
    let itemToDelete = $state<DropPoint | null>(null);

    function confirmDelete(item: DropPoint) {
        itemToDelete = item;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!itemToDelete) return;

        router.delete(`/admin/drop-points/${itemToDelete.id}`, {
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
            "/admin/drop-points?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    const handleSearch = debounce(() => {
        goToPage(1);
    }, 500);

    // Watch for search query changes to trigger debounced search
    $effect(() => {
        if (searchQuery !== (filters?.search || "")) {
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
</script>

<svelte:head>
    <title>Titik Jemput | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Titik Jemput (Drop Point)
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola daftar lokasi titik jemput pengiriman
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/drop-points/create"
            >
                Tambah Drop Point
            </Button>
        </div>
    </header>

    <Card title="Daftar Drop Point" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-lg gap-2"
            >
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama, alamat, pic..."
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
                            <th>Nama & Alamat</th>
                            <th>Kontak & PIC</th>
                            <th>Biaya Pengiriman</th>
                            <th>Status</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.name}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xs truncate"
                                        >
                                            {item.address}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            <i
                                                class="fa-solid fa-user text-gray-400 mr-1"
                                            ></i>
                                            {item.pic_name}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                        >
                                            <i
                                                class="fa-solid fa-phone text-gray-400 mr-1"
                                            ></i>
                                            {item.pic_phone}
                                        </div>
                                        {#if item.phone && item.phone !== item.pic_phone}
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                            >
                                                <i
                                                    class="fa-solid fa-building text-gray-400 mr-1"
                                                ></i>
                                                {item.phone}
                                            </div>
                                        {/if}
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white font-medium"
                                        >
                                            {item.delivery_fee > 0
                                                ? formatCurrency(
                                                      item.delivery_fee,
                                                  )
                                                : "Gratis"}
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
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/admin/drop-points/${item.id}/edit`}
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
                                    colspan="5"
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

<!-- Delete Confirmation Dialog -->
<Dialog
    bind:isOpen={deleteModalOpen}
    title="Konfirmasi Hapus"
    type="danger"
    message={`Apakah Anda yakin ingin menghapus Drop Point ${itemToDelete?.name}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
