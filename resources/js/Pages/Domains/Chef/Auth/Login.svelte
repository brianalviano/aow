<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";

    const currentYear = new Date().getFullYear();

    const form = useForm({
        login: "",
        password: "",
        remember: false,
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/chef/login", {
            preserveScroll: true,
            preserveState: (page: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(page.props.errors ?? {}).length > 0,
            replace: false,
            onError: () => {
                $form.reset("password");
            },
        });
    }
</script>

<svelte:head>
    <title>Chef Login | {appName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen p-4">
    <div class="mb-8 pt-10 text-center">
        <h2 class="text-3xl font-extrabold text-gray-900">Chef Portal</h2>
        <p class="mt-2 text-sm text-gray-600">
            Silahkan masuk ke akun Chef Anda
        </p>
    </div>

    <div class="flex-1">
        <form class="space-y-6" onsubmit={handleSubmit}>
            <TextInput
                id="login"
                name="login"
                label="Email atau Nomor HP"
                type="text"
                autofocus={true}
                required={true}
                disabled={$form.processing}
                bind:value={$form.login}
                placeholder="nama@email.com atau 0812xxxx"
                error={$form.errors.login}
            />

            <TextInput
                id="password"
                name="password"
                label="Kata Sandi"
                type="password"
                autocomplete="current-password"
                required={true}
                disabled={$form.processing}
                bind:value={$form.password}
                placeholder="••••••••"
                error={$form.errors.password}
            />

            <div class="flex items-center justify-between">
                <Checkbox
                    id="remember"
                    name="remember"
                    label="Ingat saya"
                    disabled={$form.processing}
                    bind:checked={$form.remember}
                />
            </div>

            <div>
                <Button
                    type="submit"
                    variant="primary"
                    size="normal"
                    fullWidth={true}
                    disabled={$form.processing}
                    loading={$form.processing}
                    icon="fa-solid fa-right-to-bracket"
                >
                    Masuk
                </Button>
            </div>
        </form>

        <div class="mt-8 text-center">
            <Link
                href="/"
                class="text-sm font-medium text-blue-600 hover:text-blue-500"
            >
                Kembali ke Beranda
            </Link>
        </div>
    </div>

    <footer class="mt-auto py-6 text-center">
        <p class="text-xs text-gray-400">
            &copy; {currentYear}
            {appName($page.props.settings)}
        </p>
    </footer>
</div>
