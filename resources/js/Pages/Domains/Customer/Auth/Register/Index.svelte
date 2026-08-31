<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";

    const currentYear = new Date().getFullYear();

    const form = useForm({
        name: "",
        username: "",
        email: "",
        phone: "",
        address: "",
        school_class: "",
        password: "",
        password_confirmation: "",
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        form.post("/register", {
            preserveScroll: true,
            preserveState: (page: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(page.props.errors ?? {}).length > 0,
            replace: false,
            onError: () => {
                form.reset("password", "password_confirmation");
            },
        });
    }
</script>

<svelte:head>
    <title>Daftar Akun | {appName(page.props.settings)}</title>
</svelte:head>

<div
    class="min-h-screen bg-slate-950 flex flex-col max-w-md mx-auto relative shadow-xl overflow-x-hidden"
>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-slate-950 sticky top-0 z-10 border-b border-slate-800"
    >
        <Link
            href="/menu"
            class="text-slate-300 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-slate-100">
            Daftar Akun
        </h1>
        <div class="w-8"></div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col p-4">
        <!-- Header area -->
        <div class="mb-4 pt-2">
            <h2 class="text-2xl font-bold text-slate-100">Buat Akun Baru</h2>
            <p class="text-sm text-slate-400 mt-1">
                Lengkapi form di bawah ini untuk mendaftar.
            </p>
        </div>

        <!-- Register Form -->
        <form class="space-y-4 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <div class="space-y-6">
                <TextInput
                    id="name"
                    name="name"
                    label="Nama Lengkap"
                    type="text"
                    required={true}
                    autofocus={true}
                    disabled={form.processing}
                    bind:value={form.name}
                    placeholder="Cth: Rino Nathanael"
                    error={form.errors.name}
                />

                <TextInput
                    id="email"
                    name="email"
                    label="Email"
                    type="email"
                    required={true}
                    disabled={form.processing}
                    bind:value={form.email}
                    placeholder="rino@example.com"
                    error={form.errors.email}
                />

                <TextInput
                    id="phone"
                    name="phone"
                    label="Nomor Telepon/WA"
                    type="text"
                    required={true}
                    disabled={form.processing}
                    bind:value={form.phone}
                    placeholder="Cth: 08123456789"
                    error={form.errors.phone}
                />

                <TextInput
                    id="school_class"
                    name="school_class"
                    label="Kelas"
                    type="text"
                    disabled={form.processing}
                    bind:value={form.school_class}
                    placeholder="Cth: 12 IPA 1"
                    error={form.errors.school_class}
                />

                <TextInput
                    id="address"
                    name="address"
                    label="Alamat"
                    type="text"
                    disabled={form.processing}
                    bind:value={form.address}
                    placeholder="Cth: Jl. Merdeka No. 123"
                    error={form.errors.address}
                />

                <TextInput
                    id="password"
                    name="password"
                    label="Kata Sandi"
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
                    bind:value={form.password_confirmation}
                    placeholder="••••••••"
                    error={form.errors.password_confirmation}
                />

                <Button
                    type="submit"
                    variant="primary"
                    size="normal"
                    fullWidth={true}
                    disabled={form.processing}
                    loading={form.processing}
                    icon="fa-solid fa-user-plus"
                >
                    Daftar Sekarang
                </Button>
            </div>

            <div class="text-center text-sm text-slate-400 mt-2">
                Telah memiliki akun?
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
