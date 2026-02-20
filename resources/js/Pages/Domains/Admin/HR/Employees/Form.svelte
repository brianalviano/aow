<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type Role = { id: string; name: string };

    type EmployeeData = {
        id: string;
        name: string;
        email: string;
        username: string;
        phone_number: string | null;
        address: string | null;
        birth_date: string | null;
        join_date: string | null;
        gender: string | null;
        role: { id: string | null; name: string | null };
        photo_url: string | null;
    };

    let roles = $derived($page.props.roles as Role[]);
    let employee = $derived($page.props.employee as EmployeeData | null);
    let isEdit = $derived(employee !== null);
    let isKtpViewerOpen = $state(false);

    const form = useForm(
        untrack(() => ({
            name: employee?.name ?? "",
            email: employee?.email ?? "",
            username: employee?.username ?? "",
            password: "",
            pin: "",
            role_id: employee?.role?.id ? String(employee.role.id) : "",
            join_date: employee?.join_date ?? "",
            phone_number: employee?.phone_number ?? "",
            address: employee?.address ?? "",
            birth_date: employee?.birth_date ?? "",
            gender: employee?.gender ?? "",
            photo: null as File | null,
        })),
    );

    function backToList() {
        router.visit("/employees");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEdit && employee) {
            $form.put(`/employees/${employee.id}`, {
                onSuccess: () => {
                    router.visit("/employees");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/employees", {
                onSuccess: () => {
                    router.visit("/employees");
                },
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Karyawan | {siteName(
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
                {isEdit ? "Edit" : "Tambah"} Karyawan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi karyawan"
                    : "Tambahkan karyawan baru"}
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
                form="employee-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Karyawan
            </Button>
        </div>
    </header>

    <form id="employee-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Informasi Karyawan" collapsible={false}>
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
                                id="username"
                                name="username"
                                label="Username"
                                placeholder="Username unik"
                                bind:value={$form.username}
                                error={$form.errors.username}
                                required
                            />
                            <TextInput
                                id="phone_number"
                                name="phone_number"
                                label="Nomor Telepon"
                                placeholder="08xxxxxxxxxx"
                                bind:value={$form.phone_number}
                                error={$form.errors.phone_number}
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
                            <DateInput
                                id="birth_date"
                                name="birth_date"
                                label="Tanggal Lahir"
                                bind:value={$form.birth_date}
                                error={$form.errors.birth_date}
                            />
                            <DateInput
                                id="join_date"
                                name="join_date"
                                label="Tanggal Bergabung"
                                bind:value={$form.join_date}
                                error={$form.errors.join_date}
                            />
                            <Select
                                id="role_id"
                                name="role_id"
                                label="Role"
                                bind:value={$form.role_id}
                                error={$form.errors.role_id}
                                options={[
                                    {
                                        value: "",
                                        label: "Pilih Role (Opsional)",
                                    },
                                    ...roles.map((r) => ({
                                        value: String(r.id),
                                        label: r.name,
                                    })),
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
                                    label="Foto KTP (opsional)"
                                    accept=".jpg,.jpeg,.png"
                                    bind:value={$form.photo}
                                    error={$form.errors.photo}
                                    uploadText="Pilih atau drag file gambar"
                                    onchange={(files) => {
                                        const f = files[0] ?? null;
                                        $form.photo = f;
                                    }}
                                />
                                {#if employee?.photo_url}
                                    <div
                                        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        File tersimpan:
                                        <button
                                            type="button"
                                            class="underline"
                                            onclick={() =>
                                                (isKtpViewerOpen = true)}
                                            aria-label="Lihat foto KTP"
                                        >
                                            Lihat
                                        </button>
                                    </div>
                                    <MediaViewer
                                        bind:isOpen={isKtpViewerOpen}
                                        items={employee.photo_url}
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
                        <div class="space-y-4">
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
                            <TextInput
                                id="pin"
                                name="pin"
                                label={isEdit ? "PIN Baru (opsional)" : "PIN"}
                                type="password"
                                placeholder="Minimal & Maksimal 6 karakter"
                                bind:value={$form.pin}
                                error={$form.errors.pin}
                                required={!isEdit}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
