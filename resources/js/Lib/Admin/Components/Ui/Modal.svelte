<script lang="ts">
    import Button from "./Button.svelte";
    import { onDestroy } from "svelte";

    interface Props {
        isOpen: boolean;
        size?: "sm" | "md" | "lg" | "xl" | "full";
        centered?: boolean;
        backdrop?: "static" | "clickable";
        keyboard?: boolean;
        lockScroll?: boolean;
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
        centered = true,
        backdrop = "clickable",
        keyboard = true,
        lockScroll = true,
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

    let modalElement = $state<HTMLDivElement>();
    let hasLocked = $state<boolean>(false);
    function lockBody(): void {
        const w = window as any;
        const count = Number(w.__app_modal_scroll_lock_count ?? 0);
        if (count === 0) {
            w.__app_modal_prev_html_overflow =
                document.documentElement.style.overflow || "";
            w.__app_modal_prev_body_overflow =
                document.body.style.overflow || "";
            document.documentElement.style.overflow = "hidden";
            document.body.style.overflow = "hidden";
            document.documentElement.classList.add("modal-open");
            document.body.classList.add("modal-open");
        }
        w.__app_modal_scroll_lock_count = count + 1;
    }
    function unlockBody(): void {
        const w = window as any;
        let count = Number(w.__app_modal_scroll_lock_count ?? 0);
        count = Math.max(0, count - 1);
        w.__app_modal_scroll_lock_count = count;
        if (count === 0) {
            document.documentElement.style.overflow =
                w.__app_modal_prev_html_overflow ?? "";
            document.body.style.overflow =
                w.__app_modal_prev_body_overflow ?? "";
            document.documentElement.classList.remove("modal-open");
            document.body.classList.remove("modal-open");
            w.__app_modal_prev_html_overflow = null;
            w.__app_modal_prev_body_overflow = null;
        }
    }

    /**
     * Get modal size classes
     */
    function getModalSizeClasses(): string {
        const sizeClasses = {
            sm: "max-w-sm w-full", // ~384px
            md: "max-w-md w-full", // ~448px
            lg: "max-w-2xl w-full", // ~672px
            xl: "max-w-4xl w-full", // ~896px
            full: "max-w-full w-full mx-4",
        };
        return sizeClasses[size] || sizeClasses.md;
    }

    /**
     * Get modal positioning classes
     */
    function getModalPositionClasses(): string {
        if (centered) {
            return "flex items-center justify-center min-h-screen p-4";
        }
        return "flex justify-center pt-16 pb-4 px-4";
    }

    /**
     * Get modal content classes
     */
    function getModalContentClasses(): string {
        let classes =
            "relative bg-white dark:bg-[#0f0f0f] rounded-lg shadow-xl transform transition-all";

        return `${classes} ${getModalSizeClasses()} ${contentClass}`;
    }

    /**
     * Get modal body classes
     */
    function getModalBodyClasses(): string {
        return `px-6 py-4 ${bodyClass}`;
    }

    /**
     * Close modal
     */
    function closeModal() {
        if (onClose) {
            onClose();
        } else {
            isOpen = false;
        }
    }

    /**
     * Handle backdrop click
     */
    function handleBackdropClick(event: MouseEvent) {
        if (backdrop === "clickable" && event.target === modalElement) {
            closeModal();
        }
    }

    /**
     * Handle escape key
     */
    function handleKeydown(event: KeyboardEvent) {
        if (keyboard && event.key === "Escape" && isOpen) {
            closeModal();
        }
    }

    /**
     * Handle modal content click (prevent event bubbling)
     */
    function handleContentClick(event: MouseEvent) {
        event.stopPropagation();
    }

    /**
     * Handle modal content keydown for accessibility
     */
    function handleContentKeydown(event: KeyboardEvent) {
        // Allow normal keyboard navigation within modal content
        event.stopPropagation();
    }

    /**
     * Handle backdrop keydown for accessibility
     */
    function handleBackdropKeydown(event: KeyboardEvent) {
        if (event.key === "Enter" || event.key === " ") {
            if (backdrop === "clickable") {
                closeModal();
            }
        }
    }

    // Manage modal state
    $effect(() => {
        if (isOpen) {
            // Focus management
            if (modalElement) {
                modalElement.focus();
            }
            if (onShow) {
                onShow();
            }
        } else {
            if (onHide) {
                onHide();
            }
        }
    });

    $effect(() => {
        if (!lockScroll) return;
        if (isOpen && !hasLocked) {
            lockBody();
            hasLocked = true;
        } else if (!isOpen && hasLocked) {
            unlockBody();
            hasLocked = false;
        }
    });

    onDestroy(() => {
        if (hasLocked) {
            unlockBody();
            hasLocked = false;
        }
    });
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
    <!-- Modal Backdrop -->
    <div
        bind:this={modalElement}
        class="fixed inset-0 z-50 bg-black/50 overflow-y-auto overflow-x-hidden overscroll-contain"
        onclick={handleBackdropClick}
        onkeydown={handleBackdropKeydown}
        role="dialog"
        aria-modal="true"
        aria-labelledby={title ? "modal-title" : undefined}
        tabindex="-1"
    >
        <!-- Modal Container -->
        <div class={getModalPositionClasses()}>
            <!-- Modal Content -->
            <div
                class="{getModalContentClasses()} modal-content my-8"
                onclick={handleContentClick}
                onkeydown={handleContentKeydown}
                role="presentation"
            >
                <!-- Modal Header -->
                {#if showHeader}
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-[#212121] {headerClass}"
                    >
                        {#if headerSlot}
                            {@render headerSlot()}
                        {:else}
                            <div class="flex items-center space-x-3">
                                {#if title}
                                    <h3
                                        id="modal-title"
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
                                onclick={closeModal}
                                class="flex justify-center items-center w-8 h-8 rounded-full transition-colors duration-200 shrink-0 hover:bg-gray-100 dark:hover:bg-gray-800"
                                aria-label="Tutup modal"
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

                <!-- Modal Body -->
                <div class={getModalBodyClasses()}>
                    {#if children}
                        {@render children()}
                    {/if}
                </div>

                <!-- Modal Footer -->
                {#if footerSlot}
                    <div
                        class="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-[#212121] bg-gray-50 dark:bg-[#0f0f0f] rounded-b-lg {footerClass}"
                    >
                        {#if footerSlot}
                            {@render footerSlot()}
                        {:else}
                            <Button
                                variant="outline-secondary"
                                onclick={closeModal}
                            >
                                Tutup
                            </Button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    </div>
{/if}

<style>
    /* Animation for modal content */
    .modal-content {
        animation: modal-appear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modal-appear {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    :global(html.modal-open),
    :global(body.modal-open) {
        overflow: hidden !important;
    }
</style>
