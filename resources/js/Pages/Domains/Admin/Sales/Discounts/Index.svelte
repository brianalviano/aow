<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type Discount = {
        id: string;
        name: string;
        type: string;
        scope: string;
        value_type: string | null;
        value: string | null;
        start_at: string | null;
        end_at: string | null;
        is_active: boolean;
        created_at?: string | null;
        updated_at?: string | null;
    };

    let discounts = $derived($page.props.discounts as Discount[]);
    let meta = $derived(
        $page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );

    type LocalFilters = {
        q: string;
        type: string;
        scope: string;
        is_active: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as any)?.q ?? "",
        type: ($page.props.filters as any)?.type ?? "",
        scope: ($page.props.filters as any)?.scope ?? "",
        is_active: ($page.props.filters as any)?.is_active ?? "",
    });

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedDiscount = $state<Discount | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.scope) params.set("scope", filters.scope);
        if (filters.is_active) params.set("is_active", filters.is_active);
        router.get("/discounts?" + params.toString(), {}, { preserveState: true, preserveScroll: true });
    }

    function resetFilters() {
        filters.q = "";
        filters.type = "";
        filters.scope = "";
        filters.is_active = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.scope) params.set("scope", filters.scope);
        if (filters.is_active) params.set("is_active", filters.is_active);
        params.set("page", String(pageNum));
        router.get("/discounts?" + params.toString(), {}, { preserveState: true, preserveScroll: true });
    }

    function openDeleteDialog(d: Discount) {
        selectedDiscount = d;
        showDeleteDialog = true;
    }

    function formatValue(d: Discount): string {
        if (!d.value_type || !d.value) return "-";
        if (d.value_type === "percentage") {
            return `${d.value}%`;
        }
        return formatCurrency(Number(d.value));
    }
</script>

<svelte:head>
    <title>Diskon | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Diskon</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Kelola diskon penjualan</p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button variant="success" href="/discounts/create" icon="fa-solid fa-plus">Tambah Diskon</Button>
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <TextInput id="q" name="q" label="Cari" placeholder="Nama..." bind:value={filters.q} />
            <Select
                id="type"
                name="type"
                label="Tipe"
                options={[
                    { value: "", label: "Semua" },
                    { value: "basic", label: "Diskon Nilai" },
                    { value: "bogo", label: "BOGO" },
                ]}
                bind:value={filters.type}
            />
            <Select
                id="scope"
                name="scope"
                label="Ruang Lingkup"
                options={[
                    { value: "", label: "Semua" },
                    { value: "global", label: "Global" },
                    { value: "specific", label: "Spesifik" },
                ]}
                bind:value={filters.scope}
            />
            <Select
                id="is_active"
                name="is_active"
                label="Status"
                options={[
                    { value: "", label: "Semua" },
                    { value: "1", label: "Aktif" },
                    { value: "0", label: "Tidak Aktif" },
                ]}
                bind:value={filters.is_active}
            />
        </div>
    </Card>

    <Card title="Daftar Diskon" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr class="text-sm text-left text-gray-600 dark:text-gray-400">
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Scope</th>
                            <th>Nilai</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if discounts?.length}
                            {#each discounts as d}
                                <tr>
                                    <td>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {d.name}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {d.type === "bogo" ? "BOGO" : "Diskon Nilai"}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {d.scope === "global" ? "Global" : "Spesifik"}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{formatValue(d)}</div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="text-xs text-gray-900 dark:text-white">
                                            {formatDateTimeDisplay(d.start_at)} — {formatDateTimeDisplay(d.end_at)}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span
                                            class={`px-2 py-1 text-xs rounded ${
                                                d.is_active
                                                    ? "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                                    : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300"
                                            }`}
                                            aria-label={d.is_active ? "Aktif" : "Tidak Aktif"}
                                            >{d.is_active ? "Aktif" : "Tidak Aktif"}</span
                                        >
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button variant="primary" size="sm" icon="fa-solid fa-eye" href={`/discounts/${d.id}`}>Detail</Button>
                                            <Button variant="warning" size="sm" icon="fa-solid fa-edit" href={`/discounts/${d.id}/edit`}>Edit</Button>
                                            <Button variant="danger" size="sm" icon="fa-solid fa-trash" onclick={() => openDeleteDialog(d)}>Hapus</Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td colspan="7" class="py-6 text-sm text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
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
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Diskon"
    message={`Apakah Anda yakin ingin menghapus diskon ${selectedDiscount?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedDiscount) {
            await router.delete(`/discounts/${selectedDiscount.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedDiscount = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedDiscount = null;
    }}
    onClose={() => {
        selectedDiscount = null;
        deleteProcessing = false;
    }}
/>
