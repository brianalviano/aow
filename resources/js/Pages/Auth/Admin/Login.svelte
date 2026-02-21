<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import AuthLeftPanel from "@/Lib/Admin/Components/Auth/AuthLeftPanel.svelte";
    const currentYear = new Date().getFullYear();

    const form = useForm({
        login: "",
        password: "",
        remember: false,
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form
            .transform(
                (data: {
                    email: string;
                    password: string;
                    remember: boolean;
                }) => ({
                    ...data,
                    remember: true,
                }),
            )
            .post("/admin/login", {
                preserveScroll: true,
                preserveState: (page: {
                    props: { errors?: Record<string, string[]> };
                }) => Object.keys(page.props.errors ?? {}).length > 0,
                replace: false,
                onError: (errors: Record<string, string[]>) => {
                    $form.reset("password");
                },
            });
    }
</script>

<svelte:head>
    <title>Login | {siteName($page.props.settings)}</title>
</svelte:head>

<div class="relative h-screen overflow-hidden bg-slate-200 dark:bg-neutral-900">
    <!-- Main Container -->
    <div class="relative flex h-screen">
        <AuthLeftPanel />
        <!-- Right Panel: Login Form - Full width on mobile, partial on desktop -->
        <div
            class="relative flex min-h-screen w-full items-center justify-center p-4 lg:w-2/5 lg:p-8 xl:p-16"
        >
            <!-- Login Card - Properly centered -->
            <div class="w-full max-w-md relative z-10">
                <!-- Glassmorphism backdrop -->
                <div class="relative">
                    <!-- Main card -->
                    <Card>
                        <!-- Header -->
                        <div class="mb-6">
                            <h2
                                class="mb-1 text-3xl font-bold text-gray-800 dark:text-gray-100"
                            >
                                Login
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-400">
                                Silahkan Masukkan E-Mail dan Kata Sandi.
                            </p>
                        </div>

                        <!-- Login Form -->
                        <form
                            class="space-y-4 lg:space-y-6"
                            onsubmit={handleSubmit}
                        >
                            <div class="space-y-4 lg:space-y-6">
                                <TextInput
                                    id="login"
                                    name="login"
                                    label="Email/Username/No HP"
                                    type="text"
                                    autofocus={true}
                                    required={true}
                                    disabled={$form.processing}
                                    bind:value={$form.login}
                                    placeholder="you@example.com / username / 08xxxxxxxxxx"
                                    error={$form.errors.login}
                                />

                                <!-- Password field -->
                                <div>
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <label
                                            for="password"
                                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                        >
                                            Kata Sandi
                                        </label>
                                        <Link
                                            href="/admin/forgot-password"
                                            class="rounded text-xs font-medium text-[#0060B2] transition hover:text-[#004d8f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0060B2]/50 dark:text-[#0060B2] dark:hover:text-[#00559e] dark:focus-visible:ring-[#0060B2]/50"
                                            tabindex={$form.processing ? -1 : 0}
                                        >
                                            Lupa Kata Sandi?
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
                    </Card>
                </div>

                <!-- Status & Footer -->
                <div class="mt-6 space-y-4 text-center">
                    <!-- Footer -->
                    <p
                        class="text-xs font-medium text-slate-700 dark:text-white"
                    >
                        © {currentYear}
                        {siteName($page.props.settings)}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes float {
        0%,
        100% {
            transform: translateY(0) translateX(0);
        }
        50% {
            transform: translateY(-20px) translateX(10px);
        }
    }

    @keyframes float-delayed {
        0%,
        100% {
            transform: translateY(0) translateX(0);
        }
        50% {
            transform: translateY(20px) translateX(-10px);
        }
    }

    @keyframes float-slow {
        0%,
        100% {
            transform: translateY(0) translateX(0) scale(1);
        }
        50% {
            transform: translateY(-15px) translateX(15px) scale(1.05);
        }
    }

    @keyframes spin-slow {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes pulse-slow {
        0%,
        100% {
            opacity: 0.3;
            transform: scale(1);
        }
        50% {
            opacity: 0.5;
            transform: scale(1.1);
        }
    }

    @keyframes bounce-slow {
        0%,
        100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    @keyframes draw {
        to {
            stroke-dashoffset: 0;
        }
    }

    @keyframes draw-delayed {
        to {
            stroke-dashoffset: 0;
        }
    }
</style>
