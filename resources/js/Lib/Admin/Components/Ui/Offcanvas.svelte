<script lang="ts">
    interface Props {
        isOpen: boolean;
        size?: "sm" | "md" | "lg" | "full";
        position?: "right" | "left" | "top" | "bottom";
        backdrop?: "static" | "clickable";
        keyboard?: boolean;
        title?: string;
        showHeader?: boolean;
        showCloseButton?: boolean;
        headerClass?: string;
        bodyClass?: string;
        footerClass?: string;
        contentClass?: string;
        onClose?: () => void;
        onShow?: () => void;
        onHide?: () => void;
        children?: any;
        headerSlot?: any;
        footerSlot?: any;
    }

    let {
        isOpen = $bindable(),
        size = "md",
        position = "right",
        backdrop = "clickable",
        keyboard = true,
        title,
        showHeader = true,
        showCloseButton = true,
        headerClass = "",
        bodyClass = "",
        footerClass = "",
        contentClass = "",
        onClose,
        onShow,
        onHide,
        children,
        headerSlot,
        footerSlot,
    }: Props = $props();

    let offcanvasElement = $state<HTMLDivElement>();

    /**
     * Get offcanvas size classes
     */
    function getOffcanvasSizeClasses(): string {
        if (position === "top" || position === "bottom") {
            const sizeClasses = {
                sm: "h-1/4",
                md: "h-1/3",
                lg: "h-1/2",
                full: "h-full",
            };
            return sizeClasses[size] || sizeClasses.md;
        } else {
            const sizeClasses = {
                sm: "w-full sm:w-80",
                md: "w-full sm:w-96",
                lg: "w-full sm:w-[32rem]",
                full: "w-full",
            };
            return sizeClasses[size] || sizeClasses.md;
        }
    }

    /**
     * Get offcanvas position classes
     */
    function getOffcanvasPositionClasses(): string {
        const positionClasses = {
            right: "top-0 right-0 h-full",
            left: "top-0 left-0 h-full",
            top: "top-0 left-0 right-0 w-full",
            bottom: "bottom-0 left-0 right-0 w-full",
        };
        return positionClasses[position] || positionClasses.right;
    }

    /**
     * Get offcanvas content classes
     */
    function getOffcanvasContentClasses(): string {
        let classes =
            "fixed bg-white dark:bg-[#0f0f0f] shadow-2xl flex flex-col offcanvas-content";

        return `${classes} ${getOffcanvasPositionClasses()} ${getOffcanvasSizeClasses()} ${contentClass}`;
    }

    /**
     * Get animation class based on position
     */
    function getAnimationClass(): string {
        return `offcanvas-${position}`;
    }

    /**
     * Get offcanvas body classes
     */
    function getOffcanvasBodyClasses(): string {
        let classes = "flex-1 overflow-y-auto px-4 py-3 sm:px-6 sm:py-4";
        return `${classes} ${bodyClass}`;
    }

    /**
     * Close offcanvas
     */
    function closeOffcanvas() {
        if (onClose) {
            onClose();
        } else {
            isOpen = false;
        }

        if (onHide) {
            onHide();
        }
    }

    /**
     * Handle backdrop click
     */
    function handleBackdropClick(event: MouseEvent) {
        if (backdrop === "clickable" && event.target === offcanvasElement) {
            closeOffcanvas();
        }
    }

    /**
     * Handle escape key
     */
    function handleKeydown(event: KeyboardEvent) {
        if (keyboard && event.key === "Escape" && isOpen) {
            closeOffcanvas();
        }
    }

    /**
     * Handle offcanvas content click (prevent event bubbling)
     */
    function handleContentClick(event: MouseEvent) {
        event.stopPropagation();
    }

    /**
     * Handle offcanvas content keydown for accessibility
     */
    function handleContentKeydown(event: KeyboardEvent) {
        // Allow normal keyboard navigation within offcanvas content
        event.stopPropagation();
    }

    /**
     * Handle backdrop keydown for accessibility
     */
    function handleBackdropKeydown(event: KeyboardEvent) {
        if (event.key === "Enter" || event.key === " ") {
            if (backdrop === "clickable") {
                closeOffcanvas();
            }
        }
    }

    // Manage offcanvas state and body scroll
    $effect(() => {
        if (isOpen) {
            document.body.style.overflow = "hidden";
            // Focus management
            if (offcanvasElement) {
                offcanvasElement.focus();
            }
            if (onShow) {
                onShow();
            }
        } else {
            document.body.style.overflow = "";
            if (onHide) {
                onHide();
            }
        }
    });

    // Cleanup on unmount
    $effect(() => {
        return () => {
            document.body.style.overflow = "";
        };
    });
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
    <!-- Offcanvas Backdrop -->
    <div
        bind:this={offcanvasElement}
        class="fixed inset-0 z-50 bg-black/50"
        onclick={handleBackdropClick}
        onkeydown={handleBackdropKeydown}
        role="dialog"
        aria-modal="true"
        aria-labelledby={title ? "offcanvas-title" : undefined}
        tabindex="-1"
    >
        <!-- Offcanvas Content -->
        <div
            class="{getOffcanvasContentClasses()} {getAnimationClass()}"
            onclick={handleContentClick}
            onkeydown={handleContentKeydown}
            role="presentation"
        >
            <!-- Offcanvas Header -->
            {#if showHeader}
                <div
                    class="flex items-center justify-between px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 dark:border-[#212121] bg-white dark:bg-[#0f0f0f] sticky top-0 {headerClass}"
                >
                    {#if headerSlot}
                        {@render headerSlot()}
                    {:else}
                        <div class="flex items-center space-x-3">
                            {#if title}
                                <h3
                                    id="offcanvas-title"
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    {title}
                                </h3>
                            {/if}
                        </div>
                    {/if}

                    {#if showCloseButton}
                        <button
                            type="button"
                            onclick={closeOffcanvas}
                            class="flex justify-center items-center w-8 h-8 rounded-full transition-colors duration-200 shrink-0 hover:bg-gray-100 dark:hover:bg-gray-800"
                            aria-label="Tutup offcanvas"
                        >
                            <svg
                                class="w-5 h-5 text-gray-400 dark:text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    {/if}
                </div>
            {/if}

            <!-- Offcanvas Body -->
            <div class={getOffcanvasBodyClasses()}>
                {#if children}
                    {@render children()}
                {/if}
            </div>

            <!-- Offcanvas Footer -->
            {#if footerSlot}
                <div
                    class="flex items-center justify-end space-x-3 px-4 py-3 sm:px-6 sm:py-4 border-t border-gray-200 dark:border-[#212121] bg-gray-50 dark:bg-[#0f0f0f] sticky bottom-0 {footerClass}"
                >
                    {@render footerSlot()}
                </div>
            {/if}
        </div>
    </div>
{/if}

<style>
    /* Smooth animations for offcanvas */
    .offcanvas-content {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        max-height: 100dvh;
    }

    /* Animation from right */
    .offcanvas-right {
        animation: slide-in-right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slide-in-right {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }

    /* Animation from left */
    .offcanvas-left {
        animation: slide-in-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slide-in-left {
        from {
            transform: translateX(-100%);
        }
        to {
            transform: translateX(0);
        }
    }

    /* Animation from top */
    .offcanvas-top {
        animation: slide-in-top 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slide-in-top {
        from {
            transform: translateY(-100%);
        }
        to {
            transform: translateY(0);
        }
    }

    /* Animation from bottom */
    .offcanvas-bottom {
        animation: slide-in-bottom 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slide-in-bottom {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }

    /* Custom scrollbar for offcanvas body */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    .overflow-y-auto {
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    :global(.dark) .overflow-y-auto::-webkit-scrollbar-track {
        background: #1e293b;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    :global(.dark) .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #475569;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    :global(.dark) .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
</style>
