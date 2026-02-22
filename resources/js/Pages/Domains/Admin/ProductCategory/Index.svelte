<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface ProductCategory {
        id: string;
        name: string;
        is_active: boolean;
        sort_order: number;
        created_at: string;
        updated_at: string;
    }

    let productCategories = $derived(
        $page.props.productCategories as {
            data: ProductCategory[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        productCategories?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(productCategories?.data ?? []);

    let deleteModalOpen = $state(false);
    let categoryToDelete = $state<ProductCategory | null>(null);

    function confirmDelete(category: ProductCategory) {
        categoryToDelete = category;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!categoryToDelete) return;

        router.delete(`/admin/product-categories/${categoryToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteModalOpen = false;
                categoryToDelete = null;
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
            "/admin/product-categories?" + params.toString(),
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
</script>

<svelte:head>
    <title>Kategori Produk | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Kategori Produk
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola kategori produk untuk menu
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/product-categories/create"
            >
                Tambah Kategori
            </Button>
        </div>
    </header>

    <Card title="Daftar Kategori Produk" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div class="flex items-center w-full max-w-sm gap-2">
                <div class="flex-1">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari kategori..."
                        bind:value={searchQuery}
                        class="mb-0!"
                    />
                </div>
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
        {/snippet}
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th class="w-20 text-center">Urutan</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td class="text-center">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.sort_order}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.name}
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
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(item.created_at)}
                                        </div>
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
                                                href={`/admin/product-categories/${item.id}/edit`}
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
    message={`Apakah Anda yakin ingin menghapus kategori produk ${categoryToDelete?.name}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
