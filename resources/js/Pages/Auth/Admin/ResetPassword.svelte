<script lang="ts">
    import { page, useForm, router } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import AuthLeftPanel from "@/Lib/Admin/Components/Auth/AuthLeftPanel.svelte";
    type Props = { token: string; email: string };
    let { token, email }: Props = $props();
    const currentYear = new Date().getFullYear();
    const tokenValue = $derived(token);
    const emailValue = $derived(email ?? "");
    const form = useForm(
        untrack(() => ({
            token: tokenValue,
            email: emailValue,
            password: "",
            password_confirmation: "",
        })),
    );
    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/admin/reset-password", {
            preserveScroll: true,
            preserveState: (p: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(p.props.errors ?? {}).length > 0,
            replace: false,
            onError: () => {
                $form.reset("password", "password_confirmation");
            },
        });
    }
</script>

<svelte:head>
    <title>Atur Ulang Kata Sandi | {name($page.props.settings)}</title>
</svelte:head>

<div class="relative h-screen overflow-hidden bg-slate-200 dark:bg-neutral-900">
    <div class="relative flex h-screen">
        <AuthLeftPanel />
        <div
            class="relative flex min-h-screen w-full items-center justify-center p-4 lg:w-2/5 lg:p-8 xl:p-16"
        >
            <div class="w-full max-w-md relative z-10">
                <div class="relative">
                    <Card>
                        <div class="mb-6">
                            <h2
                                class="mb-1 text-3xl font-bold text-gray-800 dark:text-gray-100"
                            >
                                Atur Ulang Kata Sandi
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-400">
                                Masukkan kata sandi baru untuk akun Anda.
                            </p>
                        </div>
                        <form
                            class="space-y-4 lg:space-y-6"
                            onsubmit={handleSubmit}
                        >
                            <input
                                type="hidden"
                                name="token"
                                value={tokenValue}
                            />
                            <TextInput
                                id="email"
                                name="email"
                                label="Email"
                                type="email"
                                required={true}
                                disabled={$form.processing}
                                bind:value={$form.email}
                                placeholder="you@example.com"
                                error={$form.errors.email}
                            />
                            <TextInput
                                id="password"
                                name="password"
                                label="Kata Sandi Baru"
                                type="password"
                                required={true}
                                disabled={$form.processing}
                                bind:value={$form.password}
                                placeholder="Minimal 8 karakter"
                                error={$form.errors.password}
                            />
                            <TextInput
                                id="password_confirmation"
                                name="password_confirmation"
                                label="Konfirmasi Kata Sandi"
                                type="password"
                                required={true}
                                disabled={$form.processing}
                                bind:value={$form.password_confirmation}
                                placeholder="Ulangi kata sandi"
                                error={$form.errors.password_confirmation}
                            />
                            {#if $form.hasErrors && !$form.errors.email && !$form.errors.password}
                                <div
                                    class="p-3 bg-red-50 rounded-lg border border-red-200 dark:bg-red-950/20 dark:border-red-900/30"
                                    role="alert"
                                >
                                    <p
                                        class="text-sm text-red-800 dark:text-red-300"
                                    >
                                        Terjadi kesalahan. Silakan periksa data
                                        Anda.
                                    </p>
                                </div>
                            {/if}
                            <div class="flex gap-3">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    size="normal"
                                    fullWidth={true}
                                    disabled={$form.processing}
                                    loading={$form.processing}
                                    icon="fa-solid fa-key"
                                >
                                    Simpan Kata Sandi
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="normal"
                                    fullWidth={false}
                                    onclick={() => router.visit("/admin/login")}
                                    icon="fa-solid fa-arrow-left"
                                >
                                    Kembali
                                </Button>
                            </div>
                        </form>
                    </Card>
                </div>
                <div class="mt-6 space-y-4 text-center">
                    <p
                        class="text-xs font-medium text-slate-700 dark:text-white"
                    >
                        © {currentYear}
                        {name($page.props.settings)}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
