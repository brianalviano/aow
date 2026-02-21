<script lang="ts">
    import { page, useForm, router } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import AuthLeftPanel from "@/Lib/Admin/Components/Auth/AuthLeftPanel.svelte";
    const currentYear = new Date().getFullYear();
    const form = useForm({ email: "" });
    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/admin/forgot-password", {
            preserveScroll: true,
            preserveState: (p: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(p.props.errors ?? {}).length > 0,
            replace: false,
        });
    }
</script>

<svelte:head>
    <title>Lupa Kata Sandi | {name($page.props.settings)}</title>
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
                                Lupa Kata Sandi
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-400">
                                Masukkan email untuk menerima tautan reset kata
                                sandi.
                            </p>
                        </div>
                        <form
                            class="space-y-4 lg:space-y-6"
                            onsubmit={handleSubmit}
                        >
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
                            {#if $form.recentlySuccessful}
                                <div
                                    class="p-3 bg-green-50 rounded-lg border border-green-200 dark:bg-green-950/20 dark:border-green-900/30"
                                    role="status"
                                >
                                    <p
                                        class="text-sm text-green-800 dark:text-green-300"
                                    >
                                        Kami sudah mengirimkan tautan reset ke
                                        email Anda.
                                    </p>
                                </div>
                            {/if}
                            {#if $form.hasErrors && !$form.errors.email}
                                <div
                                    class="p-3 bg-red-50 rounded-lg border border-red-200 dark:bg-red-950/20 dark:border-red-900/30"
                                    role="alert"
                                >
                                    <p
                                        class="text-sm text-red-800 dark:text-red-300"
                                    >
                                        Terjadi kesalahan. Silakan coba lagi.
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
                                    icon="fa-solid fa-paper-plane"
                                >
                                    Kirim Tautan
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
