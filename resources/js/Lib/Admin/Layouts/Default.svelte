<script lang="ts">
    import type { Snippet } from "svelte";
    import { page } from "@inertiajs/svelte";
    import Sidebar from "@/Lib/Admin/Components/Layout/Sidebar.svelte";
    import { toastStore } from "@/Lib/Admin/Stores/toast";
    import Toast from "@/Lib/Admin/Components/Ui/Toast.svelte";

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
</script>

{#if isAuthenticated}
    <Sidebar
        user={auth?.user}
        onDesktopCollapsed={(data) => (sidebarCollapsed = data.collapsed)}
    />

    <main
        class="transition-all duration-300 print:p-0 lg:print:ml-0 {sidebarCollapsed
            ? 'pr-7 py-7 lg:ml-[3%]'
            : 'p-7 lg:ml-[15%]'}"
    >
        {@render children()}
    </main>
{:else}
    <main>
        {@render children()}
    </main>
{/if}

<Toast />
