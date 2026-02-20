<script lang="ts">
    interface Props {
        text?: string;
        position?: "top" | "bottom" | "left" | "right" | "auto";
        trigger?: "hover" | "focus" | "click" | "manual";
        isVisible?: boolean;
        delay?: number;
        offset?: number;
        arrow?: boolean;
        disabled?: boolean;
        maxWidth?: "sm" | "md" | "lg" | "xl" | "none";
        variant?:
            | "dark"
            | "light"
            | "primary"
            | "success"
            | "warning"
            | "danger"
            | "info";
        class?: string;
        tooltipClass?: string;
        wrapperClass?: string;
        onShow?: () => void;
        onHide?: () => void;
        children?: any;
        contentSlot?: any;
    }

    let {
        text = "",
        position = "top",
        trigger = "hover",
        isVisible = $bindable(false),
        delay = 300,
        offset = 8,
        arrow = true,
        disabled = false,
        maxWidth = "md",
        variant = "dark",
        class: className = "",
        tooltipClass = "",
        wrapperClass = "",
        onShow,
        onHide,
        children,
        contentSlot,
    }: Props = $props();

    let wrapperElement = $state<HTMLElement>();
    let tooltipElement = $state<HTMLElement>();
    let actualPosition = $state<string>();
    $effect(() => {
        actualPosition = position;
    });
    let showTimeout: ReturnType<typeof setTimeout> | null = null;
    let hideTimeout: ReturnType<typeof setTimeout> | null = null;

    /**
     * Get max width classes
     */
    function getMaxWidthClasses(): string {
        const widths = {
            sm: "max-w-[150px]",
            md: "max-w-[200px]",
            lg: "max-w-[250px]",
            xl: "max-w-[300px]",
            none: "max-w-none",
        };
        return widths[maxWidth] || widths.md;
    }

    /**
     * Get variant classes
     */
    function getVariantClasses(): string {
        const variants = {
            dark: "bg-gray-900 dark:bg-gray-800 text-white border-gray-700 dark:border-gray-600",
            light: "bg-white dark:bg-gray-100 text-gray-900 dark:text-gray-800 border-gray-200 dark:border-gray-300 shadow-lg",
            primary:
                "bg-blue-600 dark:bg-blue-700 text-white border-blue-500 dark:border-blue-600",
            success:
                "bg-green-600 dark:bg-green-700 text-white border-green-500 dark:border-green-600",
            warning:
                "bg-orange-500 dark:bg-orange-600 text-white border-orange-400 dark:border-orange-500",
            danger: "bg-red-600 dark:bg-red-700 text-white border-red-500 dark:border-red-600",
            info: "bg-cyan-600 dark:bg-cyan-700 text-white border-cyan-500 dark:border-cyan-600",
        };
        return variants[variant] || variants.dark;
    }

    /**
     * Calculate tooltip position
     */
    function calculatePosition() {
        if (!wrapperElement || !tooltipElement) return;

        const wrapperRect = wrapperElement.getBoundingClientRect();
        const tooltipRect = tooltipElement.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const windowWidth = window.innerWidth;

        let calculatedPosition = position;

        // Auto positioning
        if (position === "auto") {
            const spaceTop = wrapperRect.top;
            const spaceBottom = windowHeight - wrapperRect.bottom;
            const spaceLeft = wrapperRect.left;
            const spaceRight = windowWidth - wrapperRect.right;

            if (spaceTop >= tooltipRect.height + offset) {
                calculatedPosition = "top";
            } else if (spaceBottom >= tooltipRect.height + offset) {
                calculatedPosition = "bottom";
            } else if (spaceRight >= tooltipRect.width + offset) {
                calculatedPosition = "right";
            } else if (spaceLeft >= tooltipRect.width + offset) {
                calculatedPosition = "left";
            } else {
                // Default to bottom if no space
                calculatedPosition = "bottom";
            }
        }

        actualPosition = calculatedPosition;
    }

    /**
     * Get position classes
     */
    function getPositionClasses(): string {
        const positions = {
            top: `bottom-full left-1/2 -translate-x-1/2 mb-${offset / 4}`,
            bottom: `top-full left-1/2 -translate-x-1/2 mt-${offset / 4}`,
            left: `right-full top-1/2 -translate-y-1/2 mr-${offset / 4}`,
            right: `left-full top-1/2 -translate-y-1/2 ml-${offset / 4}`,
        };
        return (
            positions[actualPosition as keyof typeof positions] || positions.top
        );
    }

    /**
     * Get arrow classes based on variant and position
     */
    function getArrowClasses(): string {
        const arrowColors = {
            dark: "border-gray-900 dark:border-gray-800",
            light: "border-white dark:border-gray-100",
            primary: "border-blue-600 dark:border-blue-700",
            success: "border-green-600 dark:border-green-700",
            warning: "border-orange-500 dark:border-orange-600",
            danger: "border-red-600 dark:border-red-700",
            info: "border-cyan-600 dark:border-cyan-700",
        };

        const arrowColor = arrowColors[variant] || arrowColors.dark;

        const arrows = {
            top: `top-full left-1/2 -translate-x-1/2 border-t-[${arrowColor}] border-l-transparent border-r-transparent border-b-transparent`,
            bottom: `bottom-full left-1/2 -translate-x-1/2 border-b-[${arrowColor}] border-l-transparent border-r-transparent border-t-transparent`,
            left: `left-full top-1/2 -translate-y-1/2 border-l-[${arrowColor}] border-t-transparent border-b-transparent border-r-transparent`,
            right: `right-full top-1/2 -translate-y-1/2 border-r-[${arrowColor}] border-t-transparent border-b-transparent border-l-transparent`,
        };

        return arrows[actualPosition as keyof typeof arrows] || arrows.top;
    }

    /**
     * Get arrow border color based on variant
     */
    function getArrowBorderColor(): string {
        const colors = {
            dark: "gray-900",
            light: "white",
            primary: "blue-600",
            success: "green-600",
            warning: "orange-500",
            danger: "red-600",
            info: "cyan-600",
        };
        return colors[variant] || colors.dark;
    }

    /**
     * Show tooltip
     */
    function showTooltip() {
        if (disabled || isVisible) return;

        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }

        showTimeout = setTimeout(() => {
            isVisible = true;
            if (onShow) onShow();

            // Calculate position after tooltip is rendered
            setTimeout(() => {
                calculatePosition();
            }, 0);
        }, delay);
    }

    /**
     * Hide tooltip
     */
    function hideTooltip() {
        if (showTimeout) {
            clearTimeout(showTimeout);
            showTimeout = null;
        }

        hideTimeout = setTimeout(() => {
            isVisible = false;
            if (onHide) onHide();
        }, 100);
    }

    /**
     * Toggle tooltip (for click trigger)
     */
    function toggleTooltip() {
        if (disabled) return;

        if (isVisible) {
            hideTooltip();
        } else {
            showTooltip();
        }
    }

    /**
     * Handle mouse enter
     */
    function handleMouseEnter() {
        if (trigger === "hover") {
            showTooltip();
        }
    }

    /**
     * Handle mouse leave
     */
    function handleMouseLeave() {
        if (trigger === "hover") {
            hideTooltip();
        }
    }

    /**
     * Handle focus
     */
    function handleFocus() {
        if (trigger === "focus") {
            showTooltip();
        }
    }

    /**
     * Handle blur
     */
    function handleBlur() {
        if (trigger === "focus") {
            hideTooltip();
        }
    }

    /**
     * Handle click
     */
    function handleClick(event: MouseEvent) {
        if (trigger === "click") {
            event.stopPropagation();
            toggleTooltip();
        }
    }

    /**
     * Handle click outside
     */
    function handleClickOutside(event: MouseEvent) {
        if (trigger !== "click" || !isVisible) return;

        const target = event.target as Node;
        if (
            wrapperElement &&
            !wrapperElement.contains(target) &&
            tooltipElement &&
            !tooltipElement.contains(target)
        ) {
            hideTooltip();
        }
    }

    /**
     * Handle escape key
     */
    function handleEscape(event: KeyboardEvent) {
        if (event.key === "Escape" && isVisible) {
            hideTooltip();
        }
    }

    // Handle position recalculation on window resize
    $effect(() => {
        if (isVisible) {
            const handleResize = () => calculatePosition();
            window.addEventListener("resize", handleResize);
            return () => window.removeEventListener("resize", handleResize);
        }
    });

    // Cleanup timeouts on unmount
    $effect(() => {
        return () => {
            if (showTimeout) clearTimeout(showTimeout);
            if (hideTimeout) clearTimeout(hideTimeout);
        };
    });
</script>

<svelte:window onclick={handleClickOutside} onkeydown={handleEscape} />

<div class="relative inline-block {wrapperClass}">
    <!-- Wrapper -->
    {#if trigger === "click"}
        <button
            bind:this={wrapperElement}
            class="inline-block {className}"
            onclick={handleClick}
            onmouseenter={handleMouseEnter}
            onmouseleave={handleMouseLeave}
            onfocus={handleFocus}
            onblur={handleBlur}
            type="button"
            aria-describedby={isVisible ? "tooltip" : undefined}
            disabled={disabled ? true : undefined}
        >
            {#if children}
                {@render children()}
            {/if}
        </button>
    {:else}
        <div
            bind:this={wrapperElement}
            class="inline-block {className}"
            onmouseenter={handleMouseEnter}
            onmouseleave={handleMouseLeave}
            onfocus={handleFocus}
            onblur={handleBlur}
            role="group"
            aria-describedby={isVisible ? "tooltip" : undefined}
        >
            {#if children}
                {@render children()}
            {/if}
        </div>
    {/if}

    <!-- Tooltip -->
    {#if isVisible && !disabled && (text || contentSlot)}
        <div
            bind:this={tooltipElement}
            id="tooltip"
            class="absolute z-50 {getPositionClasses()} {getMaxWidthClasses()}"
            role="tooltip"
        >
            <div
                class="px-3 py-2 text-sm rounded-lg border whitespace-normal wrap-break-word {getVariantClasses()} {tooltipClass} animate-tooltip"
            >
                {#if contentSlot}
                    {@render contentSlot()}
                {:else}
                    {text}
                {/if}
            </div>

            <!-- Arrow -->
            {#if arrow}
                <div
                    class="absolute w-0 h-0 border-[5px]"
                    style="
                        {actualPosition === 'top'
                        ? `border-top-color: var(--tw-border-${getArrowBorderColor()});`
                        : ''}
                        {actualPosition === 'bottom'
                        ? `border-bottom-color: var(--tw-border-${getArrowBorderColor()});`
                        : ''}
                        {actualPosition === 'left'
                        ? `border-left-color: var(--tw-border-${getArrowBorderColor()});`
                        : ''}
                        {actualPosition === 'right'
                        ? `border-right-color: var(--tw-border-${getArrowBorderColor()});`
                        : ''}
                        {actualPosition === 'top'
                        ? 'top: 100%; left: 50%; transform: translateX(-50%); border-left-color: transparent; border-right-color: transparent; border-bottom-color: transparent;'
                        : ''}
                        {actualPosition === 'bottom'
                        ? 'bottom: 100%; left: 50%; transform: translateX(-50%); border-left-color: transparent; border-right-color: transparent; border-top-color: transparent;'
                        : ''}
                        {actualPosition === 'left'
                        ? 'left: 100%; top: 50%; transform: translateY(-50%); border-top-color: transparent; border-bottom-color: transparent; border-right-color: transparent;'
                        : ''}
                        {actualPosition === 'right'
                        ? 'right: 100%; top: 50%; transform: translateY(-50%); border-top-color: transparent; border-bottom-color: transparent; border-left-color: transparent;'
                        : ''}
                        border-width: 5px;
                    "
                ></div>
            {/if}
        </div>
    {/if}
</div>

<style>
    @keyframes tooltip-appear {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    :global(.animate-tooltip) {
        animation: tooltip-appear 0.15s ease-out;
    }
</style>
