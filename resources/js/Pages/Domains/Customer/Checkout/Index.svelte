<script lang="ts">
    import { router, page } from "@inertiajs/svelte";
    import ProductDetailModal from "../Product/Modal.svelte";
    import ScheduleModal from "./ScheduleModal.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";

    interface Props {
        cart?: Record<string, any>;
        dropPoint?: {
            id: string;
            name: string;
            address: string;
        } | null;
        address?: {
            id: string;
            name: string;
            phone: string;
            address: string;
            note?: string;
        } | null;
        fees: {
            deliveryFee: number;
            baseDeliveryFee: number;
            adminFee: number;
            taxAmount: number;
            taxPercentage: number;
            taxEnabled: boolean;
        };
        settings: {
            delivery_fee_mode: string;
            free_courier_min_order: number;
            admin_fee_enabled: boolean;
            admin_fee_type: string;
            admin_fee_value: number;
            tax_enabled: boolean;
            order_cutoff_time: string;
            order_min_days_ahead: number;
        };
        orderType?: "instant" | "preorder";
        delivery_date?: string;
        delivery_time?: string;
    }

    let {
        cart = $bindable({}),
        dropPoint = $bindable(null),
        address = $bindable(null),
        fees,
        settings,
        orderType = "preorder",
        delivery_date = "",
        delivery_time = "",
    }: Props = $props();

    // Ensure cart is an object even if passed as empty array from PHP
    if (Array.isArray(cart) && cart.length === 0) {
        cart = {};
    }

    // State for Editing
    let showModal = $state(false);
    let showScheduleModal = $state(false);
    let selectedItem: any = $state(null);
    let editingItemKey: string | null = $state(null);

    // Convert cart object to array for easier iteration
    const items = $derived(
        Object.keys(cart).map((key) => ({ ...cart[key], _key: key })),
    );

    const subtotal = $derived(
        items.reduce((sum, item) => sum + item.totalPrice, 0),
    );

    // Recalculate fees locally to handle quantity changes
    const localDeliveryFee = $derived.by(() => {
        if (
            settings.free_courier_min_order > 0 &&
            subtotal >= settings.free_courier_min_order
        ) {
            return 0;
        }
        return fees.baseDeliveryFee;
    });

    const localTaxAmount = $derived(
        settings.tax_enabled
            ? Math.round((subtotal * fees.taxPercentage) / 100)
            : 0,
    );

    const localAdminFee = $derived.by(() => {
        if (!settings.admin_fee_enabled) return 0;
        if (settings.admin_fee_type === "fixed")
            return settings.admin_fee_value;
        return Math.round((subtotal * settings.admin_fee_value) / 100);
    });

    const totalAmount = $derived(
        subtotal + localDeliveryFee + localTaxAmount + localAdminFee,
    );

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function goBack() {
        if (dropPoint) {
            router.visit(`/drop-points/${dropPoint.id}/products`);
        } else {
            router.visit(`/products`);
        }
    }

    function handleLanjutPembayaran() {
        router.visit("/payment-summary");
    }

    function handleEditClick(item: any, key: string) {
        selectedItem = item;
        editingItemKey = key;
        showModal = true;
    }

    function updateQuantity(key: string, delta: number) {
        const item = cart[key];
        if (!item) return;

        const newQuantity = item.quantity + delta;
        if (newQuantity <= 0) {
            delete cart[key];
        } else {
            const unitPrice = item.totalPrice / item.quantity;
            item.quantity = newQuantity;
            item.totalPrice = unitPrice * newQuantity;
        }
        cart = { ...cart };
        updateSession();
    }

    function setQuantity(key: string, value: number) {
        const item = cart[key];
        if (!item) return;

        const newQuantity = Math.max(1, value);
        const unitPrice = item.totalPrice / item.quantity;
        item.quantity = newQuantity;
        item.totalPrice = unitPrice * newQuantity;

        cart = { ...cart };
        updateSession();
    }

    function handleUpdateItem(
        product: any,
        selectedOptions: any,
        notes: string,
        quantity: number,
    ) {
        // Generate a new ID if options changed (same as in Product/Index.svelte)
        const optionsString = JSON.stringify(selectedOptions);
        const newKey = `${product.id}-${optionsString}-${notes}`;

        // Calculate new total price
        let itemTotalPrice = product.price;
        const productOptions = Array.isArray(product.options)
            ? product.options
            : product.options?.data || Object.values(product.options || {});

        for (const optionId in selectedOptions) {
            const selectedItemIds = Array.isArray(selectedOptions[optionId])
                ? selectedOptions[optionId]
                : [selectedOptions[optionId]];

            const option = productOptions.find((o: any) => o.id === optionId);
            if (option) {
                selectedItemIds.forEach((itemId: string) => {
                    const optionItem = option.items.find(
                        (i: any) => i.id === itemId,
                    );
                    if (optionItem && optionItem.extra_price) {
                        itemTotalPrice += optionItem.extra_price;
                    }
                });
            }
        }
        itemTotalPrice *= quantity;

        if (editingItemKey && editingItemKey !== newKey) {
            // Options or notes changed, remove old key and use new key
            delete cart[editingItemKey];
        }

        cart[newKey] = {
            id: newKey,
            product,
            selectedOptions,
            notes,
            quantity,
            basePrice: product.price,
            totalPrice: itemTotalPrice,
        };

        cart = { ...cart };
        updateSession();
        showModal = false;
        editingItemKey = null;
        selectedItem = null;
    }

    function getSelectedOptionsLabels(item: any) {
        const labels: string[] = [];
        const { product, selectedOptions } = item;
        const productOptions = Array.isArray(product.options)
            ? product.options
            : product.options?.data || Object.values(product.options || {});

        for (const optionId in selectedOptions) {
            const selection = selectedOptions[optionId];
            if (!selection) continue;

            const option = productOptions.find((o: any) => o.id === optionId);
            if (option) {
                const itemIds = Array.isArray(selection)
                    ? selection
                    : [selection];
                itemIds.forEach((itemId: string) => {
                    const optionItem = option.items.find(
                        (i: any) => i.id === itemId,
                    );
                    if (optionItem) {
                        labels.push(`${option.name}: ${optionItem.name}`);
                    }
                });
            }
        }
        return labels;
    }

    // Delivery Date & Time Logic
    let deliveryDateIso = $state("");
    let deliveryTime = $state("");

    $effect(() => {
        deliveryDateIso = delivery_date || "";
    });

    $effect(() => {
        deliveryTime = delivery_time || "";
    });

    const minDateIso = $derived.by(() => {
        const now = new Date();
        if (orderType === "instant") {
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        }

        // Pre-order logic
        const cutoffParts = (settings.order_cutoff_time || "20:00").split(":");
        const cutoffHour = Number(cutoffParts[0] || 20);
        const cutoffMinute = Number(cutoffParts[1] || 0);

        const cutoffDate = new Date();
        cutoffDate.setHours(cutoffHour, cutoffMinute, 0, 0);

        let targetDate = new Date();
        const minDays =
            typeof settings.order_min_days_ahead === "number"
                ? settings.order_min_days_ahead
                : 1;
        targetDate.setDate(now.getDate() + minDays);

        if (now > cutoffDate) {
            targetDate.setDate(targetDate.getDate() + 1);
        }

        const year = targetDate.getFullYear();
        const month = String(targetDate.getMonth() + 1).padStart(2, "0");
        const day = String(targetDate.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    });

    function updateSession() {
        // Use fetch or router.post with preserveState/scroll to sync session quietly
        fetch("/checkout/update-session", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || "",
            },
            body: JSON.stringify({
                cart,
                dropPoint,
                address,
                delivery_date: deliveryDateIso,
                delivery_time: deliveryTime,
            }),
        });
    }

    // Reactive Date Object for display strings
    const deliveryDate = $derived(
        deliveryDateIso ? new Date(deliveryDateIso) : null,
    );

    const deliveryDateStr = $derived(
        deliveryDate && !isNaN(deliveryDate.getTime())
            ? new Intl.DateTimeFormat("id-ID", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
              }).format(deliveryDate)
            : "",
    );

    function handleUpdateSchedule(dateIso: string, timeValue: string) {
        deliveryDateIso = dateIso;
        deliveryTime = timeValue;
        showScheduleModal = false;
        updateSession();
    }
</script>

<svelte:head>
    <title>Ringkasan Pesanan | {getSettingName($page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header class="flex items-center p-4 bg-white sticky top-0 z-30">
        <button
            onclick={goBack}
            class="w-10 h-10 flex items-center justify-center text-gray-900"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </button>
        <h1 class="flex-1 text-center font-bold text-xl text-gray-900 mr-10">
            Ringkasan Pesanan
        </h1>
    </header>

    <main class="px-4 space-y-4 mt-2 mb-10">
        <!-- Location Section -->
        <section class="flex items-start gap-4">
            <div class="mt-1">
                <i class="fa-solid fa-location-dot text-[#f44336] text-lg"></i>
            </div>
            <div>
                {#if dropPoint}
                    <h3 class="font-bold text-gray-900 text-base">
                        {dropPoint.name}
                    </h3>
                    <p class="text-gray-500 text-xs leading-relaxed">
                        {dropPoint.address}
                    </p>
                {:else if address}
                    <h3 class="font-bold text-gray-900 text-base">
                        {address.name}
                        <span class="text-xs font-normal text-gray-500"
                            >({address.phone})</span
                        >
                    </h3>
                    <p class="text-gray-500 text-xs leading-relaxed">
                        {address.address}
                        {#if address.note}
                            <br /><span class="italic text-gray-400"
                                >Catatan: {address.note}</span
                            >
                        {/if}
                    </p>
                {/if}
            </div>
        </section>

        <!-- Delivery Date Section -->
        <section class="flex items-start gap-4">
            <div class="mt-1">
                <i class="fa-solid fa-clock text-[#2196f3] text-lg"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-base">
                        Tanggal Pengiriman
                    </h3>
                    <button
                        class="text-[#2196f3] text-xs font-semibold"
                        onclick={() => (showScheduleModal = true)}
                    >
                        {deliveryDateIso ? "Ubah" : "Pilih"}
                    </button>
                </div>
                <p class="text-gray-500 text-xs leading-relaxed">
                    {#if deliveryDateIso && deliveryTime}
                        {deliveryDateStr}
                        {deliveryTime} WIB
                    {:else}
                        Mau dikirim kapan kak?
                    {/if}
                </p>
            </div>
        </section>

        <!-- Products Section -->
        <section>
            <h2 class="font-bold text-gray-900 text-lg mb-4">Menu Terkait</h2>
            <div class="space-y-6">
                {#each items as item}
                    <div class="flex gap-4">
                        <div
                            class="w-20 h-20 rounded-xl overflow-hidden shrink-0 bg-gray-100"
                        >
                            {#if item.product.image_url}
                                <img
                                    src={item.product.image_url}
                                    alt={item.product.name}
                                    class="w-full h-full object-cover"
                                />
                            {:else}
                                <div
                                    class="w-full h-full flex items-center justify-center text-gray-300"
                                >
                                    <i class="fa-solid fa-image text-2xl"></i>
                                </div>
                            {/if}
                        </div>
                        <div class="flex-1 relative">
                            <h3
                                class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 pr-12"
                            >
                                {item.product.name}
                            </h3>
                            <button
                                class="absolute top-0 right-0 text-[#2196f3] text-xs font-semibold"
                                onclick={() => handleEditClick(item, item._key)}
                                >Ubah</button
                            >

                            <div class="mt-1 space-y-0.5">
                                {#each getSelectedOptionsLabels(item) as label}
                                    <p
                                        class="text-gray-500 text-[10px] leading-tight flex items-start gap-1"
                                    >
                                        <span class="text-[#FFD700]">•</span>
                                        {label}
                                    </p>
                                {/each}
                                {#if item.notes}
                                    <p
                                        class="text-gray-400 text-[10px] italic leading-tight mt-1"
                                    >
                                        Catatan: {item.notes}
                                    </p>
                                {/if}
                            </div>

                            <div class="flex items-center justify-between mt-3">
                                <span class="text-[#f44336] font-bold"
                                    >{formatRupiah(item.totalPrice)}</span
                                >
                                <div class="flex items-center gap-3">
                                    <button
                                        class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 bg-gray-50 active:bg-gray-100 transition-colors"
                                        aria-label="Kurangi"
                                        onclick={() =>
                                            updateQuantity(item._key, -1)}
                                    >
                                        <i class="fa-solid fa-minus text-[10px]"
                                        ></i>
                                    </button>
                                    <input
                                        type="number"
                                        value={item.quantity}
                                        min="1"
                                        class="font-bold text-sm w-10 text-center bg-transparent border-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        aria-label="Jumlah"
                                        oninput={(e) => {
                                            const val = parseInt(
                                                e.currentTarget.value,
                                            );
                                            if (!isNaN(val)) {
                                                setQuantity(item._key, val);
                                            }
                                        }}
                                        onblur={(e) => {
                                            const val = parseInt(
                                                e.currentTarget.value,
                                            );
                                            if (isNaN(val) || val < 1) {
                                                setQuantity(item._key, 1);
                                            }
                                        }}
                                    />
                                    <button
                                        class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 bg-gray-50 active:bg-gray-100 transition-colors"
                                        aria-label="Tambah"
                                        onclick={() =>
                                            updateQuantity(item._key, 1)}
                                    >
                                        <i class="fa-solid fa-plus text-[10px]"
                                        ></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        </section>

        <!-- Divider -->
        <div class="border-t border-gray-100 pt-4"></div>

        <!-- Notes Section -->
        <section class="flex items-center justify-between">
            <span class="font-semibold text-gray-900 text-sm">Catatan:</span>
            <input
                type="text"
                placeholder="Tinggalkan catatan..."
                class="text-right text-sm focus:outline-none flex-1 ml-4"
            />
        </section>

        <!-- Divider -->
        <div class="border-t border-gray-100"></div>

        <!-- Cost Breakdown Section -->
        <section class="space-y-4 pt-2">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Subtotal ({items.length} menu)</span
                >
                <span class="font-semibold text-gray-900"
                    >{formatRupiah(subtotal)}</span
                >
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Biaya Pengiriman</span>
                <span class="font-semibold text-gray-900">
                    {#if localDeliveryFee === 0}
                        <span class="text-[#FFD700] font-bold">Gratis</span>
                    {:else}
                        {formatRupiah(localDeliveryFee)}
                    {/if}
                </span>
            </div>
            {#if settings.tax_enabled}
                <div class="flex justify-between items-center">
                    <span class="text-gray-600"
                        >PPN ({fees.taxPercentage}%)</span
                    >
                    <span class="font-semibold text-gray-900"
                        >{formatRupiah(localTaxAmount)}</span
                    >
                </div>
            {/if}
            {#if settings.admin_fee_enabled && localAdminFee > 0}
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Biaya Admin</span>
                    <span class="font-semibold text-gray-900"
                        >{formatRupiah(localAdminFee)}</span
                    >
                </div>
            {/if}
            <div class="flex justify-between items-center pt-2">
                <span class="font-bold text-gray-900 text-lg">Total</span>
                <span class="font-bold text-[#f44336] text-lg"
                    >{formatRupiah(totalAmount)}</span
                >
            </div>
        </section>
    </main>

    <!-- Bottom Action Bar -->
    <div
        class="fixed bottom-0 left-0 right-0 p-4 border-t border-gray-100 bg-white shadow-[0_-5px_15px_rgba(0,0,0,0.05)] rounded-t-3xl"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-gray-500 text-xs">Total Pembayaran</p>
                <p class="text-gray-900 font-bold text-base">
                    {formatRupiah(totalAmount)}
                </p>
            </div>
            <button
                onclick={handleLanjutPembayaran}
                class="bg-[#FFD700] text-gray-900 font-bold py-3 px-6 rounded-xl shadow-sm hover:opacity-90 transition-opacity whitespace-nowrap text-sm"
            >
                Lanjut Pembayaran
            </button>
        </div>
    </div>
</div>

{#if showModal && selectedItem}
    <ProductDetailModal
        product={selectedItem.product}
        initialQuantity={selectedItem.quantity}
        initialNotes={selectedItem.notes}
        initialOptions={selectedItem.selectedOptions}
        onClose={() => {
            showModal = false;
            selectedItem = null;
            editingItemKey = null;
        }}
        onAdd={handleUpdateItem}
    />
{/if}

{#if showScheduleModal}
    <ScheduleModal
        initialDateIso={deliveryDateIso}
        initialTime={deliveryTime}
        {minDateIso}
        {orderType}
        onClose={() => (showScheduleModal = false)}
        onSave={handleUpdateSchedule}
    />
{/if}
