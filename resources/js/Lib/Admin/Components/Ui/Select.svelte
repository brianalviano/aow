<script lang="ts">
    import Fuse from "fuse.js";
    import { onMount } from "svelte";

    interface Option {
        value: string | number;
        label: string;
        disabled?: boolean;
    }

    interface Props {
        id?: string;
        name?: string;
        label?: string;
        value: string | number;
        options: Option[];
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        searchable?: boolean;
        searchPlaceholder?: string;
        icon?: string;
        customIcon?: boolean;
        error?: string;
        minimal?: boolean;
        onchange?: (value: string | number) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
    }

    let {
        id,
        name,
        label,
        value = $bindable(),
        options = [],
        placeholder = "Pilih opsi...",
        required = false,
        disabled = false,
        searchable = true,
        searchPlaceholder = "Cari...",
        icon,
        customIcon = false,
        minimal = false,
        onchange,
        onfocus,
        onblur,
        error,
    }: Props = $props();

    let isOpen = $state(false);
    let searchQuery = $state("");
    let filteredOptions = $state<Option[]>([]);
    let highlightedIndex = $state(-1);
    let selectElement: HTMLDivElement;
    let searchInput: HTMLInputElement | undefined = $state();
    let fuse: Fuse<Option>;
    let dropdownStyles = $state("");
    let dropdownElement: HTMLDivElement | undefined = $state();
    let optionsContainerStyles = $state("");
    let headerElement: HTMLDivElement | undefined = $state();
    let lastMaxHeight = $state(0);
    function portal(node: HTMLElement) {
        const target = document.body;
        target.appendChild(node);
        return {
            destroy() {
                if (node.parentNode === target) {
                    target.removeChild(node);
                }
            },
        };
    }

    /**
     * Initialize Fuse.js for search functionality
     */
    function initializeFuse() {
        if (!fuse || options.length === 0) {
            fuse = new Fuse(options, {
                keys: ["label"],
                threshold: 0.3,
                includeScore: true,
                shouldSort: true,
            });
        } else {
            fuse.setCollection(options);
        }
        updateFilteredOptions();
    }

    /**
     * Update filtered options based on search query
     */
    function updateFilteredOptions() {
        let newFilteredOptions;
        if (!searchQuery.trim()) {
            newFilteredOptions = options.filter((option) => !option.disabled);
        } else {
            const results = fuse.search(searchQuery);
            newFilteredOptions = results
                .map((result) => result.item)
                .filter((option) => !option.disabled);
        }

        const hasChanged =
            filteredOptions.length !== newFilteredOptions.length ||
            (newFilteredOptions.length > 0 &&
                filteredOptions.length > 0 &&
                newFilteredOptions[0]?.value !== filteredOptions[0]?.value);

        if (hasChanged) {
            filteredOptions = newFilteredOptions;
            highlightedIndex = filteredOptions.length > 0 ? 0 : -1;
        }
    }

    /**
     * Get the display label for the selected value
     */
    function getSelectedLabel(): string {
        const selectedOption = options.find((option) => option.value === value);
        return selectedOption ? selectedOption.label : placeholder;
    }

    /**
     * Handle option selection
     */
    function selectOption(option: Option) {
        if (option.disabled) return;

        value = option.value;
        isOpen = false;
        searchQuery = "";
        updateFilteredOptions();

        if (onchange) {
            onchange(option.value);
        }
    }

    /**
     * Toggle dropdown open/close
     */
    function toggleDropdown() {
        if (disabled) return;

        isOpen = !isOpen;
        if (isOpen) {
            searchQuery = "";
            updateFilteredOptions();
            updateDropdownPosition();
            updateOptionsMaxHeight();

            // Focus search input if searchable
            if (searchable) {
                setTimeout(() => {
                    searchInput?.focus();
                }, 10);
            }
        }
    }

    /**
     * Update dropdown position using fixed portal-style placement
     */
    function updateDropdownPosition() {
        if (!selectElement) return;
        const rect = selectElement.getBoundingClientRect();
        const spacing = 6;
        const viewportHeight = window.innerHeight;
        const availableBelow = Math.max(
            0,
            viewportHeight - rect.bottom - spacing,
        );

        const desiredMax = 320;
        const minHeight = 80;
        const maxHeight = Math.max(
            minHeight,
            Math.min(desiredMax, availableBelow),
        );
        const top = Math.min(
            viewportHeight - maxHeight - spacing,
            rect.bottom + spacing,
        );

        const left = rect.left;
        const width = rect.width;

        lastMaxHeight = maxHeight;
        dropdownStyles =
            `position: fixed; left: ${Math.round(left)}px; top: ${Math.round(top)}px; ` +
            `width: ${Math.round(width)}px; max-height: ${Math.round(maxHeight)}px;`;
        updateOptionsMaxHeight();
    }

    function handleViewportChange() {
        if (isOpen) {
            updateDropdownPosition();
        }
    }

    function updateOptionsMaxHeight() {
        const headerHeight =
            headerElement?.offsetHeight ?? (searchable ? 48 : 0);
        const max = Math.max(100, lastMaxHeight - headerHeight);
        optionsContainerStyles = `max-height: ${Math.round(max)}px;`;
    }

    /**
     * Handle keyboard navigation
     */
    function handleKeydown(event: KeyboardEvent) {
        if (disabled) return;

        switch (event.key) {
            case "Enter":
                event.preventDefault();
                if (
                    isOpen &&
                    highlightedIndex >= 0 &&
                    filteredOptions[highlightedIndex]
                ) {
                    const selectedOption = filteredOptions[highlightedIndex];
                    if (selectedOption) {
                        selectOption(selectedOption);
                    }
                } else if (!isOpen) {
                    toggleDropdown();
                }
                break;
            case "Escape":
                if (isOpen) {
                    event.preventDefault();
                    isOpen = false;
                    searchQuery = "";
                    updateFilteredOptions();
                }
                break;
            case "ArrowDown":
                event.preventDefault();
                if (!isOpen) {
                    toggleDropdown();
                } else {
                    highlightedIndex = Math.min(
                        highlightedIndex + 1,
                        filteredOptions.length - 1,
                    );
                }
                break;
            case "ArrowUp":
                event.preventDefault();
                if (isOpen) {
                    highlightedIndex = Math.max(highlightedIndex - 1, 0);
                }
                break;
        }
    }

    /**
     * Handle search input
     */
    function handleSearchInput(event: Event) {
        const target = event.target as HTMLInputElement;
        searchQuery = target.value;
        updateFilteredOptions();
    }

    /**
     * Handle focus events
     */
    function handleFocus(event: FocusEvent) {
        if (onfocus) {
            onfocus(event);
        }
    }

    /**
     * Handle blur events
     */
    function handleBlur(event: FocusEvent) {
        setTimeout(() => {
            const active = document.activeElement;
            const insideSelect =
                selectElement?.contains(active) ||
                dropdownElement?.contains(active);
            if (!insideSelect) {
                isOpen = false;
                searchQuery = "";
                updateFilteredOptions();
                if (onblur) {
                    onblur(event);
                }
            }
        }, 150);
    }

    /**
     * Handle clicks outside the component
     */
    function handleClickOutside(event: MouseEvent) {
        const target = event.target as Node;
        const clickedInsideSelect = selectElement?.contains(target);
        const clickedInsideDropdown = dropdownElement?.contains(target);
        if (!clickedInsideSelect && !clickedInsideDropdown) {
            isOpen = false;
            searchQuery = "";
            updateFilteredOptions();
        }
    }

    // Initialize component
    onMount(() => {
        initializeFuse();
        document.addEventListener("click", handleClickOutside);
        document.addEventListener("scroll", handleViewportChange, true);
        window.addEventListener("resize", handleViewportChange);

        return () => {
            document.removeEventListener("click", handleClickOutside);
            document.removeEventListener("scroll", handleViewportChange, true);
            window.removeEventListener("resize", handleViewportChange);
        };
    });

    // Watch for options changes
    let previousOptionsHash = $state("");
    $effect(() => {
        const currentOptionsHash = JSON.stringify(
            options.map((o) => ({ value: o.value, label: o.label })),
        );
        if (currentOptionsHash !== previousOptionsHash) {
            previousOptionsHash = currentOptionsHash;
            if (options.length > 0) {
                initializeFuse();
            }
        }
    });
</script>

<svelte:window
    on:keydown={handleKeydown}
    on:scroll={handleViewportChange}
    on:resize={handleViewportChange}
/>

<div class="space-y-2">
    {#if label}
        <label
            for={id}
            class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
        >
            {label}
            {#if required}
                <span class="ml-1 text-red-500">*</span>
            {/if}
        </label>
    {/if}

    <div class="relative select-container" bind:this={selectElement}>
        {#if icon && !minimal}
            <div
                class="flex absolute inset-y-0 left-0 z-10 items-center pl-3 pointer-events-none"
            >
                {#if customIcon}
                    {@html icon}
                {:else}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="w-5 h-5 text-gray-400 dark:text-gray-500"
                    >
                        <path d={icon} />
                    </svg>
                {/if}
            </div>
        {/if}

        <!-- Select trigger -->
        <button
            type="button"
            {id}
            {disabled}
            class="{minimal
                ? 'inline-flex items-center gap-1.5 text-sm text-gray-900 dark:text-white hover:text-[#0060B2] dark:hover:text-[#0060B2] transition-colors duration-200'
                : 'block w-full ' +
                  (icon ? 'pl-10' : 'pl-3') +
                  ' pr-10 py-2.5 border border-gray-200 dark:border-[#212121] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0060B2] focus:border-transparent bg-white dark:bg-[#0a0a0a] backdrop-blur-sm text-gray-900 dark:text-white transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 text-left text-sm'} {disabled
                ? 'opacity-50 cursor-not-allowed'
                : 'cursor-pointer'} {error && !minimal
                ? 'border-red-500 focus:ring-red-500'
                : ''}"
            onclick={toggleDropdown}
            onfocus={handleFocus}
            onblur={handleBlur}
            aria-haspopup="listbox"
            aria-expanded={isOpen}
            aria-describedby={error && id ? `${id}-error` : undefined}
        >
            <span
                class="{minimal
                    ? 'flex-1 min-w-0'
                    : 'block w-full'} truncate {value
                    ? 'text-gray-900 dark:text-white'
                    : 'text-gray-500 dark:text-gray-400'}"
            >
                {getSelectedLabel()}
            </span>
            {#if minimal}
                <svg
                    class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform duration-200 {isOpen
                        ? 'rotate-180'
                        : ''}"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            {/if}
        </button>

        <!-- Dropdown arrow -->
        {#if !minimal}
            <div
                class="flex absolute inset-y-0 right-0 items-center pr-3 pointer-events-none"
            >
                <svg
                    class="h-5 w-5 text-gray-400 dark:text-gray-500 transition-transform duration-200 {isOpen
                        ? 'rotate-180'
                        : ''}"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </div>
        {/if}

        <!-- Dropdown menu via action portal to escape clipping -->
        {#if isOpen}
            <div
                bind:this={dropdownElement}
                use:portal
                class="select-dropdown z-99999 bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#212121] rounded-lg shadow-lg"
                style={dropdownStyles}
            >
                {#if searchable}
                    <div
                        bind:this={headerElement}
                        class="p-2 border-b border-gray-100 dark:border-[#212121]"
                    >
                        <div class="relative">
                            <div
                                class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none"
                            >
                                <svg
                                    class="w-4 h-4 text-gray-400 dark:text-gray-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                bind:this={searchInput}
                                type="text"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-[#212121] rounded-md focus:outline-none focus:ring-2 focus:ring-[#0060B2] focus:border-transparent text-sm bg-white dark:bg-[#0a0a0a] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder:text-gray-500"
                                placeholder={searchPlaceholder}
                                bind:value={searchQuery}
                                oninput={handleSearchInput}
                                autocomplete="off"
                            />
                        </div>
                    </div>
                {/if}
                <div
                    class="overflow-y-auto overflow-x-hidden"
                    style={optionsContainerStyles}
                >
                    {#if filteredOptions.length > 0}
                        {#each filteredOptions as option, index}
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left text-sm hover:bg-[#0060B2]/10 focus:bg-[#0060B2]/10 focus:outline-none transition-colors duration-150 whitespace-nowrap {index ===
                                highlightedIndex
                                    ? 'bg-[#0060B2]/10'
                                    : ''} {option.value === value
                                    ? 'bg-[#0060B2]/20 text-[#0060B2] font-medium'
                                    : 'text-gray-900 dark:text-gray-300'} {option.disabled
                                    ? 'opacity-50 cursor-not-allowed'
                                    : 'cursor-pointer'}"
                                onclick={() => selectOption(option)}
                                disabled={option.disabled}
                                role="option"
                                aria-selected={option.value === value}
                            >
                                <span class="flex justify-between items-center">
                                    <span class="truncate min-w-0"
                                        >{option.label}</span
                                    >
                                    {#if option.value === value}
                                        <svg
                                            class="w-4 h-4 text-[#0060B2] shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    {/if}
                                </span>
                            </button>
                        {/each}
                    {:else}
                        <div
                            class="px-3 py-2 text-sm text-center text-gray-500 dark:text-gray-400"
                        >
                            {searchQuery
                                ? "Tidak ada hasil ditemukan"
                                : "Tidak ada opsi tersedia"}
                        </div>
                    {/if}
                </div>
            </div>
        {/if}

        <!-- Hidden input for form submission -->
        {#if name}
            <input type="hidden" {name} {value} />
        {/if}
    </div>
    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}
</div>

<style>
    /* Container untuk select yang akan memiliki stacking context baru */
    .select-container {
        position: relative;
    }

    /* Dropdown dengan z-index sangat tinggi untuk memastikan tampil di atas modal */
    .select-dropdown {
        position: fixed !important;
        z-index: 999999 !important;
        max-width: none;
    }

    /* Animation */
    .select-dropdown {
        animation: slideDownFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        transform-origin: top;
    }

    @keyframes slideDownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Custom scrollbar */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
