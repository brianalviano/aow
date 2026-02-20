<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type WarehouseItem = { id: string; name: string };
    type MarketingItem = { id: string; name: string };
    type WarehouseStock = {
        id: string;
        name: string;
        vat_quantity: number;
        non_vat_quantity: number;
    };
    type MarketingStock = {
        id: string;
        name: string;
        vat_quantity: number;
        non_vat_quantity: number;
    };
    type ProductItem = {
        id: string;
        name: string;
        sku: string | null;
        vat_quantity_total: number;
        non_vat_quantity_total: number;
        warehouses: WarehouseStock[];
        marketings: MarketingStock[];
    };

    let products = $derived(($page.props.products as ProductItem[]) ?? []);
    let warehouses = $derived(
        (($page.props.warehouses as WarehouseItem[]) ?? []) as WarehouseItem[],
    );
    let marketings = $derived(
        (($page.props.marketings as MarketingItem[]) ?? []) as MarketingItem[],
    );
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
        bucket: string;
        warehouse_id: string;
        marketing_id: string;
        per_page: string;
    };
    let filters = $state<LocalFilters>({
        q: ($page.props.filters as any)?.q ?? "",
        bucket: ($page.props.filters as any)?.bucket ?? "",
        warehouse_id: ($page.props.filters as any)?.warehouse_id ?? "",
        marketing_id: ($page.props.filters as any)?.marketing_id ?? "",
        per_page: String(($page.props.filters as any)?.per_page ?? "10"),
    });

    const bucketOptions = $derived([
        { value: "", label: "Semua" },
        { value: "vat", label: "PPN" },
        { value: "non_vat", label: "Non PPN" },
    ]);
    const warehouseFilterOptions = $derived([
        { value: "", label: "Semua Gudang" },
        ...warehouses.map((w) => ({ value: w.id, label: w.name })),
    ]);
    const marketingFilterOptions = $derived([
        { value: "", label: "Semua Marketing" },
        ...marketings.map((m) => ({ value: m.id, label: m.name })),
    ]);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.bucket) params.set("bucket", filters.bucket);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.marketing_id)
            params.set("marketing_id", filters.marketing_id);
        if (filters.per_page) params.set("per_page", filters.per_page);
        router.get(
            "/product-stocks?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.bucket = "";
        filters.warehouse_id = "";
        filters.marketing_id = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.bucket) params.set("bucket", filters.bucket);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.marketing_id)
            params.set("marketing_id", filters.marketing_id);
        if (filters.per_page) params.set("per_page", filters.per_page);
        params.set("page", String(pageNum));
        router.get(
            "/product-stocks?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Stok Produk | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Stok Produk
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lihat stok PPN/Non PPN per produk dan per gudang
            </p>
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <TextInput
                id="q"
                name="q"
                label="Cari produk"
                placeholder="Nama atau SKU..."
                bind:value={filters.q}
            />
            <Select
                id="bucket"
                name="bucket"
                label="Bucket PPN"
                bind:value={filters.bucket}
                options={bucketOptions}
                minimal={false}
            />
            <Select
                id="warehouse"
                name="warehouse_id"
                label="Filter Gudang"
                bind:value={filters.warehouse_id}
                options={warehouseFilterOptions}
                minimal={false}
            />
            <Select
                id="marketing"
                name="marketing_id"
                label="Filter Marketing"
                bind:value={filters.marketing_id}
                options={marketingFilterOptions}
                minimal={false}
            />
        </div>
    </Card>

    <Card title="Stok Produk" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>SKU</th>
                            {#if !filters.bucket || filters.bucket === "vat"}
                                <th>Stok PPN</th>
                            {/if}
                            {#if !filters.bucket || filters.bucket === "non_vat"}
                                <th>Stok Non PPN</th>
                            {/if}
                            <th>Per Gudang</th>
                            <th>Per Marketing</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if products?.length}
                            {#each products as p}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {p.name}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {p.sku ?? "-"}
                                        </div>
                                    </td>
                                    {#if !filters.bucket || filters.bucket === "vat"}
                                        <td>
                                            <div
                                                class="text-sm text-gray-700 dark:text-gray-300"
                                            >
                                                {p.vat_quantity_total}
                                            </div>
                                        </td>
                                    {/if}
                                    {#if !filters.bucket || filters.bucket === "non_vat"}
                                        <td>
                                            <div
                                                class="text-sm text-gray-700 dark:text-gray-300"
                                            >
                                                {p.non_vat_quantity_total}
                                            </div>
                                        </td>
                                    {/if}
                                    <td>
                                        <div class="space-y-1">
                                            {#if p.warehouses?.length}
                                                {#each p.warehouses as w}
                                                    <div
                                                        class="text-xs text-gray-700 dark:text-gray-300"
                                                    >
                                                        <span
                                                            class="inline-block font-semibold"
                                                            >{w.name}:</span
                                                        >
                                                        {#if !filters.bucket || filters.bucket === "vat"}
                                                            <span
                                                                class="inline-block ml-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                                                                >PPN {w.vat_quantity}</span
                                                            >
                                                        {/if}
                                                        {#if !filters.bucket || filters.bucket === "non_vat"}
                                                            <span
                                                                class="inline-block ml-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                                                >Non {w.non_vat_quantity}</span
                                                            >
                                                        {/if}
                                                    </div>
                                                {/each}
                                            {:else}
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Tidak ada stok per gudang
                                                </div>
                                            {/if}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            {#if p.marketings?.length}
                                                {#each p.marketings as m}
                                                    <div
                                                        class="text-xs text-gray-700 dark:text-gray-300"
                                                    >
                                                        <span
                                                            class="inline-block font-semibold"
                                                            >{m.name}:</span
                                                        >
                                                        {#if !filters.bucket || filters.bucket === "vat"}
                                                            <span
                                                                class="inline-block ml-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                                                                >PPN {m.vat_quantity}</span
                                                            >
                                                        {/if}
                                                        {#if !filters.bucket || filters.bucket === "non_vat"}
                                                            <span
                                                                class="inline-block ml-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                                                >Non {m.non_vat_quantity}</span
                                                            >
                                                        {/if}
                                                    </div>
                                                {/each}
                                            {:else}
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Tidak ada stok per marketing
                                                </div>
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Tidak ada data</td
                                >
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
