<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import {
        getCurrencySymbol,
        formatCurrencyWithoutSymbol,
    } from "@/Lib/Admin/Utils/currency";
    import { untrack } from "svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import logo from "@img/logo.png";

    type Option = { value: string; label: string };
    type SupplierItem = {
        id: string;
        name: string;
        phone: string;
        email: string;
        address: string;
    };
    type WarehouseItem = {
        id: string;
        name: string;
        address: string;
        phone: string;
    };
    type TaxItem = { id: string; name: string; percentage: string };
    type ProductItem = { id: string; name: string; sku: string };
    type ProductSupplierPrice = {
        supplier_id: string;
        product_id: string;
        price: number;
    };
    type ProductPurchasePrice = {
        product_id: string;
        price: number;
    };
    type StockInfo = {
        warehouse_id: string;
        product_id: string;
        quantity: number;
    };

    type PurchaseOrderItem = {
        product_id: string;
        quantity: string;
        price: string;
        notes: string;
    };

    type PurchaseOrderData = {
        id: string;
        number: string;
        supplier: {
            id: string | null;
            name: string | null;
            phone: string | null;
            email: string | null;
            address: string | null;
        };
        warehouse: {
            id: string | null;
            name: string | null;
            address: string | null;
            phone: string | null;
        };
        order_date: string | null;
        due_date: string | null;
        notes: string | null;
        status: string | null;
        subtotal: number;
        delivery_cost: number;
        discount_percentage: string | null;
        discount_amount: number;
        total_after_discount: number;
        value_added_tax_included: boolean;
        is_value_added_tax_enabled: boolean;
        value_added_tax_id: string | null;
        value_added_tax_percentage: string | null;
        value_added_tax_amount: number;
        total_after_value_added_tax: number;
        is_income_tax_enabled: boolean;
        income_tax_id: string | null;
        income_tax_percentage: string | null;
        income_tax_amount: number;
        total_after_income_tax: number;
        grand_total: number;
        supplier_invoice_number: string | null;
        supplier_invoice_file: string | null;
        supplier_invoice_date: string | null;
        items: Array<{
            id: string;
            product: { id: string | null; name: string | null };
            quantity: number;
            price: number;
            subtotal: number;
            notes: string | null;
        }>;
    } | null;

    let purchaseOrder = $derived(
        $page.props.purchase_order as PurchaseOrderData,
    );
    let suppliers = $derived($page.props.suppliers as SupplierItem[]);
    let warehouses = $derived($page.props.warehouses as WarehouseItem[]);
    let valueAddedTaxes = $derived($page.props.value_added_taxes as TaxItem[]);
    let incomeTaxes = $derived($page.props.income_taxes as TaxItem[]);
    let products = $derived($page.props.products as ProductItem[]);
    let productSupplierPrices = $derived(
        $page.props.product_supplier_prices as ProductSupplierPrice[],
    );
    let productPurchasePrices = $derived(
        ($page.props.product_purchase_prices ?? []) as ProductPurchasePrice[],
    );
    let stocks = $derived(($page.props.stocks ?? []) as StockInfo[]);

    let isEdit = $derived(purchaseOrder !== null);

    // Modal states
    let showWarehouseModal = $state(false);
    let showSupplierModal = $state(false);
    let showDeliveryCostModal = $state(false);
    let showDiscountModal = $state(false);
    let showPPNModal = $state(false);
    let showPPhModal = $state(false);
    let showNotesModal = $state(false);
    let showEditItemIndex = $state<number | null>(null);
    let isAddingItem = $state(false);
    let editingItemDraft = $state<PurchaseOrderItem | null>(null);

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

    const currencySymbol = getCurrencySymbol();
    const form = useForm(
        untrack(() => ({
            supplier_id: purchaseOrder?.supplier?.id ?? "",
            supplier_name: purchaseOrder?.supplier?.name ?? "",
            supplier_phone: purchaseOrder?.supplier?.phone ?? "",
            supplier_email: purchaseOrder?.supplier?.email ?? "",
            supplier_address: purchaseOrder?.supplier?.address ?? "",
            warehouse_id: purchaseOrder?.warehouse?.id ?? "",
            order_date:
                purchaseOrder?.order_date ??
                new Date().toISOString().split("T")[0],
            notes: purchaseOrder?.notes ?? "",
            status: purchaseOrder?.status ?? "",
            delivery_cost: String(purchaseOrder?.delivery_cost ?? 0),
            discount_percentage: purchaseOrder?.discount_percentage ?? "",
            is_value_added_tax_enabled:
                purchaseOrder?.is_value_added_tax_enabled ? "1" : "0",
            value_added_tax_id: purchaseOrder?.value_added_tax_id ?? "",
            value_added_tax_percentage:
                purchaseOrder?.value_added_tax_percentage ?? "",
            is_income_tax_enabled: purchaseOrder?.is_income_tax_enabled
                ? "1"
                : "0",
            income_tax_id: purchaseOrder?.income_tax_id ?? "",
            income_tax_percentage: purchaseOrder?.income_tax_percentage ?? "",
            items:
                purchaseOrder?.items?.map((it) => ({
                    product_id: it.product?.id ?? "",
                    quantity: String(it.quantity),
                    price: String(it.price),
                    notes: it.notes ?? "",
                })) ?? ([] as PurchaseOrderItem[]),
        })),
    );

    // Supplier options
    const supplierOptions = $derived(
        suppliers.map((s) => ({
            value: String(s.id),
            label: s.name,
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
        const currentId = String(editingItemDraft?.product_id ?? "");
        return products
            .filter((p) => {
                const id = String(p.id);
                if (currentId && id === currentId) return true;
                return !takenProductIds().has(id);
            })
            .map((p) => ({
                value: String(p.id),
                label: `${p.name} (${p.sku})`,
            }));
    });

    // Tax options
    const valueAddedTaxOptions = $derived(
        valueAddedTaxes.map((t) => ({
            value: String(t.id),
            label: `${t.name} (${t.percentage}%)`,
        })),
    );

    const incomeTaxOptions = $derived(
        incomeTaxes.map((t) => ({
            value: String(t.id),
            label: `${t.name} (${t.percentage}%)`,
        })),
    );

    // Calculations
    const subtotal = $derived(() => {
        return $form.items.reduce((sum, item) => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.price) || 0;
            return sum + qty * price;
        }, 0);
    });

    const deliveryCost = $derived(() => parseFloat($form.delivery_cost) || 0);

    const totalBeforeDiscount = $derived(() => subtotal() + deliveryCost());

    const discountAmount = $derived(() => {
        const percentage = parseFloat($form.discount_percentage) || 0;
        return (totalBeforeDiscount() * percentage) / 100;
    });

    const totalAfterDiscount = $derived(
        () => totalBeforeDiscount() - discountAmount(),
    );

    const ppnAmount = $derived(() => {
        if ($form.is_value_added_tax_enabled === "0") return 0;
        const percentage = parseFloat($form.value_added_tax_percentage) || 0;
        return (totalAfterDiscount() * percentage) / 100;
    });

    const totalAfterPPN = $derived(() => totalAfterDiscount() + ppnAmount());

    const pphAmount = $derived(() => {
        if ($form.is_income_tax_enabled === "0") return 0;
        const percentage = parseFloat($form.income_tax_percentage) || 0;
        return (deliveryCost() * percentage) / 100;
    });

    const grandTotal = $derived(() => totalAfterPPN() + pphAmount());

    // Get selected supplier
    const selectedSupplier = $derived(() => {
        const sid = $form.supplier_id ? String($form.supplier_id) : "";
        if (!sid) return null;
        return suppliers.find((s) => String(s.id) === sid) ?? null;
    });

    // Get stock for product
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
    const selectedWarehouse = $derived(() => {
        const wid = $form.warehouse_id ? String($form.warehouse_id) : "";
        if (!wid) return null;
        return warehouses.find((w) => String(w.id) === wid) ?? null;
    });

    // Add item
    function addItem() {
        isAddingItem = true;
        editingItemDraft = {
            product_id: "",
            quantity: "1",
            price: "0",
            notes: "",
        };
        showEditItemIndex = null;
    }

    // Remove item
    function removeItem(index: number) {
        $form.items = $form.items.filter((_, i) => i !== index);
    }

    // Handle PPN tax selection
    function handlePPNSelect() {
        const taxId = $form.value_added_tax_id;
        if (taxId) {
            const tax = valueAddedTaxes.find((t) => String(t.id) === taxId);
            if (tax) {
                $form.value_added_tax_percentage = String(tax.percentage);
            }
        }
        showPPNModal = false;
    }

    // Handle PPh tax selection
    function handlePPhSelect() {
        const taxId = $form.income_tax_id;
        if (taxId) {
            const tax = incomeTaxes.find((t) => String(t.id) === taxId);
            if (tax) {
                $form.income_tax_percentage = String(tax.percentage);
            }
        }
        showPPhModal = false;
    }
    function handleEditItem(index: number) {
        isAddingItem = false;
        showEditItemIndex = index;
        const existing = $form.items[index];
        editingItemDraft = existing ? { ...existing } : null;
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

    function keydownActivate(action: () => void) {
        return (e: KeyboardEvent) => {
            if (e.key === "Enter" || e.key === " ") {
                action();
            }
        };
    }

    // Form submission
    function handleSubmit(isDraft: boolean) {
        $form.status = isDraft ? "draft" : "pending_ho_approval";
        if (isEdit) {
            $form.put(`/purchase-orders/${purchaseOrder!.id}`, {
                onSuccess: () => router.visit("/purchase-orders"),
            });
        } else {
            $form.post("/purchase-orders", {
                onSuccess: () => router.visit("/purchase-orders"),
            });
        }
    }

    function backToList() {
        router.visit("/purchase-orders");
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Purchase Order | {siteName(
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
                {isEdit ? "Edit" : "Tambah"} Purchase Order
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi purchase order"
                    : "Tambahkan purchase order baru"}
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
                disabled={$form.processing}
                icon="fa-solid fa-save"
                onclick={() => handleSubmit(true)}
                class="w-full"
            >
                Simpan Draft
            </Button>
            <Button
                variant={isEdit ? "warning" : "success"}
                type="button"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                onclick={() => handleSubmit(false)}
                class="w-full"
            >
                Simpan & Ajukan
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
                    <div
                        class="flex items-center gap-2 mt-1 text-sm text-gray-700 dark:text-gray-300"
                    >
                        <span
                            >Nama Gudang : {(() => {
                                const wid = $form.warehouse_id
                                    ? String($form.warehouse_id)
                                    : "";
                                const wh = warehouses.find(
                                    (w) => String(w.id) === wid,
                                );
                                return wh ? wh.name : "-";
                            })()}</span
                        >
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer hover:text-orange-500"
                            role="button"
                            tabindex="0"
                            aria-label="Pilih gudang"
                            onclick={() => (showWarehouseModal = true)}
                            onkeydown={keydownActivate(
                                () => (showWarehouseModal = true),
                            )}
                        ></i>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                        Alamat: {selectedWarehouse()?.address ||
                            purchaseOrder?.warehouse?.address ||
                            "-"}
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                        No. Whatsapp : {selectedWarehouse()?.phone ||
                            purchaseOrder?.warehouse?.phone ||
                            "-"}
                    </p>
                </div>

                <div
                    class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                >
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                    >
                        PURCHASE ORDER
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
                                {purchaseOrder?.number ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                            >
                                Tgl Buat
                            </p>
                            <p
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {formatDateIndo($form.order_date)}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <p
                        class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                    >
                        SUPPLIER
                    </p>
                    <div class="flex items-center gap-2 mb-2">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white"
                        >
                            {$form.supplier_name ||
                                selectedSupplier()?.name ||
                                "-"}
                        </h3>
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer text-sm"
                            role="button"
                            tabindex="0"
                            aria-label="Pilih supplier"
                            onclick={() => (showSupplierModal = true)}
                            onkeydown={keydownActivate(
                                () => (showSupplierModal = true),
                            )}
                        ></i>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                        No. Whatsapp :
                        {$form.supplier_phone ||
                            selectedSupplier()?.phone ||
                            "-"}
                    </p>
                    <div
                        class="flex text-sm text-gray-700 dark:text-gray-300 mb-1"
                    >
                        <span class="w-16">Email</span>
                        <span
                            >:
                            {$form.supplier_email ||
                                selectedSupplier()?.email ||
                                "-"}</span
                        >
                    </div>
                    <div class="flex text-sm text-gray-700 dark:text-gray-300">
                        <span class="w-16">Alamat</span>
                        <span
                            >:
                            {$form.supplier_address ||
                                selectedSupplier()?.address ||
                                "-"}</span
                        >
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            CATATAN UNTUK SUPPLIER
                        </p>
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                            role="button"
                            tabindex="0"
                            aria-label="Ubah catatan untuk supplier"
                            onclick={() => (showNotesModal = true)}
                            onkeydown={keydownActivate(
                                () => (showNotesModal = true),
                            )}
                        ></i>
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
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center w-12">NO</th>
                            <th scope="col" class="w-1/4">PRODUK</th>
                            <th scope="col" class="text-center w-20">STOK</th>
                            <th scope="col" class="text-center w-20">QTY</th>
                            <th scope="col" class="text-right">HARGA BELI</th>
                            <th scope="col" class="text-right">SUBTOTAL</th>
                            <th scope="col" class="w-1/4">CATATAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each $form.items as item, index}
                            <tr>
                                <td class="text-center">{index + 1}</td>
                                <td>
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
                                            <i
                                                class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                                role="button"
                                                tabindex="0"
                                                aria-label="Edit item"
                                                onclick={() =>
                                                    handleEditItem(index)}
                                                onkeydown={keydownActivate(() =>
                                                    handleEditItem(index),
                                                )}
                                            ></i>
                                            <i
                                                class="fa-solid fa-trash-can text-red-500 cursor-pointer text-xs"
                                                role="button"
                                                tabindex="0"
                                                aria-label="Hapus item"
                                                onclick={() =>
                                                    removeItem(index)}
                                                onkeydown={keydownActivate(() =>
                                                    removeItem(index),
                                                )}
                                            ></i>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    {getStock(item.product_id)}
                                </td>
                                <td class="text-center">
                                    {item.quantity}
                                </td>
                                <td>
                                    <div class="flex justify-between">
                                        <span>{currencySymbol}</span>
                                        <span
                                            >{formatCurrencyWithoutSymbol(
                                                item.price,
                                            )}</span
                                        >
                                    </div>
                                </td>
                                <td>
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
                                <td class="text-gray-600 dark:text-gray-400">
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
                        <tr>
                            <td
                                colspan="5"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <span>Biaya Pengiriman</span>
                                    <i
                                        class="fa-solid fa-pen text-orange-400 text-xs cursor-pointer"
                                        role="button"
                                        tabindex="0"
                                        aria-label="Ubah biaya pengiriman"
                                        onclick={() =>
                                            (showDeliveryCostModal = true)}
                                        onkeydown={keydownActivate(
                                            () =>
                                                (showDeliveryCostModal = true),
                                        )}
                                    ></i>
                                </div>
                            </td>
                            <td
                                class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                            >
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            deliveryCost(),
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800 border-none"
                            ></td>
                        </tr>
                        <tr>
                            <td
                                colspan="5"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                >Total sebelum diskon</td
                            >
                            <td
                                class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                            >
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            totalBeforeDiscount(),
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800 border-none"
                            ></td>
                        </tr>
                        <tr>
                            <td
                                colspan="5"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <span
                                        >Diskon ({$form.discount_percentage ||
                                            "0"}%)</span
                                    >
                                    <i
                                        class="fa-solid fa-pen text-orange-400 text-xs cursor-pointer"
                                        role="button"
                                        tabindex="0"
                                        aria-label="Ubah diskon"
                                        onclick={() =>
                                            (showDiscountModal = true)}
                                        onkeydown={keydownActivate(
                                            () => (showDiscountModal = true),
                                        )}
                                    ></i>
                                </div>
                            </td>
                            <td
                                class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                            >
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            discountAmount(),
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800 border-none"
                            ></td>
                        </tr>
                        <tr>
                            <td
                                colspan="5"
                                class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                >Setelah diskon</td
                            >
                            <td
                                class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                            >
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            totalAfterDiscount(),
                                        )}</span
                                    >
                                </div>
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800 border-none"
                            ></td>
                        </tr>
                        {#if $form.is_value_added_tax_enabled === "1"}
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                >
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <span
                                            >PPN ({$form.value_added_tax_percentage ||
                                                "0"}%)</span
                                        >
                                        <i
                                            class="fa-solid fa-pen text-orange-400 text-xs cursor-pointer"
                                            role="button"
                                            tabindex="0"
                                            aria-label="Pilih jenis PPN"
                                            onclick={() =>
                                                (showPPNModal = true)}
                                            onkeydown={keydownActivate(
                                                () => (showPPNModal = true),
                                            )}
                                        ></i>
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                >
                                    <div class="flex justify-between">
                                        <span>{currencySymbol}</span>
                                        <span
                                            >{formatCurrencyWithoutSymbol(
                                                ppnAmount(),
                                            )}</span
                                        >
                                    </div>
                                </td>
                                <td
                                    class="bg-gray-100 dark:bg-gray-800 border-none"
                                ></td>
                            </tr>
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                    >Total dengan PPN</td
                                >
                                <td
                                    class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                >
                                    <div class="flex justify-between">
                                        <span>{currencySymbol}</span>
                                        <span
                                            >{formatCurrencyWithoutSymbol(
                                                totalAfterPPN(),
                                            )}</span
                                        >
                                    </div>
                                </td>
                                <td
                                    class="bg-gray-100 dark:bg-gray-800 border-none"
                                ></td>
                            </tr>
                        {/if}
                        {#if $form.is_income_tax_enabled === "1"}
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-3 py-1 text-right bg-white dark:bg-[#0a0a0a] border-l border-b border-gray-300 dark:border-gray-700"
                                >
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <span
                                            >PPh ({$form.income_tax_percentage ||
                                                "0"}%)</span
                                        >
                                        <i
                                            class="fa-solid fa-pen text-orange-400 text-xs cursor-pointer"
                                            role="button"
                                            tabindex="0"
                                            aria-label="Pilih jenis PPh"
                                            onclick={() =>
                                                (showPPhModal = true)}
                                            onkeydown={keydownActivate(
                                                () => (showPPhModal = true),
                                            )}
                                        ></i>
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-1 bg-white dark:bg-[#0a0a0a] border-b border-r border-gray-300 dark:border-gray-700"
                                >
                                    <div class="flex justify-between">
                                        <span>{currencySymbol}</span>
                                        <span
                                            >{formatCurrencyWithoutSymbol(
                                                pphAmount(),
                                            )}</span
                                        >
                                    </div>
                                </td>
                                <td
                                    class="bg-gray-100 dark:bg-gray-800 border-none"
                                ></td>
                            </tr>
                        {/if}
                        <tr class="font-bold">
                            <td
                                colspan="5"
                                class="px-3 py-2 text-right bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700"
                                >GRAND TOTAL</td
                            >
                            <td
                                class="px-3 py-2 bg-white dark:bg-[#0a0a0a] border border-gray-300 dark:border-gray-700"
                            >
                                <div class="flex justify-between">
                                    <span>{currencySymbol}</span>
                                    <span
                                        >{formatCurrencyWithoutSymbol(
                                            grandTotal(),
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

            <div
                class="flex flex-col md:flex-row justify-between items-center gap-4"
            >
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <div class="relative flex items-center">
                            <input
                                type="checkbox"
                                checked={$form.is_value_added_tax_enabled ===
                                    "1"}
                                class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-400 dark:border-gray-600 checked:bg-blue-600 checked:border-blue-600 transition-all"
                                onchange={(e) =>
                                    ($form.is_value_added_tax_enabled = e
                                        .currentTarget.checked
                                        ? "1"
                                        : "0")}
                            />
                            <i
                                class="fa-solid fa-check text-white absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs opacity-0 peer-checked:opacity-100"
                            ></i>
                        </div>
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Pakai PPN</span
                        >
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <div class="relative flex items-center">
                            <input
                                type="checkbox"
                                checked={$form.is_income_tax_enabled === "1"}
                                class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-400 dark:border-gray-600 checked:bg-blue-600 checked:border-blue-600 transition-all"
                                onchange={(e) =>
                                    ($form.is_income_tax_enabled = e
                                        .currentTarget.checked
                                        ? "1"
                                        : "0")}
                            />
                            <i
                                class="fa-solid fa-check text-white absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs opacity-0 peer-checked:opacity-100"
                            ></i>
                        </div>
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Pakai PPh</span
                        >
                    </label>
                </div>
            </div>
        </Card>
        <Modal
            bind:isOpen={showWarehouseModal}
            title="Pilih Gudang"
            onClose={() => (showWarehouseModal = false)}
        >
            {#snippet children()}
                <Select
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
            bind:isOpen={showSupplierModal}
            title="Pilih Supplier"
            onClose={() => (showSupplierModal = false)}
        >
            {#snippet children()}
                <Select
                    value={$form.supplier_id}
                    options={supplierOptions}
                    onchange={(v) => ($form.supplier_id = String(v))}
                />
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showSupplierModal = false)}
                >
                    Batal
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showDeliveryCostModal}
            title="Biaya Pengiriman"
            onClose={() => (showDeliveryCostModal = false)}
        >
            {#snippet children()}
                <TextInput
                    id="delivery_cost"
                    name="delivery_cost"
                    label="Biaya Pengiriman"
                    currency={true}
                    value={$form.delivery_cost}
                    oninput={(e) => {
                        if (typeof e !== "object") return;
                        const val =
                            ((e as any).numericValue ??
                                Number($form.delivery_cost)) ||
                            0;
                        $form.delivery_cost = String(val);
                    }}
                />
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showDeliveryCostModal = false)}
                >
                    Tutup
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showDiscountModal}
            title="Diskon (%)"
            onClose={() => (showDiscountModal = false)}
        >
            {#snippet children()}
                <TextInput
                    id="discount_percentage"
                    name="discount_percentage"
                    label="Diskon (%)"
                    type="number"
                    value={$form.discount_percentage}
                    oninput={(ev) => {
                        const v =
                            (ev as any).value ??
                            String($form.discount_percentage ?? "");
                        $form.discount_percentage = v;
                    }}
                />
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showDiscountModal = false)}
                >
                    Tutup
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showPPNModal}
            title="PPN"
            onClose={() => (showPPNModal = false)}
        >
            {#snippet children()}
                <div class="space-y-4">
                    <Checkbox
                        id="is_value_added_tax_enabled"
                        name="is_value_added_tax_enabled"
                        label="Aktifkan PPN"
                        checked={$form.is_value_added_tax_enabled === "1"}
                        onchange={(ev) =>
                            ($form.is_value_added_tax_enabled = (
                                ev.target as HTMLInputElement
                            ).checked
                                ? "1"
                                : "0")}
                    />
                    <Select
                        label="Jenis PPN"
                        value={$form.value_added_tax_id}
                        options={valueAddedTaxOptions}
                        onchange={(v) => ($form.value_added_tax_id = String(v))}
                    />
                </div>
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showPPNModal = false)}
                >
                    Batal
                </Button>
                <Button variant="primary" onclick={handlePPNSelect}>
                    Pilih
                </Button>
            {/snippet}
        </Modal>
        <Modal
            bind:isOpen={showPPhModal}
            title="PPh"
            onClose={() => (showPPhModal = false)}
        >
            {#snippet children()}
                <div class="space-y-4">
                    <Checkbox
                        id="is_income_tax_enabled"
                        name="is_income_tax_enabled"
                        label="Aktifkan PPh"
                        checked={$form.is_income_tax_enabled === "1"}
                        onchange={(ev) =>
                            ($form.is_income_tax_enabled = (
                                ev.target as HTMLInputElement
                            ).checked
                                ? "1"
                                : "0")}
                    />
                    <Select
                        label="Jenis PPh"
                        value={$form.income_tax_id}
                        options={incomeTaxOptions}
                        onchange={(v) => ($form.income_tax_id = String(v))}
                    />
                </div>
            {/snippet}
            {#snippet footerSlot()}
                <Button
                    variant="secondary"
                    onclick={() => (showPPhModal = false)}
                >
                    Batal
                </Button>
                <Button variant="primary" onclick={handlePPhSelect}>
                    Pilih
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
                title="Edit Item"
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
                                const sid = $form.supplier_id
                                    ? String($form.supplier_id)
                                    : "";
                                if (sid) {
                                    const psp = productSupplierPrices.find(
                                        (ps) =>
                                            String(ps.supplier_id) === sid &&
                                            String(ps.product_id) === String(v),
                                    );
                                    if (psp) {
                                        item.price = String(psp.price);
                                    } else {
                                        const pp = productPurchasePrices.find(
                                            (p) =>
                                                String(p.product_id) ===
                                                String(v),
                                        );
                                        if (pp) {
                                            item.price = String(pp.price);
                                        }
                                    }
                                }
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
                            label="Harga Beli"
                            currency={true}
                            value={editingItemDraft?.price ?? "0"}
                            oninput={(e) => {
                                if (typeof e !== "object") return;
                                const item = editingItemDraft;
                                if (!item) return;
                                const val =
                                    (e as any).numericValue ??
                                    Number(item.price);
                                item.price = String(val);
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
