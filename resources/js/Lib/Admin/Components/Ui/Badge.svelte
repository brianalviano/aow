<script lang="ts">
    interface Props {
        variant?:
            | "primary"
            | "success"
            | "warning"
            | "danger"
            | "info"
            | "secondary"
            | "light"
            | "dark"
            | "purple"
            | "white";
        size?: "xs" | "sm" | "normal" | "lg";
        rounded?: "default" | "pill" | "square";
        outlined?: boolean;
        removable?: boolean;
        dot?: boolean;
        dotPosition?: "left" | "right";
        icon?: string;
        iconPosition?: "left" | "right";
        customIcon?: boolean;
        pulse?: boolean; // Animated pulse effect for dot
        class?: string;
        onclick?: (event: MouseEvent) => void;
        onremove?: (event: MouseEvent) => void;
        children?: any;
        title?: string;
    }

    let {
        variant = "primary",
        size = "normal",
        rounded = "default",
        outlined = false,
        removable = false,
        dot = false,
        dotPosition = "left",
        icon,
        iconPosition = "left",
        customIcon = false,
        pulse = false,
        class: className,
        onclick,
        onremove,
        children,
        title,
    }: Props = $props();

    /**
     * Get variant-specific classes
     */
    function getVariantClasses(): string {
        if (outlined) {
            const outlineVariants = {
                primary:
                    "text-[#0060B2] bg-[#0060B2]/10 border border-[#0060B2] dark:text-[#0060B2] dark:bg-[#0060B2]/20 dark:border-[#0060B2]",
                success:
                    "text-green-700 bg-green-50 border border-green-600 dark:text-green-400 dark:bg-green-900/30 dark:border-green-500",
                warning:
                    "text-orange-700 bg-orange-50 border border-orange-500 dark:text-orange-400 dark:bg-orange-900/30 dark:border-orange-500",
                danger: "text-red-700 bg-red-50 border border-red-600 dark:text-red-400 dark:bg-red-900/30 dark:border-red-500",
                info: "text-blue-700 bg-blue-50 border border-blue-500 dark:text-blue-400 dark:bg-blue-900/30 dark:border-blue-500",
                secondary:
                    "text-gray-700 bg-gray-50 border border-gray-400 dark:text-gray-300 dark:bg-gray-800/50 dark:border-gray-600",
                light: "text-gray-800 bg-white border border-gray-300 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600",
                dark: "text-gray-800 bg-gray-100 border border-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-500",
                purple: "text-purple-700 bg-purple-50 border border-purple-600 dark:text-purple-400 dark:bg-purple-900/30 dark:border-purple-500",
                white: "text-gray-800 bg-white border border-gray-300 dark:text-gray-800 dark:bg-white dark:border-gray-300",
            };
            return outlineVariants[variant] || outlineVariants.primary;
        }

        const solidVariants = {
            primary: "text-white bg-[#0060B2] dark:bg-[#0060B2]",
            success: "text-white bg-green-600 dark:bg-green-600",
            warning: "text-white bg-orange-500 dark:bg-orange-500",
            danger: "text-white bg-red-600 dark:bg-red-600",
            info: "text-white bg-blue-500 dark:bg-blue-500",
            purple: "text-white bg-purple-600 dark:bg-purple-600",
            secondary:
                "text-gray-700 bg-gray-200 dark:text-gray-300 dark:bg-gray-700",
            light: "text-gray-800 bg-gray-100 dark:text-gray-800 dark:bg-gray-200",
            dark: "text-white bg-gray-800 dark:bg-gray-600",
            white: "text-gray-800 bg-white dark:text-gray-800 dark:bg-white",
        };

        return solidVariants[variant] || solidVariants.primary;
    }

    /**
     * Get size-specific classes
     */
    function getSizeClasses(): string {
        const sizes = {
            xs: "px-1.5 py-0.5 text-xs",
            sm: "px-2 py-0.5 text-xs",
            normal: "px-2.5 py-1 text-sm",
            lg: "px-3 py-1.5 text-base",
        };

        return sizes[size] || sizes.normal;
    }

    /**
     * Get rounded classes
     */
    function getRoundedClasses(): string {
        const roundedStyles = {
            default: "rounded",
            pill: "rounded-full",
            square: "rounded-none",
        };

        return roundedStyles[rounded] || roundedStyles.default;
    }

    /**
     * Get icon size based on badge size
     */
    function getIconSize(): string {
        const iconSizes = {
            xs: "w-2.5 h-2.5",
            sm: "w-3 h-3",
            normal: "w-3.5 h-3.5",
            lg: "w-4 h-4",
        };

        return iconSizes[size] || iconSizes.normal;
    }

    /**
     * Get dot size based on badge size
     */
    function getDotSize(): string {
        const dotSizes = {
            xs: "w-1.5 h-1.5",
            sm: "w-2 h-2",
            normal: "w-2 h-2",
            lg: "w-2.5 h-2.5",
        };

        return dotSizes[size] || dotSizes.normal;
    }

    /**
     * Get dot color based on variant
     */
    function getDotColor(): string {
        if (outlined) {
            const outlineDotColors = {
                primary: "bg-[#0060B2]",
                success: "bg-green-600",
                warning: "bg-orange-500",
                danger: "bg-red-600",
                info: "bg-blue-500",
                purple: "bg-purple-600",
                secondary: "bg-gray-500",
                light: "bg-gray-400",
                dark: "bg-gray-700",
                white: "bg-gray-600",
            };
            return outlineDotColors[variant] || outlineDotColors.primary;
        }

        const dotColors = {
            primary: "bg-white",
            success: "bg-white",
            warning: "bg-white",
            danger: "bg-white",
            info: "bg-white",
            purple: "bg-white",
            secondary: "bg-gray-500 dark:bg-gray-300",
            light: "bg-gray-600",
            dark: "bg-white",
            white: "bg-gray-600",
        };

        return dotColors[variant] || dotColors.primary;
    }

    /**
     * Get icon content
     */
    function getIconContent(): string {
        if (!icon) return "";

        if (customIcon) {
            return icon;
        }

        // Check if icon is a FontAwesome class
        if (icon.startsWith("fa") || icon.includes("fa-")) {
            return `<i class="${icon} ${getIconSize().replace("w-", "").replace("h-", "").replace(" ", "")}"></i>`;
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
     * Get spacing for elements
     */
    function getSpacing(): string {
        const spacing = {
            xs: "gap-1",
            sm: "gap-1",
            normal: "gap-1.5",
            lg: "gap-2",
        };

        return spacing[size] || spacing.normal;
    }

    /**
     * Handle remove button click or keyboard event
     */
    function handleRemove(event: MouseEvent | KeyboardEvent): void {
        event.stopPropagation();
        if (onremove && event instanceof MouseEvent) {
            onremove(event);
        } else if (onremove && event instanceof KeyboardEvent) {
            // Convert KeyboardEvent to MouseEvent-like object for compatibility
            const mouseEvent = new MouseEvent("click", {
                bubbles: true,
                cancelable: true,
                view: window,
            });
            onremove(mouseEvent);
        }
    }

    /**
     * Handle badge click
     */
    function handleClick(event: MouseEvent): void {
        if (onclick) {
            onclick(event);
        }
    }
</script>

{#if onclick}
    <button
        type="button"
        {title}
        class="inline-flex items-center font-medium whitespace-nowrap transition-all duration-200 cursor-pointer hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-current
        {getVariantClasses()} 
        {getSizeClasses()} 
        {getRoundedClasses()} 
        {getSpacing()}
        {className || ''}"
        onclick={handleClick}
    >
        <!-- Dot indicator (left) -->
        {#if dot && dotPosition === "left"}
            <span
                class="inline-block rounded-full {getDotSize()} {getDotColor()} 
                {pulse ? 'animate-pulse' : ''}"
            ></span>
        {/if}

        <!-- Icon (left) -->
        {#if icon && iconPosition === "left"}
            <span class="inline-flex items-center">
                {@html getIconContent()}
            </span>
        {/if}

        <!-- Content -->
        {#if children}
            {@render children()}
        {/if}

        <!-- Icon (right) -->
        {#if icon && iconPosition === "right"}
            <span class="inline-flex items-center">
                {@html getIconContent()}
            </span>
        {/if}

        <!-- Dot indicator (right) -->
        {#if dot && dotPosition === "right"}
            <span
                class="inline-block rounded-full {getDotSize()} {getDotColor()} 
                {pulse ? 'animate-pulse' : ''}"
            ></span>
        {/if}

        <!-- Remove button as span to avoid nested button -->
        {#if removable}
            <span
                role="button"
                tabindex="0"
                class="inline-flex items-center justify-center ml-0.5 -mr-0.5 hover:bg-black/10 dark:hover:bg-white/10 rounded-full transition-colors duration-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-current cursor-pointer"
                onclick={handleRemove}
                onkeydown={(e) => {
                    if (e.key === "Enter" || e.key === " ") {
                        e.preventDefault();
                        handleRemove(e);
                    }
                }}
                aria-label="Remove badge"
                style="width: {size === 'xs'
                    ? '14px'
                    : size === 'sm'
                      ? '16px'
                      : size === 'lg'
                        ? '20px'
                        : '18px'}; height: {size === 'xs'
                    ? '14px'
                    : size === 'sm'
                      ? '16px'
                      : size === 'lg'
                        ? '20px'
                        : '18px'};"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class={size === "xs"
                        ? "w-2.5 h-2.5"
                        : size === "sm"
                          ? "w-3 h-3"
                          : size === "lg"
                            ? "w-4 h-4"
                            : "w-3.5 h-3.5"}
                >
                    <path
                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                    />
                </svg>
            </span>
        {/if}
    </button>
{:else}
    <span
        {title}
        class="inline-flex items-center font-medium whitespace-nowrap transition-all duration-200
        {getVariantClasses()} 
        {getSizeClasses()} 
        {getRoundedClasses()} 
        {getSpacing()}
        {className || ''}"
    >
        <!-- Dot indicator (left) -->
        {#if dot && dotPosition === "left"}
            <span
                class="inline-block rounded-full {getDotSize()} {getDotColor()} 
                {pulse ? 'animate-pulse' : ''}"
            ></span>
        {/if}

        <!-- Icon (left) -->
        {#if icon && iconPosition === "left"}
            <span class="inline-flex items-center">
                {@html getIconContent()}
            </span>
        {/if}

        <!-- Content -->
        {#if children}
            {@render children()}
        {/if}

        <!-- Icon (right) -->
        {#if icon && iconPosition === "right"}
            <span class="inline-flex items-center">
                {@html getIconContent()}
            </span>
        {/if}

        <!-- Dot indicator (right) -->
        {#if dot && dotPosition === "right"}
            <span
                class="inline-block rounded-full {getDotSize()} {getDotColor()} 
                {pulse ? 'animate-pulse' : ''}"
            ></span>
        {/if}

        <!-- Remove button as button element (safe when parent is span) -->
        {#if removable}
            <button
                type="button"
                class="inline-flex items-center justify-center ml-0.5 -mr-0.5 hover:bg-black/10 dark:hover:bg-white/10 rounded-full transition-colors duration-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-current"
                onclick={handleRemove}
                aria-label="Remove badge"
                style="width: {size === 'xs'
                    ? '14px'
                    : size === 'sm'
                      ? '16px'
                      : size === 'lg'
                        ? '20px'
                        : '18px'}; height: {size === 'xs'
                    ? '14px'
                    : size === 'sm'
                      ? '16px'
                      : size === 'lg'
                        ? '20px'
                        : '18px'};"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class={size === "xs"
                        ? "w-2.5 h-2.5"
                        : size === "sm"
                          ? "w-3 h-3"
                          : size === "lg"
                            ? "w-4 h-4"
                            : "w-3.5 h-3.5"}
                >
                    <path
                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                    />
                </svg>
            </button>
        {/if}
    </span>
{/if}

<style>
    /* Ensure proper animation */
    @keyframes pulse {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Reset button styles for badge button */
    button[type="button"] {
        border: none;
        background: none;
        padding: 0;
        margin: 0;
        font: inherit;
        color: inherit;
        cursor: pointer;
        appearance: none;
    }

    /* Badge hover effect for clickable badges */
    button[type="button"]:not(:has(svg)):hover {
        transform: scale(1.02);
    }

    button[type="button"]:not(:has(svg)):active {
        transform: scale(0.98);
    }
</style>
