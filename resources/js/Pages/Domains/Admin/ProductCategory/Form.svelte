<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface ProductCategory {
        id: string;
        name: string;
        is_active: boolean;
        sort_order: number;
        created_at: string;
        updated_at: string;
    }

    let productCategory = $derived(
        ($page.props.productCategory as { data: ProductCategory } | null)
            ?.data ?? null,
    );

    let isEditMode = $derived(!!productCategory);

    // Default configuration for form initialization
    const DEFAULT_FORM_STATE = {
        name: "",
        is_active: true,
        sort_order: 0,
    };

    const form = useForm(
        untrack(() => ({
            name: productCategory?.name ?? DEFAULT_FORM_STATE.name,
            is_active:
                productCategory?.is_active ?? DEFAULT_FORM_STATE.is_active,
            sort_order:
                productCategory?.sort_order ?? DEFAULT_FORM_STATE.sort_order,
        })),
    );

    function backToIndex() {
        router.visit("/admin/product-categories");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && productCategory) {
            $form.put(`/admin/product-categories/${productCategory.id}`, {
                preserveScroll: true,
            });
        } else {
            $form.post("/admin/product-categories", {
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Kategori Produk | {getSettingName(
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
                {isEditMode ? "Edit Kategori Produk" : "Tambah Kategori Produk"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} kategori produk
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
                form="product-category-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="product-category-form" onsubmit={submitForm}>
        <Card title="Informasi Kategori" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <TextInput
                            id="name"
                            name="name"
                            label="Nama Kategori"
                            placeholder="Contoh: Makanan, Minuman"
                            bind:value={$form.name}
                            error={$form.errors.name}
                            required
                        />
                    </div>
                    <div>
                        <TextInput
                            id="sort_order"
                            name="sort_order"
                            label="Urutan"
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
                            Urutan tampilan kategori (angka lebih kecil tampil
                            lebih dulu).
                        </p>
                    </div>
                    <div class="flex items-center mt-6">
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
                            (Kategori yang tidak aktif tidak akan ditampilkan)
                        </span>
                    </div>
                </div>
            {/snippet}
        </Card>
    </form>
</section>
