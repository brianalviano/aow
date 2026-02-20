<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { untrack } from "svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import logo from "@img/logo.png";

    type Option = { value: string; label: string };
    type WarehouseItem = {
        id: string;
        name: string;
        address: string;
        phone: string;
    };
    type ProductItem = { id: string; name: string; sku: string };
    type StockInfo = {
        warehouse_id: string;
        product_id: string;
        quantity: number;
    };

    type StockDocumentItem = {
        product_id: string;
        quantity: string;
        notes: string;
    };

    type StockDocumentData = {
        id: string;
        number: string;
        warehouse: {
            id: string | null;
            name: string | null;
            address: string | null;
            phone: string | null;
        };
        document_date: string | null;
        type: string | null;
        reason: string | null;
        notes: string | null;
        status: string | null;
        items: Array<{
            id: string;
            product: { id: string | null; name: string | null };
            quantity: number;
            notes: string | null;
        }>;
    } | null;

    let stockDocument = $derived(
        $page.props.stock_document as StockDocumentData,
    );
    let warehouses = $derived($page.props.warehouses as WarehouseItem[]);
    let products = $derived($page.props.products as ProductItem[]);
    let stocks = $derived(($page.props.stocks ?? []) as StockInfo[]);
    const typeOptions = $derived(($page.props.typeOptions ?? []) as Option[]);
    const reasonOptions = $derived(
        ($page.props.reasonOptions ?? []) as Option[],
    );
    const bucketOptions = $derived(
        ($page.props.bucketOptions ?? []) as Option[],
    );
    const reasonOptionsByType = $derived(
        ($page.props.reasonOptionsByType ?? {
            in: [] as Option[],
            out: [] as Option[],
        }) as { in: Option[]; out: Option[] },
    );
    const visibleReasonOptions = $derived(() => {
        const t = String($form.type ?? "");
        if (t === "in") return reasonOptionsByType.in;
        if (t === "out") return reasonOptionsByType.out;
        return [];
    });

    let isEdit = $derived(stockDocument !== null);

    // Modal states
    let showWarehouseModal = $state(false);
    let showDocumentTypeModal = $state(false);
    let showReasonModal = $state(false);
    let showNotesModal = $state(false);
    let showBucketModal = $state(false);
    let showEditItemIndex = $state<number | null>(null);
    let isAddingItem = $state(false);
    let editingItemDraft = $state<StockDocumentItem | null>(null);
    let showAddItemWarning = $state(false);
    let showReasonTypeWarning = $state(false);
    let showSourceReasonWarning = $state(false);

    // Format date indo
    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(d);
    }

    const form = useForm(
        untrack(() => ({
            warehouse_id: stockDocument?.warehouse?.id ?? "",
            document_date:
                stockDocument?.document_date ??
                new Date().toISOString().split("T")[0],
            type: stockDocument?.type ?? "",
            reason: stockDocument?.reason ?? "",
            bucket: (stockDocument as any)?.bucket ?? "",
            notes: stockDocument?.notes ?? "",
            status: stockDocument?.status ?? "",
            items:
                stockDocument?.items?.map((it) => ({
                    product_id: it.product?.id ?? "",
                    quantity: String(it.quantity),
                    notes: it.notes ?? "",
                })) ?? ([] as StockDocumentItem[]),
        })),
    );

    const warehouseOptions = $derived(
        warehouses.map((w) => ({
            value: String(w.id),
            label: w.name,
        })),
    );

    const takenProductIds = $derived(() => {
        return new Set(
            $form.items
                .map((it) => String(it.product_id).trim())
                .filter((id) => id.length > 0),
        );
    });
    const availableProductOptions = $derived(() => {
        const taken = takenProductIds();
        const currentEditId =
            showEditItemIndex !== null
                ? String(
                      $form.items[showEditItemIndex]?.product_id ?? "",
                  ).trim()
                : "";
        return products
            .filter((p) => {
                const pid = String(p.id);
                return !taken.has(pid) || pid === currentEditId;
            })
            .map((p) => ({
                value: String(p.id),
                label: `${p.name} (${p.sku})`,
            }));
    });

    // Warehouse handlers
    const selectedWarehouse = $derived(() => {
        const wid = String($form.warehouse_id ?? "");
        return warehouses.find((w) => String(w.id) === wid) ?? null;
    });
    function openWarehouseModal() {
        showWarehouseModal = true;
    }

    // Document Type handlers
    function openDocumentTypeModal() {
        showDocumentTypeModal = true;
    }

    // Notes handlers
    function openNotesModal() {
        showNotesModal = true;
    }

    // Item handlers
    function openAddItemModal() {
        const wid = String($form.warehouse_id ?? "");
        if (!wid) {
            showAddItemWarning = true;
            return;
        }
        editingItemDraft = {
            product_id: "",
            quantity: "1",
            notes: "",
        };
        isAddingItem = true;
    }

    function openEditItemModal(idx: number) {
        const it = $form.items[idx];
        if (!it) return;
        editingItemDraft = {
            product_id: it.product_id,
            quantity: it.quantity,
            notes: it.notes,
        };
        showEditItemIndex = idx;
    }

    function handleEditItemSave() {
        const draft = editingItemDraft;
        if (!draft) return;
        const pidStr = String(draft.product_id).trim();
        if (!pidStr) return;

        if (isAddingItem) {
            $form.items = [
                ...$form.items,
                {
                    product_id: pidStr,
                    quantity: draft.quantity,
                    notes: draft.notes,
                },
            ];
        } else if (showEditItemIndex !== null) {
            $form.items[showEditItemIndex] = {
                product_id: pidStr,
                quantity: draft.quantity,
                notes: draft.notes,
            };
        }
        handleEditItemCancel();
    }

    function handleEditItemCancel() {
        editingItemDraft = null;
        showEditItemIndex = null;
        isAddingItem = false;
    }

    const canSaveEditedItem = $derived(() => {
        const draft = editingItemDraft;
        if (!draft) return false;
        const pidStr = String(draft.product_id).trim();
        return pidStr.length > 0;
    });

    function removeItem(idx: number) {
        $form.items = $form.items.filter((_, i) => i !== idx);
    }

    // Get product name
    function getProductName(productId: string): string {
        const pid = String(productId);
        const product = products.find((p) => String(p.id) === pid);
        return product ? product.name : "-";
    }

    // Get product stock in warehouse
    function getProductStock(productId: string): number {
        const wid = String($form.warehouse_id ?? "");
        const pid = String(productId);
        if (!wid || !pid) return 0;
        const stock = stocks.find(
            (s) =>
                String(s.warehouse_id) === wid && String(s.product_id) === pid,
        );
        return stock ? stock.quantity : 0;
    }

    // Get document type label
    function getDocumentTypeLabel(type: string): string {
        const opt = typeOptions.find((o) => o.value === type);
        return opt ? opt.label : type;
    }
    function getReasonLabel(reason: string): string {
        const opt = reasonOptions.find((o) => o.value === reason);
        return opt ? opt.label : reason;
    }
    function getBucketLabel(bucket: string): string {
        const opt = bucketOptions.find((o) => o.value === bucket);
        return opt ? opt.label : bucket || "-";
    }

    // Submit
    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && stockDocument) {
            router.put(`/stock-documents/${stockDocument.id}`, {
                warehouse_id: $form.warehouse_id,
                document_date: $form.document_date,
                type: $form.type,
                reason: $form.reason,
                notes: $form.notes,
                items: $form.items,
            });
        } else {
            router.post("/stock-documents", {
                warehouse_id: $form.warehouse_id,
                document_date: $form.document_date,
                type: $form.type,
                reason: $form.reason,
                bucket: $form.bucket,
                notes: $form.notes,
                status: $form.status,
                items: $form.items,
            });
        }
    }

    function backToList() {
        router.visit("/stock-documents");
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Dokumen Stok | {siteName(
            $page.props.settings,
        )}</title
    >
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Dokumen Stok
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Ubah dokumen stok yang sudah ada"
                    : "Tambahkan dokumen stok baru"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}
            >
                Kembali
            </Button>
            <Button
                variant="warning"
                icon="fa-solid fa-save"
                type="submit"
                form="stock-document-form"
                class="w-full"
            >
                Simpan Draft
            </Button>
            <Button
                variant="success"
                icon="fa-solid fa-check"
                disabled={$form.items.length === 0 ||
                    !$form.warehouse_id ||
                    !$form.type ||
                    !$form.reason}
                onclick={() => {
                    $form.status = "pending_ho_approval";
                    const el = document.getElementById("stock-document-form");
                    if (el instanceof HTMLFormElement) {
                        el.requestSubmit();
                    }
                }}
                class="w-full"
            >
                Simpan & Ajukan
            </Button>
        </div>
    </header>

    <!-- Main Form Card -->
    <form id="stock-document-form" onsubmit={handleSubmit}>
        <Card collapsible={false}>
            <!-- Document Header -->
            <div>
                <div class="flex items-start justify-between">
                    <!-- Left: Company Info -->
                    <div class="flex-1">
                        <div class="mb-2">
                            <img
                                src={logo}
                                alt="Logo"
                                class="w-90 object-contain"
                                loading="lazy"
                            />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                    >Nama Gudang :</span
                                >
                                {#if selectedWarehouse()}
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                        >{selectedWarehouse()?.name}</span
                                    >
                                {:else}
                                    <span class="text-sm text-gray-500">-</span>
                                {/if}
                                <button
                                    type="button"
                                    class="text-orange-500 hover:text-orange-600"
                                    onclick={openWarehouseModal}
                                    aria-label="Ubah gudang"
                                    title="Ubah gudang"
                                >
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </div>
                            <div
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Alamat: {selectedWarehouse()?.address ||
                                    stockDocument?.warehouse?.address ||
                                    "-"}
                            </div>
                            <div
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                No. Whatsapp : {selectedWarehouse()?.phone ||
                                    stockDocument?.warehouse?.phone ||
                                    "-"}
                            </div>
                        </div>
                    </div>

                    <!-- Right: Document Title & Info -->
                    <div class="text-right">
                        <h2
                            class="mb-4 text-3xl font-bold text-gray-900 dark:text-white"
                        >
                            DOKUMEN STOK
                        </h2>
                        <div class="space-y-2">
                            <div>
                                <div
                                    class="text-xs font-bold uppercase text-gray-400 dark:text-gray-500"
                                >
                                    NO DOKUMEN
                                </div>
                                <div
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {isEdit && stockDocument
                                        ? stockDocument.number
                                        : "-"}
                                </div>
                            </div>
                            <div>
                                <div
                                    class="text-xs font-bold uppercase text-gray-400 dark:text-gray-500"
                                >
                                    TGL BUAT
                                </div>
                                <div
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {formatDateIndo($form.document_date)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <!-- Document Info Section -->
            <div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Left: Document Type -->
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <h3
                                class="text-xs font-bold uppercase text-gray-400 dark:text-gray-500"
                            >
                                JENIS DOKUMEN
                            </h3>
                            <button
                                type="button"
                                class="text-orange-500 hover:text-orange-600"
                                onclick={openDocumentTypeModal}
                                aria-label="Ubah jenis dokumen"
                                title="Ubah jenis dokumen"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                        {#if $form.type}
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {getDocumentTypeLabel($form.type)}
                            </div>
                        {:else}
                            <div class="text-base text-gray-500">-</div>
                        {/if}
                    </div>

                    <!-- Right: Notes -->
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <h3
                                class="text-xs font-bold uppercase text-gray-400 dark:text-gray-500"
                            >
                                CATATAN
                            </h3>
                            <button
                                type="button"
                                class="text-orange-500 hover:text-orange-600"
                                onclick={openNotesModal}
                                aria-label="Ubah catatan"
                                title="Ubah catatan"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                        {#if $form.notes}
                            <div
                                class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300"
                            >
                                {$form.notes}
                            </div>
                        {:else}
                            <div class="text-sm text-gray-500">-</div>
                        {/if}
                    </div>
                    <!-- Reason -->
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <h3
                                class="text-xs font-bold uppercase text-gray-400 dark:text-gray-500"
                            >
                                ALASAN
                            </h3>
                            <button
                                type="button"
                                class="text-orange-500 hover:text-orange-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick={() => {
                                    const t = String($form.type ?? "");
                                    if (!t) {
                                        showReasonTypeWarning = true;
                                        return;
                                    }
                                    showReasonModal = true;
                                }}
                                aria-label="Ubah alasan dokumen"
                                title="Ubah alasan dokumen"
                                disabled={!$form.type}
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                        {#if $form.reason}
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {getReasonLabel($form.reason)}
                            </div>
                        {:else}
                            <div class="text-base text-gray-500">-</div>
                        {/if}
                    </div>
                    <!-- Bucket -->
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <h3
                                class="text-xs font-bold uppercase text-gray-400 dark:text-gray-500"
                            >
                                BUCKET
                            </h3>
                            <button
                                type="button"
                                class="text-orange-500 hover:text-orange-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick={() => (showBucketModal = true)}
                                aria-label="Ubah bucket"
                                title="Ubah bucket"
                                disabled={isEdit}
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                        {#if $form.bucket}
                            <div
                                class="text-base font-medium text-gray-900 dark:text-white"
                            >
                                {getBucketLabel($form.bucket)}
                            </div>
                        {:else}
                            <div class="text-base text-gray-500">-</div>
                        {/if}
                    </div>
                </div>
            </div>
            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <!-- Items Table -->
            <div class="overflow-x-auto mb-6">
                <table class="custom-table">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th
                                class="border-b border-gray-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-600 dark:text-gray-300"
                            >
                                NO
                            </th>
                            <th
                                class="border-b border-gray-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-600 dark:text-gray-300"
                            >
                                PRODUK
                            </th>
                            <th
                                class="border-b border-gray-200 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-600 dark:text-gray-300"
                            >
                                STOK
                            </th>
                            <th
                                class="border-b border-gray-200 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-600 dark:text-gray-300"
                            >
                                QTY
                            </th>
                            <th
                                class="border-b border-gray-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-600 dark:text-gray-300"
                            >
                                CATATAN
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        {#if $form.items.length === 0}
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <Button
                                        variant="link"
                                        icon="fa-solid fa-circle-plus"
                                        class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-bold hover:text-green-700 dark:hover:text-green-300 text-xs uppercase"
                                        onclick={openAddItemModal}
                                        >Tambah Produk</Button
                                    >
                                </td>
                            </tr>
                        {:else}
                            {#each $form.items as item, idx}
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm text-gray-900 dark:text-white"
                                    >
                                        {idx + 1}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                >{getProductName(
                                                    item.product_id,
                                                )}</span
                                            >
                                            <button
                                                type="button"
                                                class="text-orange-500 hover:text-orange-600"
                                                aria-label="Edit item"
                                                title="Edit item"
                                                onclick={() =>
                                                    openEditItemModal(idx)}
                                            >
                                                <i
                                                    class="fa-solid fa-pen text-orange-400 text-xs"
                                                ></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="text-red-600 hover:text-red-700"
                                                aria-label="Hapus item"
                                                title="Hapus item"
                                                onclick={() => removeItem(idx)}
                                            >
                                                <i
                                                    class="fa-solid fa-trash-can text-red-500 text-xs"
                                                ></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300"
                                    >
                                        {getProductStock(item.product_id)}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white"
                                    >
                                        {item.quantity}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {item.notes || "-"}
                                    </td>
                                </tr>
                            {/each}
                            <tr>
                                <td colspan="5" class="px-4 py-4">
                                    <Button
                                        variant="link"
                                        icon="fa-solid fa-circle-plus"
                                        class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-bold hover:text-green-700 dark:hover:text-green-300 text-xs uppercase"
                                        onclick={openAddItemModal}
                                        >Tambah Produk</Button
                                    >
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </Card>
    </form>

    <!-- Modals -->
    <Modal
        bind:isOpen={showWarehouseModal}
        title="Pilih Gudang"
        onClose={() => (showWarehouseModal = false)}
    >
        {#snippet children()}
            <Select
                label="Gudang"
                value={$form.warehouse_id}
                options={warehouseOptions}
                onchange={(v) => ($form.warehouse_id = String(v))}
            />
        {/snippet}
        {#snippet footerSlot()}
            <Button
                variant="secondary"
                onclick={() => (showWarehouseModal = false)}
            >
                Tutup
            </Button>
        {/snippet}
    </Modal>

    <Modal
        bind:isOpen={showDocumentTypeModal}
        title="Pilih Jenis Dokumen"
        onClose={() => (showDocumentTypeModal = false)}
    >
        {#snippet children()}
            <Select
                label="Jenis Dokumen"
                value={$form.type}
                options={typeOptions}
                onchange={(v) => {
                    const newType = String(v);
                    $form.type = newType;
                    const allowed = new Set(
                        (newType === "in"
                            ? reasonOptionsByType.in
                            : newType === "out"
                              ? reasonOptionsByType.out
                              : []
                        ).map((o) => o.value),
                    );
                    if (!allowed.has(String($form.reason))) {
                        $form.reason = "";
                    }
                }}
            />
        {/snippet}
        {#snippet footerSlot()}
            <Button
                variant="secondary"
                onclick={() => (showDocumentTypeModal = false)}
            >
                Tutup
            </Button>
        {/snippet}
    </Modal>
    <Modal
        bind:isOpen={showReasonModal}
        title="Pilih Alasan"
        onClose={() => (showReasonModal = false)}
    >
        {#snippet children()}
            <Select
                label="Alasan"
                value={$form.reason}
                options={visibleReasonOptions()}
                onchange={(v) => ($form.reason = String(v))}
            />
        {/snippet}
        {#snippet footerSlot()}
            <Button
                variant="secondary"
                onclick={() => (showReasonModal = false)}
            >
                Tutup
            </Button>
        {/snippet}
    </Modal>
    <Modal
        bind:isOpen={showBucketModal}
        title="Pilih Bucket Stok"
        onClose={() => (showBucketModal = false)}
    >
        {#snippet children()}
            <Select
                label="Bucket"
                value={$form.bucket}
                options={bucketOptions}
                onchange={(v) => ($form.bucket = String(v))}
            />
        {/snippet}
        {#snippet footerSlot()}
            <Button
                variant="secondary"
                onclick={() => (showBucketModal = false)}
            >
                Tutup
            </Button>
        {/snippet}
    </Modal>

    <Modal
        bind:isOpen={showNotesModal}
        title="Catatan Dokumen"
        onClose={() => (showNotesModal = false)}
    >
        {#snippet children()}
            <TextArea
                id="notes"
                name="notes"
                bind:value={$form.notes}
                rows={5}
                placeholder="Tambahkan catatan untuk dokumen ini..."
            />
        {/snippet}
        {#snippet footerSlot()}
            <Button
                variant="secondary"
                onclick={() => (showNotesModal = false)}
            >
                Tutup
            </Button>
        {/snippet}
    </Modal>

    {#if showEditItemIndex !== null || isAddingItem}
        <Modal
            isOpen={showEditItemIndex !== null || isAddingItem}
            title={isAddingItem ? "Tambah Produk" : "Edit Produk"}
            onClose={handleEditItemCancel}
        >
            {#snippet children()}
                <div class="space-y-4">
                    <Select
                        label="Produk"
                        value={editingItemDraft?.product_id ?? ""}
                        options={availableProductOptions()}
                        onchange={(v) => {
                            const item = editingItemDraft;
                            if (!item) return;
                            item.product_id = String(v);
                        }}
                    />
                    <TextInput
                        id="quantity"
                        name="quantity"
                        label="Qty"
                        type="number"
                        value={editingItemDraft?.quantity ?? "1"}
                        oninput={(ev) => {
                            const item = editingItemDraft;
                            if (!item) return;
                            const v =
                                (ev as any).value ?? String(item.quantity);
                            item.quantity = v;
                        }}
                    />
                    <TextArea
                        id="notes_item"
                        name="notes_item"
                        label="Catatan"
                        value={editingItemDraft?.notes ?? ""}
                        oninput={(ev) => {
                            const item = editingItemDraft;
                            if (!item) return;
                            const v = (ev.target as HTMLTextAreaElement).value;
                            item.notes = v;
                        }}
                    />
                </div>
            {/snippet}
            {#snippet footerSlot()}
                <Button variant="secondary" onclick={handleEditItemCancel}>
                    Tutup
                </Button>
                <Button
                    variant="primary"
                    type="button"
                    icon="fa-solid fa-save"
                    disabled={!canSaveEditedItem}
                    onclick={handleEditItemSave}
                >
                    Simpan
                </Button>
            {/snippet}
        </Modal>
    {/if}

    <Dialog
        bind:isOpen={showAddItemWarning}
        type="warning"
        title="Pilih Gudang"
        message="Pilih gudang terlebih dahulu sebelum menambah produk."
        confirmText="Tutup"
        showCancel={false}
    />
    <Dialog
        bind:isOpen={showReasonTypeWarning}
        type="warning"
        title="Pilih Jenis Dokumen"
        message="Pilih jenis dokumen terlebih dahulu sebelum memilih alasan."
        confirmText="Tutup"
        showCancel={false}
    />
    <Dialog
        bind:isOpen={showSourceReasonWarning}
        type="warning"
        title="Pilih Alasan Dokumen"
        message="Pilih alasan dokumen terlebih dahulu sebelum mengatur referensi sumber."
        confirmText="Tutup"
        showCancel={false}
    />
</section>
