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

    interface PaymentMethod {
        id: string;
        name: string;
        category: "manual" | "gateway";
        photo: string | null;
        is_active: boolean;
        type: "cash" | "online";
        created_at: string;
        updated_at: string;
    }

    let paymentMethods = $derived(
        $page.props.paymentMethods as {
            data: PaymentMethod[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        paymentMethods?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(paymentMethods?.data ?? []);

    let deleteModalOpen = $state(false);
    let itemToDelete = $state<PaymentMethod | null>(null);

    function confirmDelete(item: PaymentMethod) {
        itemToDelete = item;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!itemToDelete) return;

        router.delete(`/admin/payment-methods/${itemToDelete.id}`, {
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
            "/admin/payment-methods?" + params.toString(),
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

    function getTypeName(type: string) {
        return type === "cash" ? "Tunai" : "Online / Transfer";
    }
</script>

<svelte:head>
    <title>Metode Pembayaran | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Metode Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola metode pembayaran yang tersedia untuk pelanggan
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/payment-methods/create"
            >
                Tambah Metode Pembayaran
            </Button>
        </div>
    </header>

    <Card title="Daftar Metode Pembayaran" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-lg gap-2"
            >
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama atau tipe..."
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
                            <th class="w-16">Logo</th>
                            <th>Nama Metode</th>
                            <th>Kategori</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        {#if item.photo}
                                            <img
                                                src={item.photo}
                                                alt={item.name}
                                                class="w-12 h-12 object-contain rounded bg-white p-1 border dark:border-gray-700"
                                            />
                                        {:else}
                                            <div
                                                class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center border dark:border-gray-700"
                                            >
                                                <i
                                                    class="fa-solid fa-credit-card text-gray-400"
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
                                        <Badge
                                            variant={item.category === "manual"
                                                ? "secondary"
                                                : "warning"}
                                            size="sm"
                                        >
                                            {#snippet children()}
                                                {item.category === "manual"
                                                    ? "Manual"
                                                    : "Gateway"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            <Badge
                                                variant={item.type === "cash"
                                                    ? "info"
                                                    : "primary"}
                                                size="sm"
                                            >
                                                {#snippet children()}
                                                    {getTypeName(item.type)}
                                                {/snippet}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td>
                                        {#if item.is_active}
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant="success"
                                            >
                                                {#snippet children()}Aktif{/snippet}
                                            </Badge>
                                        {:else}
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant="danger"
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
                                                href={`/admin/payment-methods/${item.id}/edit`}
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
    message={`Apakah Anda yakin ingin menghapus metode pembayaran ${itemToDelete?.name}?`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
