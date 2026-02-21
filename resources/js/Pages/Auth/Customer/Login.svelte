<script lang="ts">
    import { page, useForm } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";

    const currentYear = new Date().getFullYear();

    const form = useForm({
        login: "",
        password: "",
        remember: false,
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/login", {
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
    <title>Login Customer | {name($page.props.settings)}</title>
</svelte:head>

<div class="relative h-screen overflow-hidden bg-slate-100 dark:bg-neutral-900">
    <div class="relative flex h-screen items-center justify-center p-4">
        <div class="w-full max-w-md">
            <Card>
                <!-- Header -->
                <div class="mb-6 text-center">
                    <div class="mb-4 flex justify-center">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 shadow-lg"
                        >
                            <i class="fa-solid fa-user text-2xl text-white"></i>
                        </div>
                    </div>
                    <h2
                        class="mb-1 text-2xl font-bold text-gray-800 dark:text-gray-100"
                    >
                        Portal Pelanggan
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Masuk dengan email atau username Anda
                    </p>
                </div>

                <!-- Login Form -->
                <form class="space-y-4" onsubmit={handleSubmit}>
                    <TextInput
                        id="login"
                        name="login"
                        label="Email / Username"
                        type="text"
                        autofocus={true}
                        required={true}
                        disabled={$form.processing}
                        bind:value={$form.login}
                        placeholder="you@example.com atau username"
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

                    <Checkbox
                        id="remember"
                        name="remember"
                        label="Ingat saya"
                        disabled={$form.processing}
                        bind:checked={$form.remember}
                    />

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
                </form>
            </Card>

            <p
                class="mt-6 text-center text-xs font-medium text-slate-500 dark:text-slate-400"
            >
                © {currentYear}
                {name($page.props.settings)}
            </p>
        </div>
    </div>
</div>
