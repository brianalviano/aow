<script lang="ts">
    import { page, useForm } from "@inertiajs/svelte";
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
        form.post("/pic/login", {
            preserveScroll: true,
            preserveState: (page: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(page.props.errors ?? {}).length > 0,
            replace: false,
            onError: () => {
                form.reset("password");
            },
        });
    }
</script>

<svelte:head>
    <title>Pickup Point Login | {appName(page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen p-6 bg-slate-950 text-slate-100">
    <div class="mb-10 pt-12 text-center">
        <div
            class="inline-flex items-center justify-center w-20 h-20 bg-slate-950 rounded-4xl mb-6 shadow-2xl border border-slate-800"
        >
            <i class="fa-solid fa-truck text-[#FFD700] text-3xl"></i>
        </div>
        <h2 class="text-4xl font-black text-slate-100 tracking-tight">
            PIC Portal
        </h2>
        <p class="mt-3 text-slate-400 font-medium">
            Pickup Point Point of Contact
        </p>
    </div>

    <div class="flex-1 max-w-sm mx-auto w-full">
        <form class="space-y-8" onsubmit={handleSubmit}>
            <TextInput
                id="login"
                name="login"
                label="Email atau Nomor HP"
                type="text"
                autofocus={true}
                required={true}
                disabled={form.processing}
                bind:value={form.login}
                placeholder="nama@email.com atau 0812xxxx"
                error={form.errors.login}
            />

            <TextInput
                id="password"
                name="password"
                label="Kata Sandi"
                type="password"
                autocomplete="current-password"
                required={true}
                disabled={form.processing}
                bind:value={form.password}
                placeholder="••••••••"
                error={form.errors.password}
            />

            <div class="flex items-center justify-between">
                <Checkbox
                    id="remember"
                    name="remember"
                    label="Ingat saya"
                    disabled={form.processing}
                    bind:checked={form.remember}
                />
            </div>

            <div class="pt-2">
                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    fullWidth={true}
                    disabled={form.processing}
                    loading={form.processing}
                    icon="fa-solid fa-right-to-bracket"
                    class="bg-[#FFD700] text-slate-900 hover:bg-[#FFC700] font-black shadow-lg shadow-[#FFD700]/10"
                >
                    Masuk
                </Button>
            </div>
        </form>
    </div>

    <footer class="mt-auto py-8 text-center border-t border-slate-900">
        <p class="text-xs text-slate-500 font-bold tracking-widest uppercase">
            &copy; {currentYear}
            {appName(page.props.settings)}
        </p>
    </footer>
</div>
