<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";

    type IdName = { id: string | null; name: string | null };

    type ProductDetail = {
        id: string;
        name: string;
        sku: string;
        description: string;
        image_url: string | null;
        weight: number | null;
        is_active: boolean;
        product_type: string | null;
        product_variant_type: string | null;
        min_stock: number | null;
        max_stock: number | null;
        category: IdName;
        sub_category: IdName;
        unit: IdName;
        factory: IdName;
        sub_factory: IdName;
        condition: IdName;
        parent: { id: string | null; name: string | null; sku: string | null };
        created_at: string | null;
        updated_at: string | null;
    };

    let product = $derived($page.props.product as ProductDetail);
    let barcodePng = $derived(($page.props as any).barcode_png as string | null);

    function backToList() {
        router.visit("/products");
    }

    function editProduct() {
        router.visit(`/products/${product.id}/edit`);
    }

    function printBarcode() {
        openCenteredWindow(`/products/${product.id}/print`, {
            width: 960,
            height: 700,
            fallbackWhenBlocked: false,
        });
    }
</script>

<svelte:head>
    <title>Detail Produk | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Produk
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {product.name} - {product.sku}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="primary"
                icon="fa-solid fa-print"
                onclick={printBarcode}>Cetak Barcode</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                onclick={editProduct}>Edit</Button
            >
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Produk" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                SKU
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.sku}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Kategori
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.category?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Sub Kategori
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.sub_category?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Unit
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.unit?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Pabrik
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.factory?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Sub Pabrik
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.sub_factory?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Kondisi
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.condition?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tipe Produk
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.product_type || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tipe Varian
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.product_variant_type || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Produk Induk
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.parent?.sku || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Berat
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.weight ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Status
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.is_active ? "Aktif" : "Nonaktif"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Stok Minimum
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.min_stock ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Stok Maksimum
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.max_stock ?? "-"}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Deskripsi
                            </p>
                            {#if product.description}
                                <p
                                    class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {product.description}
                                </p>
                            {:else}
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada deskripsi.
                                </p>
                            {/if}
                        </div>
                    </div>
                {/snippet}
            </Card>

            <Card title="Informasi Sistem" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dibuat Pada
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.created_at}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {product.updated_at}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>

        <div class="space-y-4">
            <Card title="Gambar Produk" collapsible={false}>
                {#snippet children()}
                    {#if product.image_url}
                        <img
                            class="rounded-lg border border-gray-200 dark:border-gray-700"
                            src={product.image_url}
                            alt="Gambar Produk"
                        />
                        <a
                            class="text-xs text-blue-600 underline dark:text-blue-400"
                            href={product.image_url}
                            target="_blank">Buka di tab baru</a
                        >
                    {:else}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada gambar.
                        </p>
                    {/if}
                {/snippet}
            </Card>
            <Card title="Barcode Produk" collapsible={false}>
                {#snippet children()}
                    {#if barcodePng}
                        <img
                            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-2"
                            src={barcodePng}
                            alt="Barcode SKU"
                        />
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                            <span>SKU: {product.sku}</span>
                        </div>
                    {:else}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada barcode (SKU kosong).
                        </p>
                    {/if}
                {/snippet}
            </Card>
        </div>
    </div>
</section>
