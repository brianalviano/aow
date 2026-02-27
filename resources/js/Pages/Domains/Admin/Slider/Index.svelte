<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface Slider {
        id: string;
        name: string;
        photo: string;
        created_at: string;
        updated_at: string;
    }

    let sliders = $derived(
        $page.props.sliders as {
            data: Slider[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        sliders?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(sliders?.data ?? []);

    let deleteModalOpen = $state(false);
    let sliderToDelete = $state<Slider | null>(null);

    function confirmDelete(slider: Slider) {
        sliderToDelete = slider;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!sliderToDelete) return;

        router.delete(`/admin/sliders/${sliderToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteModalOpen = false;
                sliderToDelete = null;
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
            "/admin/sliders?" + params.toString(),
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
    <title>Slider | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Slider
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola gambar slider untuk halaman utama
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/sliders/create"
            >
                Tambah Slider
            </Button>
        </div>
    </header>

    <Card title="Daftar Slider" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div class="flex items-center w-full max-w-sm gap-2">
                <div class="flex-1">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari slider..."
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
                            <th class="w-32">Foto</th>
                            <th>Nama</th>
                            <th>Dibuat</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        <img
                                            src={item.photo}
                                            alt={item.name}
                                            class="w-24 h-16 object-cover rounded shadow-sm border border-gray-100 dark:border-gray-700"
                                        />
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
                                                href={`/admin/sliders/${item.id}/edit`}
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
                                    colspan="4"
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
    message={`Apakah Anda yakin ingin menghapus slider ${sliderToDelete?.name}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
