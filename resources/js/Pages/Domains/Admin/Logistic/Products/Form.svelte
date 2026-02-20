<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type Option = { value: string; label: string };
    type IdName = { id: string; name: string };
    type UnitItem = { id: string; code: string; name: string };
    type ParentItem = { id: string; name: string; sku: string };

    type ProductData = {
        id: string;
        name: string;
        sku: string;
        description: string;
        image_url: string | null;
        is_active: boolean;
        product_type: string | null;
        product_variant_type: string | null;
        min_stock: number | null;
        max_stock: number | null;
        is_stock_tracked: boolean;
        weight: number | null;
        category: IdName;
        sub_category: IdName;
        unit: IdName;
        factory: IdName;
        sub_factory: IdName;
        condition: IdName;
        parent: { id: string | null; name: string | null; sku: string | null };
    };

    let product = $derived($page.props.product as ProductData | null);
    let options = $derived(
        $page.props.options as {
            categories: IdName[];
            sub_categories: IdName[];
            units: UnitItem[];
            factories: IdName[];
            sub_factories: IdName[];
            conditions: IdName[];
            parents: ParentItem[];
            product_types: Option[];
            variant_types: Option[];
        },
    );
    let isEdit = $derived(product !== null);
    let isImageViewerOpen = $state(false);

    const form = useForm(
        untrack(() => ({
            name: product?.name ?? "",
            sku: product?.sku ?? "",
            description: product?.description ?? "",
            image: null as File | null,
            is_active: product?.is_active ? "1" : "0",
            product_category_id: product?.category?.id
                ? String(product.category.id)
                : "",
            product_sub_category_id: product?.sub_category?.id
                ? String(product.sub_category.id)
                : "",
            product_unit_id: product?.unit?.id ? String(product.unit.id) : "",
            product_factory_id: product?.factory?.id
                ? String(product.factory.id)
                : "",
            product_sub_factory_id: product?.sub_factory?.id
                ? String(product.sub_factory.id)
                : "",
            product_condition_id: product?.condition?.id
                ? String(product.condition.id)
                : "",
            product_type: product?.product_type ?? "",
            product_variant_type: product?.product_variant_type ?? "",
            parent_product_id: product?.parent?.id
                ? String(product.parent.id)
                : "",
            weight: product?.weight != null ? String(product?.weight) : "",
            min_stock:
                product?.min_stock != null ? String(product?.min_stock) : "0",
            max_stock:
                product?.max_stock != null ? String(product?.max_stock) : "0",
            is_stock_tracked: product?.is_stock_tracked ? "1" : "0",
        })),
    );

    function backToList() {
        router.visit("/products");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && product) {
            $form.put(`/products/${product.id}`, {
                onSuccess: () => {
                    router.visit("/products");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/products", {
                onSuccess: () => {
                    router.visit("/products");
                },
                preserveScroll: true,
            });
        }
    }

    function generateSku(): string {
        const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        const length = 13;
        let out = "";
        const hasCrypto =
            typeof globalThis !== "undefined" &&
            "crypto" in globalThis &&
            typeof globalThis.crypto?.getRandomValues === "function";
        if (hasCrypto) {
            const bytes = new Uint32Array(length);
            globalThis.crypto!.getRandomValues(bytes);
            for (const b of bytes) {
                const idx = b % chars.length;
                out += chars.charAt(idx);
            }
        } else {
            for (let i = 0; i < length; i++) {
                const idx = Math.floor(Math.random() * chars.length);
                out += chars.charAt(idx);
            }
        }
        return out;
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Produk | {siteName(
            $page.props.settings,
        )}</title
    >
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Produk
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit ? "Perbarui informasi produk" : "Tambahkan produk baru"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant={isEdit ? "warning" : "success"}
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="product-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Produk
            </Button>
        </div>
    </header>

    <form id="product-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Informasi Utama" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama"
                                placeholder="Nama produk"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />
                            <TextInput
                                id="sku"
                                name="sku"
                                label="SKU"
                                placeholder="SKU unik"
                                bind:value={$form.sku}
                                error={$form.errors.sku}
                                maxlength={13}
                                rightButton={{
                                    text: "Buat Otomatis",
                                    icon: "fa-solid fa-wand-magic-sparkles",
                                    onclick: () => {
                                        $form.sku = generateSku();
                                    },
                                }}
                                required
                            />
                            <Select
                                id="is_active"
                                name="is_active"
                                label="Status"
                                bind:value={$form.is_active}
                                error={$form.errors.is_active}
                                options={[
                                    { value: "1", label: "Aktif" },
                                    { value: "0", label: "Nonaktif" },
                                ]}
                            />
                        </div>
                    {/snippet}
                </Card>

                <Card title="Klasifikasi & Pabrik" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Select
                                id="product_category_id"
                                name="product_category_id"
                                label="Kategori"
                                bind:value={$form.product_category_id}
                                error={$form.errors.product_category_id}
                                options={[
                                    { value: "", label: "Pilih Kategori" },
                                    ...options.categories.map((x) => ({
                                        value: String(x.id),
                                        label: x.name,
                                    })),
                                ]}
                            />
                            <Select
                                id="product_sub_category_id"
                                name="product_sub_category_id"
                                label="Sub Kategori"
                                bind:value={$form.product_sub_category_id}
                                error={$form.errors.product_sub_category_id}
                                options={[
                                    { value: "", label: "Pilih Sub Kategori" },
                                    ...options.sub_categories.map((x) => ({
                                        value: String(x.id),
                                        label: x.name,
                                    })),
                                ]}
                            />
                            <Select
                                id="product_unit_id"
                                name="product_unit_id"
                                label="Unit"
                                bind:value={$form.product_unit_id}
                                error={$form.errors.product_unit_id}
                                options={[
                                    { value: "", label: "Pilih Unit" },
                                    ...options.units.map((x) => ({
                                        value: String(x.id),
                                        label: `${x.code} - ${x.name}`,
                                    })),
                                ]}
                            />
                            <Select
                                id="product_factory_id"
                                name="product_factory_id"
                                label="Pabrik"
                                bind:value={$form.product_factory_id}
                                error={$form.errors.product_factory_id}
                                options={[
                                    { value: "", label: "Pilih Pabrik" },
                                    ...options.factories.map((x) => ({
                                        value: String(x.id),
                                        label: x.name,
                                    })),
                                ]}
                            />
                            <Select
                                id="product_sub_factory_id"
                                name="product_sub_factory_id"
                                label="Sub Pabrik"
                                bind:value={$form.product_sub_factory_id}
                                error={$form.errors.product_sub_factory_id}
                                options={[
                                    { value: "", label: "Pilih Sub Pabrik" },
                                    ...options.sub_factories.map((x) => ({
                                        value: String(x.id),
                                        label: x.name,
                                    })),
                                ]}
                            />
                            <Select
                                id="product_condition_id"
                                name="product_condition_id"
                                label="Kondisi"
                                bind:value={$form.product_condition_id}
                                error={$form.errors.product_condition_id}
                                options={[
                                    { value: "", label: "Pilih Kondisi" },
                                    ...options.conditions.map((x) => ({
                                        value: String(x.id),
                                        label: x.name,
                                    })),
                                ]}
                            />
                        </div>
                    {/snippet}
                </Card>

                <Card title="Tipe & Relasi" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Select
                                id="product_type"
                                name="product_type"
                                label="Tipe Produk"
                                bind:value={$form.product_type}
                                error={$form.errors.product_type}
                                options={[
                                    { value: "", label: "Pilih Tipe" },
                                    ...options.product_types,
                                ]}
                            />
                            <Select
                                id="product_variant_type"
                                name="product_variant_type"
                                label="Tipe Varian"
                                bind:value={$form.product_variant_type}
                                error={$form.errors.product_variant_type}
                                options={[
                                    { value: "", label: "Pilih Tipe Varian" },
                                    ...options.variant_types,
                                ]}
                            />
                            <Select
                                id="parent_product_id"
                                name="parent_product_id"
                                label="Produk Induk"
                                bind:value={$form.parent_product_id}
                                error={$form.errors.parent_product_id}
                                options={[
                                    { value: "", label: "Pilih Produk Induk" },
                                    ...options.parents.map((x) => ({
                                        value: String(x.id),
                                        label: `${x.sku} - ${x.name}`,
                                    })),
                                ]}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>
            <div class="space-y-6">
                <Card title="Logistik" collapsible={false}>
                    {#snippet children()}
                        <TextInput
                            id="weight"
                            name="weight"
                            label="Berat"
                            type="number"
                            bind:value={$form.weight}
                            error={$form.errors.weight}
                        />
                    {/snippet}
                </Card>

                <Card title="Stok" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="min_stock"
                                name="min_stock"
                                label="Stok Minimum"
                                type="number"
                                bind:value={$form.min_stock}
                                error={$form.errors.min_stock}
                            />
                            <TextInput
                                id="max_stock"
                                name="max_stock"
                                label="Stok Maksimum"
                                type="number"
                                bind:value={$form.max_stock}
                                error={$form.errors.max_stock}
                            />
                            <Select
                                id="is_stock_tracked"
                                name="is_stock_tracked"
                                label="Pantau Stok"
                                bind:value={$form.is_stock_tracked}
                                error={$form.errors.is_stock_tracked}
                                options={[
                                    { value: "1", label: "Ya" },
                                    { value: "0", label: "Tidak" },
                                ]}
                            />
                        </div>
                    {/snippet}
                </Card>

                <Card title="Deskripsi & Media" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <TextArea
                                    id="description"
                                    name="description"
                                    label="Deskripsi"
                                    placeholder="Deskripsi produk"
                                    error={$form.errors.description}
                                    bind:value={$form.description}
                                />
                            </div>
                            <div class="md:col-span-2">
                                <FileUpload
                                    id="image"
                                    name="image"
                                    label="Gambar Produk"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    bind:value={$form.image}
                                    error={$form.errors.image}
                                    uploadText="Pilih atau drag file gambar"
                                    onchange={(files) => {
                                        const f = files[0] ?? null;
                                        $form.image = f;
                                    }}
                                />
                                {#if product?.image_url}
                                    <div
                                        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        File tersimpan:
                                        <button
                                            type="button"
                                            class="underline"
                                            onclick={() =>
                                                (isImageViewerOpen = true)}
                                            aria-label="Lihat gambar produk"
                                        >
                                            Lihat
                                        </button>
                                    </div>
                                    <MediaViewer
                                        bind:isOpen={isImageViewerOpen}
                                        items={product.image_url}
                                        showThumbnails={false}
                                        enableRotate={true}
                                        enableZoom={true}
                                        enableDownload={true}
                                    />
                                {/if}
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
