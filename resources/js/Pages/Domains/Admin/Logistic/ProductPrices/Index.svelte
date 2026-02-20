<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import {
        getCurrencySymbol,
        formatCurrencyWithoutSymbol,
    } from "@/Lib/Admin/Utils/currency";

    type ProductItem = { id: string; name: string; sku: string | null };
    type LevelItem = {
        id: string;
        name: string;
        percent_adjust?: number | null;
    };
    type SupplierItem = {
        id: string;
        name: string;
        percent_adjust?: number | null;
    };

    let products = $derived(($page.props.products as ProductItem[]) ?? []);
    let levels = $derived(($page.props.levels as LevelItem[]) ?? []);
    let sellingPriceMap = $derived(
        ($page.props.sellingPriceMap as Record<
            string,
            Record<string, number>
        >) ?? {},
    );
    let sellingPriceMainMap = $derived(
        ($page.props.sellingPriceMainMap as Record<string, number>) ?? {},
    );
    let purchasePriceMap = $derived(
        ($page.props.purchasePriceMap as Record<string, number>) ?? {},
    );
    let suppliers = $derived(($page.props.suppliers as SupplierItem[]) ?? []);
    let supplierPriceMap = $derived(
        ($page.props.supplierPriceMap as Record<
            string,
            Record<string, number>
        >) ?? {},
    );
    const supplierNameMap = $derived(
        Object.fromEntries(suppliers.map((s) => [s.id, s.name])),
    );
    const currencySymbol = getCurrencySymbol();
    let activeTab = $state<"purchase" | "selling">("purchase");

    type Filters = {
        q: string;
        per_page?: number;
    };
    let filters = $state<Filters>({
        q: ($page.props.filters?.q as string) ?? "",
        per_page: ($page.props.filters?.per_page as number) ?? 15,
    });

    let lastAppliedQ = $state<string>(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | null = null;
    $effect(() => {
        const q = filters.q;
        if (q === lastAppliedQ) {
            return;
        }
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            lastAppliedQ = q;
            applyFilters();
        }, 400);
    });

    let localPurchase = $state<Record<string, string>>({});
    let localSellingMain = $state<Record<string, string>>({});
    let localSelling = $state<Record<string, Record<string, string>>>({});
    let localSupplier = $state<Record<string, Record<string, string>>>({});
    function initLocalMaps() {
        const p: Record<string, string> = {};
        const m: Record<string, string> = {};
        const s: Record<string, Record<string, string>> = {};
        const sp: Record<string, Record<string, string>> = {};
        for (const prod of products) {
            const pid = prod.id;
            p[pid] =
                purchasePriceMap[pid] != null
                    ? String(purchasePriceMap[pid])
                    : "";
            m[pid] =
                sellingPriceMainMap[pid] != null
                    ? String(sellingPriceMainMap[pid])
                    : "";
            const row: Record<string, string> = {};
            for (const lvl of levels) {
                const lid = lvl.id;
                const initial = sellingPriceMap[pid]?.[lid] ?? null;
                row[lid] = initial != null ? String(initial) : "";
            }
            s[pid] = row;
            const sprow: Record<string, string> = {};
            for (const sup of suppliers) {
                const sid = sup.id;
                const initialSp = supplierPriceMap[pid]?.[sid] ?? null;
                sprow[sid] = initialSp != null ? String(initialSp) : "";
            }
            sp[pid] = sprow;
        }
        localPurchase = p;
        localSellingMain = m;
        localSelling = s;
        localSupplier = sp;
    }
    $effect(() => {
        products;
        levels;
        sellingPriceMap;
        sellingPriceMainMap;
        purchasePriceMap;
        suppliers;
        supplierPriceMap;
        initLocalMaps();
    });

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.per_page) params.set("per_page", String(filters.per_page));
        router.get(
            "/product-prices?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    let saving = $state(false);
    let addLevelOpen = $state(false);
    let addLevelLoading = $state(false);
    function openAddLevel() {
        addLevelOpen = true;
    }
    async function submitAddLevel(formData?: Record<string, any>) {
        const name = String(formData?.name ?? "").trim();
        if (!name) {
            return;
        }
        addLevelLoading = true;
        await router.post(
            "/product-prices/levels",
            { name },
            {
                preserveScroll: true,
                onFinish: () => {
                    addLevelLoading = false;
                    addLevelOpen = false;
                },
            },
        );
    }
    let deleteLevelOpen = $state(false);
    let deleteLevelLoading = $state(false);
    let deleteTarget = $state<{ id: string; name: string } | null>(null);
    function openDeleteLevel(lvl: LevelItem) {
        deleteTarget = { id: lvl.id, name: lvl.name };
        deleteLevelOpen = true;
    }
    async function submitDeleteLevel() {
        if (!deleteTarget) return;
        deleteLevelLoading = true;
        await router.delete(`/product-prices/levels/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => {
                deleteLevelLoading = false;
                deleteLevelOpen = false;
                deleteTarget = null;
            },
        });
    }
    let adjustLevelOpen = $state(false);
    let adjustLevelLoading = $state(false);
    let adjustTarget = $state<{
        id: string;
        name: string;
        percent_adjust?: number | null;
    } | null>(null);
    function openAdjustLevel(lvl: LevelItem) {
        adjustTarget = {
            id: lvl.id,
            name: lvl.name,
            ...(lvl.percent_adjust !== undefined
                ? { percent_adjust: lvl.percent_adjust }
                : {}),
        };
        adjustLevelOpen = true;
    }
    async function submitAdjustLevel(formData?: Record<string, any>) {
        if (!adjustTarget) return;
        const raw = String(formData?.percent ?? "").trim();
        if (raw === "") return;
        const percent = Number(raw);
        if (!isFinite(percent)) return;
        adjustLevelLoading = true;
        await router.post(
            `/product-prices/levels/${adjustTarget.id}/adjust`,
            { percent, q: filters.q },
            {
                preserveScroll: true,
                onFinish: () => {
                    adjustLevelLoading = false;
                    adjustLevelOpen = false;
                    adjustTarget = null;
                },
            },
        );
    }
    let adjustSupplierOpen = $state(false);
    let adjustSupplierLoading = $state(false);
    let adjustSupplierTarget = $state<{
        id: string;
        name: string;
        percent_adjust?: number | null;
    } | null>(null);
    function openAdjustSupplier(sup: SupplierItem) {
        adjustSupplierTarget = {
            id: sup.id,
            name: sup.name,
            ...(sup.percent_adjust !== undefined
                ? { percent_adjust: sup.percent_adjust }
                : {}),
        };
        adjustSupplierOpen = true;
    }
    async function submitAdjustSupplier(formData?: Record<string, any>) {
        if (!adjustSupplierTarget) return;
        const raw = String(formData?.percent ?? "").trim();
        if (raw === "") return;
        const percent = Number(raw);
        if (!isFinite(percent)) return;
        adjustSupplierLoading = true;
        await router.post(
            `/product-prices/suppliers/${adjustSupplierTarget.id}/adjust`,
            { percent, q: filters.q },
            {
                preserveScroll: true,
                onFinish: () => {
                    adjustSupplierLoading = false;
                    adjustSupplierOpen = false;
                    adjustSupplierTarget = null;
                },
            },
        );
    }
    function savePrices() {
        const entries: Array<{
            type: "purchase" | "selling" | "supplier";
            product_id: string;
            selling_price_level_id?: string | null;
            supplier_id?: string | null;
            price: number | null;
        }> = [];
        for (const prod of products) {
            const pid = prod.id;
            const initialPurchase =
                purchasePriceMap[pid] != null
                    ? Number(purchasePriceMap[pid])
                    : null;
            const currentPurchaseRaw = localPurchase[pid] ?? "";
            const currentPurchase =
                currentPurchaseRaw.trim() === ""
                    ? null
                    : Number(currentPurchaseRaw.replace(/\D+/g, ""));
            const samePurchase = initialPurchase === currentPurchase;
            if (!samePurchase) {
                entries.push({
                    type: "purchase",
                    product_id: pid,
                    price: currentPurchase,
                });
            }
            const initialMain =
                sellingPriceMainMap[pid] != null
                    ? Number(sellingPriceMainMap[pid])
                    : null;
            const currentMainRaw = localSellingMain[pid] ?? "";
            const currentMain =
                currentMainRaw.trim() === ""
                    ? null
                    : Number(currentMainRaw.replace(/\D+/g, ""));
            const sameMain = initialMain === currentMain;
            if (!sameMain) {
                entries.push({
                    type: "selling",
                    product_id: pid,
                    price: currentMain,
                });
            }
            for (const lvl of levels) {
                const lid = lvl.id;
                const initialSelling =
                    sellingPriceMap[pid]?.[lid] != null
                        ? Number(sellingPriceMap[pid][lid])
                        : null;
                const currentSellingRaw = localSelling[pid]?.[lid] ?? "";
                const currentSelling =
                    currentSellingRaw.trim() === ""
                        ? null
                        : Number(currentSellingRaw.replace(/\D+/g, ""));
                const sameSelling = initialSelling === currentSelling;
                if (!sameSelling) {
                    entries.push({
                        type: "selling",
                        product_id: pid,
                        selling_price_level_id: lid,
                        price: currentSelling,
                    });
                }
            }
            for (const sup of suppliers) {
                const sid = sup.id;
                const initialSupplier =
                    supplierPriceMap[pid]?.[sid] != null
                        ? Number(supplierPriceMap[pid][sid])
                        : null;
                const currentSupplierRaw = localSupplier[pid]?.[sid] ?? "";
                const currentSupplier =
                    currentSupplierRaw.trim() === ""
                        ? null
                        : Number(currentSupplierRaw.replace(/\D+/g, ""));
                const sameSupplier = initialSupplier === currentSupplier;
                if (!sameSupplier) {
                    entries.push({
                        type: "supplier",
                        product_id: pid,
                        supplier_id: sid,
                        price: currentSupplier,
                    });
                }
            }
        }
        if (entries.length === 0) return;
        saving = true;
        router.post(
            "/product-prices",
            {
                q: filters.q,
                per_page: filters.per_page,
                entries,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    saving = false;
                },
            },
        );
    }

    let meta = $derived(
        ($page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        }) ?? { current_page: 1, per_page: 15, total: 0, last_page: 1 },
    );
    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.per_page) params.set("per_page", String(filters.per_page));
        params.set("page", String(pageNum));
        router.get(
            "/product-prices?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Harga Produk | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Harga Produk
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola harga beli dan harga jual per level
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="warning"
                icon="fa-solid fa-floppy-disk"
                loading={saving}
                onclick={savePrices}>Simpan Perubahan</Button
            >
            {#if activeTab === "selling"}
                <Button
                    variant="secondary"
                    icon="fa-solid fa-tags"
                    onclick={openAddLevel}>Tambah Level Harga Jual</Button
                >
            {/if}
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <div class="flex gap-2">
                <Button
                    variant="secondary"
                    icon="fa-solid fa-arrow-rotate-left"
                    onclick={() => {
                        filters.q = "";
                        lastAppliedQ = "";
                        applyFilters();
                    }}>Reset</Button
                >
            </div>
        {/snippet}
        {#snippet children()}
            <TextInput
                id="q"
                name="q"
                label="Cari Produk"
                placeholder="Nama atau SKU..."
                bind:value={filters.q}
            />
        {/snippet}
    </Card>

    <div>
        <Tab
            tabs={[
                { id: "purchase", label: "Harga Beli" },
                { id: "selling", label: "Harga Jual" },
            ]}
            {activeTab}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                activeTab = tabId as "purchase" | "selling";
            }}
        />
    </div>

    {#if activeTab === "purchase"}
        <Card collapsible={false} bodyWithoutPadding={true}>
            {#snippet children()}
                <div class="overflow-x-auto">
                    <table
                        class="custom-table min-w-max table-auto overflow-hidden"
                    >
                        <thead>
                            <tr>
                                <th
                                    class="sticky left-0 z-20 text-left w-56 bg-gray-200 dark:bg-gray-800"
                                >
                                    Produk
                                </th>
                                <th class="text-right w-36"> Harga Beli </th>
                                {#if suppliers.length > 0}
                                    {#each suppliers as sup}
                                        <th class="text-right w-48">
                                            <div
                                                class="flex items-center justify-end gap-2"
                                            >
                                                <span>Supplier: {sup.name}</span
                                                >
                                                <Button
                                                    variant="secondary"
                                                    size="xs"
                                                    icon="fa-solid fa-percent"
                                                    onclick={() =>
                                                        openAdjustSupplier(sup)}
                                                >
                                                    Atur %
                                                </Button>
                                            </div>
                                        </th>
                                    {/each}
                                {/if}
                            </tr>
                        </thead>
                        <tbody>
                            {#each products as p}
                                <tr>
                                    <td
                                        class="sticky left-0 z-10 w-56 bg-white dark:bg-gray-800"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {p.name}
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {p.sku || "-"}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="w-36 ml-auto">
                                            <TextInput
                                                id={"purchase_" + p.id}
                                                name={"purchase_" + p.id}
                                                type="text"
                                                currency={true}
                                                currencySymbol="Rp"
                                                thousandSeparator="."
                                                decimalSeparator=","
                                                maxDecimals={0}
                                                value={localPurchase[p.id] ??
                                                    ""}
                                                oninput={(e) => {
                                                    const ev = e as unknown as {
                                                        value: string;
                                                        numericValue:
                                                            | number
                                                            | null;
                                                    };
                                                    localPurchase[p.id] =
                                                        ev.value ?? "";
                                                }}
                                                placeholder="Rp 0"
                                            />
                                        </div>
                                    </td>
                                    {#if suppliers.length > 0}
                                        {#each suppliers as sup}
                                            <td class="text-right">
                                                <div class="w-48 ml-auto">
                                                    <TextInput
                                                        id={"supplier_" +
                                                            p.id +
                                                            "_" +
                                                            sup.id}
                                                        name={"supplier_" +
                                                            p.id +
                                                            "_" +
                                                            sup.id}
                                                        type="text"
                                                        currency={true}
                                                        currencySymbol="Rp"
                                                        thousandSeparator="."
                                                        decimalSeparator=","
                                                        maxDecimals={0}
                                                        value={localSupplier[
                                                            p.id
                                                        ]?.[sup.id] ?? ""}
                                                        oninput={(e) => {
                                                            const ev =
                                                                e as unknown as {
                                                                    value: string;
                                                                    numericValue:
                                                                        | number
                                                                        | null;
                                                                };
                                                            const row =
                                                                localSupplier[
                                                                    p.id
                                                                ] ??
                                                                (localSupplier[
                                                                    p.id
                                                                ] = {});
                                                            row[sup.id] =
                                                                ev.value ?? "";
                                                        }}
                                                        placeholder="Rp 0"
                                                    />
                                                </div>
                                            </td>
                                        {/each}
                                    {/if}
                                </tr>
                            {/each}
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
    {/if}

    {#if activeTab === "selling"}
        <Card collapsible={false} bodyWithoutPadding={true}>
            {#snippet children()}
                <div class="w-full overflow-x-auto">
                    <table
                        class="custom-table min-w-max table-auto overflow-hidden"
                    >
                        <thead>
                            <tr>
                                <th
                                    class="sticky left-0 z-20 text-left w-56 bg-gray-200 dark:bg-gray-800"
                                >
                                    Produk
                                </th>
                                <th class="text-right w-48">
                                    Harga Jual Utama
                                </th>
                                {#each levels as lvl}
                                    <th class="text-right w-48">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <span>Level: {lvl.name}</span>
                                            <Button
                                                variant="danger"
                                                size="xs"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteLevel(lvl)}
                                            >
                                                Hapus
                                            </Button>
                                            <Button
                                                variant="secondary"
                                                size="xs"
                                                icon="fa-solid fa-percent"
                                                onclick={() =>
                                                    openAdjustLevel(lvl)}
                                            >
                                                Atur %
                                            </Button>
                                        </div>
                                    </th>
                                {/each}
                            </tr>
                        </thead>
                        <tbody>
                            {#each products as p}
                                <tr>
                                    <td
                                        class="sticky left-0 z-10 w-56 bg-white dark:bg-gray-800"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {p.name}
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {p.sku || "-"}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="w-48 ml-auto">
                                            <TextInput
                                                id={"selling_main_" + p.id}
                                                name={"selling_main_" + p.id}
                                                type="text"
                                                currency={true}
                                                currencySymbol="Rp"
                                                thousandSeparator="."
                                                decimalSeparator=","
                                                maxDecimals={0}
                                                value={localSellingMain[p.id] ??
                                                    ""}
                                                oninput={(e) => {
                                                    const ev = e as unknown as {
                                                        value: string;
                                                        numericValue:
                                                            | number
                                                            | null;
                                                    };
                                                    localSellingMain[p.id] =
                                                        ev.value ?? "";
                                                }}
                                                placeholder="Rp 0"
                                            />
                                        </div>
                                    </td>
                                    {#each levels as lvl}
                                        <td class="px-4 py-3 text-right">
                                            <div class="w-48 ml-auto">
                                                <TextInput
                                                    id={"selling_" +
                                                        p.id +
                                                        "_" +
                                                        lvl.id}
                                                    name={"selling_" +
                                                        p.id +
                                                        "_" +
                                                        lvl.id}
                                                    type="text"
                                                    currency={true}
                                                    currencySymbol="Rp"
                                                    thousandSeparator="."
                                                    decimalSeparator=","
                                                    maxDecimals={0}
                                                    value={localSelling[p.id]?.[
                                                        lvl.id
                                                    ] ?? ""}
                                                    oninput={(e) => {
                                                        const ev =
                                                            e as unknown as {
                                                                value: string;
                                                                numericValue:
                                                                    | number
                                                                    | null;
                                                            };
                                                        const row =
                                                            localSelling[
                                                                p.id
                                                            ] ??
                                                            (localSelling[
                                                                p.id
                                                            ] = {});
                                                        row[lvl.id] =
                                                            ev.value ?? "";
                                                    }}
                                                    placeholder="Rp 0"
                                                />
                                            </div>
                                        </td>
                                    {/each}
                                </tr>
                            {/each}
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
    {/if}

    <Dialog
        isOpen={addLevelOpen}
        title="Tambah Level Harga Jual"
        message="Masukkan nama level harga jual"
        confirmText="Simpan"
        cancelText="Batal"
        showCancel={true}
        loading={addLevelLoading}
        form_fields={[
            {
                id: "name",
                name: "name",
                type: "text",
                label: "Nama Level",
                placeholder: "Contoh: Retail, Grosir, VIP",
                required: true,
            },
        ]}
        form_data={{ name: "" }}
        onConfirm={submitAddLevel}
        onCancel={() => {
            addLevelOpen = false;
        }}
        onClose={() => {
            addLevelOpen = false;
        }}
    />
    <Dialog
        isOpen={deleteLevelOpen}
        type="danger"
        title="Hapus Level Harga Jual"
        message={`Anda yakin ingin menghapus level "${deleteTarget?.name ?? ""}"? Semua harga jual pada level ini akan dihapus.`}
        confirmText="Hapus"
        cancelText="Batal"
        showCancel={true}
        loading={deleteLevelLoading}
        onConfirm={submitDeleteLevel}
        onCancel={() => {
            deleteLevelOpen = false;
            deleteTarget = null;
        }}
        onClose={() => {
            deleteLevelOpen = false;
            deleteTarget = null;
        }}
    />
    <Dialog
        isOpen={adjustLevelOpen}
        title="Atur Persentase Level Harga Jual"
        message={`Masukkan persentase untuk level "${adjustTarget?.name ?? ""}". Gunakan nilai negatif untuk penurunan.`}
        confirmText="Terapkan"
        cancelText="Batal"
        showCancel={true}
        loading={adjustLevelLoading}
        form_fields={[
            {
                id: "percent",
                name: "percent",
                type: "number",
                label: "Persentase (%)",
                placeholder: "Contoh: 10 atau -5",
                required: true,
            },
        ]}
        form_data={{
            percent: adjustLevelOpen
                ? (adjustTarget?.percent_adjust ?? "")
                : "",
        }}
        onConfirm={submitAdjustLevel}
        onCancel={() => {
            adjustLevelOpen = false;
            adjustTarget = null;
        }}
        onClose={() => {
            adjustLevelOpen = false;
            adjustTarget = null;
        }}
    />
    <Dialog
        isOpen={adjustSupplierOpen}
        title="Atur Persentase Harga Beli Supplier"
        message={`Masukkan persentase untuk supplier "${adjustSupplierTarget?.name ?? ""}". Gunakan nilai negatif untuk penurunan.`}
        confirmText="Terapkan"
        cancelText="Batal"
        showCancel={true}
        loading={adjustSupplierLoading}
        form_fields={[
            {
                id: "percent",
                name: "percent",
                type: "number",
                label: "Persentase (%)",
                placeholder: "Contoh: 10 atau -5",
                required: true,
            },
        ]}
        form_data={{
            percent: adjustSupplierOpen
                ? (adjustSupplierTarget?.percent_adjust ?? "")
                : "",
        }}
        onConfirm={submitAdjustSupplier}
        onCancel={() => {
            adjustSupplierOpen = false;
            adjustSupplierTarget = null;
        }}
        onClose={() => {
            adjustSupplierOpen = false;
            adjustSupplierTarget = null;
        }}
    />
</section>
