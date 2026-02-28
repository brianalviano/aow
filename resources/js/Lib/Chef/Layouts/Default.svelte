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

<div
    class="min-h-screen bg-gray-50 flex justify-center font-sans text-gray-800"
>
    <!-- Mobile Container -->
    <div class="w-full max-w-md bg-white min-h-screen shadow-md relative">
        {@render children()}
    </div>
</div>

<Toast />
