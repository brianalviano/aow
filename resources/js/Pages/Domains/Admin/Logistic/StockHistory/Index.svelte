<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Offcanvas from "@/Lib/Admin/Components/Ui/Offcanvas.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { useForm } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type Rel = { id: string | null; name: string | null };

    type StockCard = {
        id: string;
        date: string | null;
        time: string | null;
        type: string;
        quantity: number;
        unit_price: number;
        subtotal: number;
        balance_before: number;
        balance_after: number;
        last_hpp: number;
        referencable: string | null;
        notes: string | null;
        bucket?: string | null;
        bucket_label?: string | null;
        warehouse: Rel;
        product: Rel;
        user: Rel;
    };

    let stockCards = $derived($page.props.stockCards as StockCard[]);
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
        date_from: string;
        date_to: string;
        product_id: string;
        bucket: string;
        warehouse_id: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        type: ($page.props.filters as { type?: string })?.type ?? "",
        date_from:
            ($page.props.filters as { date_from?: string })?.date_from ?? "",
        date_to: ($page.props.filters as { date_to?: string })?.date_to ?? "",
        product_id:
            ($page.props.filters as { product_id?: string })?.product_id ?? "",
        bucket: ($page.props.filters as { bucket?: string })?.bucket ?? "",
        warehouse_id:
            ($page.props.filters as { warehouse_id?: string })?.warehouse_id ??
            "",
    });

    type ProductItem = { id: string; name: string; sku: string | null };
    let products = $derived(($page.props.products as ProductItem[]) ?? []);
    let productOptions = $derived([
        { value: "", label: "Semua Produk" },
        ...products.map((p) => ({
            value: p.id,
            label: p.sku ? `${p.name} (${p.sku})` : p.name,
        })),
    ]);

    type WarehouseItem = { id: string; name: string };
    let warehouses = $derived(
        ($page.props.warehouses as WarehouseItem[]) ?? [],
    );
    let warehouseOptions = $derived([
        ...warehouses.map((w) => ({ value: w.id, label: w.name })),
    ]);
    let warehouseFilterOptions = $derived([
        { value: "", label: "Semua Gudang" },
        ...warehouseOptions,
    ]);

    type TabLocal = { id: string; label: string };
    let typeTabs = $derived<TabLocal[]>([
        { id: "", label: "Semua" },
        { id: "in", label: "Stok Masuk" },
        { id: "out", label: "Stok Keluar" },
    ]);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.date_from) params.set("date_from", filters.date_from);
        if (filters.date_to) params.set("date_to", filters.date_to);
        if (filters.product_id) params.set("product_id", filters.product_id);
        if (filters.bucket) params.set("bucket", filters.bucket);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        router.get(
            "/stock-histories?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.type = "";
        filters.date_from = "";
        filters.date_to = "";
        filters.product_id = "";
        filters.bucket = "";
        filters.warehouse_id = "";
        applyFilters();
    }

    function exportExcel() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.date_from) params.set("date_from", filters.date_from);
        if (filters.date_to) params.set("date_to", filters.date_to);
        if (filters.product_id) params.set("product_id", filters.product_id);
        if (filters.bucket) params.set("bucket", filters.bucket);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        const url = params.toString()
            ? `/stock-histories/export?${params.toString()}`
            : "/stock-histories/export";
        window.open(url, "_blank");
    }

    let showInModal = $state(false);
    let showOutModal = $state(false);
    let showImportInModal = $state(false);
    let showImportOutModal = $state(false);
    let showDetailOffcanvas = $state(false);
    let selectedCard = $state<StockCard | null>(null);

    const stockInForm = useForm({
        warehouse_id: "",
        product_id: "",
        quantity: "",
        unit_cost: "",
        is_vat: false,
        notes: "",
    });

    const stockOutForm = useForm({
        warehouse_id: "",
        product_id: "",
        quantity: "",
        notes: "",
    });

    const importInForm = useForm({
        file: null as File | null,
    });
    const importOutForm = useForm({
        file: null as File | null,
    });

    function openInModal() {
        showInModal = true;
        $stockInForm.reset();
    }
    function openOutModal() {
        showOutModal = true;
        $stockOutForm.reset();
    }
    function openImportInModal() {
        showImportInModal = true;
        $importInForm.reset();
    }
    function openImportOutModal() {
        showImportOutModal = true;
        $importOutForm.reset();
    }

    function submitStockIn(e: SubmitEvent) {
        e.preventDefault();
        $stockInForm.post("/stock-histories/in", {
            preserveScroll: true,
            onSuccess: () => {
                showInModal = false;
            },
        });
    }

    function submitStockOut(e: SubmitEvent) {
        e.preventDefault();
        $stockOutForm.post("/stock-histories/out", {
            preserveScroll: true,
            onSuccess: () => {
                showOutModal = false;
            },
        });
    }

    function submitImportIn(e: SubmitEvent) {
        e.preventDefault();
        if (!$importInForm.file) return;
        $importInForm.post("/stock-histories/import/in", {
            preserveScroll: true,
            onSuccess: () => {
                showImportInModal = false;
            },
        });
    }

    function submitImportOut(e: SubmitEvent) {
        e.preventDefault();
        if (!$importOutForm.file) return;
        $importOutForm.post("/stock-histories/import/out", {
            preserveScroll: true,
            onSuccess: () => {
                showImportOutModal = false;
            },
        });
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.date_from) params.set("date_from", filters.date_from);
        if (filters.date_to) params.set("date_to", filters.date_to);
        if (filters.product_id) params.set("product_id", filters.product_id);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        params.set("page", String(pageNum));
        router.get(
            "/stock-histories?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDetail(c: StockCard) {
        selectedCard = c;
        showDetailOffcanvas = true;
    }
</script>

<svelte:head>
    <title>Riwayat Stok | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Riwayat Stok
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lihat pergerakan stok per produk/gudang
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            {#if filters.type === "in"}
                <Button
                    variant="success"
                    icon="fa-solid fa-plus"
                    onclick={openInModal}>Stok Masuk</Button
                >
                <Button
                    variant="info"
                    icon="fa-solid fa-file-import"
                    onclick={openImportInModal}>Import Masuk</Button
                >
            {:else if filters.type === "out"}
                <Button
                    variant="warning"
                    icon="fa-solid fa-plus"
                    onclick={openOutModal}>Stok Keluar</Button
                >
                <Button
                    variant="info"
                    icon="fa-solid fa-file-import"
                    onclick={openImportOutModal}>Import Keluar</Button
                >
            {/if}
            <Button
                variant="warning"
                icon="fa-solid fa-file-excel"
                onclick={exportExcel}>Export</Button
            >
        </div>
    </header>

    <div>
        <Tab
            tabs={typeTabs}
            activeTab={filters.type}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.type = tabId;
                applyFilters();
            }}
        />
    </div>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="col-span-4">
                <TextInput
                    id="q"
                    name="q"
                    label="Cari Riwayat Stok"
                    placeholder="Nomor, produk, gudang, catatan..."
                    bind:value={filters.q}
                />
            </div>
            <div class="col-span-4">
                <Select
                    id="product_id"
                    name="product_id"
                    label="Produk"
                    options={productOptions}
                    searchable={true}
                    bind:value={filters.product_id}
                />
            </div>
            <div class="col-span-4">
                <Select
                    id="warehouse_id"
                    name="warehouse_id"
                    label="Gudang"
                    options={warehouseFilterOptions}
                    searchable={true}
                    bind:value={filters.warehouse_id}
                />
            </div>
            <div class="col-span-2">
                <Select
                    id="bucket"
                    name="bucket"
                    label="PPN"
                    options={[
                        { value: "", label: "Semua" },
                        { value: "vat", label: "PPN" },
                        { value: "non_vat", label: "Non PPN" },
                    ]}
                    searchable={false}
                    bind:value={filters.bucket}
                />
            </div>
            <div class="col-span-5">
                <DateInput
                    id="date_from"
                    name="date_from"
                    label="Tanggal dari"
                    bind:value={filters.date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
            <div class="col-span-5">
                <DateInput
                    id="date_to"
                    name="date_to"
                    label="Tanggal sampai"
                    bind:value={filters.date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Riwayat Stok" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Gudang</th>
                            <th>Produk</th>
                            <th>Tipe</th>
                            <th>PPN</th>
                            <th>Qty</th>
                            <th>Saldo Sebelum</th>
                            <th>Saldo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if stockCards?.length}
                            {#each stockCards as c}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(c.date)}
                                        </div>
                                        <div
                                            class="text-xs text-gray-600 dark:text-gray-300"
                                        >
                                            {c.time || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {c.warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {c.product?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {c.type}
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1
                                            {c.bucket === 'vat'
                                                ? 'bg-indigo-100 text-indigo-800 ring-indigo-200 dark:bg-indigo-800/20 dark:text-indigo-300 dark:ring-indigo-700'
                                                : 'bg-slate-100 text-slate-800 ring-slate-200 dark:bg-slate-800/20 dark:text-slate-300 dark:ring-slate-700'}"
                                        >
                                            {c.bucket === "vat"
                                                ? "PPN"
                                                : "Non PPN"}
                                        </span>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {c.quantity}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {c.balance_before}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {c.balance_after}
                                        </div>
                                    </td>
                                    <td>
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            icon="fa-solid fa-eye"
                                            onclick={() => openDetail(c)}
                                            >Detail</Button
                                        >
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="9"
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

<Offcanvas
    bind:isOpen={showDetailOffcanvas}
    title="Detail Riwayat Stok"
    size="lg"
    position="right"
>
    {#snippet children()}
        {#if selectedCard}
            <div class="space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Produk
                        </div>
                        <div
                            class="text-xl font-semibold text-gray-900 dark:text-white"
                        >
                            {selectedCard.product?.name || "-"}
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1
                                {selectedCard.type === 'in'
                                    ? 'bg-green-100 text-green-800 ring-green-200 dark:bg-green-800/20 dark:text-green-300 dark:ring-green-700'
                                    : 'bg-orange-100 text-orange-800 ring-orange-200 dark:bg-orange-800/20 dark:text-orange-300 dark:ring-orange-700'}"
                            >
                                {selectedCard.type === "in"
                                    ? "Stok Masuk"
                                    : "Stok Keluar"}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1
                                {selectedCard.bucket === 'vat'
                                    ? 'bg-indigo-100 text-indigo-800 ring-indigo-200 dark:bg-indigo-800/20 dark:text-indigo-300 dark:ring-indigo-700'
                                    : 'bg-slate-100 text-slate-800 ring-slate-200 dark:bg-slate-800/20 dark:text-slate-300 dark:ring-slate-700'}"
                            >
                                {selectedCard.bucket === "vat"
                                    ? "PPN"
                                    : "Non PPN"}
                            </span>
                        </div>
                    </div>
                    <div class="text-right space-y-2">
                        <div
                            class="flex items-center justify-end gap-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            <i class="fa-regular fa-calendar"></i>
                            <span>{formatDateDisplay(selectedCard.date)}</span>
                        </div>
                        <div
                            class="flex items-center justify-end gap-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            <i class="fa-regular fa-clock"></i>
                            <span>{selectedCard.time || "-"}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="min-w-0 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Qty
                        </div>
                        <div
                            class="mt-1 text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                        >
                            {selectedCard.quantity}
                        </div>
                    </div>
                    <div
                        class="min-w-0 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Saldo Sebelum
                        </div>
                        <div
                            class="mt-1 text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                        >
                            {selectedCard.balance_before}
                        </div>
                    </div>
                    <div
                        class="min-w-0 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Saldo Setelah
                        </div>
                        <div
                            class="mt-1 text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                        >
                            {selectedCard.balance_after}
                        </div>
                    </div>
                    <div
                        class="min-w-0 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Harga Per Unit
                        </div>
                        <div
                            class="mt-1 text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                        >
                            {formatCurrency(selectedCard.unit_price)}
                        </div>
                    </div>
                    <div
                        class="min-w-0 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Subtotal
                        </div>
                        <div
                            class="mt-1 text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                        >
                            {formatCurrency(selectedCard.subtotal)}
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div
                            class="min-w-0 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                            <div
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                HPP Terakhir
                            </div>
                            <div
                                class="mt-1 text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                            >
                                {formatCurrency(selectedCard.last_hpp)}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <i class="fa-solid fa-warehouse"></i>
                            <span>Gudang</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {selectedCard.warehouse?.name || "-"}
                        </div>
                    </div>
                    <div
                        class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <i class="fa-solid fa-link"></i>
                            <span>Referensi</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {selectedCard.referencable || "-"}
                        </div>
                    </div>
                    <div
                        class="sm:col-span-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <i class="fa-regular fa-note-sticky"></i>
                            <span>Catatan</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {selectedCard.notes || "-"}
                        </div>
                    </div>
                    <div
                        class="sm:col-span-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <i class="fa-regular fa-user"></i>
                            <span>Pengguna</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {selectedCard.user?.name || "-"}
                        </div>
                    </div>
                </div>
            </div>
        {:else}
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Tidak ada data untuk ditampilkan.
            </div>
        {/if}
    {/snippet}
</Offcanvas>

<Modal bind:isOpen={showInModal} title="Catat Stok Masuk" size="lg">
    {#snippet children()}
        <form id="stock-in-form" onsubmit={submitStockIn}>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <Select
                    id="warehouse_id"
                    name="warehouse_id"
                    label="Gudang"
                    options={warehouseOptions}
                    searchable={true}
                    required
                    bind:value={$stockInForm.warehouse_id}
                    error={$stockInForm.errors.warehouse_id}
                />
                <Select
                    id="product_id"
                    name="product_id"
                    label="Produk"
                    options={products.map((p) => ({
                        value: p.id,
                        label: p.sku ? `${p.name} (${p.sku})` : p.name,
                    }))}
                    searchable={true}
                    required
                    bind:value={$stockInForm.product_id}
                    error={$stockInForm.errors.product_id}
                    onchange={async (val) => {
                        try {
                            const pid = String(val ?? "");
                            if (!pid) {
                                $stockInForm.unit_cost = "";
                                return;
                            }
                            const resp = await fetch(
                                `/stock-histories/product-price?product_id=${encodeURIComponent(pid)}`,
                                { headers: { Accept: "application/json" } },
                            );
                            if (!resp.ok) {
                                $stockInForm.unit_cost = "";
                                return;
                            }
                            const data = await resp.json();
                            $stockInForm.unit_cost = String(data?.price ?? 0);
                        } catch {
                            $stockInForm.unit_cost = "";
                        }
                    }}
                />
                <TextInput
                    id="quantity"
                    name="quantity"
                    label="Jumlah"
                    type="number"
                    min={1}
                    stripZeros={true}
                    required
                    bind:value={$stockInForm.quantity}
                    error={$stockInForm.errors.quantity}
                />
                <TextInput
                    id="unit_cost"
                    name="unit_cost"
                    label="Harga Satuan"
                    type="number"
                    min={0}
                    stripZeros={true}
                    required
                    bind:value={$stockInForm.unit_cost}
                    error={$stockInForm.errors.unit_cost}
                    currency={true}
                    currencySymbol="Rp"
                />
                <Checkbox
                    id="is_vat"
                    name="is_vat"
                    label="PPN aktif"
                    bind:checked={$stockInForm.is_vat}
                />
                <TextInput
                    id="notes"
                    name="notes"
                    label="Catatan"
                    bind:value={$stockInForm.notes}
                    error={$stockInForm.errors.notes}
                />
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showInModal = false)}
            >Batal</Button
        >
        <Button
            variant="primary"
            type="submit"
            form="stock-in-form"
            loading={$stockInForm.processing}
            disabled={$stockInForm.processing}
            icon="fa-solid fa-save">Simpan</Button
        >
    {/snippet}
</Modal>

<Modal bind:isOpen={showOutModal} title="Catat Stok Keluar" size="lg">
    {#snippet children()}
        <form id="stock-out-form" onsubmit={submitStockOut}>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <Select
                    id="warehouse_id_out"
                    name="warehouse_id"
                    label="Gudang"
                    options={warehouseOptions}
                    searchable={true}
                    required
                    bind:value={$stockOutForm.warehouse_id}
                    error={$stockOutForm.errors.warehouse_id}
                />
                <Select
                    id="product_id_out"
                    name="product_id"
                    label="Produk"
                    options={products.map((p) => ({
                        value: p.id,
                        label: p.sku ? `${p.name} (${p.sku})` : p.name,
                    }))}
                    searchable={true}
                    required
                    bind:value={$stockOutForm.product_id}
                    error={$stockOutForm.errors.product_id}
                />
                <TextInput
                    id="quantity_out"
                    name="quantity"
                    label="Jumlah"
                    type="number"
                    min={1}
                    stripZeros={true}
                    required
                    bind:value={$stockOutForm.quantity}
                    error={$stockOutForm.errors.quantity}
                />
                <TextInput
                    id="notes_out"
                    name="notes"
                    label="Catatan"
                    bind:value={$stockOutForm.notes}
                    error={$stockOutForm.errors.notes}
                />
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showOutModal = false)}
            >Batal</Button
        >
        <Button
            variant="primary"
            type="submit"
            form="stock-out-form"
            loading={$stockOutForm.processing}
            disabled={$stockOutForm.processing}
            icon="fa-solid fa-save">Simpan</Button
        >
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showImportInModal}
    title="Import Stok Masuk dari Excel"
    size="md"
>
    {#snippet children()}
        <form id="import-stock-in-form" onsubmit={submitImportIn}>
            <div class="space-y-4">
                <FileUpload
                    id="import-file-in"
                    name="file"
                    label="Pilih File Excel"
                    accept=".xlsx,.xls,.csv"
                    bind:value={$importInForm.file}
                    required
                    error={$importInForm.errors.file}
                    uploadText="Pilih atau drag file Excel"
                    onchange={(files) => {
                        $importInForm.file = files[0] ?? null;
                    }}
                />
                <div
                    class="p-4 bg-blue-50 rounded-lg border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800"
                >
                    <div class="flex justify-between items-center mb-2">
                        <h4
                            class="text-sm font-semibold text-blue-900 dark:text-blue-300"
                        >
                            Format Excel yang dibutuhkan:
                        </h4>
                        <a
                            href="/stock-histories/import/in/template"
                            class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                        >
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>Warehouse Name (wajib)</li>
                        <li>Product Name / SKU (wajib)</li>
                        <li>quantity (wajib, angka)</li>
                        <li>unit_cost (wajib, angka Rupiah)</li>
                        <li>is_vat (opsional, true/false)</li>
                        <li>notes (opsional)</li>
                    </ul>
                </div>
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showImportInModal = false)}
            >Batal</Button
        >
        <Button
            variant="primary"
            type="submit"
            form="import-stock-in-form"
            loading={$importInForm.processing}
            disabled={$importInForm.processing || !$importInForm.file}
            icon="fa-solid fa-file-import">Import</Button
        >
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showImportOutModal}
    title="Import Stok Keluar dari Excel"
    size="md"
>
    {#snippet children()}
        <form id="import-stock-out-form" onsubmit={submitImportOut}>
            <div class="space-y-4">
                <FileUpload
                    id="import-file-out"
                    name="file"
                    label="Pilih File Excel"
                    accept=".xlsx,.xls,.csv"
                    bind:value={$importOutForm.file}
                    required
                    error={$importOutForm.errors.file}
                    uploadText="Pilih atau drag file Excel"
                    onchange={(files) => {
                        $importOutForm.file = files[0] ?? null;
                    }}
                />
                <div
                    class="p-4 bg-blue-50 rounded-lg border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800"
                >
                    <div class="flex justify-between items-center mb-2">
                        <h4
                            class="text-sm font-semibold text-blue-900 dark:text-blue-300"
                        >
                            Format Excel yang dibutuhkan:
                        </h4>
                        <a
                            href="/stock-histories/import/out/template"
                            class="flex gap-1 items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                        >
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <ul
                        class="space-y-1 text-xs list-disc list-inside text-blue-800 dark:text-blue-400"
                    >
                        <li>Warehouse Name (wajib)</li>
                        <li>Product Name / SKU (wajib)</li>
                        <li>quantity (wajib, angka)</li>
                        <li>notes (opsional)</li>
                    </ul>
                </div>
            </div>
        </form>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showImportOutModal = false)}
            >Batal</Button
        >
        <Button
            variant="primary"
            type="submit"
            form="import-stock-out-form"
            loading={$importOutForm.processing}
            disabled={$importOutForm.processing || !$importOutForm.file}
            icon="fa-solid fa-file-import">Import</Button
        >
    {/snippet}
</Modal>
