<script lang="ts">
    interface TabItem {
        id: string;
        label: string;
        badge?: number | string;
        badgeVariant?:
            | "primary"
            | "success"
            | "warning"
            | "danger"
            | "info"
            | "secondary"
            | "light"
            | "dark"
            | "purple";
        icon?: string;
        disabled?: boolean;
        content?: any;
    }

    type BadgeVariant =
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple";

    interface Props {
        tabs: TabItem[];
        activeTab?: string;
        variant?: "default" | "pills" | "underline" | "bordered";
        size?: "sm" | "normal" | "lg";
        fullWidth?: boolean;
        justified?: boolean;
        vertical?: boolean;
        class?: string;
        tabClass?: string;
        contentClass?: string;
        onTabChange?: (tabId: string) => void;
        children?: any;
    }

    let {
        tabs = [],
        activeTab = $bindable(tabs[0]?.id || ""),
        variant = "default",
        size = "normal",
        fullWidth = false,
        justified = false,
        vertical = false,
        class: className,
        tabClass,
        contentClass,
        onTabChange,
        children,
    }: Props = $props();

    /**
     * Get size-specific classes
     */
    function getSizeClasses(): string {
        const sizes = {
            sm: "text-sm px-3 py-2",
            normal: "text-sm px-4 py-2.5",
            lg: "text-base px-5 py-3",
        };
        return sizes[size] || sizes.normal;
    }

    /**
     * Get variant-specific classes for tab items
     */
    function getTabVariantClasses(
        isActive: boolean,
        isDisabled: boolean,
    ): string {
        if (isDisabled) {
            return "text-gray-400 cursor-not-allowed opacity-50 dark:text-gray-600";
        }

        const variants = {
            default: isActive
                ? "text-[#0060B2] bg-gray-100 border-b-2 border-[#0060B2] dark:text-[#0060B2] dark:bg-gray-800 dark:border-[#0060B2]"
                : "text-gray-600 hover:text-gray-800 hover:bg-gray-50 border-b-2 border-transparent hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 dark:hover:border-gray-600",
            pills: isActive
                ? "text-white bg-[#0060B2] dark:bg-[#0060B2]"
                : "text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800",
            underline: isActive
                ? "text-[#0060B2] border-b-2 border-[#0060B2] dark:text-[#0060B2] dark:border-[#0060B2]"
                : "text-gray-600 hover:text-gray-800 border-b-2 border-transparent hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600",
            bordered: isActive
                ? "text-[#0060B2] bg-white border border-gray-300 border-b-0 -mb-px z-10 dark:text-[#0060B2] dark:bg-gray-900 dark:border-gray-600"
                : "text-gray-600 hover:text-gray-800 bg-gray-50 border border-transparent hover:border-gray-200 dark:text-gray-400 dark:hover:text-gray-200 dark:bg-gray-800 dark:hover:border-gray-700",
        };

        return variants[variant] || variants.default;
    }

    /**
     * Get container variant classes
     */
    function getContainerVariantClasses(): string {
        const variants = {
            default: "border-b border-gray-200 dark:border-gray-700",
            pills: "",
            underline: "border-b border-gray-200 dark:border-gray-700",
            bordered: "border-b border-gray-300 dark:border-gray-600",
        };

        return variants[variant] || variants.default;
    }

    /**
     * Get badge variant classes
     */
    function getBadgeVariantClasses(
        badgeVariant: BadgeVariant = "primary",
        isActive: boolean,
    ): string {
        const variants: Record<BadgeVariant, string> = {
            primary: "text-white bg-[#0060B2] dark:bg-[#0060B2]",
            success: "text-white bg-green-600 dark:bg-green-600",
            warning: "text-white bg-orange-500 dark:bg-orange-500",
            danger: "text-white bg-red-600 dark:bg-red-600",
            info: "text-white bg-blue-500 dark:bg-blue-500",
            secondary:
                "text-gray-700 bg-gray-200 dark:text-gray-300 dark:bg-gray-700",
            light: "text-gray-800 bg-gray-100 dark:text-gray-800 dark:bg-gray-200",
            dark: "text-white bg-gray-800 dark:bg-gray-600",
            purple: "text-white bg-purple-600 dark:bg-purple-600",
        };

        // Special handling for active tab in pills variant
        if (variant === "pills" && isActive) {
            return "text-[#0060B2] bg-white dark:text-[#0060B2] dark:bg-white";
        }

        return variants[badgeVariant] || variants.primary;
    }

    /**
     * Get badge size classes
     */
    function getBadgeSizeClasses(): string {
        const sizes = {
            sm: "px-1.5 py-0.5 text-xs min-w-[18px]",
            normal: "px-2 py-0.5 text-xs min-w-[20px]",
            lg: "px-2.5 py-1 text-sm min-w-[22px]",
        };
        return sizes[size] || sizes.normal;
    }

    /**
     * Get icon size based on tab size
     */
    function getIconSize(): string {
        const iconSizes = {
            sm: "w-4 h-4",
            normal: "w-4 h-4",
            lg: "w-5 h-5",
        };
        return iconSizes[size] || iconSizes.normal;
    }

    /**
     * Get icon content
     */
    function getIconContent(icon: string): string {
        if (!icon) return "";

        // Check if icon is a FontAwesome class
        if (icon.startsWith("fa") || icon.includes("fa-")) {
            return `<i class="${icon}"></i>`;
        }

        // Check if icon contains SVG markup
        if (icon.includes("<svg")) {
            return icon;
        }

        // Treat as path data
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="${getIconSize()}">
            <path d="${icon}" />
        </svg>`;
    }

    /**
     * Handle tab click
     */
    function handleTabClick(tabId: string, disabled: boolean): void {
        if (disabled) return;

        activeTab = tabId;
        if (onTabChange) {
            onTabChange(tabId);
        }
    }

    /**
     * Handle keyboard navigation
     */
    function handleKeyDown(
        event: KeyboardEvent,
        index: number,
        disabled: boolean,
    ): void {
        if (disabled) return;

        const container = (event.currentTarget as HTMLElement | null)
            ?.parentElement;
        const tabElements = Array.from(
            container?.querySelectorAll('[role="tab"]:not([disabled])') ?? [],
        );
        let nextIndex = index;

        if (vertical) {
            if (event.key === "ArrowDown") {
                event.preventDefault();
                nextIndex = (index + 1) % tabElements.length;
            } else if (event.key === "ArrowUp") {
                event.preventDefault();
                nextIndex =
                    (index - 1 + tabElements.length) % tabElements.length;
            }
        } else {
            if (event.key === "ArrowRight") {
                event.preventDefault();
                nextIndex = (index + 1) % tabElements.length;
            } else if (event.key === "ArrowLeft") {
                event.preventDefault();
                nextIndex =
                    (index - 1 + tabElements.length) % tabElements.length;
            }
        }

        if (event.key === "Home") {
            event.preventDefault();
            nextIndex = 0;
        } else if (event.key === "End") {
            event.preventDefault();
            nextIndex = tabElements.length - 1;
        }

        if (nextIndex !== index) {
            (tabElements[nextIndex] as HTMLElement)?.focus();
            const enabledTabs = tabs.filter((t) => !t.disabled);
            const nextTabId = enabledTabs[nextIndex]?.id;
            if (nextTabId) {
                handleTabClick(nextTabId, false);
            }
        }
    }

    // Get active tab content
    const activeTabContent = $derived(tabs.find((tab) => tab.id === activeTab));
</script>

<div
    class="tab-container {vertical ? 'flex' : ''} {className || ''}"
    role="tablist"
    aria-orientation={vertical ? "vertical" : "horizontal"}
>
    <!-- Tab Navigation -->
    <div
        class="tab-nav
        {vertical
            ? 'flex flex-col border-r border-gray-200 dark:border-gray-700 pr-0'
            : `flex ${getContainerVariantClasses()} overflow-y-hidden overflow-x-auto flex-nowrap min-w-0`}
        {fullWidth || justified ? 'w-full' : ''}
        {justified && !vertical
            ? 'justify-between'
            : variant === 'pills'
              ? 'gap-1'
              : ''}
        {variant === 'pills'
            ? 'p-1 bg-gray-100 rounded-lg dark:bg-gray-800'
            : ''}"
    >
        {#each tabs as tab, index}
            {@const isActive = activeTab === tab.id}
            {@const isDisabled = tab.disabled || false}
            <button
                type="button"
                role="tab"
                aria-selected={isActive}
                aria-controls="tabpanel-{tab.id}"
                aria-disabled={isDisabled}
                tabindex={isActive ? 0 : -1}
                disabled={isDisabled}
                class="tab-item inline-flex items-center justify-center gap-2 font-medium transition-all duration-200 focus:outline-none shrink-0
                {getSizeClasses()}
                {getTabVariantClasses(isActive, isDisabled)}
                {variant === 'pills'
                    ? 'rounded-md'
                    : variant === 'bordered'
                      ? 'rounded-t-lg'
                      : ''}
                {fullWidth || justified ? 'flex-1' : ''}
                {isDisabled ? 'pointer-events-none' : 'cursor-pointer'}
                {tabClass || ''}"
                onclick={() => handleTabClick(tab.id, isDisabled)}
                onkeydown={(e) => handleKeyDown(e, index, isDisabled)}
            >
                <!-- Icon -->
                {#if tab.icon}
                    <span class="inline-flex items-center">
                        {@html getIconContent(tab.icon)}
                    </span>
                {/if}

                <!-- Label -->
                <span>{tab.label}</span>

                <!-- Badge -->
                {#if tab.badge !== undefined && tab.badge !== null && tab.badge !== ""}
                    <span
                        class="inline-flex items-center justify-center font-semibold rounded-full
                        {getBadgeSizeClasses()}
                        {getBadgeVariantClasses(tab.badgeVariant, isActive)}"
                    >
                        {tab.badge}
                    </span>
                {/if}
            </button>
        {/each}
    </div>

    <!-- Tab Content -->
    {#if activeTabContent}
        <div
            id="tabpanel-{activeTabContent.id}"
            role="tabpanel"
            aria-labelledby="tab-{activeTabContent.id}"
            class="tab-content {vertical
                ? 'flex-1 pl-4'
                : 'mt-4'} {contentClass || ''}"
        >
            {#if activeTabContent.content}
                {@render activeTabContent.content()}
            {:else if children}
                {@render children()}
            {/if}
        </div>
    {/if}
</div>

<style>
    /* Smooth transitions */
    .tab-item {
        position: relative;
        white-space: nowrap;
    }

    /* Active indicator animation for underline variant */
    .tab-item::after {
        content: "";
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: transparent;
        transition: all 0.3s ease;
    }

    /* Hover effects */
    .tab-item:not([disabled]):hover {
        transform: translateY(-1px);
    }

    .tab-item:not([disabled]):active {
        transform: translateY(0);
    }

    /* Focus styles */
    .tab-item:focus-visible {
        z-index: 10;
    }

    /* Vertical tab specific styles */
    .tab-container[role="tablist"][aria-orientation="vertical"] .tab-nav {
        min-width: 200px;
    }

    .tab-container[role="tablist"][aria-orientation="vertical"] .tab-item {
        justify-content: flex-start;
        text-align: left;
    }
</style>
