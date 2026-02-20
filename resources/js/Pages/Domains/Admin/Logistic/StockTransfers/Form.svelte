<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import logo from "@img/logo.png";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";

    type Warehouse = { id: string; name: string };
    type Product = { id: string; name: string; sku: string };
    type Marketing = { id: string; name: string };

    type StockTransferItemInput = {
        product_id: string;
        quantity: number;
        from_owner_user_id?: string | null;
        to_owner_user_id?: string | null;
    };

    type StockTransferHead = {
        id: string;
        number: string | null;
        transfer_date: string | null;
        status: string | null;
        status_label: string | null;
        from_warehouse: { id: string | null; name: string | null };
        to_warehouse: { id: string | null; name: string | null };
        to_owner_user: { id: string | null; name: string | null } | null;
        notes: string | null;
        items: Array<{
            id: string;
            product: { id: string | null; name: string | null };
            quantity: number;
        }>;
    } | null;

    let stockTransfer = $derived(
        $page.props.stock_transfer as StockTransferHead | null,
    );
    let warehouses = $derived(($page.props.warehouses as Warehouse[]) ?? []);
    let products = $derived(($page.props.products as Product[]) ?? []);
    let marketings = $derived(($page.props.marketings as Marketing[]) ?? []);
    let defaultTransferDate = $derived(
        ($page.props.default_transfer_date as string) ?? "",
    );

    let isEdit = $derived(stockTransfer !== null);

    const warehouseOptions = $derived([
        ...warehouses.map((w) => ({ value: w.id, label: w.name })),
    ]);
    const productOptions = $derived([
        ...products.map((p) => ({
            value: p.id,
            label: `${p.name}${p.sku ? " (" + p.sku + ")" : ""}`,
        })),
    ]);
    const marketingOptions = $derived([
        { value: "", label: "Pilih Marketing" },
        ...marketings.map((m) => ({ value: m.id, label: m.name })),
    ]);
    type ProductStockEntry = {
        warehouses: Record<string, number>;
        marketings: Record<string, number>;
    };
    let productStocks = $derived(
        ($page.props.product_stocks as Record<string, ProductStockEntry>) ?? {},
    );

    const form = useForm(
        untrack(() => ({
            from_warehouse_id: stockTransfer?.from_warehouse?.id ?? "",
            to_warehouse_id: stockTransfer?.to_warehouse?.id ?? "",
            to_owner_user_id: stockTransfer?.to_owner_user?.id ?? "",
            from_owner_user_id: "",
            transfer_date:
                stockTransfer?.transfer_date ?? defaultTransferDate ?? "",
            notes: stockTransfer?.notes ?? "",
            items: (stockTransfer?.items ?? []).map((it) => ({
                product_id: it.product.id ?? "",
                quantity: it.quantity ?? 0,
                from_owner_user_id: null,
                to_owner_user_id: stockTransfer?.to_owner_user?.id ?? null,
            })) as StockTransferItemInput[],
        })),
    );

    function backToList() {
        router.visit("/stock-transfers");
    }

    function addItem() {
        const hasFrom =
            fromPartyType === "warehouse"
                ? String($form.from_warehouse_id || "").trim().length > 0
                : String(headerFromOwnerUserId || "").trim().length > 0;
        const hasTo =
            toPartyType === "warehouse"
                ? String($form.to_warehouse_id || "").trim().length > 0
                : String($form.to_owner_user_id || "").trim().length > 0;
        if (!hasFrom || !hasTo) {
            showAddItemWarning = true;
            return;
        }
        isAddingItem = true;
        showEditItemIndex = null;
        editingItemDraft = {
            product_id: "",
            quantity: "1",
        };
        showItemModal = true;
    }

    function removeItem(idx: number) {
        const clone = untrack(() => $form.items.slice());
        clone.splice(idx, 1);
        $form.items = clone;
    }
    function handleEditItem(index: number) {
        isAddingItem = false;
        showEditItemIndex = index;
        const existing = $form.items[index];
        editingItemDraft = existing
            ? {
                  product_id: existing.product_id ?? "",
                  quantity: String(existing.quantity ?? 1),
              }
            : null;
        showItemModal = true;
    }
    function handleEditItemCancel() {
        showItemModal = false;
        showEditItemIndex = null;
        isAddingItem = false;
        editingItemDraft = null;
    }
    function handleEditItemSave() {
        if (!editingItemDraft) return;
        const qtyNum =
            parseInt(String(editingItemDraft.quantity).replace(/\D+/g, "")) ||
            1;
        const itemData = {
            product_id: String(editingItemDraft.product_id || ""),
            quantity: qtyNum,
        } as StockTransferItemInput;
        const availableForProduct = getAvailableStockForDraft(
            itemData.product_id,
        );
        if (itemData.quantity > availableForProduct) {
            return;
        }
        if (isAddingItem) {
            $form.items = [...$form.items, itemData];
        } else if (showEditItemIndex !== null) {
            const idx = showEditItemIndex;
            $form.items = $form.items.map((it, i) =>
                i === idx ? itemData : it,
            );
        }
        handleEditItemCancel();
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && stockTransfer) {
            $form.put(`/stock-transfers/${stockTransfer.id}`, {
                preserveScroll: true,
                onSuccess: () => router.visit("/stock-transfers"),
            });
        } else {
            $form.post("/stock-transfers", {
                preserveScroll: true,
                onSuccess: () => router.visit("/stock-transfers"),
            });
        }
    }

    function toBadgeVariant(s: string | null) {
        switch (String(s ?? "")) {
            case "draft":
                return "secondary";
            case "in_transit":
                return "warning";
            case "received":
                return "success";
            case "canceled":
                return "danger";
            default:
                return "secondary";
        }
    }
    let isDraft = $derived(
        String(stockTransfer?.status ?? "draft") === "draft",
    );
    let showSubmitErrorDialog = $state(false);
    let submitErrorMessage = $state<string>("");

    const selectedFromWarehouse = $derived(() => {
        const wid = String($form.from_warehouse_id || "").trim();
        if (!wid) return null;
        return warehouses.find((w) => String(w.id) === wid) ?? null;
    });
    const selectedToWarehouse = $derived(() => {
        const wid = String($form.to_warehouse_id || "").trim();
        if (!wid) return null;
        return warehouses.find((w) => String(w.id) === wid) ?? null;
    });
    const selectedFromMarketingName = $derived(() => {
        const mid = String(headerFromOwnerUserId || "").trim();
        if (!mid) return null;
        const m = marketings.find((mm) => String(mm.id) === mid);
        return m ? m.name : null;
    });
    const selectedToMarketingName = $derived(() => {
        const mid = String($form.to_owner_user_id || "").trim();
        if (!mid) return null;
        const m = marketings.find((mm) => String(mm.id) === mid);
        return m ? m.name : null;
    });
    function getCurrentStock(pid: string): number {
        const p = productStocks[String(pid)] ?? null;
        if (!p) return 0;
        if (fromPartyType === "warehouse") {
            const wid = String($form.from_warehouse_id || "").trim();
            if (!wid) return 0;
            return (p.warehouses?.[wid] ?? 0) as number;
        }
        const mid = String(headerFromOwnerUserId || "").trim();
        if (!mid) return 0;
        return (p.marketings?.[mid] ?? 0) as number;
    }
    function getRequestedQtyOtherItems(pid: string): number {
        const idStr = String(pid || "");
        if (!idStr) return 0;
        return $form.items.reduce((sum, it, i) => {
            if (showEditItemIndex !== null && i === showEditItemIndex)
                return sum;
            return String(it.product_id) === idStr
                ? sum + (it.quantity || 0)
                : sum;
        }, 0);
    }
    function getAvailableStockForDraft(pid: string): number {
        const base = getCurrentStock(pid);
        const used = getRequestedQtyOtherItems(pid);
        const avail = base - used;
        return avail > 0 ? avail : 0;
    }
    type TransferPartyType = "warehouse" | "marketing";
    const partyTypeOptions = $derived([
        { value: "warehouse", label: "Gudang" },
        { value: "marketing", label: "Marketing" },
    ]);
    let fromPartyType = $state<TransferPartyType>("warehouse");
    let toPartyType = $state<TransferPartyType>("warehouse");
    let headerFromOwnerUserId = $state<string>("");
    let toPartyTypeInitialized = $state(false);
    $effect(() => {
        if (!toPartyTypeInitialized) {
            const defaultTo: TransferPartyType = String(
                stockTransfer?.to_owner_user?.id ?? "",
            )
                ? "marketing"
                : "warehouse";
            toPartyType = defaultTo;
            toPartyTypeInitialized = true;
        }
    });
    $effect(() => {
        if (!toPartyTypeInitialized) return;
        if (toPartyType === "warehouse") {
            $form.to_owner_user_id = "";
        } else {
            if (!String($form.to_warehouse_id || "").trim()) {
                $form.to_warehouse_id = String($form.from_warehouse_id || "");
            }
        }
    });
    $effect(() => {
        if (fromPartyType === "warehouse") {
            $form.from_owner_user_id = "";
        } else {
            $form.from_owner_user_id = String(headerFromOwnerUserId || "");
        }
    });
    type StockTransferItemDraft = {
        product_id: string;
        quantity: string;
    } | null;
    let showItemModal = $state(false);
    let isAddingItem = $state(false);
    let showEditItemIndex = $state<number | null>(null);
    let editingItemDraft = $state<StockTransferItemDraft>(null);
    let canSaveEditedItem = $state(false);
    let qtyOverStock = $state(false);
    let showAddItemWarning = $state(false);
    $effect(() => {
        const item = editingItemDraft;
        if (!item) {
            canSaveEditedItem = false;
            qtyOverStock = false;
            return;
        }
        const hasProduct = String(item.product_id ?? "").trim().length > 0;
        const qtyStr = String(item.quantity ?? "").trim();
        const qtyNum = Number(qtyStr.replace(/\D+/g, ""));
        const available = hasProduct
            ? getAvailableStockForDraft(String(item.product_id))
            : 0;
        qtyOverStock =
            hasProduct && Number.isFinite(qtyNum) && qtyNum > available;
        canSaveEditedItem =
            hasProduct &&
            Number.isFinite(qtyNum) &&
            qtyNum > 0 &&
            qtyNum <= available;
    });
</script>

<svelte:head>
    <title>
        {isEdit ? "Edit" : "Tambah"} Mutasi Stok | {siteName(
            $page.props.settings,
        )}
    </title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Mutasi Stok
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui data mutasi stok antar gudang"
                    : "Buat mutasi stok antar gudang"}
            </p>
            {#if stockTransfer}
                <div class="mt-2">
                    <Badge variant={toBadgeVariant(stockTransfer.status)}>
                        {stockTransfer.status_label ??
                            stockTransfer.status ??
                            "Draft"}
                    </Badge>
                </div>
            {/if}
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant={isEdit ? "warning" : "success"}
                type="submit"
                loading={$form.processing}
                disabled={$form.processing || (isEdit && !isDraft)}
                icon="fa-solid fa-save"
                form="stock-transfer-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Mutasi Stok
            </Button>
        </div>
    </header>

    <form id="stock-transfer-form" onsubmit={submitForm}>
        <Card collapsible={false} bodyWithoutPadding={true}>
            {#snippet children()}
                <div class="p-6">
                    <div
                        class="flex flex-col md:flex-row justify-between items-start"
                    >
                        <div class="w-full md:w-1/2">
                            <div class="mb-2">
                                <img
                                    src={logo}
                                    alt="Logo"
                                    class="w-90 object-contain"
                                    loading="lazy"
                                />
                            </div>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                {(fromPartyType === "marketing"
                                    ? "Marketing Asal"
                                    : "Gudang Asal") +
                                    " : " +
                                    (fromPartyType === "marketing"
                                        ? selectedFromMarketingName() || "-"
                                        : selectedFromWarehouse()?.name || "-")}
                            </p>
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                            >
                                {(toPartyType === "marketing"
                                    ? "Marketing Tujuan"
                                    : "Gudang Tujuan") +
                                    " : " +
                                    (toPartyType === "marketing"
                                        ? selectedToMarketingName() || "-"
                                        : selectedToWarehouse()?.name || "-")}
                            </p>
                        </div>
                        <div
                            class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                        >
                            <h2
                                class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                            >
                                MUTASI STOK
                            </h2>
                            <div
                                class="w-full flex justify-end gap-12 text-right"
                            >
                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        No Surat
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {stockTransfer?.number ?? "-"}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                    >
                                        Tgl Mutasi
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {$form.transfer_date || "-"}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-[#212121] my-4" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select
                            label="Tipe Sumber Stok"
                            options={partyTypeOptions}
                            value={fromPartyType}
                            onchange={(val) =>
                                (fromPartyType = String(
                                    val ?? "",
                                ) as TransferPartyType)}
                        />
                        <Select
                            label="Tipe Tujuan Stok"
                            options={partyTypeOptions}
                            value={toPartyType}
                            onchange={(val) =>
                                (toPartyType = String(
                                    val ?? "",
                                ) as TransferPartyType)}
                        />
                        {#if fromPartyType === "warehouse"}
                            <Select
                                label="Gudang Asal"
                                options={warehouseOptions}
                                value={$form.from_warehouse_id}
                                onchange={(val) =>
                                    ($form.from_warehouse_id = String(
                                        val ?? "",
                                    ))}
                                required={true}
                            />
                        {/if}
                        {#if fromPartyType === "marketing"}
                            <Select
                                label="Marketing Asal"
                                options={marketingOptions}
                                value={headerFromOwnerUserId}
                                onchange={(val) =>
                                    (() => {
                                        const v = String(val ?? "");
                                        headerFromOwnerUserId = v;
                                        $form.from_owner_user_id = v;
                                    })()}
                            />
                        {/if}
                        {#if toPartyType === "warehouse"}
                            <Select
                                label="Gudang Tujuan"
                                options={warehouseOptions}
                                value={$form.to_warehouse_id}
                                onchange={(val) =>
                                    ($form.to_warehouse_id = String(val ?? ""))}
                                required={true}
                            />
                        {/if}

                        {#if toPartyType === "marketing"}
                            <Select
                                label="Marketing Tujuan"
                                options={marketingOptions}
                                value={$form.to_owner_user_id ?? ""}
                                onchange={(val) =>
                                    ($form.to_owner_user_id =
                                        String(val ?? "") || "")}
                            />
                        {/if}
                        <DateInput
                            label="Tanggal Mutasi"
                            value={$form.transfer_date}
                            onchange={(val) =>
                                ($form.transfer_date = String(val ?? ""))}
                            required={true}
                        />
                        <TextInput
                            id="notes"
                            name="notes"
                            label="Catatan"
                            placeholder="Opsional"
                            value={$form.notes}
                            oninput={(e) => {
                                const val =
                                    "value" in e
                                        ? e.value
                                        : (e.target as HTMLInputElement).value;
                                $form.notes = val;
                            }}
                            class="md:col-span-2"
                        />
                    </div>
                    <hr class="border-gray-200 dark:border-[#212121] my-4" />
                    <div class="overflow-x-auto mb-6">
                        <table
                            class="w-full text-sm text-left custom-table border-collapse dark:text-gray-300"
                        >
                            <thead
                                class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-200 dark:bg-gray-800"
                            >
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-12"
                                        >NO</th
                                    >
                                    <th scope="col" class="px-3 py-2 w-1/4"
                                        >PRODUK</th
                                    >
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-20"
                                        >QTY</th
                                    >
                                    <th
                                        scope="col"
                                        class="px-3 py-2 text-center w-24"
                                        >STOK</th
                                    >
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 dark:text-gray-300">
                                {#each $form.items as item, index}
                                    <tr>
                                        <td class="px-3 py-2 text-center"
                                            >{index + 1}</td
                                        >
                                        <td class="px-3 py-2">
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <span>
                                                    {(() => {
                                                        const p = products.find(
                                                            (pp) =>
                                                                String(
                                                                    pp.id,
                                                                ) ===
                                                                String(
                                                                    item.product_id,
                                                                ),
                                                        );
                                                        return p
                                                            ? `${p.name}${p.sku ? " (" + p.sku + ")" : ""}`
                                                            : "-";
                                                    })()}
                                                </span>
                                                <div class="flex gap-2">
                                                    <i
                                                        class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                                        role="button"
                                                        tabindex="0"
                                                        aria-label="Edit item"
                                                        onclick={() =>
                                                            handleEditItem(
                                                                index,
                                                            )}
                                                        onkeydown={(e) => {
                                                            if (
                                                                e.key ===
                                                                    "Enter" ||
                                                                e.key === " "
                                                            )
                                                                handleEditItem(
                                                                    index,
                                                                );
                                                        }}
                                                    ></i>
                                                    <i
                                                        class="fa-solid fa-trash-can text-red-500 cursor-pointer text-xs"
                                                        role="button"
                                                        tabindex="0"
                                                        aria-label="Hapus item"
                                                        onclick={() =>
                                                            removeItem(index)}
                                                        onkeydown={(e) => {
                                                            if (
                                                                e.key ===
                                                                    "Enter" ||
                                                                e.key === " "
                                                            )
                                                                removeItem(
                                                                    index,
                                                                );
                                                        }}
                                                    ></i>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center"
                                            >{item.quantity}</td
                                        >
                                        <td class="px-3 py-2 text-center">
                                            {getCurrentStock(item.product_id)}
                                        </td>
                                    </tr>
                                {/each}
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-2 bg-gray-50/50 dark:bg-gray-800/50"
                                    >
                                        <Button
                                            variant="link"
                                            icon="fa-solid fa-circle-plus"
                                            class="flex items-center gap-1 text-green-600 dark:text-green-400 font-bold hover:text-green-700 dark:hover:text-green-300 text-xs uppercase"
                                            onclick={addItem}
                                        >
                                            Tambah Item
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            {/snippet}
        </Card>
        {#if $form.errors.items}
            <div
                class="mt-2 text-sm text-red-600 dark:text-red-400"
                role="alert"
            >
                {$form.errors.items}
            </div>
        {/if}
        {#if Object.keys(($form.errors as any) ?? {}).length}
            <div
                class="mt-2 text-sm text-red-600 dark:text-red-400"
                role="alert"
            >
                <p class="font-semibold">Validasi gagal:</p>
                <ul class="list-disc ml-5">
                    {#each Object.entries(($form.errors as Record<string, string>) ?? {}) as entry}
                        <li>{entry[0]}: {entry[1]}</li>
                    {/each}
                </ul>
            </div>
        {/if}
    </form>
</section>

<Modal
    isOpen={showItemModal}
    onClose={handleEditItemCancel}
    title={isAddingItem ? "Tambah Item" : "Edit Item"}
>
    <form
        onsubmit={(e) => {
            e.preventDefault();
            handleEditItemSave();
        }}
    >
        <div class="space-y-3">
            <Select
                label="Produk"
                options={productOptions}
                value={editingItemDraft?.product_id ?? ""}
                onchange={(val) => {
                    if (!editingItemDraft) return;
                    editingItemDraft = {
                        ...editingItemDraft,
                        product_id: String(val ?? ""),
                    };
                }}
                required={true}
            />
            <p class="text-xs text-gray-600 dark:text-gray-400">
                Stok Terkini:
                {editingItemDraft
                    ? getCurrentStock(editingItemDraft.product_id)
                    : 0}
            </p>
            <TextInput
                id="edit-qty"
                name="edit-qty"
                type="number"
                min={1}
                max={editingItemDraft
                    ? getAvailableStockForDraft(
                          String(editingItemDraft.product_id),
                      )
                    : null}
                label="Jumlah"
                value={String(editingItemDraft?.quantity ?? "1")}
                oninput={(e) => {
                    const raw =
                        "value" in e
                            ? e.value
                            : (e.target as HTMLInputElement).value;
                    if (!editingItemDraft) return;
                    editingItemDraft = {
                        ...editingItemDraft,
                        quantity: String(raw),
                    };
                }}
                required={true}
            />
            {#if qtyOverStock}
                <p class="text-xs text-red-600 dark:text-red-400" role="alert">
                    Jumlah melebihi stok tersedia (tersisa
                    {editingItemDraft
                        ? getAvailableStockForDraft(
                              String(editingItemDraft.product_id),
                          )
                        : 0}
                    ).
                </p>
            {/if}
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <Button variant="secondary" onclick={handleEditItemCancel}
                >Batal</Button
            >
            <Button
                variant="primary"
                type="submit"
                disabled={!canSaveEditedItem}>Simpan</Button
            >
        </div>
    </form>
</Modal>

<Dialog
    bind:isOpen={showAddItemWarning}
    type="warning"
    title="Peringatan"
    message="Pilih asal dan tujuan terlebih dahulu sebelum menambah item."
    confirmText="Tutup"
    showCancel={false}
/>

<Dialog
    bind:isOpen={showSubmitErrorDialog}
    type="danger"
    title="Gagal Submit"
    message={submitErrorMessage || "Terjadi kesalahan validasi."}
    confirmText="Tutup"
    showCancel={false}
/>
