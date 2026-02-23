<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type RoleInfo = { id: string | null; name: string | null };
    type AccountProps = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        role: RoleInfo;
    };

    let account = $derived(
        ($page.props.account as AccountProps | null) ?? null,
    );

    const form = useForm(
        untrack(() => ({
            email: account?.email ?? "",
            password: "" as string,
            pin: "" as string,
            phone: account?.phone ?? "",
        })),
    );

    function backToDashboard() {
        router.visit("/dashboard");
    }

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
    <title>Pengaturan Akun | {name($page.props.settings)}</title>
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
                        id="phone"
                        name="phone"
                        label="Nomor Telepon"
                        placeholder="08xxxxxxxxxx"
                        bind:value={$form.phone}
                        error={$form.errors.phone}
                    />
                </div>
            {/snippet}
        </Card>
    </form>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        Catatan: Nama, role, dan tanggal masuk tidak dapat diubah.
    </p>
</section>
