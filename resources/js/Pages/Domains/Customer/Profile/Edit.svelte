<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";

    const { customer } = $page.props as {
        customer: {
            name: string;
            email: string;
            username: string;
        };
    };

    const form = useForm({
        username: customer.username ?? "",
        name: customer.name ?? "",
        email: customer.email ?? "",
        phone: "",
        password: "",
        password_confirmation: "",
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.clearErrors();
        $form.put("/profile", {
            preserveScroll: true,
            onSuccess: () => {
                $form.reset("password", "password_confirmation");
            },
        });
    }
</script>

<svelte:head>
    <title>Ubah Profil | {appName($page.props.settings)}</title>
</svelte:head>

<div
    class="min-h-screen bg-gray-50 flex flex-col max-w-md mx-auto relative shadow-xl overflow-x-hidden"
>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-white sticky top-0 z-10 border-b border-gray-100"
    >
        <Link
            href="/menu"
            class="text-gray-800 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-gray-900">
            Ubah Profil
        </h1>
        <div class="w-8"></div>
    </header>

    <!-- Main Content -->
    <main
        class="flex-1 w-full flex flex-col p-4 bg-white mt-2 border-t border-gray-100"
    >
        <form class="space-y-6 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <div class="space-y-4">
                <TextInput
                    id="username"
                    name="username"
                    label="Username"
                    type="text"
                    required={false}
                    disabled={$form.processing}
                    bind:value={$form.username}
                    error={$form.errors.username}
                />

                <TextInput
                    id="name"
                    name="name"
                    label="Nama Lengkap"
                    type="text"
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.name}
                    error={$form.errors.name}
                />

                <TextInput
                    id="email"
                    name="email"
                    label="Email"
                    type="email"
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.email}
                    error={$form.errors.email}
                />
            </div>

            <div class="pt-4 border-t border-gray-100 space-y-4">
                <h3 class="font-semibold text-gray-800 text-sm">
                    Ubah Kata Sandi (Opsional)
                </h3>
                <TextInput
                    id="password"
                    name="password"
                    label="Kata Sandi Baru"
                    type="password"
                    disabled={$form.processing}
                    bind:value={$form.password}
                    placeholder="Biarkan kosong jika tidak ingin mengubah"
                    error={$form.errors.password}
                />

                <TextInput
                    id="password_confirmation"
                    name="password_confirmation"
                    label="Konfirmasi Kata Sandi Baru"
                    type="password"
                    disabled={$form.processing}
                    bind:value={$form.password_confirmation}
                    placeholder="Biarkan kosong jika tidak ingin mengubah"
                    error={$form.errors.password_confirmation}
                />
            </div>

            <div class="mb-4">
                <Button
                    type="submit"
                    variant="warning"
                    fullWidth={true}
                    disabled={$form.processing || !$form.isDirty}
                    loading={$form.processing}
                    icon="fa-solid fa-save"
                >
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </main>
</div>
