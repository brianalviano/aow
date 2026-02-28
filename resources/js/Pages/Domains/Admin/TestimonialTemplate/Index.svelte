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

    interface TestimonialTemplate {
        id: string;
        customer_name: string;
        rating: number;
        content: string;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    }

    let templates = $derived(
        $page.props.templates as {
            data: TestimonialTemplate[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        templates?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(templates?.data ?? []);

    let deleteModalOpen = $state(false);
    let templateToDelete = $state<TestimonialTemplate | null>(null);

    function confirmDelete(template: TestimonialTemplate) {
        templateToDelete = template;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!templateToDelete) return;

        router.delete(`/admin/testimonial-templates/${templateToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteModalOpen = false;
                templateToDelete = null;
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
            "/admin/testimonial-templates?" + params.toString(),
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
</script>

<svelte:head>
    <title>Template Testimoni | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Template Testimoni
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola template testimoni untuk manipulasi data produk (Social
                Proof)
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/testimonial-templates/create"
            >
                Tambah Template
            </Button>
        </div>
    </header>

    <Card title="Daftar Template Testimoni" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div class="flex items-center w-full max-w-sm gap-2">
                <div class="flex-1">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama atau konten..."
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
                <table class="custom-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="w-48">Nama Customer</th>
                            <th class="w-24 text-center">Rating</th>
                            <th>Konten</th>
                            <th class="w-24">Status</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.customer_name}
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="flex items-center justify-center gap-1 text-yellow-500"
                                        >
                                            <i class="fa-solid fa-star"></i>
                                            <span class="font-bold"
                                                >{item.rating}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="whitespace-normal max-w-xs xl:max-w-md truncate-line-2 italic text-gray-600 dark:text-gray-400"
                                    >
                                        "{item.content}"
                                    </td>
                                    <td>
                                        {#if item.is_active}
                                            <Badge variant="success"
                                                >Aktif</Badge
                                            >
                                        {:else}
                                            <Badge variant="danger"
                                                >Nonaktif</Badge
                                            >
                                        {/if}
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="flex gap-2 items-center justify-center"
                                        >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/admin/testimonial-templates/${item.id}/edit`}
                                            />
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    confirmDelete(item)}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="5"
                                    class="py-10 text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data template
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

<Dialog
    bind:isOpen={deleteModalOpen}
    title="Konfirmasi Hapus"
    type="danger"
    message={`Apakah Anda yakin ingin menghapus template testimoni dari "${templateToDelete?.customer_name}"?`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>

<style>
    .truncate-line-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
