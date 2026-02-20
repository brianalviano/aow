<script lang="ts">
    interface Props {
        trigger?: "click" | "hover" | "focus" | "manual";
        position?: "top" | "bottom" | "left" | "right" | "auto";
        isOpen?: boolean;
        offset?: number;
        arrow?: boolean;
        closeOnClickOutside?: boolean;
        closeOnEscape?: boolean;
        disabled?: boolean;
        width?: "auto" | "sm" | "md" | "lg" | "full";
        class?: string;
        triggerClass?: string;
        contentClass?: string;
        onOpen?: () => void;
        onClose?: () => void;
        children?: any;
        triggerSlot?: any;
        contentSlot?: any;
    }

    let {
        trigger = "click",
        position = "bottom",
        isOpen = $bindable(false),
        offset = 8,
        arrow = true,
        closeOnClickOutside = true,
        closeOnEscape = true,
        disabled = false,
        width = "auto",
        class: className = "",
        triggerClass = "",
        contentClass = "",
        onOpen,
        onClose,
        children,
        triggerSlot,
        contentSlot,
    }: Props = $props();

    let triggerElement = $state<HTMLElement>();
    let popoverElement = $state<HTMLElement>();
    let actualPosition = $state<string>();
    $effect(() => {
        actualPosition = position;
    });
    let isHovering = $state(false);
    let hoverTimeout: ReturnType<typeof setTimeout> | null = null;

    /**
     * Get width classes
     */
    function getWidthClasses(): string {
        const widths = {
            auto: "w-auto max-w-xs",
            sm: "w-48",
            md: "w-64",
            lg: "w-80",
            full: "w-full",
        };
        return widths[width] || widths.auto;
    }

    /**
     * Calculate popover position
     */
    function calculatePosition() {
        if (!triggerElement || !popoverElement) return;

        const triggerRect = triggerElement.getBoundingClientRect();
        const popoverRect = popoverElement.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const windowWidth = window.innerWidth;

        let calculatedPosition = position;

        // Auto positioning
        if (position === "auto") {
            const spaceTop = triggerRect.top;
            const spaceBottom = windowHeight - triggerRect.bottom;
            const spaceLeft = triggerRect.left;
            const spaceRight = windowWidth - triggerRect.right;

            if (spaceBottom >= popoverRect.height || spaceBottom >= spaceTop) {
                calculatedPosition = "bottom";
            } else if (spaceTop >= popoverRect.height) {
                calculatedPosition = "top";
            } else if (spaceRight >= popoverRect.width) {
                calculatedPosition = "right";
            } else {
                calculatedPosition = "left";
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
            positions[actualPosition as keyof typeof positions] ||
            positions.bottom
        );
    }

    /**
     * Get arrow classes
     */
    function getArrowClasses(): string {
        const arrows = {
            top: "top-full left-1/2 -translate-x-1/2 border-t-gray-900 dark:border-t-gray-800 border-l-transparent border-r-transparent border-b-transparent",
            bottom: "bottom-full left-1/2 -translate-x-1/2 border-b-gray-900 dark:border-b-gray-800 border-l-transparent border-r-transparent border-t-transparent",
            left: "left-full top-1/2 -translate-y-1/2 border-l-gray-900 dark:border-l-gray-800 border-t-transparent border-b-transparent border-r-transparent",
            right: "right-full top-1/2 -translate-y-1/2 border-r-gray-900 dark:border-r-gray-800 border-t-transparent border-b-transparent border-l-transparent",
        };
        return arrows[actualPosition as keyof typeof arrows] || arrows.bottom;
    }

    /**
     * Toggle popover
     */
    function togglePopover() {
        if (disabled) return;

        if (isOpen) {
            closePopover();
        } else {
            openPopover();
        }
    }

    /**
     * Open popover
     */
    function openPopover() {
        if (disabled || isOpen) return;

        isOpen = true;
        if (onOpen) onOpen();

        // Calculate position after popover is rendered
        setTimeout(() => {
            calculatePosition();
        }, 0);
    }

    /**
     * Close popover
     */
    function closePopover() {
        if (!isOpen) return;

        isOpen = false;
        if (onClose) onClose();
    }

    /**
     * Handle click outside
     */
    function handleClickOutside(event: MouseEvent) {
        if (!closeOnClickOutside || !isOpen) return;

        const target = event.target as Node;
        if (
            triggerElement &&
            !triggerElement.contains(target) &&
            popoverElement &&
            !popoverElement.contains(target)
        ) {
            closePopover();
        }
    }

    /**
     * Handle escape key
     */
    function handleEscape(event: KeyboardEvent) {
        if (closeOnEscape && event.key === "Escape" && isOpen) {
            closePopover();
        }
    }

    /**
     * Handle hover enter
     */
    function handleMouseEnter() {
        if (trigger !== "hover" || disabled) return;

        isHovering = true;
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
        }
        openPopover();
    }

    /**
     * Handle hover leave
     */
    function handleMouseLeave() {
        if (trigger !== "hover" || disabled) return;

        isHovering = false;
        hoverTimeout = setTimeout(() => {
            if (!isHovering) {
                closePopover();
            }
        }, 200);
    }

    /**
     * Handle popover hover enter
     */
    function handlePopoverMouseEnter() {
        if (trigger !== "hover") return;
        isHovering = true;
    }

    /**
     * Handle popover hover leave
     */
    function handlePopoverMouseLeave() {
        if (trigger !== "hover") return;
        isHovering = false;
        handleMouseLeave();
    }

    /**
     * Handle trigger click
     */
    function handleTriggerClick(event: MouseEvent) {
        if (trigger === "click") {
            event.stopPropagation();
            togglePopover();
        }
    }
    function handleTriggerKeydown(event: KeyboardEvent) {
        if (
            trigger === "click" &&
            (event.key === "Enter" || event.key === " ")
        ) {
            event.preventDefault();
            togglePopover();
        }
    }
    /**
     * Handle trigger focus
     */
    function handleTriggerFocus() {
        if (trigger === "focus" && !disabled) {
            openPopover();
        }
    }

    /**
     * Handle trigger blur
     */
    function handleTriggerBlur() {
        if (trigger === "focus") {
            closePopover();
        }
    }

    // Handle position recalculation on window resize
    $effect(() => {
        if (isOpen) {
            const handleResize = () => calculatePosition();
            window.addEventListener("resize", handleResize);
            return () => window.removeEventListener("resize", handleResize);
        }
    });
</script>

<svelte:window onclick={handleClickOutside} onkeydown={handleEscape} />

<div class="relative inline-block {className}">
    <!-- Trigger -->
    <div
        bind:this={triggerElement}
        class="inline-block cursor-pointer {triggerClass}"
        onclick={handleTriggerClick}
        onkeydown={handleTriggerKeydown}
        onmouseenter={handleMouseEnter}
        onmouseleave={handleMouseLeave}
        onfocus={handleTriggerFocus}
        onblur={handleTriggerBlur}
        role="button"
        tabindex={disabled ? -1 : 0}
        aria-expanded={isOpen}
        aria-haspopup="true"
    >
        {#if triggerSlot}
            {@render triggerSlot()}
        {/if}
    </div>

    <!-- Popover Content -->
    {#if isOpen && !disabled}
        <div
            bind:this={popoverElement}
            class="absolute z-50 {getPositionClasses()} {getWidthClasses()}"
            onmouseenter={handlePopoverMouseEnter}
            onmouseleave={handlePopoverMouseLeave}
            role="tooltip"
        >
            <div
                class="bg-gray-900 dark:bg-gray-800 text-white rounded-lg shadow-lg border border-gray-700 dark:border-gray-600 overflow-hidden animate-popover {contentClass}"
            >
                {#if contentSlot}
                    {@render contentSlot()}
                {:else if children}
                    <div class="p-4">
                        {@render children()}
                    </div>
                {/if}
            </div>

            <!-- Arrow -->
            {#if arrow}
                <div
                    class="absolute w-0 h-0 border-[6px] {getArrowClasses()}"
                ></div>
            {/if}
        </div>
    {/if}
</div>

<style>
    @keyframes popover-appear {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    :global(.animate-popover) {
        animation: popover-appear 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
</style>
