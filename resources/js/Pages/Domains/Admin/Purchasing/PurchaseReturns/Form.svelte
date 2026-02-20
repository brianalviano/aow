<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        getCurrencySymbol,
        formatCurrencyWithoutSymbol,
    } from "@/Lib/Admin/Utils/currency";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { untrack } from "svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import logo from "@img/logo.png";

    type SupplierItem = {
        id: string;
        name: string;
        phone: string;
        email: string;
        address: string;
    };
    type ProductItem = { id: string; name: string; sku: string };
    type StockInfo = {
        warehouse_id: string;
        product_id: string;
        quantity: number;
    };
    type Option = { value: string; label: string };

    let suppliers = $derived($page.props.suppliers as SupplierItem[]);
    let products = $derived($page.props.products as ProductItem[]);
    let stocks = $derived(($page.props.stocks ?? []) as StockInfo[]);
    let reasonOptions = $derived(($page.props.reasonOptions ?? []) as Option[]);
    let resolutionOptions = $derived(
        ($page.props.resolutionOptions ?? []) as Option[],
    );
    type AllowedProductsMap = Record<string, string[]>;
    let allowedProductsByPO = $derived(
        ($page.props.allowedProductsByPO ?? {}) as AllowedProductsMap,
    );
    type AllowedProductPricesMap = Record<string, Record<string, number>>;
    let poProductPricesByPO = $derived(
        ($page.props.poProductPricesByPO ?? {}) as AllowedProductPricesMap,
    );

    type ReturnItem = {
        product_id: string;
        quantity: string;
        price: string;
        notes: string;
    };
    type FormData = {
        supplier_id: string;
        warehouse_id: string;
        purchase_order_id: string;
        return_date: string;
        reason: string;
        resolution: string;
        status: string;
        notes: string;
        items: ReturnItem[];
    };
    type PurchaseOrderOption = {
        id: string;
        number: string;
        order_date: string | null;
        supplier: { id: string | null; name: string | null };
        warehouse: {
            id: string | null;
            name: string | null;
            address?: string | null;
            phone?: string | null;
        };
    };
    const prefill = $derived(
        ($page.props.prefill ?? {}) as {
            supplier_id?: string;
            warehouse_id?: string;
            purchase_order_id?: string;
        },
    );
    let purchaseOrders = $derived(
        ($page.props.purchase_orders ?? []) as PurchaseOrderOption[],
    );
    const form = useForm<FormData>(
        untrack(() => ({
            supplier_id: String(prefill.supplier_id ?? ""),
            warehouse_id: String(prefill.warehouse_id ?? ""),
            purchase_order_id: String(prefill.purchase_order_id ?? ""),
            return_date: String(new Date().toISOString().split("T")[0]),
            reason: "",
            resolution: "",
            status: "draft",
            notes: "",
            items: [] as ReturnItem[],
        })),
    );
    const poOptions = $derived(
        purchaseOrders.map((po) => ({
            value: String(po.id),
            label: `${po.number || "-"}`,
        })),
    );
    const hasSelectedPO = $derived(
        String($form.purchase_order_id || "").trim().length > 0,
    );
    const selectedPO = $derived(() => {
        const id = String($form.purchase_order_id || "");
        if (!id) return null;
        return purchaseOrders.find((p) => String(p.id) === id) ?? null;
    });
    $effect(() => {
        const id = $form.purchase_order_id
            ? String($form.purchase_order_id)
            : "";
        if (!id) return;
        const po = purchaseOrders.find((p) => String(p.id) === id);
        if (!po) return;
        const sid = String(po.supplier?.id ?? "");
        const wid = String(po.warehouse?.id ?? "");
        if ($form.supplier_id !== sid) {
            $form.supplier_id = sid;
        }
        if ($form.warehouse_id !== wid) {
            $form.warehouse_id = wid;
        }
    });

    const selectedPOSupplierDetail = $derived(() => {
        const po = selectedPO();
        const sid = po?.supplier?.id ? String(po.supplier.id) : "";
        if (!sid) return null;
        return suppliers.find((s) => String(s.id) === sid) ?? null;
    });

    const currencySymbol = getCurrencySymbol();
    function getPOUnitPrice(productId: string): number {
        const poId = String($form.purchase_order_id || "");
        if (!poId || !productId) return 0;
        const map = poProductPricesByPO[poId] ?? {};
        const price = map[String(productId)];
        return typeof price === "number" && Number.isFinite(price) ? price : 0;
    }

    function getStock(productId: string): number {
        const wid = $form.warehouse_id ? String($form.warehouse_id) : "";
        if (!wid || !productId) return 0;
        const stockInfo = stocks.find(
            (s) =>
                String(s.warehouse_id) === wid &&
                String(s.product_id) === productId,
        );
        return stockInfo ? stockInfo.quantity : 0;
    }

    let showPOModal = $state(false);
    let showReturnDateModal = $state(false);
    let showNotesModal = $state(false);
    let showReasonModal = $state(false);
    let showResolutionModal = $state(false);
    let showEditItemIndex = $state<number | null>(null);
    let isAddingItem = $state(false);
    let editingItemDraft = $state<ReturnItem | null>(null);
    const poSummary = $derived(() => {
        const po = selectedPO();
        return po
            ? `${po.number || "-"} | Supplier : ${po.supplier?.name || "-"} | Gudang : ${po.warehouse?.name || "-"} | Tgl PO : ${formatDateDisplay(po.order_date)}`
            : "-";
    });

    function addItem() {
        if (!hasSelectedPO) return;
        const poId = String($form.purchase_order_id || "");
        const allowedIds = (allowedProductsByPO[poId] ?? []).map((x) =>
            String(x),
        );
        if (allowedIds.length === 0) return;
        isAddingItem = true;
        editingItemDraft = {
            product_id: "",
            quantity: "1",
            price: "0",
            notes: "",
        };
        showEditItemIndex = null;
    }
    function removeItem(index: number) {
        $form.items = $form.items.filter((_, i) => i !== index);
    }
    function handleEditItem(index: number) {
        isAddingItem = false;
        showEditItemIndex = index;
        const existing = $form.items[index];
        editingItemDraft = existing ? { ...existing } : null;
        if (editingItemDraft) {
            const pid = String(editingItemDraft.product_id || "");
            if (pid) {
                const p = getPOUnitPrice(pid);
                editingItemDraft.price = String(p);
            }
        }
    }
    function handleEditItemCancel() {
        showEditItemIndex = null;
        isAddingItem = false;
        editingItemDraft = null;
    }
    function handleEditItemSave() {
        if (!editingItemDraft) {
            handleEditItemCancel();
            return;
        }
        if (String(editingItemDraft.product_id || "").length > 0) {
            const p = getPOUnitPrice(String(editingItemDraft.product_id));
            editingItemDraft.price = String(p);
        }
        if (isAddingItem) {
            $form.items = [...$form.items, { ...editingItemDraft }];
        } else if (showEditItemIndex !== null) {
            const idx = showEditItemIndex;
            $form.items = $form.items.map((it, i) =>
                i === idx ? { ...editingItemDraft! } : it,
            );
        }
        handleEditItemCancel();
    }
    let canSaveEditedItem = $state(false);
    $effect(() => {
        const item = editingItemDraft;
        if (!item) {
            canSaveEditedItem = false;
            return;
        }
        const hasProduct = String(item.product_id ?? "").trim().length > 0;
        const qtyStr = String(item.quantity ?? "").trim();
        const qtyNum = Number(qtyStr.replaceAll(".", "").replace(",", "."));
        canSaveEditedItem = hasProduct && Number.isFinite(qtyNum) && qtyNum > 0;
    });
    $effect(() => {
        const item = editingItemDraft;
        if (!item) return;
        const pid = String(item.product_id || "");
        const poId = String($form.purchase_order_id || "");
        if (!poId || !pid) return;
        const targetPrice = getPOUnitPrice(pid);
        const currentPrice = Number(item.price || "0");
        if (Number.isFinite(targetPrice) && targetPrice !== currentPrice) {
            item.price = String(targetPrice);
        }
    });

    const takenProductIds = $derived(() => {
        return new Set(
            $form.items
                .map((it) => String(it.product_id).trim())
                .filter((id) => id.length > 0),
        );
    });
    const availableProductOptions = $derived(() => {
        const poId = String($form.purchase_order_id || "");
        const allowedIds = (allowedProductsByPO[poId] ?? []).map((x) =>
            String(x),
        );
        const allowedSet = new Set(allowedIds);
        const currentId = String(editingItemDraft?.product_id ?? "");
        return products
            .filter((p) => {
                const id = String(p.id);
                if (!allowedSet.has(id)) return false;
                if (currentId && id === currentId) return true;
                return !takenProductIds().has(id);
            })
            .map((p) => ({
                value: String(p.id),
                label: `${p.name} (${p.sku})`,
            }));
    });
    $effect(() => {
        const poId = String($form.purchase_order_id || "");
        const allowedIds = (allowedProductsByPO[poId] ?? []).map((x) =>
            String(x),
        );
        const allowedSet = new Set(allowedIds);
        untrack(() => {
            const curr = $form.items;
            const filtered = curr.filter((it) =>
                allowedSet.has(String(it.product_id)),
            );
            if (filtered.length !== curr.length) {
                $form.items = filtered;
                return;
            }
            const len = filtered.length;
            for (let i = 0; i < len; i++) {
                const a = filtered[i]!;
                const b = curr[i]!;
                if (
                    String(a.product_id) !== String(b.product_id) ||
                    String(a.quantity) !== String(b.quantity) ||
                    String(a.price) !== String(b.price) ||
                    String(a.notes ?? "") !== String(b.notes ?? "")
                ) {
                    $form.items = filtered;
                    return;
                }
            }
        });
    });
    $effect(() => {
        const poId = String($form.purchase_order_id || "");
        if (!poId) return;
        untrack(() => {
            const curr = $form.items;
            let changed = false;
            const updated = curr.map((it) => {
                const pid = String(it.product_id || "");
                const target = getPOUnitPrice(pid);
                const currPriceNum = Number(it.price || "0");
                const nextPriceStr = String(target);
                if (Number.isFinite(target) && target !== currPriceNum) {
                    changed = true;
                    return { ...it, price: nextPriceStr };
                }
                return it;
            });
            if (changed) {
                $form.items = updated;
            }
        });
    });

    function keydownActivate(action: () => void) {
        return (e: KeyboardEvent) => {
            if (e.key === "Enter" || e.key === " ") {
                action();
            }
        };
    }

    const subtotal = $derived(() => {
        return $form.items.reduce((sum, item) => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.price) || 0;
            return sum + qty * price;
        }, 0);
    });

    function backToList() {
        router.visit("/purchase-returns");
    }
    function handleSubmit(isComplete: boolean) {
        $form.status = isComplete ? "completed" : "draft";
        $form.post("/purchase-returns", {
            onSuccess: () => router.visit("/purchase-returns"),
        });
    }
</script>

<svelte:head>
    <title>Tambah Retur Pembelian | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Tambah Retur Pembelian
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Buat dokumen retur pembelian secara manual
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                type="button"
                loading={$form.processing}
                disabled={!hasSelectedPO || $form.processing}
                icon="fa-solid fa-save"
                onclick={() => handleSubmit(false)}
                class="w-full"
            >
                Simpan Draft
            </Button>
            <Button
                variant="success"
                type="button"
                loading={$form.processing}
                disabled={!hasSelectedPO || $form.processing}
                icon="fa-solid fa-check"
                onclick={() => handleSubmit(true)}
                class="w-full"
            >
                Selesaikan Retur
            </Button>
        </div>
    </header>
    <form onsubmit={(e) => e.preventDefault()}>
        <Card collapsible={false}>
            <div class="flex flex-col md:flex-row justify-between items-start">
                <div class="w-full md:w-1/2">
                    <div class="mb-2">
                        <img
                            src={logo}
                            alt="Logo"
                            class="w-90 object-contain"
                            loading="lazy"
                        />
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                        Gudang: {selectedPO()?.warehouse?.name || "-"}
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                        Alamat: {selectedPO()?.warehouse?.address ||
                            ($page.props.settings as any)?.address ||
                            "-"}
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                        No. Whatsapp: {selectedPO()?.warehouse?.phone ||
                            ($page.props.settings as any)?.whatsapp_number ||
                            "-"}
                    </p>
                </div>

                <div
                    class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                >
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                    >
                        RETUR PEMBELIAN
                    </h2>
                    <div class="w-full flex justify-end gap-12 text-right">
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                            >
                                No Surat
                            </p>
                            <p
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                -
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                            >
                                Tgl Buat
                            </p>
                            <div class="flex items-center justify-end gap-2">
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {new Intl.DateTimeFormat("id-ID", {
                                        weekday: "long",
                                        year: "numeric",
                                        month: "long",
                                        day: "numeric",
                                    }).format(new Date($form.return_date))}
                                </p>
                                {#if hasSelectedPO}
                                    <i
                                        class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                        role="button"
                                        tabindex="0"
                                        aria-label="Ubah tanggal retur"
                                        onclick={() =>
                                            (showReturnDateModal = true)}
                                        onkeydown={keydownActivate(
                                            () => (showReturnDateModal = true),
                                        )}
                                    ></i>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            Purchase Order
                        </p>
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                            role="button"
                            tabindex="0"
                            aria-label="Pilih purchase order"
                            onclick={() => (showPOModal = true)}
                            onkeydown={keydownActivate(
                                () => (showPOModal = true),
                            )}
                        ></i>
                    </div>
                    <div
                        class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed mb-4"
                    >
                        {poSummary()}
                    </div>

                    <p
                        class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                    >
                        SUPPLIER
                    </p>
                    <div class="flex items-center gap-2 mb-2">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white"
                        >
                            {selectedPO()?.supplier?.name || "-"}
                        </h3>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                        No. Whatsapp :
                        {selectedPOSupplierDetail()?.phone || "-"}
                    </p>
                    <div
                        class="flex text-sm text-gray-700 dark:text-gray-300 mb-1"
                    >
                        <span class="w-16">Email</span>
                        <span
                            >:
                            {selectedPOSupplierDetail()?.email || "-"}
                            >
                        </span>
                    </div>
                    <div class="flex text-sm text-gray-700 dark:text-gray-300">
                        <span class="w-16">Alamat</span>
                        <span
                            >:
                            {selectedPOSupplierDetail()?.address || "-"}
                            >
                        </span>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            Alasan Retur
                        </p>
                        {#if hasSelectedPO}
                            <i
                                class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                role="button"
                                tabindex="0"
                                aria-label="Ubah alasan retur"
                                onclick={() => (showReasonModal = true)}
                                onkeydown={keydownActivate(
                                    () => (showReasonModal = true),
                                )}
                            ></i>
                        {/if}
                    </div>
                    <div
                        class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed mb-4"
                    >
                        {(() => {
                            const v = String($form.reason || "");
                            if (!v) return "-";
                            const opt = reasonOptions.find(
                                (o) => String(o.value) === v,
                            );
                            return opt ? opt.label : v;
                        })()}
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            Resolusi
                        </p>
                        {#if hasSelectedPO}
                            <i
                                class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                role="button"
                                tabindex="0"
                                aria-label="Ubah resolusi retur"
                                onclick={() => (showResolutionModal = true)}
                                onkeydown={keydownActivate(
                                    () => (showResolutionModal = true),
                                )}
                            ></i>
                        {/if}
                    </div>
                    <div
                        class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed mb-4"
                    >
                        {(() => {
                            const v = String($form.resolution || "");
                            if (!v) return "-";
                            const opt = resolutionOptions.find(
                                (o) => String(o.value) === v,
                            );
                            return opt ? opt.label : v;
                        })()}
                    </div>
                    <div class="flex items-center gap-2 mb-1">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            CATATAN UNTUK SUPPLIER
                        </p>
                        {#if hasSelectedPO}
                            <i
                                class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                role="button"
                                tabindex="0"
                                aria-label="Ubah catatan"
                                onclick={() => (showNotesModal = true)}
                                onkeydown={keydownActivate(
                                    () => (showNotesModal = true),
                                )}
                            ></i>
                        {/if}
                    </div>
                    <div
                        class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                    >
                        {($form.notes || "").trim() || "-"}
                    </div>
                </div>
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
                            <th scope="col" class="px-3 py-2 text-center w-12"
                                >NO</th
                            >
                            <th scope="col" class="px-3 py-2 w-1/4">PRODUK</th>
                            <th scope="col" class="px-3 py-2 text-center w-20"
                                >STOK</th
                            >
                            <th scope="col" class="px-3 py-2 text-center w-20"
                                >QTY</th
                            >
                            <th scope="col" class="px-3 py-2 text-right"
                                >HARGA RETUR</th
                            >
                            <th scope="col" class="px-3 py-2 text-right"
                                >SUBTOTAL</th
                            >
                            <th scope="col" class="px-3 py-2 w-1/4">CATATAN</th>
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
                                                        String(pp.id) ===
                                                        String(item.product_id),
                                                );
                                                return p
                                                    ? `${p.name} (${p.sku})`
                                                    : "-";
                                            })()}
                                        </span>
                                        <div class="flex gap-2">
                                            {#if hasSelectedPO}
                                                <i
                                                    class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="Edit item"
                                                    onclick={() =>
                                                        handleEditItem(index)}
                                                    onkeydown={keydownActivate(
                                                        () =>
                                                            handleEditItem(
                                                                index,
                                                            ),
                                                    )}
                                                ></i>
                                                <i
                                                    class="fa-solid fa-trash-can text-red-500 cursor-pointer text-xs"
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="Hapus item"
                                                    onclick={() =>
                                                        removeItem(index)}
                                                    onkeydown={keydownActivate(
                                                        () => removeItem(index),
                                                    )}
                                                ></i>
                                            {/if}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {getStock(item.product_id)}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {item.quantity}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-between">
                                        <span>{currencySymbol}</span>
                                        <span
                                            >{formatCurrencyWithoutSymbol(
                                                item.price,
                                            )}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-between">
                                        <span>{currencySymbol}</span>
                                        <span
                                            >{formatCurrencyWithoutSymbol(
                                                (parseFloat(item.quantity) ||
                                                    0) *
                                                    (parseFloat(item.price) ||
                                                        0),
                                            )}</span
                                        >
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-2 text-gray-600 dark:text-gray-400"
                                >
                                    {item.notes || "-"}
                                </td>
                            </tr>
                        {/each}
                        <tr>
                            <td
                                colspan="5"
                                class="px-3 py-2 bg-gray-50/50 dark:bg-gray-800/50"
                            >
                                <div class="flex justify-between">
                                    <div>
                                        <Button
                                            variant="link"
                                            icon="fa-solid fa-circle-plus"
                                            class="flex items-center gap-1 text-green-600 dark:text-green-400 font-bold hover:text-green-700 dark:hover:text-green-300 text-xs uppercase"
                                            onclick={addItem}
                                            disabled={!hasSelectedPO}
                                            >Tambah Produk</Button
                                        >
                                    </div>
                                    <div class="text-end font-bold uppercase">
                                        Total
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            subtotal(),
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800 border-none"
                            ></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
        <Modal
            bind:isOpen={showPOModal}
            title="Pilih Purchase Order"
            onClose={() => (showPOModal = false)}
        >
            {#snippet children()}
                <Select
                    value={$form.purchase_order_id}
                    options={poOptions}
                    onchange={(v) => {
                        $form.purchase_order_id = String(v);
                        showPOModal = false;
                    }}
                />
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showPOModal = false)}
                >
                    Tutup
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showReturnDateModal}
            title="Tanggal Retur"
            onClose={() => (showReturnDateModal = false)}
        >
            {#snippet children()}
                <DateInput
                    id="return_date"
                    name="return_date"
                    label="Tanggal Retur"
                    bind:value={$form.return_date}
                    format="yyyy-mm-dd"
                />
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showReturnDateModal = false)}
                >
                    Tutup
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showReasonModal}
            title="Alasan Retur"
            onClose={() => (showReasonModal = false)}
        >
            {#snippet children()}
                <Select
                    label="Alasan Retur"
                    value={$form.reason}
                    options={reasonOptions}
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
            bind:isOpen={showResolutionModal}
            title="Resolusi Retur"
            onClose={() => (showResolutionModal = false)}
        >
            {#snippet children()}
                <Select
                    label="Resolusi"
                    value={$form.resolution}
                    options={resolutionOptions}
                    onchange={(v) => ($form.resolution = String(v))}
                />
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showResolutionModal = false)}
                >
                    Tutup
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showNotesModal}
            title="Catatan untuk Supplier"
            onClose={() => (showNotesModal = false)}
        >
            {#snippet children()}
                <TextArea id="notes" name="notes" bind:value={$form.notes} />
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
                title="Edit Item Retur"
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
                                const p = getPOUnitPrice(String(v));
                                item.price = String(p);
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
                        <TextInput
                            id="price"
                            name="price"
                            label="Harga Retur (diambil dari PO)"
                            currency={true}
                            value={editingItemDraft?.price ?? "0"}
                            disabled={true}
                            readonly={true}
                        />
                        <TextArea
                            id="notes_item"
                            name="notes_item"
                            label="Catatan"
                            value={editingItemDraft?.notes ?? ""}
                            oninput={(ev) => {
                                const item = editingItemDraft;
                                if (!item) return;
                                const v = (ev.target as HTMLTextAreaElement)
                                    .value;
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
    </form>
</section>
