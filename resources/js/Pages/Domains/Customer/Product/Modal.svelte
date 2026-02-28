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
    };

    export let onClose: () => void;
    export let onAdd: (
        product: any,
        options: any,
        notes: string,
        quantity: number,
    ) => void;

    export let initialQuantity: number = 1;
    export let initialNotes: string = "";
    export let initialOptions: Record<string, string | string[]> = {};

    let quantity = initialQuantity;
    let notes = initialNotes;

    // selectedOptions structure: { [optionId]: selectedItemId | selectedItemIds[] }
    let selectedOptions: Record<string, string | string[]> = {
        ...initialOptions,
    };

    $: productOptions = (function () {
        if (!product || !product.options) return [];
        if (Array.isArray(product.options)) return product.options;
        const opts = product.options as any;
        if (opts.data && Array.isArray(opts.data)) return opts.data;
        return Object.values(product.options);
    })() as any[];

    // Initialize selectedOptions
    $: {
        for (const option of productOptions) {
            if (selectedOptions[option.id] === undefined) {
                selectedOptions[option.id] = option.is_multiple ? [] : "";
            }
        }
    }

    // Validate required options
    $: isSelectionValid = productOptions
        .filter((opt) => opt.is_required)
        .every((opt) => {
            const sel = selectedOptions[opt.id];
            if (opt.is_multiple) {
                return Array.isArray(sel) && sel.length > 0;
            }
            return !!sel;
        });

    $: currentTotalPrice = calculateTotalPrice(quantity, selectedOptions);

    function formatRupiah(amount: number) {
        return "Rp" + amount.toLocaleString("id-ID");
    }

    function calculateTotalPrice(
        qty: number,
        optionsSelection: Record<string, string | string[]>,
    ) {
        let price = product.price;
        for (const optionId in optionsSelection) {
            const selection = optionsSelection[optionId];
            if (!selection) continue;

            const option = productOptions.find((o) => o.id === optionId);
            if (option) {
                if (Array.isArray(selection)) {
                    for (const itemId of selection) {
                        const item = option.items.find(
                            (i: any) => i.id === itemId,
                        );
                        if (item && item.extra_price) {
                            price += item.extra_price;
                        }
                    }
                } else {
                    const item = option.items.find(
                        (i: any) => i.id === selection,
                    );
                    if (item && item.extra_price) {
                        price += item.extra_price;
                    }
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

<!-- svelte-ignore a11y_click_events_have_key_events -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
<div
    class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center sm:items-center"
    transition:fade={{ duration: 200 }}
    on:click={onClose}
>
    <!-- Modal Content -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
        class="bg-white w-full max-w-md max-h-svh sm:rounded-2xl rounded-t-2xl overflow-hidden flex flex-col relative"
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
                <div class="flex items-center justify-between mb-2">
                    <p class="font-bold text-gray-900 text-base">
                        {formatRupiah(product.price)}
                    </p>
                    <div class="flex items-center gap-2">
                        {#if product.testimonials_count > 0}
                            <div
                                class="flex items-center gap-1 bg-yellow-50 px-2 py-0.5 rounded-md text-xs font-bold text-gray-900"
                            >
                                <i class="fa-solid fa-star text-yellow-400"></i>
                                {product.average_rating.toFixed(1)}
                                <span
                                    class="text-[10px] text-gray-400 font-normal"
                                    >({product.testimonials_count})</span
                                >
                            </div>
                        {/if}
                        {#if product.total_sales > 0}
                            <span
                                class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-0.5 rounded-md"
                                >Terjual {product.total_sales}</span
                            >
                        {/if}
                    </div>
                </div>
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
                                {option?.name?.toUpperCase() || ""}
                            </h3>
                            {#if option?.is_required}
                                <span
                                    class="text-xs text-red-500 font-medium bg-red-50 px-2 py-0.5 rounded"
                                >
                                    {option.is_multiple ? "Minimal" : "Harus"} pilih
                                    1
                                </span>
                            {:else}
                                <span class="text-xs text-gray-400 font-medium"
                                    >Opsional (Bisa pilih {option.is_multiple
                                        ? "banyak"
                                        : "1"})</span
                                >
                            {/if}
                        </div>

                        <div class="space-y-3">
                            {#each option.items as item}
                                <label
                                    class="flex items-center justify-between cursor-pointer group"
                                >
                                    <div class="flex items-center gap-3">
                                        <!-- Custom Checkbox / Radio Button look -->
                                        <div
                                            class="relative flex items-center justify-center"
                                        >
                                            {#if option.is_multiple}
                                                <input
                                                    type="checkbox"
                                                    name={`option-${option.id}`}
                                                    value={item.id}
                                                    bind:group={
                                                        selectedOptions[
                                                            option.id
                                                        ]
                                                    }
                                                    class="peer opacity-0 absolute w-full h-full cursor-pointer z-10"
                                                />
                                                <div
                                                    class="w-5 h-5 border border-gray-300 rounded peer-checked:border-[#FFD700] peer-checked:bg-[#FFD700] flex items-center justify-center transition-colors"
                                                >
                                                    {#if Array.isArray(selectedOptions[option.id]) && (selectedOptions[option.id] as string[])?.includes(item.id)}
                                                        <i
                                                            class="fa-solid fa-check text-white text-[10px]"
                                                        ></i>
                                                    {/if}
                                                </div>
                                            {:else}
                                                <input
                                                    type="radio"
                                                    name={`option-${option.id}`}
                                                    value={item.id}
                                                    bind:group={
                                                        selectedOptions[
                                                            option.id
                                                        ]
                                                    }
                                                    class="peer opacity-0 absolute w-full h-full cursor-pointer z-10"
                                                />
                                                <div
                                                    class="w-5 h-5 border border-gray-300 rounded-full peer-checked:border-[#FFD700] peer-checked:bg-[#FFD700] flex items-center justify-center transition-colors"
                                                >
                                                    {#if selectedOptions[option.id] === item.id}
                                                        <i
                                                            class="fa-solid fa-check text-white text-[10px]"
                                                        ></i>
                                                    {/if}
                                                </div>
                                            {/if}
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

            <!-- Testimonials Section -->
            {#if product.testimonials && product.testimonials.length > 0}
                <div class="bg-white p-4 mb-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 text-sm italic">
                            Apa Kata Mereka?
                        </h3>
                        <div
                            class="flex items-center gap-1.5 bg-yellow-50 px-2 py-1 rounded-lg"
                        >
                            <i class="fa-solid fa-star text-yellow-400 text-xs"
                            ></i>
                            <span class="text-xs font-bold text-gray-900">
                                {(
                                    product.testimonials.reduce(
                                        (acc: number, t: { rating: string }) =>
                                            acc + parseInt(t.rating),
                                        0,
                                    ) / product.testimonials.length
                                ).toFixed(1)}
                            </span>
                            <span class="text-[10px] text-gray-400"
                                >({product.testimonials.length})</span
                            >
                        </div>
                    </div>

                    <div class="space-y-4">
                        {#each product.testimonials as testimonial}
                            <div
                                class="border-b border-gray-100 last:border-0 pb-4 last:pb-0"
                            >
                                <div
                                    class="flex items-start justify-between mb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 text-[10px] font-bold uppercase"
                                        >
                                            {testimonial.customer.name.charAt(
                                                0,
                                            )}
                                        </div>
                                        <div>
                                            <div
                                                class="text-[11px] font-bold text-gray-900"
                                            >
                                                {testimonial.customer.name}
                                            </div>
                                            <div
                                                class="flex items-center gap-0.5"
                                            >
                                                {#each Array(5) as _, i}
                                                    <i
                                                        class="fa-solid fa-star text-[8px] {i <
                                                        parseInt(
                                                            testimonial.rating,
                                                        )
                                                            ? 'text-yellow-400'
                                                            : 'text-gray-200'}"
                                                    ></i>
                                                {/each}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="text-[9px] text-gray-400 font-medium"
                                    >
                                        {new Date(
                                            testimonial.created_at,
                                        ).toLocaleDateString("id-ID", {
                                            day: "numeric",
                                            month: "short",
                                            year: "numeric",
                                        })}
                                    </div>
                                </div>
                                <p
                                    class="text-xs text-gray-600 leading-relaxed mb-2 italic"
                                >
                                    "{testimonial.content || "Puas banget!"}"
                                </p>
                                {#if testimonial.photo_url}
                                    <a
                                        href={testimonial.photo_url}
                                        target="_blank"
                                        class="block w-16 h-16 rounded overflow-hidden border border-gray-100 hover:opacity-90 transition-opacity"
                                    >
                                        <img
                                            src={testimonial.photo_url}
                                            alt="Review"
                                            class="w-full h-full object-cover"
                                        />
                                    </a>
                                {/if}
                            </div>
                        {/each}
                    </div>
                </div>
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
                    class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:ring-[#FFD700] focus:border-[#FFD700] transition-colors resize-none h-24 placeholder-gray-400"
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
                        class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 active:scale-95 transition-all active:bg-gray-100"
                        on:click={() => quantity > 1 && quantity--}
                        disabled={quantity <= 1}
                        class:opacity-50={quantity <= 1}
                        aria-label="Kurangi"
                    >
                        <i class="fa-solid fa-minus text-sm"></i>
                    </button>
                    <input
                        type="number"
                        bind:value={quantity}
                        min="1"
                        class="font-bold text-gray-900 w-12 text-center bg-transparent border-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                        aria-label="Jumlah"
                        on:input={(e) => {
                            const val = parseInt(e.currentTarget.value);
                            if (isNaN(val) || val < 1) {
                                quantity = 1;
                            } else {
                                quantity = val;
                            }
                        }}
                    />
                    <button
                        class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 active:scale-95 transition-all active:bg-gray-100"
                        on:click={() => quantity++}
                        aria-label="Tambah"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </button>
                </div>
            </div>

            <button
                class="w-full bg-[#FFD700] hover:bg-[#FFC700] text-[#111] font-bold py-3.5 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm"
                on:click={handleAdd}
                disabled={!isSelectionValid}
            >
                {initialQuantity > 0 ? "Update Pesanan" : "Tambah Pesanan"} - {formatRupiah(
                    currentTotalPrice,
                )}
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
