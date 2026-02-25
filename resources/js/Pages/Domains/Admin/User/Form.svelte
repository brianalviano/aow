<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface Role {
        id: string;
        name: string;
    }

    interface User {
        id: string;
        name: string;
        email: string;
        username: string;
        phone: string | null;
        role_id: string;
        role?: Role;
    }

    let user = $derived(
        ($page.props.user as { data: User } | null)?.data ?? null,
    );

    let roles = $derived(($page.props.roles as { data: Role[] })?.data ?? []);

    let roleOptions = $derived(
        roles.map((role) => ({
            value: role.id,
            label: role.name,
        })),
    );

    let isEditMode = $derived(!!user);

    const form = useForm(
        untrack(() => ({
            _method: user ? "put" : "post",
            name: user?.name ?? "",
            username: user?.username ?? "",
            email: user?.email ?? "",
            password: "",
            role_id: user?.role?.id ?? user?.role_id ?? "",
            phone: user?.phone ?? "",
        })),
    );

    function backToIndex() {
        router.visit("/admin/users");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && user) {
            $form.post(`/admin/users/${user.id}`, {
                preserveScroll: true,
            });
        } else {
            $form.post("/admin/users", {
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} User | {getSettingName(
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
                {isEditMode ? "Edit User" : "Tambah User"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} user admin
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToIndex}
            >
                Kembali
            </Button>
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="user-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="user-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <Card title="Informasi Dasar" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama Lengkap"
                                placeholder="Masukkan nama lengkap"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <TextInput
                                    id="username"
                                    name="username"
                                    label="Username"
                                    placeholder="Masukkan username"
                                    bind:value={$form.username}
                                    error={$form.errors.username}
                                    required
                                />

                                <TextInput
                                    id="email"
                                    name="email"
                                    label="Email"
                                    type="email"
                                    placeholder="Masukkan alamat email"
                                    bind:value={$form.email}
                                    error={$form.errors.email}
                                    required
                                />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <TextInput
                                    id="password"
                                    name="password"
                                    label={isEditMode
                                        ? "Password (Kosongkan jika tidak ingin diubah)"
                                        : "Password"}
                                    type="password"
                                    placeholder="Masukkan password"
                                    bind:value={$form.password}
                                    error={$form.errors.password}
                                    required={!isEditMode}
                                />

                                <TextInput
                                    id="phone"
                                    name="phone"
                                    label="No. Telepon (Opsional)"
                                    placeholder="Masukkan nomor telepon"
                                    bind:value={$form.phone}
                                    error={$form.errors.phone}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div class="md:col-span-1">
                <Card title="Pengaturan" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4">
                            <Select
                                id="role_id"
                                name="role_id"
                                label="Role Access"
                                options={roleOptions}
                                bind:value={$form.role_id}
                                error={$form.errors.role_id}
                                required
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
