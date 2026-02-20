<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type SupplierData = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        address: string | null;
        birth_date: string | null;
        gender: string | null;
        is_active: boolean;
        photo_url: string | null;
    } | null;

    let supplier = $derived($page.props.supplier as SupplierData);
    let isEdit = $derived(supplier !== null);
    let isPhotoViewerOpen = $state(false);

    const form = useForm(
        untrack(() => ({
            name: supplier?.name ?? "",
            email: supplier?.email ?? "",
            password: "",
            phone: supplier?.phone ?? "",
            address: supplier?.address ?? "",
            birth_date: supplier?.birth_date ?? "",
            gender: supplier?.gender ?? "",
            is_active: supplier?.is_active ? "1" : "0",
            photo: null as File | null,
        })),
    );

    function backToList() {
        router.visit("/suppliers");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && supplier) {
            $form.put(`/suppliers/${supplier.id}`, {
                onSuccess: () => {
                    router.visit("/suppliers");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/suppliers", {
                onSuccess: () => {
                    router.visit("/suppliers");
                },
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title>
        {isEdit ? "Edit" : "Tambah"} Supplier | {siteName($page.props.settings)}
    </title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Supplier
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi supplier"
                    : "Tambahkan supplier baru"}
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
                form="supplier-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Supplier
            </Button>
        </div>
    </header>

    <form id="supplier-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Informasi Supplier" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama"
                                placeholder="Nama lengkap"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />
                            <TextInput
                                id="email"
                                name="email"
                                label="Email"
                                type="email"
                                placeholder="email@domain.com"
                                bind:value={$form.email}
                                error={$form.errors.email}
                                required
                            />
                            <TextInput
                                id="phone"
                                name="phone"
                                label="Nomor Telepon"
                                placeholder="08xxxxxxxxxx"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                            />
                            <DateInput
                                id="birth_date"
                                name="birth_date"
                                label="Tanggal Lahir"
                                bind:value={$form.birth_date}
                                error={$form.errors.birth_date}
                            />
                            <Select
                                id="gender"
                                name="gender"
                                label="Jenis Kelamin"
                                bind:value={$form.gender}
                                error={$form.errors.gender}
                                options={[
                                    { value: "", label: "Pilih Jenis Kelamin" },
                                    { value: "male", label: "Laki-laki" },
                                    { value: "female", label: "Perempuan" },
                                ]}
                            />
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
                            <div class="md:col-span-2">
                                <TextArea
                                    id="address"
                                    name="address"
                                    label="Alamat"
                                    placeholder="Alamat lengkap"
                                    error={$form.errors.address}
                                    bind:value={$form.address}
                                />
                            </div>
                            <div class="md:col-span-2">
                                <FileUpload
                                    id="photo"
                                    name="photo"
                                    label="Foto (opsional)"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    bind:value={$form.photo}
                                    error={$form.errors.photo}
                                    uploadText="Pilih atau drag file gambar"
                                    onchange={(files) => {
                                        const f = files[0] ?? null;
                                        $form.photo = f;
                                    }}
                                />
                                {#if supplier?.photo_url}
                                    <div
                                        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        File tersimpan:
                                        <button
                                            type="button"
                                            class="underline"
                                            onclick={() =>
                                                (isPhotoViewerOpen = true)}
                                            aria-label="Lihat foto"
                                        >
                                            Lihat
                                        </button>
                                    </div>
                                    <MediaViewer
                                        bind:isOpen={isPhotoViewerOpen}
                                        items={supplier.photo_url}
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

            <div>
                <Card title="Keamanan" collapsible={false}>
                    {#snippet children()}
                        <TextInput
                            id="password"
                            name="password"
                            label={isEdit
                                ? "Password Baru (opsional)"
                                : "Password"}
                            type="password"
                            placeholder="Minimal 8 karakter"
                            bind:value={$form.password}
                            error={$form.errors.password}
                            required={!isEdit}
                        />
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
