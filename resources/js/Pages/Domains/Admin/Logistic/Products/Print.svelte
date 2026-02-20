<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { onMount } from "svelte";
    import {
        printWithLightMode,
        setPrintPage,
        enablePrintView,
    } from "@/Lib/Admin/Utils/print";

    type ProductDetail = {
        id: string;
        name: string;
        sku: string;
    };

    let product = $derived($page.props.product as ProductDetail);
    let barcodePng = $derived(
        ($page.props as any).barcode_png as string | null,
    );
    const columns = 3;
    const rows = 19;
    const labels: number[] = Array.from(
        { length: columns * rows },
        (_, i) => i,
    );

    onMount(() => {
        const cleanupLight = printWithLightMode(100);
        const cleanupPage = setPrintPage({
            size: "A4",
            orientation: "portrait",
            margin: "5mm",
        });
        const cleanupView = enablePrintView();
        return () => {
            cleanupLight();
            cleanupPage();
            cleanupView();
        };
    });
</script>

<svelte:head>
    <title>Label Barcode 33x15mm | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="w-full">
    {#if !barcodePng}
        <div class="w-full flex justify-center items-center h-[50mm]">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tidak ada barcode (SKU kosong).
            </p>
        </div>
    {:else}
        <div class="mx-auto grid grid-cols-3 gap-3 justify-start w-[99mm]">
            {#each labels as _}
                <div class="w-[33mm] h-[15mm] p-0 bg-white">
                    <div
                        class="mx-auto justify-center items-center flex w-full"
                    >
                        <img
                            src={barcodePng}
                            alt="Barcode {product.sku}"
                            class="w-[25mm] h-[12mm] object-contain"
                        />
                    </div>
                    <div
                        class="w-[33mm] text-center text-black text-[4pt] leading-none -mt-[2.5mm] truncate"
                    >
                        {product.sku}
                    </div>
                </div>
            {/each}
        </div>
    {/if}
</section>
