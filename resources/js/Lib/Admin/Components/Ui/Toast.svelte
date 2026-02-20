<script lang="ts">
    import { onMount } from "svelte";
    import { toastStore, type Toast } from "@/Lib/Admin/Stores/toast";
    import { fly, fade } from "svelte/transition";

    let toasts: Toast[] = $state([]);

    /**
     * Remove a toast notification
     */
    function removeToast(id: string) {
        toastStore.remove(id);
    }

    /**
     * Get icon class based on toast type
     */
    function getIcon(type: Toast["type"]): string {
        switch (type) {
            case "success":
                return "fas fa-check-circle";
            case "error":
                return "fas fa-exclamation-circle";
            case "warning":
                return "fas fa-exclamation-triangle";
            case "info":
                return "fas fa-info-circle";
            default:
                return "fas fa-info-circle";
        }
    }

    /**
     * Get color classes based on toast type
     */
    function getColorClasses(type: Toast["type"]): string {
        switch (type) {
            case "success":
                return "bg-green-50 dark:bg-green-900/80 border-green-200 dark:border-green-800 text-green-800 dark:text-green-400";
            case "error":
                return "bg-red-50 dark:bg-red-900/80 border-red-200 dark:border-red-800 text-red-800 dark:text-red-400";
            case "warning":
                return "bg-yellow-50 dark:bg-yellow-900/80 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-400";
            case "info":
                return "bg-blue-50 dark:bg-blue-900/80 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-400";
            default:
                return "bg-gray-50 dark:bg-[#0a0a0a] border-gray-200 dark:border-[#212121] text-gray-800 dark:text-gray-300";
        }
    }

    /**
     * Get icon color class based on toast type
     */
    function getIconColor(type: Toast["type"]): string {
        switch (type) {
            case "success":
                return "text-green-500";
            case "error":
                return "text-red-500";
            case "warning":
                return "text-yellow-500";
            case "info":
                return "text-blue-500";
            default:
                return "text-gray-500";
        }
    }

    onMount(() => {
        const unsubscribe = toastStore.subscribe((value) => {
            toasts = value;
        });

        return unsubscribe;
    });
</script>

<!-- Toast Container -->
<div class="fixed top-4 right-4 space-y-2 w-full max-w-sm z-9999">
    {#each toasts as toast (toast.id)}
        <div
            class="{getColorClasses(
                toast.type,
            )} border rounded-lg shadow-lg p-4 flex items-start space-x-3 backdrop-blur-sm"
            in:fly={{ x: 300, duration: 300 }}
            out:fade={{ duration: 200 }}
        >
            <!-- Icon -->
            <div class="shrink-0">
                <i
                    class="{getIcon(toast.type)} {getIconColor(
                        toast.type,
                    )} text-lg"
                ></i>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-semibold wrap-break-word">
                    {toast.title}
                </h4>
                {#if toast.message}
                    <p class="mt-1 text-sm whitespace-pre-wrap wrap-break-word">
                        {toast.message}
                    </p>
                {/if}
            </div>

            <!-- Close Button -->
            <button
                type="button"
                class="text-gray-400 transition-colors shrink-0 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                onclick={() => removeToast(toast.id)}
                aria-label="Close notification"
            >
                <i class="text-sm fas fa-times"></i>
            </button>
        </div>
    {/each}
</div>

<style>
    /* Ensure toasts appear above everything */
    :global(.toast-container) {
        z-index: 9999;
    }
</style>
