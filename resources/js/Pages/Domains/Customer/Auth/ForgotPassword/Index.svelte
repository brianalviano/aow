<script lang="ts">
    import { page, useForm, Link, router } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";

    const currentYear = new Date().getFullYear();

    const form = useForm({
        email: "",
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        form.post("/forgot-password", {
            preserveScroll: true,
            preserveState: (page: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(page.props.errors ?? {}).length > 0,
            replace: false,
        });
    }
</script>

<svelte:head>
    <title>Lupa Kata Sandi | {appName(page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-slate-950 sticky top-0 z-10 border-b border-slate-800"
    >
        <Link
            href="/login"
            class="text-slate-100 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-slate-100">Lupa Sandi</h1>
        <div class="w-8"></div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col p-4">
        <div class="mb-6 pt-4">
            <h2 class="text-2xl font-bold text-slate-100">Lupa Kata Sandi?</h2>
            <p class="text-sm text-slate-400 mt-1">
                Masukkan email Anda untuk menerima tautan reset kata sandi.
            </p>
        </div>

        <form class="space-y-6 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <TextInput
                id="email"
                name="email"
                label="Email Terdaftar"
                type="email"
                autofocus={true}
                required={true}
                disabled={form.processing}
                bind:value={form.email}
                placeholder="Cth: user@example.com"
                error={form.errors.email}
            />

            {#if form.recentlySuccessful}
                <div
                    class="p-4 bg-green-900/20 border border-green-800 rounded-xl"
                    role="status"
                >
                    <p class="text-sm text-green-400">
                        Kami sudah mengirimkan tautan reset ke email Anda. Silakan periksa kotak masuk atau folder spam.
                    </p>
                </div>
            {/if}

            <div class="mt-2">
                <Button
                    type="submit"
                    variant="primary"
                    size="normal"
                    fullWidth={true}
                    disabled={form.processing}
                    loading={form.processing}
                    icon="fa-solid fa-paper-plane"
                >
                    Kirim Tautan Reset
                </Button>
            </div>

            <div class="text-center text-sm text-slate-400 mt-4">
                Ingat kata sandi Anda?
                <Link
                    href="/login"
                    class="font-semibold text-[#FFD700] hover:text-yellow-300"
                >
                    Masuk Sekarang
                </Link>
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
