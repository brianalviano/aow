<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";

    const currentYear = new Date().getFullYear();

    const form = useForm({
        token: page.props.token as string,
        email: page.props.email as string,
        password: "",
        passwordConfirmation: "",
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        form.post("/reset-password", {
            preserveScroll: true,
            preserveState: (page: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(page.props.errors ?? {}).length > 0,
            replace: false,
            onFinish: () => {
                form.reset("password", "passwordConfirmation");
            },
        });
    }
</script>

<svelte:head>
    <title>Atur Ulang Kata Sandi | {appName(page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-slate-950 sticky top-0 z-10 border-b border-slate-800"
    >
        <div class="w-8"></div>
        <h1 class="font-bold text-lg leading-tight text-slate-100">Atur Ulang Sandi</h1>
        <div class="w-8"></div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col p-4">
        <div class="mb-6 pt-4">
            <h2 class="text-2xl font-bold text-slate-100">Buat Sandi Baru</h2>
            <p class="text-sm text-slate-400 mt-1">
                Silakan masukkan kata sandi baru Anda.
            </p>
        </div>

        <form class="space-y-6 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <input type="hidden" name="token" value={form.token} />
            
            <TextInput
                id="email"
                name="email"
                label="Email"
                type="email"
                readonly={true}
                required={true}
                disabled={form.processing}
                bind:value={form.email}
                error={form.errors.email}
            />

            <TextInput
                id="password"
                name="password"
                label="Kata Sandi Baru"
                type="password"
                required={true}
                disabled={form.processing}
                bind:value={form.password}
                placeholder="••••••••"
                error={form.errors.password}
            />

            <TextInput
                id="password_confirmation"
                name="password_confirmation"
                label="Konfirmasi Kata Sandi"
                type="password"
                required={true}
                disabled={form.processing}
                bind:value={form.passwordConfirmation}
                placeholder="••••••••"
                error={form.errors.passwordConfirmation}
            />

            <div class="mt-4">
                <Button
                    type="submit"
                    variant="primary"
                    size="normal"
                    fullWidth={true}
                    disabled={form.processing}
                    loading={form.processing}
                    icon="fa-solid fa-key"
                >
                    Simpan Kata Sandi
                </Button>
            </div>
        </form>

        <!-- Footer -->
        <div class="mt-auto pt-8 text-center pb-4">
            <p class="text-xs font-medium text-slate-500">
                © {currentYear}
                {appName(page.props.settings)}
            </p>
        </div>
    </main>
</div>
