<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";

    const currentYear = new Date().getFullYear();
    const dropPoints: { id: string; name: string }[] =
        $page.props.dropPoints || [];

    const dropPointOptions = dropPoints.map((dp) => ({
        value: dp.id,
        label: dp.name,
    }));

    const form = useForm({
        name: "",
        username: "",
        email: "",
        phone: "",
        dropPointId: "",
        schoolClass: "",
        password: "",
        password_confirmation: "",
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/register", {
            preserveScroll: true,
            preserveState: (page: {
                props: { errors?: Record<string, string[]> };
            }) => Object.keys(page.props.errors ?? {}).length > 0,
            replace: false,
            onError: () => {
                $form.reset("password", "password_confirmation");
            },
        });
    }
</script>

<svelte:head>
    <title>Daftar Akun | {appName($page.props.settings)}</title>
</svelte:head>

<div
    class="min-h-screen bg-gray-50 flex flex-col max-w-md mx-auto relative shadow-xl overflow-x-hidden"
>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-white sticky top-0 z-10 border-b border-gray-100"
    >
        <Link
            href="/login"
            class="text-gray-800 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-gray-900">
            Daftar Akun
        </h1>
        <div class="w-8"></div>
        <!-- placeholder for center alignment -->
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col p-4">
        <!-- Header area -->
        <div class="mb-4 pt-2">
            <h2 class="text-2xl font-bold text-gray-900">Buat Akun Baru</h2>
            <p class="text-sm text-gray-500 mt-1">
                Lengkapi form di bawah ini untuk mendaftar.
            </p>
        </div>

        <!-- Register Form -->
        <form class="space-y-4 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <div class="space-y-4">
                <TextInput
                    id="name"
                    name="name"
                    label="Nama Lengkap"
                    type="text"
                    required={true}
                    autofocus={true}
                    disabled={$form.processing}
                    bind:value={$form.name}
                    placeholder="Cth: Rino Nathanael"
                    error={$form.errors.name}
                />

                <TextInput
                    id="email"
                    name="email"
                    label="Email"
                    type="email"
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.email}
                    placeholder="rino@example.com"
                    error={$form.errors.email}
                />

                <TextInput
                    id="phone"
                    name="phone"
                    label="Nomor Telepon/WA"
                    type="text"
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.phone}
                    placeholder="Cth: 08123456789"
                    error={$form.errors.phone}
                />
            </div>

            <div class="space-y-4 pt-2">
                <Select
                    id="dropPointId"
                    name="dropPointId"
                    label="Pilih Drop Point"
                    options={dropPointOptions}
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.dropPointId}
                    error={$form.errors.dropPointId}
                    placeholder="Pilih Drop Point terdekat"
                />

                <TextInput
                    id="schoolClass"
                    name="schoolClass"
                    label="Kelas Sekolah (Opsional)"
                    type="text"
                    required={false}
                    disabled={$form.processing}
                    bind:value={$form.schoolClass}
                    placeholder="Cth: 12 IPA 1"
                    error={$form.errors.schoolClass}
                />
            </div>

            <div class="space-y-4 pt-2">
                <TextInput
                    id="password"
                    name="password"
                    label="Kata Sandi"
                    type="password"
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.password}
                    placeholder="••••••••"
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
                    placeholder="••••••••"
                    error={$form.errors.password_confirmation}
                />
            </div>

            <div class="mt-8 mb-4">
                <Button
                    type="submit"
                    variant="primary"
                    size="normal"
                    fullWidth={true}
                    disabled={$form.processing}
                    loading={$form.processing}
                    icon="fa-solid fa-user-plus"
                >
                    Daftar Sekarang
                </Button>
            </div>

            <div class="text-center text-sm text-gray-600 mt-2">
                Telah memiliki akun?
                <Link
                    href="/login"
                    class="font-semibold text-blue-600 hover:text-blue-700"
                >
                    Masuk Sekarang
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
