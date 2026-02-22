<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface ProductCategory {
        id: string;
        name: string;
    }

    interface Product {
        id: string;
        product_category_id: string;
        name: string;
        description: string;
        price: number;
        image: string;
        image_url: string;
        stock_limit: number | null;
        is_active: boolean;
        sort_order: number;
        created_at: string;
        updated_at: string;
    }

    let product = $derived(
        ($page.props.product as { data: Product } | null)?.data ?? null,
    );

    let productCategories = $derived(
        ($page.props.productCategories as { data: ProductCategory[] })?.data ??
            [],
    );

    let categoryOptions = $derived(
        productCategories.map((cat) => ({
            value: cat.id,
            label: cat.name,
        })),
    );

    let isEditMode = $derived(!!product);

    // Default configuration for form initialization
    const DEFAULT_FORM_STATE = {
        _method: "post",
        product_category_id: "",
        name: "",
        description: "",
        price: 0,
        stock_limit: null as number | null,
        is_active: true,
        sort_order: 0,
        image: null as File | null,
    };

    const form = useForm(
        untrack(() => ({
            _method: product ? "put" : "post",
            product_category_id:
                product?.product_category_id ??
                DEFAULT_FORM_STATE.product_category_id,
            name: product?.name ?? DEFAULT_FORM_STATE.name,
            description: product?.description ?? DEFAULT_FORM_STATE.description,
            price: product?.price ?? DEFAULT_FORM_STATE.price,
            stock_limit: product?.stock_limit ?? DEFAULT_FORM_STATE.stock_limit,
            is_active: product?.is_active ?? DEFAULT_FORM_STATE.is_active,
            sort_order: product?.sort_order ?? DEFAULT_FORM_STATE.sort_order,
            image: DEFAULT_FORM_STATE.image,
        })),
    );

    function backToIndex() {
        router.visit("/admin/products");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && product) {
            $form.post(`/admin/products/${product.id}`, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            $form.post("/admin/products", {
                preserveScroll: true,
                forceFormData: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Produk | {getSettingName(
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
                {isEditMode ? "Edit Produk" : "Tambah Produk"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} produk
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToIndex}>Kembali</Button
            >
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="product-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="product-form" onsubmit={submitForm}>
        <Card title="Informasi Produk" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        {#if product?.image_url}
                            <div class="mb-4">
                                <span
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"
                                    >Gambar Saat Ini</span
                                >
                                <img
                                    src={product.image_url}
                                    alt={product.name}
                                    class="w-32 h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700"
                                />
                            </div>
                        {/if}
                        <FileUpload
                            id="image"
                            name="image"
                            label="Gambar Produk"
                            accept="image/*"
                            bind:value={$form.image}
                            error={$form.errors.image}
                            uploadText="Pilih atau seret gambar ke sini"
                            uploadSubtext="Batas maksimal 2MB. Format: JPG, PNG, WEBP."
                            maxSize={2 * 1024 * 1024}
                        />
                    </div>

                    <div class="md:col-span-2">
                        <TextInput
                            id="name"
                            name="name"
                            label="Nama Produk"
                            placeholder="Contoh: Nasi Goreng Spesial"
                            bind:value={$form.name}
                            error={$form.errors.name}
                            required
                        />
                    </div>

                    <div>
                        <Select
                            id="product_category_id"
                            name="product_category_id"
                            label="Kategori Produk"
                            options={categoryOptions}
                            bind:value={$form.product_category_id}
                            error={$form.errors.product_category_id}
                            required
                        />
                    </div>

                    <div>
                        <TextInput
                            id="price"
                            name="price"
                            label="Harga (Rp)"
                            type="number"
                            placeholder="0"
                            value={$form.price.toString()}
                            oninput={(e) => {
                                if (
                                    e &&
                                    typeof e === "object" &&
                                    "numericValue" in e &&
                                    e.numericValue !== null
                                ) {
                                    $form.price = e.numericValue;
                                } else if (
                                    e &&
                                    typeof e === "object" &&
                                    "target" in e
                                ) {
                                    $form.price = Number(
                                        (e.target as HTMLInputElement).value,
                                    );
                                }
                            }}
                            error={$form.errors.price}
                            required
                        />
                    </div>

                    <div>
                        <TextInput
                            id="stock_limit"
                            name="stock_limit"
                            label="Batas Stok (Opsional)"
                            type="number"
                            placeholder="Biarkan kosong jika tidak ada limit"
                            value={$form.stock_limit !== null
                                ? $form.stock_limit.toString()
                                : ""}
                            oninput={(e) => {
                                if (
                                    e &&
                                    typeof e === "object" &&
                                    "numericValue" in e
                                ) {
                                    $form.stock_limit = e.numericValue;
                                } else if (
                                    e &&
                                    typeof e === "object" &&
                                    "target" in e
                                ) {
                                    const val = (e.target as HTMLInputElement)
                                        .value;
                                    $form.stock_limit =
                                        val === "" ? null : Number(val);
                                }
                            }}
                            error={$form.errors.stock_limit}
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Produk tidak akan bisa dibeli bila melebihi batas
                            stok
                        </p>
                    </div>

                    <div>
                        <TextInput
                            id="sort_order"
                            name="sort_order"
                            label="Urutan Tampilan"
                            type="number"
                            placeholder="0"
                            value={$form.sort_order.toString()}
                            oninput={(e) => {
                                if (
                                    e &&
                                    typeof e === "object" &&
                                    "numericValue" in e &&
                                    e.numericValue !== null
                                ) {
                                    $form.sort_order = e.numericValue;
                                } else if (
                                    e &&
                                    typeof e === "object" &&
                                    "target" in e
                                ) {
                                    $form.sort_order = Number(
                                        (e.target as HTMLInputElement).value,
                                    );
                                }
                            }}
                            error={$form.errors.sort_order}
                            required
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Urutan tampilan produk (angka lebih kecil tampil
                            lebih dulu).
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <TextArea
                            id="description"
                            name="description"
                            label="Deskripsi"
                            placeholder="Jelaskan tentang produk ini"
                            bind:value={$form.description}
                            error={$form.errors.description}
                            rows={4}
                        />
                    </div>

                    <div class="flex items-center mt-6 md:col-span-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            label="Aktif"
                            bind:checked={$form.is_active}
                            error={$form.errors.is_active}
                        />
                        <span
                            class="ml-2 text-sm text-gray-500 dark:text-gray-400"
                        >
                            (Produk yang tidak aktif tidak akan ditampilkan)
                        </span>
                    </div>
                </div>
            {/snippet}
        </Card>
    </form>
</section>
