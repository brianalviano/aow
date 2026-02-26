<script lang="ts">
    import { page, useForm, Link } from "@inertiajs/svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";

    const typeOptions = [
        { value: "kritik", label: "Kritik" },
        { value: "saran", label: "Saran" },
        { value: "lainnya", label: "Lainnya" },
    ];

    const form = useForm({
        type: "kritik",
        content: "",
    });

    function handleSubmit(e: SubmitEvent) {
        e.preventDefault();
        $form.clearErrors();
        $form.post("/feedback", {
            preserveScroll: true,
            onSuccess: () => {
                $form.reset("content");
            },
        });
    }
</script>

<svelte:head>
    <title>Kritik dan Saran | {appName($page.props.settings)}</title>
</svelte:head>

<div
    class="min-h-screen bg-gray-50 flex flex-col max-w-md mx-auto relative shadow-xl overflow-x-hidden"
>
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
        <h1 class="font-bold text-lg leading-tight text-gray-900">
            Kritik dan Saran
        </h1>
        <div class="w-8"></div>
    </header>

    <!-- Main Content -->
    <main
        class="flex-1 w-full flex flex-col p-6 bg-white mt-2 border-t border-gray-100"
    >
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">
                Bantu kami menjadi lebih baik!
            </h2>
            <p class="text-sm text-gray-600 mt-2">
                Suara Anda sangat berarti bagi kami. Silakan sampaikan kritik,
                saran, atau masukan Anda di sini.
            </p>
        </div>

        <form class="space-y-6 flex-1 flex flex-col" onsubmit={handleSubmit}>
            <div class="space-y-4">
                <Select
                    id="type"
                    name="type"
                    label="Tipe Masukan"
                    options={typeOptions}
                    required={true}
                    disabled={$form.processing}
                    bind:value={$form.type}
                    error={$form.errors.type}
                />

                <TextArea
                    id="content"
                    name="content"
                    label="Pesan Anda"
                    placeholder="Tuliskan kritik atau saran Anda di sini (minimal 10 karakter)..."
                    required={true}
                    rows={8}
                    disabled={$form.processing}
                    bind:value={$form.content}
                    error={$form.errors.content}
                />
            </div>

            <div class="mt-auto pt-6">
                <Button
                    type="submit"
                    variant="warning"
                    fullWidth={true}
                    disabled={$form.processing || !$form.content}
                    loading={$form.processing}
                    icon="fa-solid fa-paper-plane"
                >
                    Kirim Masukan
                </Button>
            </div>
        </form>

        <div class="mt-8 p-4 bg-blue-50 rounded-xl border border-blue-100">
            <div class="flex gap-3">
                <div class="text-blue-600">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <p class="text-xs text-blue-800 leading-relaxed">
                    Setiap masukan yang Anda berikan akan kami tinjau secara
                    berkala untuk meningkatkan kualitas layanan dan produk kami.
                </p>
            </div>
        </div>
    </main>
</div>
