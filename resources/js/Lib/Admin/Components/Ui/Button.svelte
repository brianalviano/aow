<script lang="ts">
    import { Link } from "@inertiajs/svelte";
    import Tooltip from "./Tooltip.svelte";

    interface Props {
        id?: string;
        type?: "button" | "submit" | "reset";
        variant?:
            | "primary"
            | "success"
            | "warning"
            | "secondary"
            | "info"
            | "danger"
            | "light"
            | "dark"
            | "white"
            | "link"
            | "icon"
            | "outline-primary"
            | "outline-success"
            | "outline-warning"
            | "outline-secondary"
            | "outline-info"
            | "outline-danger"
            | "outline-light"
            | "outline-dark"
            | "outline-white";
        size?: "xs" | "sm" | "normal" | "lg" | "xl";
        disabled?: boolean;
        loading?: boolean;
        icon?: string; // SVG path or icon identifier
        iconPosition?: "left" | "right";
        customIcon?: boolean; // Whether to use custom SVG content
        customColors?: {
            from: string;
            to: string;
            hoverFrom: string;
            hoverTo: string;
            focusRing: string;
            text: string;
        };
        textColor?: string;
        hoverTextColor?: string;
        ariaLabel?: string;
        fullWidth?: boolean;
        class?: string;
        onclick?: (event: MouseEvent) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
        children?: any;
        hasText?: boolean; // Explicitly indicate if button has text content
        title?: string; // HTML title attribute (native tooltip)
        tooltip?: string; // Custom tooltip text (overrides title if both provided)
        tooltipPosition?: "top" | "bottom" | "left" | "right"; // Tooltip position
        tooltipDelay?: number; // Tooltip delay in milliseconds
        useNativeTooltip?: boolean; // Force use native title tooltip instead of custom
        form?: string;
        href?: string;
        only?: string[];
        except?: string[];
        preserveScroll?: boolean;
        preserveState?: boolean;
        replace?: boolean;
        identityKey?: string;
    }

    let {
        id,
        type = "button",
        variant = "primary",
        size = "normal",
        disabled = false,
        loading = false,
        icon,
        iconPosition = "left",
        customIcon = false,
        customColors,
        textColor = "orange-500",
        hoverTextColor = "orange-600",
        ariaLabel,
        fullWidth = false,
        form,
        class: className,
        onclick,
        onfocus,
        onblur,
        children,
        hasText,
        title,
        tooltip,
        tooltipPosition = "top",
        tooltipDelay = 500,
        useNativeTooltip = false,
        href,
        only,
        except,
        preserveScroll = false,
        preserveState = false,
        replace = false,
        identityKey,
    }: Props = $props();

    /**
     * Get variant-specific classes - FIXED: No more ugly gradients!
     */
    function getVariantClasses(): string {
        if (customColors) {
            return `text-${customColors.text} bg-${customColors.from} hover:bg-${customColors.hoverFrom} focus:ring-${customColors.focusRing}`;
        }

        if (variant === "icon") {
            return `text-${textColor} bg-transparent hover:text-${hoverTextColor} focus:ring-${textColor}`;
        }

        const variants = {
            // Solid variants - Clean flat colors, no gradients
            primary:
                "text-white bg-[#0060B2] hover:bg-[#00559e] focus:ring-[#0060B2] dark:focus:ring-[#0060B2]",
            success:
                "text-white bg-green-600 hover:bg-green-700 focus:ring-green-500 dark:focus:ring-green-500",
            warning:
                "text-white bg-orange-500 hover:bg-orange-600 focus:ring-orange-500 dark:focus:ring-orange-500",
            secondary:
                "text-white bg-gray-600 hover:bg-gray-700 focus:ring-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-600",
            info: "text-white bg-blue-500 hover:bg-blue-600 focus:ring-blue-500 dark:focus:ring-blue-500",
            danger: "text-white bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:focus:ring-red-500",
            light: "text-gray-800 bg-white hover:bg-gray-50 focus:ring-gray-500 border border-gray-200 dark:text-white dark:bg-[#0a0a0a] dark:hover:bg-gray-800 dark:border-[#212121]",
            dark: "text-white bg-gray-800 hover:bg-gray-900 focus:ring-gray-700",
            white: "text-gray-800 bg-white hover:bg-gray-50 focus:ring-gray-400 border border-gray-300 shadow-sm dark:text-gray-800 dark:bg-white dark:hover:bg-gray-50 dark:border-gray-300",
            link: "text-[#0060B2] bg-transparent hover:text-[#00559e] focus:ring-[#0060B2] dark:text-[#0060B2]",

            // Outline variants
            "outline-primary":
                "text-[#0060B2] bg-transparent border-2 border-[#0060B2] hover:bg-[#0060B2] hover:text-white focus:ring-[#0060B2] dark:text-[#0060B2] dark:border-[#0060B2] dark:hover:bg-[#0060B2] dark:hover:text-white",
            "outline-success":
                "text-green-600 bg-transparent border-2 border-green-600 hover:bg-green-600 hover:text-white focus:ring-green-500 dark:text-green-500 dark:border-green-500 dark:hover:bg-green-600 dark:hover:text-white",
            "outline-warning":
                "text-orange-600 bg-transparent border-2 border-orange-600 hover:bg-orange-600 hover:text-white focus:ring-orange-500 dark:text-orange-500 dark:border-orange-500 dark:hover:bg-orange-600 dark:hover:text-white",
            "outline-secondary":
                "text-gray-600 bg-transparent border-2 border-gray-600 hover:bg-gray-600 hover:text-white focus:ring-gray-500 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-600 dark:hover:text-white",
            "outline-info":
                "text-blue-600 bg-transparent border-2 border-blue-600 hover:bg-blue-600 hover:text-white focus:ring-blue-500 dark:text-blue-500 dark:border-blue-500 dark:hover:bg-blue-600 dark:hover:text-white",
            "outline-danger":
                "text-red-600 bg-transparent border-2 border-red-600 hover:bg-red-600 hover:text-white focus:ring-red-500 dark:text-red-500 dark:border-red-500 dark:hover:bg-red-600 dark:hover:text-white",
            "outline-light":
                "text-gray-700 bg-transparent border-2 border-gray-300 hover:bg-gray-100 hover:border-gray-400 focus:ring-gray-500 dark:text-gray-300 dark:border-[#212121] dark:hover:bg-gray-800",
            "outline-dark":
                "text-gray-900 bg-transparent border-2 border-gray-900 hover:bg-gray-900 hover:text-white focus:ring-gray-700 dark:text-white dark:border-gray-500 dark:hover:bg-gray-800",
            "outline-white":
                "text-white bg-transparent border-2 border-white hover:bg-white hover:text-gray-900 focus:ring-white dark:text-white dark:border-white dark:hover:bg-white dark:hover:text-gray-900",
        };

        return variants[variant] || variants.primary;
    }

    function getDisabledClasses(): string {
        return "bg-gray-300 text-gray-600 border border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-700";
    }

    /**
     * Get size-specific classes
     */
    function getSizeClasses(): string {
        if (variant === "icon") {
            return "p-0";
        }
        if (variant === "link") {
            const linkSizes = {
                xs: "text-xs",
                sm: "text-sm",
                normal: "text-sm",
                lg: "text-base",
                xl: "text-lg",
            };
            return linkSizes[size] || linkSizes.normal;
        }
        const sizes = {
            xs: "px-2 py-1 text-xs",
            sm: "px-3 py-1.5 text-sm",
            normal: "px-4 py-2 text-sm",
            lg: "px-6 py-3 text-base",
            xl: "px-8 py-4 text-lg",
        };

        return sizes[size] || sizes.normal;
    }

    /**
     * Get icon size based on button size
     */
    function getIconSize(): string {
        const iconSizes = {
            xs: "w-3 h-3",
            sm: "w-4 h-4",
            normal: "w-4 h-4",
            lg: "w-5 h-5",
            xl: "w-6 h-6",
        };

        return iconSizes[size] || iconSizes.normal;
    }

    /**
     * Get the tooltip text to display
     */
    function getTooltipText(): string {
        return tooltip || title || "";
    }

    /**
     * Should use custom tooltip instead of native
     */
    function shouldUseCustomTooltip(): boolean {
        if (useNativeTooltip) return false;
        return !!(tooltip || (!useNativeTooltip && title));
    }

    /**
     * Get the native HTML title attribute value
     * Only if not using custom tooltip
     */
    function getNativeTitle(): string | undefined {
        if (shouldUseCustomTooltip()) return undefined;
        return title;
    }

    /**
     * Click handler
     */
    function handleClick(event: MouseEvent) {
        if (disabled || loading) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        if (onclick) {
            onclick(event);
        }
    }

    /**
     * Focus handler
     */
    function handleFocus(event: FocusEvent) {
        if (disabled || loading) return;

        if (onfocus) {
            onfocus(event);
        }
    }

    /**
     * Blur handler
     */
    function handleBlur(event: FocusEvent) {
        if (disabled || loading) return;

        if (onblur) {
            onblur(event);
        }
    }

    /**
     * Get loading spinner SVG
     */
    function getLoadingSpinner(): string {
        return `<svg class="animate-spin ${getIconSize()}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>`;
    }

    /**
     * Extract path data from SVG string
     */
    function extractPathsFromSvg(svgString: string): string {
        const pathRegex = /<path[^>]*d="([^"]+)"/g;
        const paths: string[] = [];
        let match: RegExpExecArray | null;

        while ((match = pathRegex.exec(svgString)) !== null) {
            const pathData = match[1];
            // Only include non-empty paths that aren't just rectangles (background)
            if (pathData && !pathData.match(/^M0\s+0h\d+v\d+H0z$/)) {
                paths.push(pathData);
            }
        }

        return paths.join(" ");
    }

    /**
     * Check if button has meaningful text content that requires spacing
     */
    function hasTextContent(): boolean {
        if (hasText !== undefined) {
            return hasText;
        }
        return !!children;
    }

    /**
     * Get icon content
     */
    function getIconContent(): string {
        if (loading) {
            return getLoadingSpinner();
        }

        if (!icon) return "";

        if (customIcon) {
            return icon;
        }

        // Check if icon is a FontAwesome class (starts with 'fa' or contains 'fa-')
        if (icon.startsWith("fa") || icon.includes("fa-")) {
            return `<i class="${icon} ${getIconSize().replace("w-", "").replace("h-", "").replace(" ", "")}"></i>`;
        }

        // Check if icon contains SVG markup
        if (icon.includes("<svg")) {
            const extractedPaths = extractPathsFromSvg(icon);
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="${getIconSize()}">
                <path d="${extractedPaths}" />
            </svg>`;
        }

        // Treat as path data
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="${getIconSize()}">
            <path d="${icon}" />
        </svg>`;
    }

    /**
     * Render button content
     */
    function renderButtonContent() {
        return {
            iconLeft: (icon || loading) && iconPosition === "left",
            iconRight: (icon || loading) && iconPosition === "right",
        };
    }

    const buttonContent = $derived(renderButtonContent());
</script>

{#if fullWidth}
    {#if href}
        {#key identityKey ?? href}
            <Link
                {href}
                {only}
                {except}
                {preserveScroll}
                {preserveState}
                {replace}
                aria-label={ariaLabel}
                {title}
                class="flex justify-center items-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 w-full
                {getVariantClasses()} 
                {getSizeClasses()} 
                {variant !== 'link' && variant !== 'icon'
                    ? 'shadow-md hover:shadow-lg transform hover:scale-[1.01] active:scale-[0.99]'
                    : ''} 
                {disabled || loading
                    ? `${variant === 'icon' ? 'opacity-50 cursor-not-allowed pointer-events-none' : `${getDisabledClasses()} opacity-50 cursor-not-allowed pointer-events-none ${variant !== 'link' ? 'shadow-none transform-none' : ''}`}`
                    : ''} 
                {className || ''}"
                onclick={handleClick}
                onfocus={handleFocus}
                onblur={handleBlur}
            >
                {#if buttonContent.iconLeft}
                    <span
                        class={hasTextContent()
                            ? size === "lg" || size === "xl"
                                ? "mr-2"
                                : "mr-1.5"
                            : ""}
                    >
                        {@html getIconContent()}
                    </span>
                {/if}

                {#if children}
                    {@render children()}
                {/if}

                {#if buttonContent.iconRight}
                    <span
                        class={hasTextContent()
                            ? size === "lg" || size === "xl"
                                ? "ml-2"
                                : "ml-1.5"
                            : ""}
                    >
                        {@html getIconContent()}
                    </span>
                {/if}
            </Link>
        {/key}
    {:else}
        <button
            {id}
            {type}
            {disabled}
            aria-label={ariaLabel}
            {title}
            class="flex justify-center items-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 w-full
            {getVariantClasses()} 
            {getSizeClasses()} 
            {variant !== 'link' && variant !== 'icon'
                ? 'shadow-md hover:shadow-lg transform hover:scale-[1.01] active:scale-[0.99]'
                : ''} 
            {disabled || loading
                ? `${variant === 'icon' ? 'opacity-50 cursor-not-allowed pointer-events-none' : `${getDisabledClasses()} opacity-50 cursor-not-allowed pointer-events-none ${variant !== 'link' ? 'shadow-none transform-none' : ''}`}`
                : ''} 
            {className || ''}"
            onclick={handleClick}
            onfocus={handleFocus}
            onblur={handleBlur}
            {form}
        >
            {#if buttonContent.iconLeft}
                <span
                    class={hasTextContent()
                        ? size === "lg" || size === "xl"
                            ? "mr-2"
                            : "mr-1.5"
                        : ""}
                >
                    {@html getIconContent()}
                </span>
            {/if}

            {#if children}
                {@render children()}
            {/if}

            {#if buttonContent.iconRight}
                <span
                    class={hasTextContent()
                        ? size === "lg" || size === "xl"
                            ? "ml-2"
                            : "ml-1.5"
                        : ""}
                >
                    {@html getIconContent()}
                </span>
            {/if}
        </button>
    {/if}
{:else}
    <!-- REGULAR MODE: Use Tooltip wrapper -->
    <Tooltip
        text={shouldUseCustomTooltip() ? getTooltipText() : ""}
        position={tooltipPosition}
        delay={tooltipDelay}
        disabled={!shouldUseCustomTooltip() || disabled || loading}
        wrapperClass="inline-block"
    >
        {#if href}
            {#key identityKey ?? href}
                <Link
                    {href}
                    {only}
                    {except}
                    {preserveScroll}
                    {preserveState}
                    {replace}
                    aria-label={ariaLabel}
                    title={getNativeTitle()}
                    class="inline-flex justify-center items-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2
                    {getVariantClasses()} 
                    {getSizeClasses()} 
                    {variant !== 'link' && variant !== 'icon'
                        ? 'shadow-md hover:shadow-lg transform hover:scale-[1.01] active:scale-[0.99]'
                        : ''} 
                    {disabled || loading
                        ? `${variant === 'icon' ? 'opacity-50 cursor-not-allowed pointer-events-none' : `${getDisabledClasses()} opacity-50 cursor-not-allowed pointer-events-none ${variant !== 'link' ? 'shadow-none transform-none' : ''}`}`
                        : ''} 
                    {className || ''}"
                    onclick={handleClick}
                    onfocus={handleFocus}
                    onblur={handleBlur}
                >
                    {#if buttonContent.iconLeft}
                        <span
                            class={hasTextContent()
                                ? size === "lg" || size === "xl"
                                    ? "mr-2"
                                    : "mr-1.5"
                                : ""}
                        >
                            {@html getIconContent()}
                        </span>
                    {/if}

                    {#if children}
                        {@render children()}
                    {/if}

                    {#if buttonContent.iconRight}
                        <span
                            class={hasTextContent()
                                ? size === "lg" || size === "xl"
                                    ? "ml-2"
                                    : "ml-1.5"
                                : ""}
                        >
                            {@html getIconContent()}
                        </span>
                    {/if}
                </Link>
            {/key}
        {:else}
            <button
                {id}
                {type}
                {disabled}
                aria-label={ariaLabel}
                title={getNativeTitle()}
                class="inline-flex justify-center items-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2
                {getVariantClasses()} 
                {getSizeClasses()} 
                {variant !== 'link' && variant !== 'icon'
                    ? 'shadow-md hover:shadow-lg transform hover:scale-[1.01] active:scale-[0.99]'
                    : ''} 
                {disabled || loading
                    ? `${variant === 'icon' ? 'opacity-50 cursor-not-allowed pointer-events-none' : `${getDisabledClasses()} opacity-50 cursor-not-allowed pointer-events-none ${variant !== 'link' ? 'shadow-none transform-none' : ''}`}`
                    : ''} 
                {className || ''}"
                onclick={handleClick}
                onfocus={handleFocus}
                onblur={handleBlur}
                {form}
            >
                {#if buttonContent.iconLeft}
                    <span
                        class={hasTextContent()
                            ? size === "lg" || size === "xl"
                                ? "mr-2"
                                : "mr-1.5"
                            : ""}
                    >
                        {@html getIconContent()}
                    </span>
                {/if}

                {#if children}
                    {@render children()}
                {/if}

                {#if buttonContent.iconRight}
                    <span
                        class={hasTextContent()
                            ? size === "lg" || size === "xl"
                                ? "ml-2"
                                : "ml-1.5"
                            : ""}
                    >
                        {@html getIconContent()}
                    </span>
                {/if}
            </button>
        {/if}
    </Tooltip>
{/if}

<style>
    /* Custom animation for loading spinner */
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    :global(.animate-spin) {
        animation: spin 1s linear infinite;
    }

    /* Ensure proper button styling */
    button {
        appearance: none;
        user-select: none;
    }

    /* Link variant specific styles */
    button:where(.bg-transparent) {
        box-shadow: none !important;
        transform: none !important;
    }

    button:where(.bg-transparent):hover {
        box-shadow: none !important;
        transform: none !important;
    }

    button:where(.bg-transparent):active {
        transform: none !important;
    }
</style>
