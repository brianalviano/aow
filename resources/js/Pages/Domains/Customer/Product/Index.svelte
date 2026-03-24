<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import { router } from "@inertiajs/svelte";
    import ProductDetailModal from "./Modal.svelte";

    export let dropPoint: {
        id: string;
        name: string;
        photo: string | null;
        photo_url: string | null;
        address: string;
    };

    export let categories: {
        id: string;
        name: string;
    }[];

    export let products: {
        id: string;
        product_category_id: string;
        name: string;
        description: string;
        price: number;
        image_url: string | null;
        stock_limit: number | null;
        options: {
            id: string;
            name: string;
            is_required: boolean;
            is_multiple: boolean;
            items: {
                id: string;
                name: string;
                extra_price: number;
            }[];
        }[];
        testimonials?: {
            id: string;
            rating: string;
            content: string;
            photo_url: string;
            created_at: string;
            customer: { name: string };
        }[];
        total_sales: number;
        average_rating: number;
        testimonials_count: number;
    }[];

    export let address: {
        id: string;
        name: string;
        address: string;
        phone: string;
    } | null = null;

    export let savedCart: Record<string, any> = {};

    export let orderType = "preorder";

    export let quotaProgress: {
        has_quota: boolean;
        min_qty: number | null;
        min_amount: number | null;
        current_qty: number;
        current_amount: number;
        is_fulfilled: boolean;
        percentage: number;
    } | null = null;

    // State
    let isCheckoutLoading = false;
    let searchQuery = "";
    let selectedCategory: string =
        categories.length > 0 && categories[0] ? categories[0].id : "";
    let selectedProduct: any = null;
    let showModal = false;
    let showChefWarningModal = false;
    let baseDeliveryFee: number = 0;

    // Scroll Spy State
    let isScrollingFromClick = false;
    let observer: IntersectionObserver | null = null;
    let categoryTabsContainer: HTMLDivElement;
    let scrollTimeout: any = null;

    // Cart State
    // format: { [cartItemId]: { product, quantity, options, notes, totalPrice } }
    // cartItemId is a unique string based on product id and selected options
    let cart: Record<string, any> =
        savedCart && !Array.isArray(savedCart) ? savedCart : {};

    $: filteredProducts = products.filter((p) => {
        return p.name.toLowerCase().includes(searchQuery.toLowerCase());
    });

    $: productsByCategory = categories
        .map((c) => {
            return {
                ...c,
                products: filteredProducts.filter(
                    (p) => p.product_category_id === c.id,
                ),
            };
        })
        .filter((c) => c.products.length > 0);

    $: totalCartItems = Object.values(cart).reduce(
        (sum, item) => sum + item.quantity,
        0,
    );
    $: totalCartPrice = Object.values(cart).reduce(
        (sum, item) => sum + item.totalPrice,
        0,
    );

    $: cartQuantities = Object.values(cart).reduce(
        (acc, item) => {
            const productId = item.product.id;
            acc[productId] = (acc[productId] || 0) + item.quantity;
            return acc;
        },
        {} as Record<string, number>,
    );

    $: uniqueChefIds = [
        ...new Set(
            Object.values(cart).flatMap((item) => {
                const rawChefs = item.product.chefs;
                const productChefs = Array.isArray(rawChefs)
                    ? rawChefs
                    : rawChefs?.data && Array.isArray(rawChefs.data)
                      ? rawChefs.data
                      : [];
                return productChefs.map((c: any) => c.id);
            }),
        ),
    ].filter(Boolean);

    $: chefCount = Math.max(1, uniqueChefIds.length);

    async function fetchBaseDeliveryFee() {
        // We can get this from dropPoint if available, or we might need a small API call if it's dynamic.
        // For now, let's assume we can derive it or get it from the first checkout attempt's fees.
        // But better yet, let's just use the dropPoint.delivery_fee if it exists.
        baseDeliveryFee = (dropPoint as any)?.deliveryFee || 0;
    }

    onMount(() => {
        setupObserver();
        fetchBaseDeliveryFee();
    });

    function goBack() {
        router.visit("/"); // Or `/drop-points/${dropPoint.id}`
    }

    function scrollTabIntoView(categoryId: string) {
        if (!categoryTabsContainer) return;
        const tab = categoryTabsContainer.querySelector(
            `[data-category-id="${categoryId}"]`,
        ) as HTMLElement | null;
        if (tab) {
            const containerWidth = categoryTabsContainer.clientWidth;
            const tabOffsetLeft = tab.offsetLeft;
            const tabWidth = tab.clientWidth;

            const targetScrollLeft = tabOffsetLeft - containerWidth / 2 + tabWidth / 2;

            categoryTabsContainer.scrollTo({
                left: targetScrollLeft,
                behavior: "smooth",
            });
        }
    }

    function setupObserver() {
        if (typeof window === "undefined") return;
        if (observer) observer.disconnect();

        const options = {
            root: null,
            rootMargin: "-120px 0px -70% 0px", // Trigger when top of section is near header
            threshold: 0,
        };

        observer = new IntersectionObserver((entries) => {
            if (isScrollingFromClick) return;

            // Find the entry that is intersecting.
            // We look for the one that is currently crossing the top margin boundary.
            const intersectingEntry = entries.find(
                (entry) => entry.isIntersecting,
            );
            if (intersectingEntry) {
                const categoryId = intersectingEntry.target.id.replace(
                    "category-",
                    "",
                );
                if (selectedCategory !== categoryId) {
                    selectedCategory = categoryId;
                    scrollTabIntoView(categoryId);
                }
            }
        }, options);

        // Use a small delay to ensure DOM is ready
        setTimeout(() => {
            const sections = document.querySelectorAll('[id^="category-"]');
            sections.forEach((section) => observer?.observe(section));
        }, 100);
    }

    onMount(() => {
        setupObserver();
    });

    onDestroy(() => {
        if (observer) observer.disconnect();
        if (scrollTimeout) clearTimeout(scrollTimeout);
    });

    // Re-setup observer if products display change (e.g. search)
    $: if (productsByCategory) {
        setupObserver();
    }

    function scrollToCategory(categoryId: string) {
        isScrollingFromClick = true;
        selectedCategory = categoryId;

        if (scrollTimeout) clearTimeout(scrollTimeout);

        const element = document.getElementById(`category-${categoryId}`);
        if (element) {
            const yOffset = -120; // Adjust based on sticky header height
            const y =
                element.getBoundingClientRect().top +
                window.pageYOffset +
                yOffset;
            window.scrollTo({ top: y, behavior: "smooth" });

            scrollTabIntoView(categoryId);
        }

        // Reset click flag after scroll animation should be done
        scrollTimeout = setTimeout(() => {
            isScrollingFromClick = false;
        }, 1000);
    }

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function getQuantityInCart(productId: string) {
        // Summarize quantity of this product in cart (regardless of options)
        return Object.values(cart)
            .filter((item) => item.product.id === productId)
            .reduce((sum, item) => sum + item.quantity, 0);
    }

    function handleTambahClick(product: any) {
        selectedProduct = product;
        showModal = true;
    }

    function addToCart(
        product: any,
        selectedOptions: any,
        notes: string,
        quantity: number,
    ) {
        // Generate a unique ID based on options and notes
        // This is simplified. In a real app, you'd sort options to ensure consistency
        const optionsString = JSON.stringify(selectedOptions);
        const cartItemId = `${product.id}-${optionsString}-${notes}`;

        let itemTotalPrice = product.price;
        // Add extra price from options
        const productOptions = (function () {
            if (!product || !product.options) return [];
            if (Array.isArray(product.options)) return product.options;
            const opts = product.options as any;
            if (opts.data && Array.isArray(opts.data)) return opts.data;
            return Object.values(product.options);
        })();
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

        if (cart[cartItemId]) {
            cart[cartItemId].quantity += quantity;
            cart[cartItemId].totalPrice =
                (cart[cartItemId].totalPrice /
                    (cart[cartItemId].quantity - quantity)) *
                cart[cartItemId].quantity;
        } else {
            cart[cartItemId] = {
                id: cartItemId,
                product,
                selectedOptions,
                notes,
                quantity,
                basePrice: product.price,
                totalPrice: itemTotalPrice,
            };
        }

        cart = { ...cart }; // Trigger reactivity
        showModal = false;
    }

    function updateCartItemQuantity(productId: string, delta: number) {
        // Find the first cart item for this product
        const cartItemIds = Object.keys(cart).filter(
            (id) => cart[id].product.id === productId,
        );
        if (cartItemIds.length === 0) return;

        // Default to modifying the first one found (if they have multiple variations, they should edit from cart. Here we do simple +-)
        const cartItemId = cartItemIds[0];
        if (!cartItemId) return;

        const item = cart[cartItemId];
        if (!item) return;
        const newQuantity = item.quantity + delta;

        if (newQuantity <= 0) {
            delete cart[cartItemId];
        } else {
            const unitPrice = item.totalPrice / item.quantity;
            item.quantity = newQuantity;
            item.totalPrice = unitPrice * newQuantity;
            cart[cartItemId] = item;
        }

        cart = { ...cart }; // Trigger reactivity
    }

    function updateCartItemQuantityWithValue(productId: string, value: number) {
        const cartItemIds = Object.keys(cart).filter(
            (id) => cart[id].product.id === productId,
        );
        if (cartItemIds.length === 0) return;

        const cartItemId = cartItemIds[0];
        if (!cartItemId) return;

        const item = cart[cartItemId];
        if (!item) return;

        const newQuantity = Math.max(1, value);
        const unitPrice = item.totalPrice / item.quantity;
        item.quantity = newQuantity;
        item.totalPrice = unitPrice * newQuantity;
        cart[cartItemId] = item;

        cart = { ...cart };
    }
</script>

<svelte:head>
    <title>Menu | {dropPoint?.name ?? address?.name ?? "Pesan Sekarang"}</title>
</svelte:head>

<div class="bg-gray-50 min-h-screen pb-24">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white">
        <header class="flex items-center p-4">
            <button
                on:click={goBack}
                class="w-8 h-8 flex items-center justify-center text-gray-800"
                aria-label="Kembali"
            >
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>
            <div class="ml-2 flex flex-col min-w-0">
                <h1
                    class="font-bold text-base text-gray-900 truncate leading-tight"
                >
                    {dropPoint?.name ?? address?.name ?? "Menu"}
                </h1>
                <p class="text-[10px] text-gray-500 truncate leading-tight">
                    {dropPoint?.address ?? address?.address ?? ""}
                </p>
            </div>
        </header>

        <!-- Search Bar -->
        <div class="px-4 pb-3">
            <div
                class="relative flex items-center w-full h-10 rounded-xl focus-within:shadow-lg bg-gray-50 overflow-hidden border border-gray-200"
            >
                <div class="grid place-items-center h-full w-12 text-gray-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input
                    class="peer h-full w-full outline-none text-sm text-gray-700 bg-gray-50 pr-2 placeholder-gray-400 focus:ring-0 border-none"
                    type="text"
                    id="search"
                    placeholder="Cari Sesuatu..."
                    bind:value={searchQuery}
                />
            </div>
        </div>

        <!-- PO Quota Info Box (if applicable) -->
        {#if orderType === "preorder" && quotaProgress && quotaProgress.has_quota}
            <div class="px-4 pb-2">
                <div
                    class="bg-blue-50 rounded-xl p-3 border border-blue-100 flex flex-col gap-2"
                >
                    <div class="flex items-center justify-between">
                        <h3
                            class="font-bold text-gray-900 text-xs flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-users text-blue-500"></i>
                            Kuota PO Drop Point
                        </h3>
                        <span
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full {quotaProgress.is_fulfilled
                                ? 'text-green-600 bg-green-100'
                                : 'text-blue-600 bg-blue-100'}"
                        >
                            {quotaProgress.is_fulfilled
                                ? "TERPENUHI"
                                : "BELUM TERPENUHI"}
                        </span>
                    </div>

                    <div class="space-y-2">
                        {#if quotaProgress.min_qty}
                            <div>
                                <div
                                    class="flex justify-between text-[10px] mb-1"
                                >
                                    <span class="text-gray-600">Pesanan</span>
                                    <span class="font-medium text-gray-900"
                                        >{quotaProgress.current_qty} / {quotaProgress.min_qty}
                                        porsi</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-blue-100 rounded-full h-1"
                                >
                                    <div
                                        class="bg-blue-500 h-1 rounded-full transition-all"
                                        style="width: {Math.min(
                                            100,
                                            (quotaProgress.current_qty /
                                                quotaProgress.min_qty) *
                                                100,
                                        )}%"
                                    ></div>
                                </div>
                            </div>
                        {/if}

                        {#if quotaProgress.min_amount}
                            <div>
                                <div
                                    class="flex justify-between text-[10px] mb-1"
                                >
                                    <span class="text-gray-600">Transaksi</span>
                                    <span class="font-medium text-gray-900"
                                        >{formatRupiah(
                                            quotaProgress.current_amount,
                                        )} / {formatRupiah(
                                            quotaProgress.min_amount,
                                        )}</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-blue-100 rounded-full h-1"
                                >
                                    <div
                                        class="bg-blue-500 h-1 rounded-full transition-all"
                                        style="width: {Math.min(
                                            100,
                                            (quotaProgress.current_amount /
                                                quotaProgress.min_amount) *
                                                100,
                                        )}%"
                                    ></div>
                                </div>
                            </div>
                        {/if}
                    </div>

                    {#if !quotaProgress.is_fulfilled}
                        <div
                            class="mt-1 text-[10px] text-blue-800 bg-blue-100/50 p-2 rounded flex gap-1.5 items-start"
                        >
                            <i class="fa-solid fa-circle-info mt-0.5 shrink-0"
                            ></i>
                            <p class="leading-tight">
                                Mohon maaf pesanan PO per kolektif drop point
                                ini masih kurang. Ayoo, <b
                                    >ajak teman order bareng</b
                                > biar bisa diproses!
                            </p>
                        </div>
                    {/if}
                </div>
            </div>
        {/if}

        <!-- Categories Tab Bar -->
        <div
            bind:this={categoryTabsContainer}
            class="flex overflow-x-auto hide-scrollbar border-b border-gray-200 px-2 pb-0 bg-white"
        >
            {#each categories as category}
                <button
                    data-category-id={category.id}
                    class="whitespace-nowrap px-4 py-3 text-sm font-semibold border-b-2 transition-colors {selectedCategory ===
                    category.id
                        ? 'border-[#FFD700] text-gray-900'
                        : 'border-transparent text-gray-500'}"
                    on:click={() => scrollToCategory(category.id)}
                >
                    {category.name.toUpperCase()}
                </button>
            {/each}
        </div>
    </div>

    <!-- Products List -->
    <div class="p-4 space-y-6">
        {#each productsByCategory as category}
            <div id={`category-${category.id}`}>
                <h2
                    class="text-xs font-bold text-gray-900 mb-3 ml-1 tracking-wider"
                >
                    {category.name.toUpperCase()}
                </h2>

                <div class="grid grid-cols-2 gap-3">
                    {#each category.products as product}
                        <div
                            class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col transition-all {(cartQuantities[
                                product.id
                            ] || 0) > 0
                                ? 'border-b-4 border-b-[#FFD700]'
                                : ''}"
                        >
                            <div
                                class="aspect-square w-full bg-gray-100 relative overflow-hidden"
                            >
                                {#if product.image_url}
                                    <img
                                        src={product.image_url}
                                        alt={product.name}
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                {:else}
                                    <div
                                        class="w-full h-full flex items-center justify-center text-gray-400"
                                    >
                                        <i class="fa-solid fa-image text-3xl"
                                        ></i>
                                    </div>
                                {/if}
                            </div>

                            <div class="p-3 flex flex-col grow">
                                <h3
                                    class="font-semibold text-gray-900 text-xs leading-tight line-clamp-2 min-h-8"
                                >
                                    {product.name}
                                </h3>

                                <div
                                    class="mt-auto pt-2 flex items-center justify-between"
                                >
                                    <span
                                        class="font-bold text-gray-900 text-xs"
                                    >
                                        {formatRupiah(product.price)}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        {#if product.testimonials_count > 0}
                                            <div
                                                class="flex items-center gap-0.5 bg-yellow-50 px-1.5 py-0.5 rounded text-[10px] font-bold text-gray-900"
                                            >
                                                <i
                                                    class="fa-solid fa-star text-yellow-400"
                                                ></i>
                                                {product.average_rating.toFixed(
                                                    1,
                                                )}
                                            </div>
                                        {/if}
                                        {#if product.total_sales > 0}
                                            <span
                                                class="text-[10px] text-gray-400 font-medium"
                                                >Terjual {product.total_sales}</span
                                            >
                                        {/if}
                                    </div>
                                </div>

                                <div class="mt-2 h-7">
                                    {#if (cartQuantities[product.id] || 0) > 0}
                                        <div
                                            class="flex items-center justify-between w-full h-full px-1"
                                        >
                                            <button
                                                class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-900 text-gray-900 focus:outline-none transition-colors active:bg-gray-100"
                                                aria-label="Kurangi"
                                                on:click={() =>
                                                    updateCartItemQuantity(
                                                        product.id,
                                                        -1,
                                                    )}
                                            >
                                                <i
                                                    class="fa-solid fa-minus text-sm"
                                                ></i>
                                            </button>
                                            <input
                                                type="number"
                                                value={cartQuantities[
                                                    product.id
                                                ]}
                                                min="1"
                                                class="text-sm font-bold text-gray-900 w-12 text-center bg-transparent border-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                aria-label="Jumlah"
                                                on:input={(e) => {
                                                    // Allow typing, but don't update cart state immediately
                                                    // The actual update will happen on blur
                                                }}
                                                on:blur={(e) => {
                                                    const val = parseInt(
                                                        e.currentTarget.value,
                                                    );
                                                    if (
                                                        !isNaN(val) &&
                                                        val >= 1
                                                    ) {
                                                        updateCartItemQuantityWithValue(
                                                            product.id,
                                                            val,
                                                        );
                                                    } else {
                                                        // If invalid, revert to previous valid quantity (or 1 if none)
                                                        updateCartItemQuantityWithValue(
                                                            product.id,
                                                            1,
                                                        );
                                                    }
                                                    // Ensure the displayed value matches the cart state after blur
                                                    e.currentTarget.value =
                                                        cartQuantities[
                                                            product.id
                                                        ]?.toString() || "1";
                                                }}
                                            />
                                            <button
                                                class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-900 text-gray-900 focus:outline-none transition-colors active:bg-gray-100"
                                                aria-label="Tambah"
                                                on:click={() =>
                                                    updateCartItemQuantity(
                                                        product.id,
                                                        1,
                                                    )}
                                            >
                                                <i
                                                    class="fa-solid fa-plus text-sm"
                                                ></i>
                                            </button>
                                        </div>
                                    {:else}
                                        <button
                                            on:click={() =>
                                                handleTambahClick(product)}
                                            class="w-full h-full rounded border border-[#FFD700] text-[#997A00] font-semibold text-xs flex items-center justify-center hover:bg-[#FFF9E6] transition-colors"
                                        >
                                            Tambah
                                        </button>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        {/each}

        {#if productsByCategory.length === 0}
            <div class="py-12 text-center text-gray-500 text-sm">
                Produk tidak ditemukan.
            </div>
        {/if}
    </div>

    <!-- Floating Cart Bar -->
    {#if totalCartItems > 0}
        <div class="fixed bottom-0 left-0 right-0 p-4 z-40">
            <div
                class="bg-[#FFD700] rounded-xl shadow-lg p-3 flex items-center justify-between cursor-pointer hover:bg-[#FFC700] transition-colors"
                role="button"
                tabindex="0"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-[#FFD700]"
                    >
                        <i class="fa-solid fa-cart-shopping text-xl"></i>
                    </div>
                    <div>
                        <div class="text-white text-xs opacity-90">Total</div>
                        <div class="text-white font-bold text-sm">
                            {formatRupiah(totalCartPrice)}
                        </div>
                    </div>
                </div>

                <button
                    class="text-white font-bold text-sm bg-[#FFC700] px-4 py-2 rounded-lg flex items-center justify-center min-w-[120px] disabled:opacity-75 disabled:cursor-not-allowed"
                    disabled={isCheckoutLoading}
                    on:click|stopPropagation={() => {
                        if (chefCount > 1) {
                            showChefWarningModal = true;
                            return;
                        }

                        isCheckoutLoading = true;
                        router.post(
                            "/checkout/session",
                            {
                                cart,
                                dropPoint,
                                address,
                                redirect_to_checkout: !dropPoint && !address,
                            },
                            {
                                onFinish: () => {
                                    isCheckoutLoading = false;
                                },
                            },
                        );
                    }}
                >
                    {#if isCheckoutLoading}
                        <i class="fa-solid fa-circle-notch animate-spin mr-2"
                        ></i>
                        LOADING...
                    {:else}
                        CHECKOUT ({totalCartItems})
                    {/if}
                </button>
            </div>
        </div>
    {/if}
</div>

{#if showModal && selectedProduct}
    <ProductDetailModal
        product={selectedProduct}
        onClose={() => (showModal = false)}
        onAdd={addToCart}
    />
{/if}

{#if showChefWarningModal}
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
        class="fixed inset-0 bg-black/60 z-60 flex items-center justify-center p-4"
        on:click={() => (showChefWarningModal = false)}
    >
        <div
            class="bg-white rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl"
            on:click|stopPropagation
        >
            <div class="p-6 text-center">
                <div
                    class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 text-yellow-600"
                >
                    <i class="fa-solid fa-kitchen-set text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">
                    Dapur Berbeda-beda
                </h3>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    Produk yang kamu pilih berasal dari <strong
                        >{chefCount} dapur</strong
                    > yang berbeda. Hal ini akan menyebabkan biaya ongkir menjadi
                    berkali lipat sesuai jumlah dapur.
                </p>

                <div class="flex flex-col gap-3">
                    <button
                        class="w-full py-3 bg-[#FFD700] text-gray-900 font-bold rounded-xl active:scale-[0.98] transition-all"
                        on:click={() => {
                            showChefWarningModal = false;
                            isCheckoutLoading = true;
                            router.post(
                                "/checkout/session",
                                {
                                    cart,
                                    dropPoint,
                                    address,
                                },
                                {
                                    onFinish: () => {
                                        isCheckoutLoading = false;
                                    },
                                },
                            );
                        }}
                    >
                        Lanjutkan Checkout
                    </button>
                    <button
                        class="w-full py-3 bg-gray-100 text-gray-600 font-bold rounded-xl active:scale-[0.98] transition-all"
                        on:click={() => (showChefWarningModal = false)}
                    >
                        Kembali
                    </button>
                </div>
            </div>
        </div>
    </div>
{/if}

<style>
    /* Hide scrollbar for Chrome, Safari and Opera */
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    /* Hide scrollbar for IE, Edge and Firefox */
    .hide-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }
</style>
