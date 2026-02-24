<script lang="ts">
    import { onMount } from "svelte";
    import { fade, slide } from "svelte/transition";

    export let product: {
        id: string;
        name: string;
        description: string;
        price: number;
        image_url: string | null;
        options: {
            id: string;
            name: string;
            is_required: boolean;
            items: {
                id: string;
                name: string;
                extra_price: number;
            }[];
        }[];
    };

    export let onClose: () => void;
    export let onAdd: (
        product: any,
        options: any,
        notes: string,
        quantity: number,
    ) => void;

    let quantity = 1;
    let notes = "";

    // selectedOptions structure: { [optionId]: selectedItemId | selectedItemIds[] }
    // Currently only supporting single select (radio buttons) based on Figma
    let selectedOptions: Record<string, string> = {};

    $: productOptions = Array.isArray(product?.options) ? product.options : [];

    // Validate required options
    $: isSelectionValid = productOptions
        .filter((opt) => opt.is_required)
        .every((opt) => selectedOptions[opt.id]);

    $: currentTotalPrice = calculateTotalPrice(quantity, selectedOptions);

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function calculateTotalPrice(
        qty: number,
        optionsSelection: Record<string, string>,
    ) {
        let price = product.price;
        for (const optionId in optionsSelection) {
            const itemId = optionsSelection[optionId];
            if (!itemId) continue;

            const option = productOptions.find((o) => o.id === optionId);
            if (option) {
                const item = option.items.find((i) => i.id === itemId);
                if (item && item.extra_price) {
                    price += item.extra_price;
                }
            }
        }
        return price * qty;
    }

    function handleAdd() {
        if (!isSelectionValid) {
            alert("Mohon pilih semua opsi yang diwajibkan.");
            return;
        }
        onAdd(product, selectedOptions, notes, quantity);
    }

    // Prevent scrolling behind modal
    onMount(() => {
        document.body.style.overflow = "hidden";
        return () => {
            document.body.style.overflow = "";
        };
    });
</script>

<!-- Backdrop -->
<!-- svelte-ignore a11y-click-events-have-key-events -->
<!-- svelte-ignore a11y-no-static-element-interactions -->
<div
    class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center sm:items-center"
    transition:fade={{ duration: 200 }}
    on:click={onClose}
>
    <!-- Modal Content -->
    <div
        class="bg-white w-full max-w-md max-h-[98vh] sm:rounded-2xl rounded-t-2xl overflow-hidden flex flex-col relative"
        on:click|stopPropagation
        transition:slide={{ duration: 300, axis: "y" }}
    >
        <!-- Close Button & Expanding Image -->
        <div class="relative w-full aspect-video bg-gray-100 shrink-0">
            {#if product.image_url}
                <img
                    src={product.image_url}
                    alt={product.name}
                    class="w-full h-full object-cover"
                />
            {:else}
                <div
                    class="w-full h-full flex items-center justify-center text-gray-400"
                >
                    <i class="fa-solid fa-image text-5xl"></i>
                </div>
            {/if}

            <!-- Floating Buttons -->
            <div class="absolute top-4 right-4 flex flex-col gap-3">
                <button
                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md text-gray-800 font-bold active:scale-95 transition-transform"
                    on:click={onClose}
                    aria-label="Tutup"
                >
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="absolute bottom-4 right-4">
                <button
                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md text-gray-800 font-bold active:scale-95 transition-transform"
                    aria-label="Buka penuh"
                >
                    <i class="fa-solid fa-expand text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable details area -->
        <div class="overflow-y-auto flex-1 bg-gray-50 hide-scrollbar pb-32">
            <!-- Header section -->
            <div class="bg-white p-4 mb-2">
                <h1
                    class="text-lg font-bold text-gray-900 leading-tight mb-1 uppercase"
                >
                    {product.name}
                </h1>
                <p class="font-bold text-gray-900 text-base mb-2">
                    {formatRupiah(product.price)}
                </p>
                {#if product.description}
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {product.description}
                    </p>
                {/if}
            </div>

            <!-- Options sections -->
            {#if productOptions.length > 0}
                {#each productOptions as option}
                    <div class="bg-white p-4 mb-2">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-900 text-sm">
                                {option.name.toUpperCase()}
                            </h3>
                            {#if option.is_required}
                                <span
                                    class="text-xs text-red-500 font-medium bg-red-50 px-2 py-0.5 rounded"
                                    >Harus pilih 1</span
                                >
                            {:else}
                                <span class="text-xs text-gray-400 font-medium"
                                    >Opsional</span
                                >
                            {/if}
                        </div>

                        <div class="space-y-3">
                            {#each option.items as item}
                                <label
                                    class="flex items-center justify-between cursor-pointer group"
                                >
                                    <div class="flex items-center gap-3">
                                        <!-- Custom Radio Button look -->
                                        <div
                                            class="relative flex items-center justify-center"
                                        >
                                            <input
                                                type="radio"
                                                name={`option-${option.id}`}
                                                value={item.id}
                                                bind:group={
                                                    selectedOptions[option.id]
                                                }
                                                class="peer opacity-0 absolute w-full h-full cursor-pointer z-10"
                                            />
                                            <div
                                                class="w-5 h-5 border border-gray-300 rounded peer-checked:border-[#CCFF33] peer-checked:bg-[#CCFF33] flex items-center justify-center transition-colors"
                                            >
                                                {#if selectedOptions[option.id] === item.id}
                                                    <i
                                                        class="fa-solid fa-check text-white text-[10px]"
                                                    ></i>
                                                {/if}
                                            </div>
                                        </div>
                                        <span
                                            class="text-sm font-medium text-gray-700 uppercase"
                                            >{item.name}</span
                                        >
                                    </div>
                                    {#if item.extra_price > 0}
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >+{formatRupiah(
                                                item.extra_price,
                                            )}</span
                                        >
                                    {/if}
                                </label>
                            {/each}
                        </div>
                    </div>
                {/each}
            {/if}

            <!-- Notes Section -->
            <div class="bg-white p-4">
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="font-bold text-gray-900 text-sm">Catatan</h3>
                    <span class="text-sm text-gray-400">(opsional)</span>
                </div>
                <textarea
                    bind:value={notes}
                    placeholder="Contoh: jangan pedas, banyakin kecap..."
                    class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:ring-[#CCFF33] focus:border-[#CCFF33] transition-colors resize-none h-24 placeholder-gray-400"
                ></textarea>
            </div>
        </div>

        <!-- Sticky Bottom Bar -->
        <div
            class="absolute bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 rounded-t-2xl shadow-[0_-4px_15px_rgba(0,0,0,0.05)] z-20"
        >
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-900"
                    >Jumlah Pesanan</span
                >
                <div class="flex items-center gap-4">
                    <button
                        class="w-8 h-8 rounded-full border border-gray-800 flex items-center justify-center text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 active:scale-95 transition-transform"
                        on:click={() => quantity > 1 && quantity--}
                        disabled={quantity <= 1}
                        class:opacity-50={quantity <= 1}
                        aria-label="Kurangi"
                    >
                        <i class="fa-solid fa-minus text-sm"></i>
                    </button>
                    <span class="font-bold text-gray-900 w-4 text-center"
                        >{quantity}</span
                    >
                    <button
                        class="w-8 h-8 rounded-full border border-gray-800 flex items-center justify-center text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 active:scale-95 transition-transform"
                        on:click={() => quantity++}
                        aria-label="Tambah"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </button>
                </div>
            </div>

            <button
                class="w-full bg-[#CCFF33] hover:bg-[#bdf33c] text-[#111] font-bold py-3.5 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm"
                on:click={handleAdd}
                disabled={!isSelectionValid}
            >
                Tambah Pesanan - {formatRupiah(currentTotalPrice)}
            </button>
        </div>
    </div>
</div>

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
