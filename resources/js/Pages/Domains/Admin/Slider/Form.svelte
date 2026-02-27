<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface Slider {
        id: string;
        name: string;
        photo: string;
        created_at: string;
        updated_at: string;
    }

    let slider = $derived(
        ($page.props.slider as { data: Slider } | null)?.data ?? null,
    );

    let isEditMode = $derived(!!slider);

    // Default configuration for form initialization
    const DEFAULT_FORM_STATE = {
        _method: "post",
        name: "",
        photo: null as File | null,
    };

    const form = useForm(
        untrack(() => ({
            _method: slider ? "put" : "post",
            name: slider?.name ?? DEFAULT_FORM_STATE.name,
            photo: DEFAULT_FORM_STATE.photo,
        })),
    );

    function backToIndex() {
        router.visit("/admin/sliders");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && slider) {
            $form.post(`/admin/sliders/${slider.id}`, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            $form.post("/admin/sliders", {
                preserveScroll: true,
                forceFormData: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Slider | {getSettingName(
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
                {isEditMode ? "Edit Slider" : "Tambah Slider"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} slider
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
                form="slider-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="slider-form" onsubmit={submitForm}>
        <Card title="Informasi Slider" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4">
                    <div class="w-full">
                        {#if slider?.photo}
                            <div class="mb-4">
                                <span
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"
                                    >Foto Saat Ini</span
                                >
                                <img
                                    src={slider.photo}
                                    alt={slider.name}
                                    class="w-full max-w-md h-48 object-cover rounded-lg border border-gray-200 dark:border-gray-700"
                                />
                            </div>
                        {/if}
                        <FileUpload
                            id="photo"
                            name="photo"
                            label="Foto Slider"
                            accept="image/*"
                            bind:value={$form.photo}
                            error={$form.errors.photo}
                            uploadText="Pilih atau seret foto ke sini"
                            uploadSubtext="Batas maksimal 2MB. Format: JPG, PNG, WEBP."
                            maxSize={2 * 1024 * 1024}
                            required={!isEditMode}
                        />
                    </div>

                    <div class="w-full">
                        <TextInput
                            id="name"
                            name="name"
                            label="Nama Slider"
                            placeholder="Contoh: Promo Ramadhan, Diskon Akhir Tahun"
                            bind:value={$form.name}
                            error={$form.errors.name}
                            required
                        />
                    </div>
                </div>
            {/snippet}
        </Card>
    </form>
</section>
