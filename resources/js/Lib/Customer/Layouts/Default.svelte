<script lang="ts">
    import { onMount } from "svelte";
    import type { Snippet } from "svelte";
    import { page } from "@inertiajs/svelte";
    import { toastStore } from "@/Lib/Admin/Stores/toast";
    import Toast from "@/Lib/Admin/Components/Ui/Toast.svelte";
    import { applyThemeClass } from "@/Lib/Admin/Hooks/sidebar";

    interface Props {
        children: Snippet;
    }

    let { children }: Props = $props();

    onMount(() => {
        applyThemeClass(true);
    });

    $effect(() => {
        const flash = (page as any).flash;
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

<div
    class="dark min-h-screen bg-slate-950 flex justify-center font-sans text-slate-100"
>
    <!-- Mobile Container -->
    <div
        class="w-full max-w-md bg-slate-950 min-h-screen shadow-md relative grid grid-cols-1 grid-rows-1 overflow-x-hidden"
    >
        <div class="col-start-1 row-start-1 w-full">
            {@render children()}
        </div>
    </div>
</div>

<Toast />
