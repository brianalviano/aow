<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface TestimonialTemplate {
        id: string;
        customer_name: string;
        rating: number;
        content: string;
        is_active: boolean;
    }

    let template = $derived(
        ($page.props.template as TestimonialTemplate | null) ?? null,
    );

    let isEditMode = $derived(!!template);

    const DEFAULT_FORM_STATE = {
        customer_name: "",
        rating: 5,
        content: "",
        is_active: true,
    };

    const form = useForm(
        untrack(() => ({
            customer_name:
                template?.customer_name ?? DEFAULT_FORM_STATE.customer_name,
            rating: template?.rating ?? DEFAULT_FORM_STATE.rating,
            content: template?.content ?? DEFAULT_FORM_STATE.content,
            is_active: template?.is_active ?? DEFAULT_FORM_STATE.is_active,
        })),
    );

    function backToIndex() {
        router.visit("/admin/testimonial-templates");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && template) {
            $form.put(`/admin/testimonial-templates/${template.id}`, {
                preserveScroll: true,
            });
        } else {
            $form.post("/admin/testimonial-templates", {
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Template Testimoni | {getSettingName(
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
                {isEditMode
                    ? "Edit Template Testimoni"
                    : "Tambah Template Testimoni"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} template testimoni
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
                form="testimonial-template-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="testimonial-template-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <Card title="Konten Testimoni" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="customer_name"
                                name="customer_name"
                                label="Nama Customer"
                                placeholder="Contoh: Budi Sudarsono"
                                bind:value={$form.customer_name}
                                error={$form.errors.customer_name}
                                required
                            />

                            <div class="space-y-1">
                                <label
                                    for="content"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Isi Testimoni <span class="text-red-500"
                                        >*</span
                                    >
                                </label>
                                <textarea
                                    id="content"
                                    name="content"
                                    rows="5"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 text-sm focus:ring-primary focus:border-primary dark:bg-gray-800 dark:text-white"
                                    placeholder="Masukkan isi testimoni..."
                                    bind:value={$form.content}
                                    required
                                ></textarea>
                                {#if $form.errors.content}
                                    <p class="text-xs text-red-500 mt-1">
                                        {$form.errors.content}
                                    </p>
                                {/if}
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Pengaturan" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-6">
                            <div>
                                <TextInput
                                    id="rating"
                                    name="rating"
                                    label="Rating (1-5)"
                                    type="number"
                                    min={1}
                                    max={5}
                                    placeholder="5"
                                    value={$form.rating.toString()}
                                    oninput={(e) => {
                                        if (
                                            e &&
                                            typeof e === "object" &&
                                            "numericValue" in e &&
                                            e.numericValue !== null
                                        ) {
                                            $form.rating = e.numericValue;
                                        } else if (
                                            e &&
                                            typeof e === "object" &&
                                            "target" in e
                                        ) {
                                            $form.rating = Number(
                                                (e.target as HTMLInputElement)
                                                    .value,
                                            );
                                        }
                                    }}
                                    error={$form.errors.rating}
                                    required
                                />
                            </div>

                            <Checkbox
                                id="is_active"
                                name="is_active"
                                label="Aktifkan Template"
                                bind:checked={$form.is_active}
                                error={$form.errors.is_active}
                            />
                            <p class="text-xs text-gray-500 italic">
                                Template yang aktif akan muncul secara acak pada
                                produk yang mengaktifkan manipulasi data.
                            </p>
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
