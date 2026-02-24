<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface PaymentGuide {
        id: string;
        name: string;
        content: any[];
        created_at: string;
        updated_at: string;
    }

    let paymentGuides = $derived(
        $page.props.paymentGuides as {
            data: PaymentGuide[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        paymentGuides?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(paymentGuides?.data ?? []);

    let deleteModalOpen = $state(false);
    let itemToDelete = $state<PaymentGuide | null>(null);

    function confirmDelete(item: PaymentGuide) {
        itemToDelete = item;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!itemToDelete) return;

        router.delete(`/admin/payment-guides/${itemToDelete.id}`, {
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
            "/admin/payment-guides?" + params.toString(),
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
    <title>Panduan Pembayaran | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Panduan Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola panduan instruksi pembayaran yang dapat dibagikan ke
                beberapa metode pembayaran
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/payment-guides/create"
            >
                Tambah Panduan
            </Button>
        </div>
    </header>

    <Card title="Daftar Panduan Pembayaran" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-lg gap-2"
            >
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama panduan..."
                        bind:value={searchQuery}
                        class="mb-0!"
                    />
                </div>
            </div>
        {/snippet}
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nama Panduan</th>
                            <th>Jumlah Seksi</th>
                            <th>Tanggal Dibuat</th>
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
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            {item.content?.length ?? 0} Seksi
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            {new Date(
                                                item.created_at,
                                            ).toLocaleDateString("id-ID")}
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
                                                href={`/admin/payment-guides/${item.id}/edit`}
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

<Dialog
    bind:isOpen={deleteModalOpen}
    title="Konfirmasi Hapus"
    type="danger"
    message={`Apakah Anda yakin ingin menghapus panduan ${itemToDelete?.name}? Panduan ini akan dilepas dari semua metode pembayaran yang menggunakannya.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
