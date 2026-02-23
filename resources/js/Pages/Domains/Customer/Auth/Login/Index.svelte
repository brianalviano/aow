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
    <title>Masuk | {appName($page.props.settings)}</title>
</svelte:head>

<div>
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
        <h1 class="font-bold text-lg leading-tight text-gray-900">Masuk</h1>
        <div class="w-8"></div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col p-4">
        <!-- Header area -->
        <div class="mb-6 pt-4">
            <h2 class="text-2xl font-bold text-gray-900">Selamat Datang</h2>
            <p class="text-sm text-gray-500 mt-1">
                Silahkan masuk ke akun Anda untuk melanjutkan.
            </p>
        </div>

        <!-- Login Form -->
        <form class="space-y-4 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <div class="space-y-6">
                <TextInput
                    id="login"
                    name="login"
                    label="Email/Username/No HP"
                    type="text"
                    autofocus={true}
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.login}
                    placeholder="Cth: 0812xxxxxx"
                    error={$form.errors.login}
                />

                <!-- Password field -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label
                            for="password"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                        >
                            Kata Sandi
                        </label>
                        <Link
                            href="/forgot-password"
                            class="text-xs font-medium text-blue-600 hover:text-blue-700 transition"
                            tabindex={$form.processing ? -1 : 0}
                        >
                            Lupa Sandi?
                        </Link>
                    </div>
                    <TextInput
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required={true}
                        disabled={$form.processing}
                        bind:value={$form.password}
                        placeholder="••••••••"
                        error={$form.errors.password}
                    />
                </div>

                <!-- Remember checkbox -->
                <div>
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
            </div>

            <div class="text-center text-sm text-gray-600 mt-2">
                Belum punya akun?
                <Link
                    href="/register"
                    class="font-semibold text-blue-600 hover:text-blue-700"
                >
                    Daftar Sekarang
                </Link>
            </div>
        </form>

        <!-- Footer -->
        <div class="mt-auto pt-8 text-center pb-4">
            <p class="text-xs font-medium text-gray-400">
                © {currentYear}
                {appName($page.props.settings)}
            </p>
        </div>
    </main>
</div>
