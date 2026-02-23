<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface Customer {
        id: string;
        name: string;
        username: string;
        phone: string | null;
        address: string | null;
        email: string | null;
        school_class: string | null;
        is_active: boolean;
        drop_point?: {
            id: string;
            name: string;
        };
        created_at: string;
    }

    let customers = $derived(
        $page.props.customers as {
            data: Customer[];
            meta?: any;
        },
    );

    let filters = $derived(
        $page.props.filters as { search?: string } | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));

    let meta = $derived(
        customers?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(customers?.data ?? []);

    function goToPage(pageNumber: number) {
        const params = new URLSearchParams();
        const limit = meta.per_page || 15;
        params.set("page", String(pageNumber));
        params.set("limit", String(limit));

        if (searchQuery) {
            params.set("search", searchQuery);
        }

        router.get(
            "/admin/customers?" + params.toString(),
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
    <title>Customer | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Customer
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola daftar customer yang terdaftar
            </p>
        </div>
    </header>

    <Card title="Daftar Customer" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div class="flex flex-col sm:flex-row items-center w-full max-w-lg gap-2">
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama, email, username..."
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
                            <th>Profil</th>
                            <th>Kontak</th>
                            <th>Informasi</th>
                            <th>Status</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {item.name}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            @{item.username}
                                        </div>
                                    </td>
                                    <td>
                                        {#if item.email}
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                <i class="fa-solid fa-envelope text-gray-400 mr-1"></i>
                                                {item.email}
                                            </div>
                                        {/if}
                                        {#if item.phone}
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <i class="fa-solid fa-phone text-gray-400 mr-1"></i>
                                                {item.phone}
                                            </div>
                                        {/if}
                                    </td>
                                    <td>
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            <i class="fa-solid fa-location-dot text-gray-400 mr-1"></i>
                                            {item.drop_point?.name || '-'}
                                        </div>
                                        {#if item.school_class}
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <i class="fa-solid fa-graduation-cap text-gray-400 mr-1"></i>
                                                Kelas: {item.school_class}
                                            </div>
                                        {/if}
                                    </td>
                                    <td>
                                        {#if item.is_active}
                                            <Badge size="sm" rounded="pill" variant="success" title="Aktif">
                                                {#snippet children()}Aktif{/snippet}
                                            </Badge>
                                        {:else}
                                            <Badge size="sm" rounded="pill" variant="danger" title="Nonaktif">
                                                {#snippet children()}Nonaktif{/snippet}
                                            </Badge>
                                        {/if}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="flex gap-2 items-center justify-center">
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/admin/customers/${item.id}`}
                                            >
                                                Lihat
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td colspan="5" class="py-6 text-sm text-center text-gray-500 dark:text-gray-400">
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
