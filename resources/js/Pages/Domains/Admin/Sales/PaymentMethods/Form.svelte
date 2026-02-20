<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type PaymentMethodData = {
        id: string;
        name: string;
        description: string | null;
        image_url: string | null;
        mdr_percentage: string;
        is_active: boolean;
    } | null;

    let paymentMethod = $derived(
        $page.props.payment_method as PaymentMethodData,
    );
    let isEdit = $derived(paymentMethod !== null);
    let isImageViewerOpen = $state(false);
    let imageMode = $state<"upload" | "link">("link");
    let imageFile = $state<File | null>(null);
    let imageUrlStr = $state<string>("");
    $effect(() => {
        imageUrlStr = paymentMethod?.image_url ?? "";
    });

    const form = useForm(
        untrack(() => ({
            name: paymentMethod?.name ?? "",
            description: paymentMethod?.description ?? "",
            image_url: null as File | string | null,
            mdr_percentage: paymentMethod?.mdr_percentage ?? "0",
            is_active: paymentMethod?.is_active ? "1" : "1",
        })),
    );

    function backToList() {
        router.visit("/payment-methods");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (imageMode === "upload") {
            $form.image_url = imageFile ?? null;
        } else {
            $form.image_url = imageUrlStr ? imageUrlStr : null;
        }
        if (isEdit && paymentMethod) {
            $form.put(`/payment-methods/${paymentMethod.id}`, {
                onSuccess: () => {
                    router.visit("/payment-methods");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/payment-methods", {
                onSuccess: () => {
                    router.visit("/payment-methods");
                },
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Metode Pembayaran | {siteName(
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
                {isEdit ? "Edit" : "Tambah"} Metode Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi metode pembayaran"
                    : "Tambahkan metode pembayaran baru"}
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
                form="pm-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Metode Pembayaran
            </Button>
        </div>
    </header>

    <form id="pm-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card title="Informasi Utama" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama"
                                placeholder="Nama metode pembayaran (unik)"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />
                            <TextInput
                                id="mdr_percentage"
                                name="mdr_percentage"
                                label="MDR (%)"
                                type="number"
                                bind:value={$form.mdr_percentage}
                                error={$form.errors.mdr_percentage}
                                required
                            />
                            <div class="md:col-span-2">
                                <Select
                                    id="is_active"
                                    name="is_active"
                                    label="Status"
                                    bind:value={$form.is_active}
                                    error={$form.errors.is_active}
                                    options={[
                                        { value: "1", label: "Aktif" },
                                        { value: "0", label: "Tidak Aktif" },
                                    ]}
                                />
                            </div>
                            <div class="md:col-span-2">
                                <TextArea
                                    id="description"
                                    name="description"
                                    label="Deskripsi"
                                    placeholder="Deskripsi singkat"
                                    error={$form.errors.description}
                                    bind:value={$form.description}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>
            <div>
                <Card title="Gambar" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <Select
                                id="image_mode"
                                name="image_mode"
                                label="Mode Input"
                                value={imageMode}
                                options={[
                                    { value: "link", label: "Link URL" },
                                    { value: "upload", label: "Upload File" },
                                ]}
                                onchange={(v) =>
                                    (imageMode =
                                        String(v) === "upload"
                                            ? "upload"
                                            : "link")}
                            />
                            {#if imageMode === "upload"}
                                <div class="md:col-span-2">
                                    <FileUpload
                                        id="image_url"
                                        name="image_url"
                                        label="Gambar"
                                        accept=".jpg,.jpeg,.png,.webp,.bmp,.svg"
                                        bind:value={imageFile}
                                        error={$form.errors.image_url}
                                        uploadText="Pilih atau drag file gambar"
                                        onchange={(files) => {
                                            const f = files[0] ?? null;
                                            imageFile = f;
                                        }}
                                    />
                                </div>
                            {:else}
                                <div class="md:col-span-2">
                                    <TextInput
                                        id="image_url"
                                        name="image_url"
                                        label="Link Gambar"
                                        placeholder="https://..."
                                        bind:value={imageUrlStr}
                                        error={$form.errors.image_url}
                                    />
                                </div>
                            {/if}
                            {#if paymentMethod?.image_url}
                                <div class="md:col-span-2">
                                    <div
                                        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        File tersimpan:
                                        <button
                                            type="button"
                                            class="underline"
                                            onclick={() =>
                                                (isImageViewerOpen = true)}
                                            aria-label="Lihat gambar"
                                        >
                                            Lihat
                                        </button>
                                    </div>
                                    <MediaViewer
                                        bind:isOpen={isImageViewerOpen}
                                        items={paymentMethod.image_url}
                                        showThumbnails={false}
                                        enableRotate={true}
                                        enableZoom={true}
                                        enableDownload={true}
                                    />
                                </div>
                            {/if}
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
