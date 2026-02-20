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

    type RoleInfo = { id: string | null; name: string | null };
    type AccountProps = {
        id: string;
        name: string;
        email: string;
        phone_number: string | null;
        address: string | null;
        birth_date: string | null;
        join_date: string | null;
        gender: "male" | "female" | null;
        photo_url: string | null;
        role: RoleInfo;
    };

    let account = $derived(
        ($page.props.account as AccountProps | null) ?? null,
    );
    let isKtpViewerOpen = $state(false);

    const form = useForm(
        untrack(() => ({
            email: account?.email ?? "",
            password: "" as string,
            pin: "" as string,
            phone_number: account?.phone_number ?? "",
            address: account?.address ?? "",
            birth_date: account?.birth_date ?? "",
            gender: account?.gender ?? "",
            photo: null as File | null,
        })),
    );

    function backToDashboard() {
        router.visit("/dashboard");
    }

    const genderOptions = [
        { value: "", label: "Tidak ditentukan" },
        { value: "male", label: "Laki-laki" },
        { value: "female", label: "Perempuan" },
    ];

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        $form.put("/account/settings", {
            onSuccess: () => {
                router.visit("/account/settings");
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Pengaturan Akun | {siteName($page.props.settings)}</title>
    <meta name="robots" content="noindex,nofollow" />
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Pengaturan Akun
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola informasi akun pribadi
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToDashboard}>Kembali</Button
            >
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="account-form"
                class="w-full"
            >
                Simpan Perubahan
            </Button>
        </div>
    </header>

    <form id="account-form" onsubmit={submitForm}>
        <Card title="Informasi Utama" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <TextInput
                        id="name"
                        name="name"
                        label="Nama"
                        placeholder="Nama"
                        value={account?.name ?? ""}
                        disabled
                    />
                    <TextInput
                        id="role"
                        name="role"
                        label="Role"
                        placeholder="Role"
                        value={account?.role?.name ?? ""}
                        disabled
                    />
                    <DateInput
                        id="join_date"
                        name="join_date"
                        label="Tanggal Masuk"
                        placeholder="YYYY-MM-DD"
                        value={account?.join_date ?? ""}
                        disabled
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
                        id="password"
                        name="password"
                        label="Password Baru (opsional)"
                        type="password"
                        placeholder="********"
                        bind:value={$form.password}
                        error={$form.errors.password}
                    />
                    <TextInput
                        id="pin"
                        name="pin"
                        label="PIN Baru (opsional)"
                        type="password"
                        placeholder="****"
                        bind:value={$form.pin}
                        error={$form.errors.pin}
                    />
                    <TextInput
                        id="phone_number"
                        name="phone_number"
                        label="Nomor Telepon"
                        placeholder="08xxxxxxxxxx"
                        bind:value={$form.phone_number}
                        error={$form.errors.phone_number}
                    />
                    <div class="md:col-span-2">
                        <TextArea
                            id="address"
                            name="address"
                            label="Alamat"
                            placeholder="Alamat rumah"
                            bind:value={$form.address}
                            error={$form.errors.address}
                        />
                    </div>
                    <DateInput
                        id="birth_date"
                        name="birth_date"
                        label="Tanggal Lahir"
                        placeholder="YYYY-MM-DD"
                        bind:value={$form.birth_date}
                        error={$form.errors.birth_date}
                    />
                    <Select
                        id="gender"
                        name="gender"
                        label="Jenis Kelamin"
                        placeholder="Pilih jenis kelamin"
                        options={genderOptions}
                        bind:value={$form.gender}
                    />
                    <div class="md:col-span-2">
                        <FileUpload
                            id="photo"
                            name="photo"
                            label="Foto KTP (opsional)"
                            accept=".jpg,.jpeg,.png,.webp"
                            bind:value={$form.photo}
                            error={$form.errors.photo}
                            uploadText="Pilih atau drag file gambar"
                            onchange={(files) => {
                                const f = files[0] ?? null;
                                $form.photo = f;
                            }}
                        />
                        {#if account?.photo_url}
                            <div
                                class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                            >
                                File tersimpan:
                                <button
                                    type="button"
                                    class="underline"
                                    onclick={() => (isKtpViewerOpen = true)}
                                    aria-label="Lihat foto KTP"
                                >
                                    Lihat
                                </button>
                            </div>
                            <MediaViewer
                                bind:isOpen={isKtpViewerOpen}
                                items={account?.photo_url ?? ""}
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
    </form>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        Catatan: Nama, role, dan tanggal masuk tidak dapat diubah.
    </p>
</section>
