<script lang="ts">
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
    }[];

    // State
    let searchQuery = "";
    let selectedCategory: string =
        categories.length > 0 && categories[0] ? categories[0].id : "";
    let selectedProduct: any = null;
    let showModal = false;

    // Cart State
    // format: { [cartItemId]: { product, quantity, options, notes, totalPrice } }
    // cartItemId is a unique string based on product id and selected options
    let cart: Record<string, any> = {};

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

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit("/"); // Or `/drop-points/${dropPoint.id}`
        }
    }

    function scrollToCategory(categoryId: string) {
        selectedCategory = categoryId;
        const element = document.getElementById(`category-${categoryId}`);
        if (element) {
            const yOffset = -120; // Adjust based on sticky header height
            const y =
                element.getBoundingClientRect().top +
                window.pageYOffset +
                yOffset;
            window.scrollTo({ top: y, behavior: "smooth" });
        }
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
</script>

<svelte:head>
    <title>Menu | {dropPoint.name}</title>
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
            <h1 class="ml-2 font-bold text-lg text-gray-900 truncate">
                {dropPoint.name}
            </h1>
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

        <!-- Categories Tab Bar -->
        <div
            class="flex overflow-x-auto hide-scrollbar border-b border-gray-200 px-2 pb-0 bg-white"
        >
            {#each categories as category}
                <button
                    class="whitespace-nowrap px-4 py-3 text-sm font-semibold border-b-2 transition-colors {selectedCategory ===
                    category.id
                        ? 'border-[#CCFF33] text-gray-900'
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
                            class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
                        >
                            <div class="aspect-4/3 w-full bg-gray-100 relative">
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
                                </div>

                                <div class="mt-2 h-7">
                                    {#if getQuantityInCart(product.id) > 0}
                                        <div
                                            class="flex items-center justify-between w-full h-full"
                                        >
                                            <button
                                                class="w-7 h-7 flex items-center justify-center rounded-full border border-gray-300 text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-200"
                                                aria-label="Kurangi"
                                                on:click={() =>
                                                    updateCartItemQuantity(
                                                        product.id,
                                                        -1,
                                                    )}
                                            >
                                                <i
                                                    class="fa-solid fa-minus text-xs"
                                                ></i>
                                            </button>
                                            <span
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                {getQuantityInCart(product.id)}
                                            </span>
                                            <button
                                                class="w-7 h-7 flex items-center justify-center rounded-full border border-gray-800 text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-200"
                                                aria-label="Tambah"
                                                on:click={() =>
                                                    updateCartItemQuantity(
                                                        product.id,
                                                        1,
                                                    )}
                                            >
                                                <i
                                                    class="fa-solid fa-plus text-xs"
                                                ></i>
                                            </button>
                                        </div>
                                    {:else}
                                        <button
                                            on:click={() =>
                                                handleTambahClick(product)}
                                            class="w-full h-full rounded border border-[#CCFF33] text-[#86ab16] font-semibold text-xs flex items-center justify-center hover:bg-[#f6ffde] transition-colors"
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
                class="bg-[#8ec210] rounded-xl shadow-lg p-3 flex items-center justify-between cursor-pointer hover:bg-[#7ea60f] transition-colors"
                role="button"
                tabindex="0"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-[#8ec210]"
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

                <div
                    class="text-white font-bold text-sm bg-[#78a20d] px-4 py-2 rounded-lg"
                >
                    CHECKOUT ({totalCartItems})
                </div>
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
