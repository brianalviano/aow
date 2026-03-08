<script lang="ts">
    import type { Snippet } from "svelte";
    import { page, Link } from "@inertiajs/svelte";
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

<div
    class="min-h-screen bg-gray-50 flex justify-center font-sans text-gray-800"
>
    <!-- Mobile Container -->
    <div
        class="w-full max-w-md bg-white min-h-screen shadow-md relative pb-20 grid grid-cols-1 grid-rows-[1fr_auto] overflow-x-hidden"
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

        <!-- Bottom Navigation -->
        {#if $page.props.auth?.user}
            <nav
                class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white/80 backdrop-blur-md border-t border-gray-100 px-6 py-3 flex justify-between items-center z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]"
            >
                <Link
                    href="/chef"
                    class="flex flex-col items-center gap-1 transition-all duration-300 {$page.url ===
                    '/chef'
                        ? 'text-orange-500 scale-110'
                        : 'text-gray-400 hover:text-gray-600'}"
                >
                    <div class="relative">
                        <i class="fa-solid fa-gauge-high text-xl"></i>
                        {#if $page.url === "/chef"}
                            <span
                                class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-orange-500 rounded-full"
                            ></span>
                        {/if}
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider"
                        >Dashboard</span
                    >
                </Link>

                <Link
                    href="/chef/orders"
                    class="flex flex-col items-center gap-1 transition-all duration-300 {$page.url ===
                    '/chef/orders'
                        ? 'text-orange-500 scale-110'
                        : 'text-gray-400 hover:text-gray-600'}"
                >
                    <div class="relative">
                        <i class="fa-solid fa-clipboard-list text-xl"></i>
                        {#if $page.url === "/chef/orders"}
                            <span
                                class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-orange-500 rounded-full"
                            ></span>
                        {/if}
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider"
                        >Pesanan</span
                    >
                </Link>

                <Link
                    href="/chef/report"
                    class="flex flex-col items-center gap-1 transition-all duration-300 {$page.url ===
                    '/chef/report'
                        ? 'text-orange-500 scale-110'
                        : 'text-gray-400 hover:text-gray-600'}"
                >
                    <div class="relative">
                        <i class="fa-solid fa-wallet text-xl"></i>
                        {#if $page.url === "/chef/report"}
                            <span
                                class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-orange-500 rounded-full"
                            ></span>
                        {/if}
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider"
                        >Laporan</span
                    >
                </Link>
            </nav>
        {/if}
    </div>
</div>

<Toast />
