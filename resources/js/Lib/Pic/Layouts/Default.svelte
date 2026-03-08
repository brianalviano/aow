<script lang="ts">
    import type { Snippet } from "svelte";
    import { page } from "@inertiajs/svelte";
    import { toastStore } from "@/Lib/Admin/Stores/toast";
    import Toast from "@/Lib/Admin/Components/Ui/Toast.svelte";
    import { fly } from "svelte/transition";
    import { usePageTransition } from "@/Lib/Utils/transition.svelte";

    interface Props {
        children: Snippet;
    }

    let { children }: Props = $props();

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

<div class="font-sans text-gray-800 grid grid-cols-1 grid-rows-1 min-h-screen overflow-x-hidden">
    {#key $page.url}
        <div
            class="col-start-1 row-start-1 w-full overflow-x-hidden"
            in:fly={{ x: 50 * transition.direction, duration: 300, delay: 300 }}
            out:fly={{ x: -50 * transition.direction, duration: 300 }}
        >
            {@render children()}
        </div>
    {/key}
</div>

<Toast />
