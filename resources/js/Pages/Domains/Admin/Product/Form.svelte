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

    interface ProductOptionItem {
        id?: string;
        name: string;
        extra_price: number;
        sort_order: number;
    }

    interface ProductOption {
        id?: string;
        name: string;
        is_required: boolean;
        is_multiple: boolean;
        sort_order: number;
        items: ProductOptionItem[];
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
        options?: ProductOption[];
        manipulation?: {
            fake_sales_count: number;
            fake_testimonials_count: number;
            is_active: boolean;
        };
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
        options: [] as ProductOption[],
        fake_sales_count: 0,
        fake_testimonials_count: 0,
        is_manipulation_active: false,
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
            options: product?.options ?? DEFAULT_FORM_STATE.options,
            fake_sales_count:
                product?.manipulation?.fake_sales_count ??
                DEFAULT_FORM_STATE.fake_sales_count,
            fake_testimonials_count:
                product?.manipulation?.fake_testimonials_count ??
                DEFAULT_FORM_STATE.fake_testimonials_count,
            is_manipulation_active:
                product?.manipulation?.is_active ??
                DEFAULT_FORM_STATE.is_manipulation_active,
        })),
    );

    function backToIndex() {
        router.visit("/admin/products");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        // Ensure sort_order defaults correctly for options and items
        $form.options = $form.options.map((opt, oIndex) => ({
            ...opt,
            sort_order: opt.sort_order || oIndex,
            items: opt.items.map((it, iIndex) => ({
                ...it,
                sort_order: it.sort_order || iIndex,
            })),
        }));

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

    function addOption() {
        $form.options = [
            ...$form.options,
            {
                name: "",
                is_required: false,
                is_multiple: false,
                sort_order: $form.options.length,
                items: [
                    {
                        name: "",
                        extra_price: 0,
                        sort_order: 0,
                    },
                ],
            },
        ];
    }

    function removeOption(index: number) {
        $form.options = $form.options.filter((_, i) => i !== index);
    }

    function addItem(optionIndex: number) {
        const option = $form.options[optionIndex];
        if (!option) return;

        option.items = [
            ...option.items,
            {
                name: "",
                extra_price: 0,
                sort_order: option.items.length,
            },
        ];
        $form.options[optionIndex] = option;
    }

    function removeItem(optionIndex: number, itemIndex: number) {
        const option = $form.options[optionIndex];
        if (!option) return;

        option.items = option.items.filter((_, i) => i !== itemIndex);
        $form.options[optionIndex] = option;
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

        <div class="mt-6"></div>

        <Card title="Opsi Tambahan (Opsional)" collapsible={false}>
            {#snippet actions()}
                <Button
                    variant="primary"
                    size="sm"
                    icon="fa-solid fa-plus"
                    onclick={addOption}
                    type="button"
                >
                    Tambah Opsi
                </Button>
            {/snippet}
            {#snippet children()}
                <div class="space-y-6">
                    {#if $form.options.length === 0}
                        <div
                            class="text-center py-6 text-gray-500 dark:text-gray-400"
                        >
                            Belum ada opsi tambahan. Klik tombol "Tambah Opsi"
                            untuk menambahkan (misal: Ukuran, Toping).
                        </div>
                    {:else}
                        {#each $form.options as option, optionIndex}
                            <div
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative bg-gray-50/50 dark:bg-gray-800/50"
                            >
                                <div class="absolute top-4 right-4">
                                    <Button
                                        variant="danger"
                                        size="sm"
                                        icon="fa-solid fa-trash"
                                        onclick={() =>
                                            removeOption(optionIndex)}
                                        type="button"
                                    />
                                </div>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 pr-12"
                                >
                                    <div>
                                        <TextInput
                                            id={`option_name_${optionIndex}`}
                                            name={`options[${optionIndex}][name]`}
                                            label="Nama Opsi"
                                            placeholder="Misal: Ukuran, Toping"
                                            bind:value={
                                                $form.options![optionIndex]!
                                                    .name
                                            }
                                            error={($form.errors as any)[
                                                `options.${optionIndex}.name`
                                            ]}
                                            required
                                        />
                                    </div>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2 md:pt-8"
                                    >
                                        <Checkbox
                                            id={`option_required_${optionIndex}`}
                                            name={`options[${optionIndex}][is_required]`}
                                            label="Wajib dipilih pembeli"
                                            bind:checked={
                                                $form.options![optionIndex]!
                                                    .is_required
                                            }
                                        />
                                        <Checkbox
                                            id={`option_multiple_${optionIndex}`}
                                            name={`options[${optionIndex}][is_multiple]`}
                                            label="Bisa pilih lebih dari satu"
                                            bind:checked={
                                                $form.options![optionIndex]!
                                                    .is_multiple
                                            }
                                        />
                                    </div>
                                </div>

                                <div
                                    class="ml-0 md:ml-4 border-l-2 border-gray-200 dark:border-gray-700 pl-4 py-2"
                                >
                                    <div
                                        class="flex justify-between items-center mb-4"
                                    >
                                        <h4
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                        >
                                            Daftar Pilihan
                                        </h4>
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            icon="fa-solid fa-plus"
                                            onclick={() => addItem(optionIndex)}
                                            type="button"
                                        >
                                            Tambah Pilihan
                                        </Button>
                                    </div>

                                    {#if ($form.errors as any)[`options.${optionIndex}.items`]}
                                        <div
                                            class="text-sm text-red-500 mt-1 mb-2"
                                        >
                                            {($form.errors as any)[
                                                `options.${optionIndex}.items`
                                            ]}
                                        </div>
                                    {/if}

                                    <div class="space-y-3">
                                        {#each option.items as item, itemIndex}
                                            <div
                                                class="flex flex-col sm:flex-row gap-3 items-start sm:items-center"
                                            >
                                                <div class="flex-1 w-full pl-2">
                                                    <TextInput
                                                        id={`item_name_${optionIndex}_${itemIndex}`}
                                                        name={`options[${optionIndex}][items][${itemIndex}][name]`}
                                                        placeholder="Misal: Besar, Sedang, Ekstra Keju"
                                                        bind:value={
                                                            $form.options![
                                                                optionIndex
                                                            ]!.items![
                                                                itemIndex
                                                            ]!.name
                                                        }
                                                        error={(
                                                            $form.errors as any
                                                        )[
                                                            `options.${optionIndex}.items.${itemIndex}.name`
                                                        ]}
                                                        class="mb-0!"
                                                        required
                                                    />
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <TextInput
                                                        id={`item_price_${optionIndex}_${itemIndex}`}
                                                        name={`options[${optionIndex}][items][${itemIndex}][extra_price]`}
                                                        placeholder="Tambahan harga (Rp)"
                                                        type="number"
                                                        value={$form.options![
                                                            optionIndex
                                                        ]!.items![
                                                            itemIndex
                                                        ]!.extra_price.toString()}
                                                        oninput={(e) => {
                                                            if (
                                                                e &&
                                                                typeof e ===
                                                                    "object" &&
                                                                "numericValue" in
                                                                    e &&
                                                                e.numericValue !==
                                                                    null
                                                            ) {
                                                                $form.options![
                                                                    optionIndex
                                                                ]!.items![
                                                                    itemIndex
                                                                ]!.extra_price =
                                                                    e.numericValue;
                                                            } else if (
                                                                e &&
                                                                typeof e ===
                                                                    "object" &&
                                                                "target" in e
                                                            ) {
                                                                $form.options![
                                                                    optionIndex
                                                                ]!.items![
                                                                    itemIndex
                                                                ]!.extra_price =
                                                                    Number(
                                                                        (
                                                                            e.target as HTMLInputElement
                                                                        ).value,
                                                                    );
                                                            }
                                                        }}
                                                        error={(
                                                            $form.errors as any
                                                        )[
                                                            `options.${optionIndex}.items.${itemIndex}.extra_price`
                                                        ]}
                                                        class="mb-0!"
                                                    />
                                                </div>
                                                <div
                                                    class="w-full sm:w-auto flex justify-end"
                                                >
                                                    <Button
                                                        variant="danger"
                                                        size="sm"
                                                        icon="fa-solid fa-times"
                                                        onclick={() =>
                                                            removeItem(
                                                                optionIndex,
                                                                itemIndex,
                                                            )}
                                                        disabled={option.items
                                                            .length <= 1}
                                                        title={option.items
                                                            .length <= 1
                                                            ? "Minimal satu pilihan"
                                                            : "Hapus pilihan"}
                                                        type="button"
                                                    />
                                                </div>
                                            </div>
                                        {/each}
                                    </div>
                                </div>
                            </div>
                        {/each}
                    {/if}
                </div>
            {/snippet}
        </Card>
        <div class="mt-6"></div>
        <Card title="Manipulasi Data (Social Proof)" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="flex items-center md:col-span-2">
                        <Checkbox
                            id="is_manipulation_active"
                            name="is_manipulation_active"
                            label="Aktifkan Manipulasi Data"
                            bind:checked={$form.is_manipulation_active}
                            error={$form.errors.is_manipulation_active}
                        />
                    </div>

                    {#if $form.is_manipulation_active}
                        <div>
                            <TextInput
                                id="fake_sales_count"
                                name="fake_sales_count"
                                label="Jumlah Pembelian Palsu"
                                type="number"
                                placeholder="0"
                                value={$form.fake_sales_count.toString()}
                                oninput={(e) => {
                                    if (
                                        e &&
                                        typeof e === "object" &&
                                        "numericValue" in e &&
                                        e.numericValue !== null
                                    ) {
                                        $form.fake_sales_count = e.numericValue;
                                    } else if (
                                        e &&
                                        typeof e === "object" &&
                                        "target" in e
                                    ) {
                                        $form.fake_sales_count = Number(
                                            (e.target as HTMLInputElement)
                                                .value,
                                        );
                                    }
                                }}
                                error={$form.errors.fake_sales_count}
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Angka ini akan ditambahkan ke jumlah pembelian
                                asli.
                            </p>
                        </div>

                        <div>
                            <TextInput
                                id="fake_testimonials_count"
                                name="fake_testimonials_count"
                                label="Jumlah Testimoni Palsu"
                                type="number"
                                placeholder="0"
                                value={$form.fake_testimonials_count.toString()}
                                oninput={(e) => {
                                    if (
                                        e &&
                                        typeof e === "object" &&
                                        "numericValue" in e &&
                                        e.numericValue !== null
                                    ) {
                                        $form.fake_testimonials_count =
                                            e.numericValue;
                                    } else if (
                                        e &&
                                        typeof e === "object" &&
                                        "target" in e
                                    ) {
                                        $form.fake_testimonials_count = Number(
                                            (e.target as HTMLInputElement)
                                                .value,
                                        );
                                    }
                                }}
                                error={$form.errors.fake_testimonials_count}
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Akan mengambil testimoni acak dari template.
                            </p>
                        </div>
                    {/if}
                </div>
            {/snippet}
        </Card>
    </form>
</section>
