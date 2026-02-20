<script lang="ts">
    import { slide } from "svelte/transition";
    import { quintOut } from "svelte/easing";

    interface Props {
        id?: string;
        title?: string;
        subtitle?: string;
        open?: boolean;
        collapsible?: boolean;
        hasFooter?: boolean;
        bodyWithoutPadding?: boolean;
        class?: string;
        headerClass?: string;
        bodyClass?: string;
        footerClass?: string;
        children?: any;
        actions?: any;
        footer?: any;
    }
    let {
        id,
        title,
        subtitle,
        open = true,
        collapsible = true,
        hasFooter = false,
        class: className,
        bodyWithoutPadding = false,
        headerClass,
        bodyClass,
        footerClass,
        children,
        actions,
        footer,
    }: Props = $props();
    let isOpen = $state<boolean>();
    $effect(() => {
        isOpen = open;
    });
    function toggle() {
        if (!collapsible) return;
        isOpen = !isOpen;
    }
    const bodyId = $derived(id ? `${id}-body` : undefined);
</script>

<div
    class="bg-white dark:bg-[#0f0f0f] rounded-xl border border-gray-200 dark:border-[#2c2c2c] {className ||
        ''}"
>
    {#if title || subtitle}
        <div
            class="flex items-center justify-between gap-3 px-5 py-4 bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-[#1a1a1a] rounded-t-xl {headerClass ||
                ''}"
        >
            <div>
                {#if title}
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        {title}
                    </h2>
                {/if}
                {#if subtitle}
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {subtitle}
                    </p>
                {/if}
            </div>
            <div class="flex gap-2 items-center">
                {@render actions?.()}
                {#if collapsible}
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0060B2] transition-all duration-200"
                        aria-label={isOpen ? "Tutup" : "Buka"}
                        aria-expanded={isOpen}
                        aria-controls={bodyId}
                        onclick={toggle}
                    >
                        <i
                            class="fa-solid fa-chevron-up text-sm {isOpen
                                ? ''
                                : 'rotate-180'}"
                        ></i>
                    </button>
                {/if}
            </div>
        </div>
    {/if}
    {#if !collapsible || isOpen}
        <div
            id={bodyId}
            class={`bg-white dark:bg-[#0f0f0f] ${footer ? "" : "rounded-b-xl"} ${title || subtitle ? "" : "rounded-t-xl"} ${
                bodyWithoutPadding ? "" : "px-5 py-4 "
            } ${bodyClass || ""}`}
            transition:slide={{ duration: 300, easing: quintOut }}
        >
            {@render children?.()}
        </div>
    {/if}
    {#if hasFooter || footer}
        <div
            class="px-5 py-4 bg-gray-50 dark:bg-[#151515] border-t border-gray-200 dark:border-[#1a1a1a] rounded-b-xl {footerClass ||
                ''}"
        >
            {@render footer?.()}
        </div>
    {/if}
</div>
