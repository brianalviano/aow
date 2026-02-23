<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface ProductCategory {
        id: string;
        name: string;
    }

    interface Product {
        id: string;
        product_category_id: string;
        name: string;
        description: string;
        price: number;
        image: string;
        image_url: string;
        stock_limit: number | null;
        is_active: boolean;
        sort_order: number;
        created_at: string;
        updated_at: string;
        options_count?: number;
        product_category?: ProductCategory;
    }

    let products = $derived(
        $page.props.products as {
            data: Product[];
            meta?: any;
        },
    );

    let categories = $derived(
        ($page.props.categories as { data: ProductCategory[] })?.data ?? [],
    );

    let categoryOptions = $derived([
        { value: "", label: "Semua Kategori" },
        ...categories.map((c) => ({ value: c.id, label: c.name })),
    ]);

    let filters = $derived(
        $page.props.filters as
            | { search?: string; category_id?: string }
            | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));
    let categoryFilter = $state(untrack(() => filters?.category_id || ""));

    let meta = $derived(
        products?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(products?.data ?? []);

    let deleteModalOpen = $state(false);
    let categoryToDelete = $state<Product | null>(null);

    function confirmDelete(product: Product) {
        categoryToDelete = product;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!categoryToDelete) return;

        router.delete(`/admin/products/${categoryToDelete.id}`, {
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
        if (categoryFilter) {
            params.set("category_id", categoryFilter);
        }

        router.get(
            "/admin/products?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    const handleSearch = debounce(() => {
        goToPage(1);
    }, 500);

    // Watch for search query or category filter changes to trigger debounced search
    $effect(() => {
        if (
            searchQuery !== (filters?.search || "") ||
            categoryFilter !== (filters?.category_id || "")
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
</script>

<svelte:head>
    <title>Produk | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Produk
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola daftar produk
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/products/create"
            >
                Tambah Produk
            </Button>
        </div>
    </header>

    <Card title="Daftar Produk" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-2xl gap-2"
            >
                <div class="w-full sm:w-48">
                    <Select
                        id="category_filter"
                        options={categoryOptions}
                        bind:value={categoryFilter}
                    />
                </div>
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari produk..."
                        bind:value={searchQuery}
                        class="mb-0!"
                    />
                </div>
                <div class="flex gap-2">
                    {#if searchQuery || categoryFilter}
                        <Button
                            variant="secondary"
                            size="sm"
                            onclick={() => {
                                searchQuery = "";
                                categoryFilter = "";
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
                            <th class="w-16">Gambar</th>
                            <th>Nama Baru</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Opsi</th>
                            <th>Stok Limit</th>
                            <th>Status</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        {#if item.image_url}
                                            <img
                                                src={item.image_url}
                                                alt={item.name}
                                                class="w-12 h-12 object-cover rounded"
                                            />
                                        {:else}
                                            <div
                                                class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center"
                                            >
                                                <i
                                                    class="fa-solid fa-image text-gray-400"
                                                ></i>
                                            </div>
                                        {/if}
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.name}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {item.product_category?.name ?? "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(item.price)}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {item.options_count ?? 0}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {item.stock_limit ?? "-"}
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
                                                href={`/admin/products/${item.id}/edit`}
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

<!-- Delete Confirmation Dialog -->
<Dialog
    bind:isOpen={deleteModalOpen}
    title="Konfirmasi Hapus"
    type="danger"
    message={`Apakah Anda yakin ingin menghapus produk ${categoryToDelete?.name}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
