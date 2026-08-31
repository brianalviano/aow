<script lang="ts">
    import type { Snippet } from "svelte";
    import { page } from "@inertiajs/svelte";
    import { toastStore } from "@/Lib/Admin/Stores/toast";
    import Toast from "@/Lib/Admin/Components/Ui/Toast.svelte";

    interface Props {
        children: Snippet;
    }

    let { children }: Props = $props();

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
    class="font-sans text-slate-100 bg-slate-950 grid grid-cols-1 grid-rows-1 min-h-screen overflow-x-hidden"
>
    <div class="col-start-1 row-start-1 w-full overflow-x-hidden">
        {@render children()}
    </div>
</div>

<Toast />
