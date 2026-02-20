<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type CartItem = {
        id: string;
        name: string;
        category: string;
        price: number;
        qty: number;
        note?: string | null;
        levelId?: string;
    };
    type SelectOption = {
        value: string | number;
        label: string;
        disabled?: boolean;
    };
    type Product = {
        id: string;
        name: string;
        category: string;
        category_label: string;
        price: number;
        image_url?: string | null;
        sku?: string | null;
    };
    type CustomerItem = {
        id: string;
        name: string;
        phone?: string | null;
        address?: string | null;
    };

    export let cart: CartItem[];
    export let clearCart: () => void;
    export let dec: (id: string) => void;
    export let inc: (id: string) => void;
    export let optionsForCart: (pid: string | null) => SelectOption[];
    export let findProduct: (pid: string | null) => Product | null;
    export let priceForLevel: (p: Product, lid: string) => number;
    export let setItemLevel: (id: string, levelId: string) => void;
    export let updateCartItemNote: (id: string, note: string | null) => void;

    export let customerOptions: () => SelectOption[];
    export let selectedCustomerId: string;

    export let taxEnabled: boolean;
    export let vatPercentSelected: number;
    export let vatSelectOptions: () => SelectOption[];
    export let taxAmount: number;

    export let useShipping: boolean;
    export let shippingStr: string;
    export let shippingNote: string | null;
    export let openShippingModal: () => void;
    export let showShippingModal: boolean;
    export let useCustomerShippingModal: boolean;
    export let shippingRecipientNameDraft: string;
    export let shippingRecipientPhoneDraft: string;
    export let shippingAddressDraft: string;
    export let shippingNoteDraft: string;
    export let findCustomer: (id: string) => CustomerItem | null;
    export let closeShippingModal: () => void;
    export let saveShippingModal: () => void;

    export let subtotal: number;
    export let itemDiscountTotal: number;
    export let extraDiscountTotal: number;
    export let extraDiscountPercentage: string | null;
    export let totalAfterDiscount: number;
    export let grandTotal: number;
    export let discountTextByItemId: Record<string, string>;

    export let voucherCode: string;
    export let voucherAmount: number;
    export let paymentMethodOptions: () => SelectOption[];
    export let selectedPayment1Id: string;
    export let selectedPayment2Id: string;
    export let payment1Str: string;
    export let payment2Str: string;
    export let paymentNote1: string;
    export let paymentNote2: string;
    export let openPaymentNoteModal: (idx: number) => void;
    export let showPaymentNoteModal: boolean;
    export let paymentNoteDraft: string;
    export let paymentNoteModalMethodIndex: number;
    export let closePaymentNoteModal: () => void;
    export let savePaymentNote: () => void;
    export let useSecondPayment: boolean;
    export let toggleSecondPayment: () => void;
    export let shortageAmount: number;
    export let changeAmount: number;
    export let canPay: boolean;
    export let openConfirmModal: () => void;
    export let setPayment1ManualOverride: (manual: boolean) => void;
    export let setPayment2ManualOverride: (manual: boolean) => void;
    export let onCustomerCreated:
        | ((customer: CustomerItem) => void)
        | undefined;

    let showAddCustomerModal = false;
    let newCustomerName = "";
    let newCustomerPhone = "";
    let newCustomerEmail = "";
    let addCustomerNameError = "";
    let addCustomerPhoneError = "";
    let addCustomerEmailError = "";
    function handleCustomerSelect(v: string | number): void {
        const val = String(v);
        if (val === "add") {
            newCustomerName = "";
            newCustomerPhone = "";
            newCustomerEmail = "";
            addCustomerNameError = "";
            addCustomerPhoneError = "";
            addCustomerEmailError = "";
            showAddCustomerModal = true;
            selectedCustomerId = "";
            return;
        }
        selectedCustomerId = val;
    }
    function closeAddCustomerModal(): void {
        showAddCustomerModal = false;
    }
    function submitAddCustomer(): void {
        addCustomerNameError = "";
        addCustomerPhoneError = "";
        addCustomerEmailError = "";
        const payload = {
            name: String(newCustomerName || ""),
            email: String(newCustomerEmail || ""),
            phone: newCustomerPhone ? String(newCustomerPhone) : null,
            is_visible_in_pos: true,
        };
        router.post("/pos/customers", payload, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                showAddCustomerModal = false;
                const flashObj = (($page as any)?.flash ??
                    ($page.props as any)?.flash ??
                    {}) as any;
                const cid = String((flashObj?.created_customer_id ?? "") || "");
                if (cid) {
                    selectedCustomerId = cid;
                }
                const created = flashObj?.created_customer as
                    | CustomerItem
                    | undefined;
                if (created && created.id) {
                    onCustomerCreated?.({
                        id: String(created.id),
                        name: String(created.name ?? "Customer"),
                        phone:
                            created.phone !== undefined ? created.phone : null,
                        address:
                            created.address !== undefined
                                ? created.address
                                : null,
                    } as CustomerItem);
                }
            },
            onError: (errors: Record<string, unknown>) => {
                addCustomerNameError = String((errors as any)?.name ?? "");
                addCustomerPhoneError = String((errors as any)?.phone ?? "");
                addCustomerEmailError = String((errors as any)?.email ?? "");
            },
        });
    }

    let showItemModal = false;
    let activeItemId: string | null = null;
    let itemNoteDraft = "";
    let selectedEditLevelId = "main";
    let priceEditPreview = 0;
    function openItemModal(id: string): void {
        const it = cart.find((x) => x.id === id) || null;
        activeItemId = id;
        itemNoteDraft = it?.note ? String(it.note) : "";
        selectedEditLevelId = String(it?.levelId ?? "main");
        const p = findProduct(id);
        priceEditPreview = p
            ? priceForLevel(p, selectedEditLevelId)
            : Number(it?.price ?? 0);
        showItemModal = true;
    }
    function closeItemModal(): void {
        showItemModal = false;
        activeItemId = null;
        itemNoteDraft = "";
        selectedEditLevelId = "main";
        priceEditPreview = 0;
    }
    function saveItemModal(): void {
        if (!activeItemId) {
            closeItemModal();
            return;
        }
        setItemLevel(activeItemId, selectedEditLevelId);
        const v = itemNoteDraft.trim();
        updateCartItemNote(activeItemId, v !== "" ? v : null);
        closeItemModal();
    }
</script>

<aside
    class="w-full lg:w-95 xl:w-105 shrink-0 lg:sticky lg:top-5 self-start rounded-xl"
>
    <div
        class="bg-white dark:bg-[#0f0f0f] rounded-xl border border-gray-200 dark:border-[#2c2c2c] flex flex-col"
    >
        <div
            class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-[#1a1a1a] rounded-xl"
        >
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Pesanan Saat Ini
                </h2>
            </div>
            <Button
                variant="icon"
                icon="fa-regular fa-trash-can"
                ariaLabel="Hapus semua"
                onclick={clearCart}
            />
        </div>

        <div class="px-5 py-3 border-b border-gray-200 dark:border-[#1a1a1a]">
            <div class="flex items-center justify-between gap-3">
                <div class="flex-1">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Customer
                    </div>
                    <div role="group" aria-label="Pilih customer">
                        <Select
                            minimal
                            placeholder="Pilih customer"
                            value={selectedCustomerId}
                            options={customerOptions()}
                            onchange={handleCustomerSelect}
                        />
                    </div>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="flex-1">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        PPN
                    </div>
                    <div class="flex items-center gap-3">
                        <Checkbox
                            label="Aktifkan PPN"
                            bind:checked={taxEnabled}
                        />
                    </div>
                </div>
                <div class="flex-1">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Pengiriman
                    </div>
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="use_shipping"
                            name="use_shipping"
                            label="Aktifkan Pengiriman"
                            bind:checked={useShipping}
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 flex-1 overflow-y-auto">
            {#if cart.length === 0}
                <div
                    class="h-40 flex flex-col gap-2 items-center justify-center text-gray-400 dark:text-gray-500"
                >
                    <i class="fa-solid fa-utensils text-4xl"></i>
                    <span class="text-sm">Mulai menambahkan item</span>
                </div>
            {:else}
                <div class="space-y-3">
                    {#each cart as it (it.id)}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1">
                                <div
                                    class="text-xs font-semibold text-gray-900 dark:text-white"
                                >
                                    {it.name}
                                </div>
                                <div
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    {formatCurrency(it.price)}
                                </div>
                                {#if discountTextByItemId && discountTextByItemId[it.id]}
                                    <div
                                        class="text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        Diskon: {discountTextByItemId[it.id]}
                                    </div>
                                {/if}

                                {#if it.note}
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400 italic mt-0.5"
                                    >
                                        Note: {it.note}
                                    </div>
                                {/if}
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="light"
                                    size="sm"
                                    icon="fa-solid fa-minus"
                                    ariaLabel="Kurangi"
                                    onclick={() => dec(it.id)}
                                />
                                <span
                                    class="w-8 text-center text-sm text-gray-900 dark:text-white"
                                    >{it.qty}</span
                                >
                                <Button
                                    variant="light"
                                    size="sm"
                                    icon="fa-solid fa-plus"
                                    ariaLabel="Tambah"
                                    onclick={() => inc(it.id)}
                                />
                                <Button
                                    variant="icon"
                                    icon="fa-regular fa-pen-to-square"
                                    ariaLabel="Ubah catatan"
                                    onclick={() => openItemModal(it.id)}
                                    textColor={it.note
                                        ? "orange-500"
                                        : "gray-400"}
                                    hoverTextColor={it.note
                                        ? "orange-600"
                                        : "gray-500"}
                                />
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <div
            class="px-5 py-4 bg-gray-50 dark:bg-[#151515] border-t border-gray-200 dark:border-[#1a1a1a] space-y-2 rounded-xl"
        >
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="text-gray-900 dark:text-white"
                    >{formatCurrency(subtotal)}</span
                >
            </div>
            {#if (itemDiscountTotal ?? 0) > 0}
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400"
                        >Total Diskon Item</span
                    >
                    <span class="text-gray-900 dark:text-white"
                        >{formatCurrency(itemDiscountTotal)}</span
                    >
                </div>
            {/if}
            {#if (extraDiscountTotal ?? 0) > 0}
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        Diskon Tambahan{extraDiscountPercentage
                            ? ` (${extraDiscountPercentage}%)`
                            : ""}
                    </span>
                    <span class="text-gray-900 dark:text-white"
                        >{formatCurrency(extraDiscountTotal)}</span
                    >
                </div>
            {/if}
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400"
                    >Total Setelah Diskon</span
                >
                <span class="text-gray-900 dark:text-white"
                    >{formatCurrency(totalAfterDiscount)}</span
                >
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Voucher</span>
                <div class="w-40">
                    <TextInput
                        id="voucher_code"
                        name="voucher_code"
                        placeholder="Kode voucher"
                        bind:value={voucherCode}
                    />
                </div>
            </div>
            {#if (voucherAmount ?? 0) > 0}
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Nilai Voucher</span>
                    <span class="text-gray-900 dark:text-white"
                        >{formatCurrency(voucherAmount ?? 0)}</span
                    >
                </div>
            {/if}
            {#if taxEnabled}
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">PPN</span
                        >
                        <div class="w-20">
                            <Select
                                minimal
                                searchable={false}
                                placeholder="Persentase PPN"
                                value={vatPercentSelected}
                                options={vatSelectOptions()}
                                onchange={(v) =>
                                    (vatPercentSelected = Number(v))}
                            />
                        </div>
                    </div>

                    <span class="text-gray-900 dark:text-white"
                        >{formatCurrency(taxAmount)}</span
                    >
                </div>
            {/if}
            {#if useShipping}
                <div class="flex items-start justify-between text-sm">
                    <div class="flex flex-col">
                        <span class="text-gray-600 dark:text-gray-400"
                            >Biaya Pengiriman</span
                        >
                        <div class="text-xs">
                            <button
                                type="button"
                                class="underline text-[#0060B2]"
                                onclick={openShippingModal}
                                disabled={!useShipping}
                                aria-label="open shipping details"
                            >
                                Catatan: {shippingNote || "......"}
                            </button>
                        </div>
                    </div>
                    <div class="w-40">
                        <TextInput
                            id="shipping_cost"
                            name="shipping_cost"
                            placeholder="Biaya"
                            currency={true}
                            currencySymbol="Rp"
                            thousandSeparator="."
                            decimalSeparator=","
                            maxDecimals={0}
                            stripZeros={true}
                            bind:value={shippingStr}
                            disabled={!useShipping}
                            oninput={(ev) =>
                                (shippingStr = String(
                                    (ev as any).numericValue ?? 0,
                                ))}
                        />
                    </div>
                </div>
            {/if}
            <div class="flex items-center justify-between pt-2">
                <span
                    class="text-base font-semibold text-gray-900 dark:text-white"
                    >Grand Total</span
                >
                <span class="text-xl font-bold text-gray-900 dark:text-white"
                    >{formatCurrency(grandTotal)}</span
                >
            </div>
            <div class="mt-3 space-y-2">
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    Metode Bayar
                </div>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <Select
                                minimal
                                searchable={false}
                                placeholder="Metode #1"
                                value={selectedPayment1Id}
                                options={paymentMethodOptions()}
                                onchange={(v) => {
                                    selectedPayment1Id = String(v);
                                    setPayment1ManualOverride(false);
                                    if (selectedPayment1Id === "") {
                                        payment1Str = "0";
                                    }
                                }}
                            />
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <button
                                    type="button"
                                    class="underline text-[#0060B2]"
                                    onclick={() => openPaymentNoteModal(1)}
                                    aria-label="open payment note method 1"
                                >
                                    Catatan: {paymentNote1 || "......"}
                                </button>
                            </div>
                        </div>
                        <div class="w-40">
                            <TextInput
                                id="pay1"
                                name="pay1"
                                placeholder="Nominal #1"
                                currency={true}
                                currencySymbol="Rp"
                                thousandSeparator="."
                                decimalSeparator=","
                                maxDecimals={0}
                                stripZeros={true}
                                bind:value={payment1Str}
                                disabled={selectedPayment1Id === ""}
                                oninput={(ev) => {
                                    setPayment1ManualOverride(true);
                                    payment1Str = String(
                                        (ev as any).numericValue ?? 0,
                                    );
                                }}
                            />
                        </div>
                    </div>

                    {#if useSecondPayment}
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <Select
                                    minimal
                                    searchable={false}
                                    placeholder="Metode #2"
                                    value={selectedPayment2Id}
                                    options={paymentMethodOptions()}
                                    onchange={(v) => {
                                        selectedPayment2Id = String(v);
                                        setPayment2ManualOverride(false);
                                        if (selectedPayment2Id === "") {
                                            payment2Str = "0";
                                        }
                                    }}
                                />
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <button
                                        type="button"
                                        class="underline text-[#0060B2]"
                                        onclick={() => openPaymentNoteModal(2)}
                                        aria-label="open payment note method 2"
                                        disabled={selectedPayment2Id === ""}
                                    >
                                        Catatan: {paymentNote2 || "......"}
                                    </button>
                                </div>
                            </div>
                            <div class="w-40">
                                <TextInput
                                    id="pay2"
                                    name="pay2"
                                    placeholder="Nominal #2"
                                    currency={true}
                                    currencySymbol="Rp"
                                    thousandSeparator="."
                                    decimalSeparator=","
                                    maxDecimals={0}
                                    stripZeros={true}
                                    bind:value={payment2Str}
                                    disabled={selectedPayment2Id === ""}
                                    oninput={(ev) => {
                                        setPayment2ManualOverride(true);
                                        payment2Str = String(
                                            (ev as any).numericValue ?? 0,
                                        );
                                    }}
                                />
                            </div>
                        </div>
                    {/if}
                    <div class="flex items-center justify-between text-xs">
                        <button
                            type="button"
                            class="underline text-[#0060B2]"
                            onclick={toggleSecondPayment}
                            aria-label="toggle second payment"
                        >
                            {useSecondPayment
                                ? "Hapus metode kedua"
                                : "Tambah metode kedua"}
                        </button>
                    </div>
                    {#if shortageAmount > 0}
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Kurang bayar</span
                            >
                            <span class="text-red-600"
                                >{formatCurrency(shortageAmount)}</span
                            >
                        </div>
                    {:else if changeAmount > 0}
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Kembalian</span
                            >
                            <span class="text-green-600"
                                >{formatCurrency(changeAmount)}</span
                            >
                        </div>
                    {/if}
                </div>
            </div>
            <Button
                variant="primary"
                fullWidth
                disabled={!canPay}
                onclick={openConfirmModal}>Konfirmasi</Button
            >
        </div>
    </div>
</aside>

<Modal
    bind:isOpen={showAddCustomerModal}
    title="Tambah Customer"
    onClose={closeAddCustomerModal}
>
    {#snippet children()}
        <div class="space-y-3">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <TextInput
                        id="new_customer_name"
                        name="new_customer_name"
                        label="Nama"
                        placeholder="Nama lengkap"
                        bind:value={newCustomerName}
                        error={addCustomerNameError}
                    />
                </div>
                <div>
                    <TextInput
                        id="new_customer_phone"
                        name="new_customer_phone"
                        label="No. HP"
                        placeholder="08xxxxxxxxxx"
                        bind:value={newCustomerPhone}
                        error={addCustomerPhoneError}
                    />
                </div>
                <div>
                    <TextInput
                        id="new_customer_email"
                        name="new_customer_email"
                        label="Email"
                        placeholder="nama@email.com"
                        bind:value={newCustomerEmail}
                        error={addCustomerEmailError}
                    />
                </div>
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={closeAddCustomerModal}
            >Batal</Button
        >
        <Button variant="primary" onclick={submitAddCustomer}>Simpan</Button>
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showItemModal}
    title="Ubah Level & Catatan"
    onClose={closeItemModal}
>
    {#snippet children()}
        {#if activeItemId}
            <div class="space-y-3">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    {cart.find((x) => x.id === activeItemId)?.name}
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600 dark:text-gray-400"
                        >Harga saat ini</span
                    >
                    <span
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        {formatCurrency(
                            cart.find((x) => x.id === activeItemId)?.price ?? 0,
                        )}
                    </span>
                </div>
                <div role="group" aria-label="Pilih level harga">
                    <Select
                        minimal
                        placeholder="Level harga"
                        value={selectedEditLevelId}
                        options={optionsForCart(activeItemId)}
                        onchange={(v) => {
                            selectedEditLevelId = String(v);
                            const p = findProduct(activeItemId);
                            priceEditPreview = p
                                ? priceForLevel(p, selectedEditLevelId)
                                : 0;
                        }}
                    />
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600 dark:text-gray-400"
                        >Harga terpilih</span
                    >
                    <span
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        {formatCurrency(priceEditPreview)}
                    </span>
                </div>
                <TextArea
                    id="item_note"
                    name="item_note"
                    label="Catatan"
                    rows={4}
                    bind:value={itemNoteDraft}
                />
            </div>
        {/if}
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={closeItemModal}>Batal</Button>
        <Button variant="primary" onclick={saveItemModal}>Simpan</Button>
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showShippingModal}
    title="Detail Pengiriman"
    onClose={closeShippingModal}
>
    {#snippet children()}
        <div class="space-y-4">
            {#if selectedCustomerId !== ""}
                <Checkbox
                    id="use_customer_shipping"
                    name="use_customer_shipping"
                    label="Gunakan data customer"
                    bind:checked={useCustomerShippingModal}
                    onchange={(e) => {
                        const checked = (e as any).target?.checked ?? false;
                        useCustomerShippingModal = checked;
                        if (checked) {
                            const cust = findCustomer(selectedCustomerId);
                            shippingRecipientNameDraft = cust?.name ?? "";
                            shippingRecipientPhoneDraft = cust?.phone ?? "";
                            shippingAddressDraft = cust?.address ?? "";
                        }
                    }}
                />
            {/if}
            <TextInput
                id="recipient_name"
                name="recipient_name"
                label="Nama Penerima"
                placeholder="Nama"
                disabled={useCustomerShippingModal}
                bind:value={shippingRecipientNameDraft}
            />
            <TextInput
                id="recipient_phone"
                name="recipient_phone"
                label="No. Telepon"
                placeholder="08xxxxxxxxxx"
                disabled={useCustomerShippingModal}
                bind:value={shippingRecipientPhoneDraft}
            />
            <TextArea
                id="delivery_address"
                name="delivery_address"
                label="Alamat Pengiriman"
                rows={4}
                disabled={useCustomerShippingModal}
                bind:value={shippingAddressDraft}
            />
            <TextArea
                id="shipping_note"
                name="shipping_note"
                label="Catatan"
                rows={4}
                bind:value={shippingNoteDraft}
            />
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={closeShippingModal}>Batal</Button>
        <Button variant="primary" onclick={saveShippingModal}>Simpan</Button>
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showPaymentNoteModal}
    title={`Catatan Pembayaran Metode #${paymentNoteModalMethodIndex}`}
    onClose={closePaymentNoteModal}
>
    {#snippet children()}
        <TextArea
            id="payment_note"
            name="payment_note"
            label="Catatan"
            rows={5}
            bind:value={paymentNoteDraft}
        />
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={closePaymentNoteModal}
            >Batal</Button
        >
        <Button variant="primary" onclick={savePaymentNote}>Simpan</Button>
    {/snippet}
</Modal>
