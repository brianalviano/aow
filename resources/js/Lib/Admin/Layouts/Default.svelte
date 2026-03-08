<script lang="ts">
    import type { Snippet } from "svelte";
    import { page } from "@inertiajs/svelte";
    import Sidebar from "@/Lib/Admin/Components/Layout/Sidebar.svelte";
    import { toastStore } from "@/Lib/Admin/Stores/toast";
    import Toast from "@/Lib/Admin/Components/Ui/Toast.svelte";
    import { fly } from "svelte/transition";
    import { usePageTransition } from "@/Lib/Utils/transition.svelte";

    interface Props {
        children: Snippet;
    }

    let { children }: Props = $props();

    let auth = $derived(
        $page.props.auth as { user?: any; guard?: string | null } | undefined,
    );
    let isAuthenticated = $derived(!!auth?.guard || !!auth?.user);
    let sidebarCollapsed = $state(false);
    $effect(() => {
        const flash = ($page as any).flash;
        const t = flash?.toast as
            | {
                  type: "success" | "error" | "warning" | "info";
                  message: string;
              }
            | undefined;
        if (t?.message) {
            if (t.type === "success") {
                toastStore.success("Berhasil", t.message);
            } else if (t.type === "error") {
                toastStore.error("Gagal", t.message);
            } else if (t.type === "warning") {
                toastStore.warning("Peringatan", t.message);
            } else {
                toastStore.info("Info", t.message);
            }
        }
    });

    const transition = usePageTransition();
</script>

{#if isAuthenticated}
    <Sidebar
        user={auth?.user}
        onDesktopCollapsed={(data) => (sidebarCollapsed = data.collapsed)}
    />

    <main
        class="transition-all duration-300 print:p-0 lg:print:ml-0 grid grid-cols-1 grid-rows-1 overflow-x-hidden {sidebarCollapsed
            ? 'pr-7 py-7 lg:ml-[3%]'
            : 'p-7 lg:ml-[15%]'}"
    >
        {#key $page.url}
            <div
                class="col-start-1 row-start-1 w-full"
                in:fly={{ x: 50 * transition.direction, duration: 300, delay: 300 }}
                out:fly={{ x: -50 * transition.direction, duration: 300 }}
            >
                {@render children()}
            </div>
        {/key}
    </main>
{:else}
    <main class="grid grid-cols-1 grid-rows-1 overflow-x-hidden">
        {#key $page.url}
            <div
                class="col-start-1 row-start-1 w-full"
                in:fly={{ x: 50 * transition.direction, duration: 300, delay: 300 }}
                out:fly={{ x: -50 * transition.direction, duration: 300 }}
            >
                {@render children()}
            </div>
        {/key}
    </main>
{/if}

<Toast />
