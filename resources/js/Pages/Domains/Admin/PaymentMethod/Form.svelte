<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface PaymentMethod {
        id: string;
        name: string;
        category:
            | "bank-transfer"
            | "e-wallet"
            | "virtual-account"
            | "cash"
            | null;
        photo: string | null;
        is_active: boolean;
        type: "manual" | "gateway";
        code: string | null;
        account_number: string | null;
        account_name: string | null;
        payment_guide_id: string | null;
        created_at: string;
        updated_at: string;
    }

    let paymentMethod = $derived(
        ($page.props.paymentMethod as { data: PaymentMethod } | null)?.data ??
            null,
    );

    let isEditMode = $derived(!!paymentMethod);

    const form = useForm(
        untrack(() => ({
            _method: paymentMethod ? "put" : "post",
            name: paymentMethod?.name ?? "",
            category: paymentMethod?.category ?? "bank-transfer",
            type: paymentMethod?.type ?? "manual",
            code: paymentMethod?.code ?? "",
            photo: null as File | null,
            is_active: paymentMethod?.is_active ?? true,
            account_number: paymentMethod?.account_number ?? "",
            account_name: paymentMethod?.account_name ?? "",
            payment_guide_id: paymentMethod?.payment_guide_id ?? "",
        })),
    );

    const categoryOptions = [
        { value: "bank-transfer", label: "Bank Transfer" },
        { value: "e-wallet", label: "E-Wallet" },
        { value: "virtual-account", label: "Virtual Account" },
        { value: "cash", label: "Tunai (Cash)" },
    ];

    const typeOptions = [
        { value: "manual", label: "Manual / Transfer" },
        { value: "gateway", label: "Otomatis (Gateway)" },
    ];

    const paymentGuideOptions = $derived([
        { value: "", label: "- Tanpa Panduan -" },
        ...($page.props.paymentGuides as any[]).map((guide) => ({
            value: guide.id,
            label: guide.name,
        })),
    ]);

    function backToIndex() {
        router.visit("/admin/payment-methods");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && paymentMethod) {
            $form.post(`/admin/payment-methods/${paymentMethod.id}`, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            $form.post("/admin/payment-methods", {
                preserveScroll: true,
                forceFormData: true,
            });
        }
    }
</script>

<svelte:head>
    <title>
        {isEditMode ? "Edit" : "Tambah"} Metode Pembayaran | {getSettingName(
            $page.props.settings,
        )}
    </title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEditMode ? "Edit " : "Tambah "} Metode Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk mengelola metode pembayaran
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToIndex}>Kembali</Button
            >
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="payment-method-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <div>
        <form id="payment-method-form" onsubmit={submitForm}>
            <Card collapsible={false}>
                {#snippet children()}
                    <div class="space-y-6">
                        {#if paymentMethod?.photo}
                            <div class="mb-4">
                                <span
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    Logo Saat Ini
                                </span>
                                <img
                                    src={paymentMethod.photo}
                                    alt={paymentMethod.name}
                                    class="w-24 h-24 object-contain rounded-lg border bg-white p-2 dark:border-gray-700"
                                />
                            </div>
                        {/if}

                        <FileUpload
                            id="photo"
                            name="photo"
                            label="Logo Metode Pembayaran"
                            accept="image/*"
                            bind:value={$form.photo}
                            error={$form.errors.photo}
                            uploadText="Pilih atau seret logo logo di sini"
                            uploadSubtext="Batas maksimal 2MB. Format: JPG, PNG, WEBP."
                            maxSize={2 * 1024 * 1024}
                        />

                        <TextInput
                            id="name"
                            name="name"
                            label="Nama Metode"
                            placeholder="Contoh: Transfer Bank BCA, Cash on Delivery"
                            bind:value={$form.name}
                            error={$form.errors.name}
                            required
                        />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Select
                                id="category"
                                name="category"
                                label="Kategori"
                                options={categoryOptions}
                                bind:value={$form.category}
                                error={$form.errors.category}
                                required
                            />
                        </div>

                        <Select
                            id="type"
                            name="type"
                            label="Tipe Pembayaran"
                            options={typeOptions}
                            bind:value={$form.type}
                            error={$form.errors.type}
                            required
                        />

                        {#if $form.type === "gateway"}
                            <TextInput
                                id="code"
                                name="code"
                                label="Kode Gateway"
                                placeholder="Contoh: bca, gopay, qris"
                                bind:value={$form.code}
                                error={$form.errors.code}
                                required
                            />
                        {/if}

                        {#if $form.type === "manual" && $form.category === "bank-transfer"}
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t dark:border-gray-700"
                            >
                                <TextInput
                                    id="account_number"
                                    name="account_number"
                                    label="Nomor Rekening / E-Wallet"
                                    placeholder="Contoh: 123456789"
                                    bind:value={$form.account_number}
                                    error={$form.errors.account_number}
                                />
                                <TextInput
                                    id="account_name"
                                    name="account_name"
                                    label="Atas Nama (Pemilik)"
                                    placeholder="Contoh: Budi Santoso"
                                    bind:value={$form.account_name}
                                    error={$form.errors.account_name}
                                />
                            </div>
                        {/if}

                        <div
                            class="space-y-2 pt-2 border-t dark:border-gray-700"
                        >
                            <Select
                                id="payment_guide_id"
                                name="payment_guide_id"
                                label="Panduan Pembayaran (Opsional)"
                                options={paymentGuideOptions}
                                bind:value={$form.payment_guide_id}
                                error={$form.errors.payment_guide_id}
                                searchable={true}
                                placeholder="- Tanpa Panduan -"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Pilih panduan instruksi yang akan ditampilkan ke
                                pelanggan saat checkout.
                            </p>
                        </div>

                        <div class="flex items-center pt-2">
                            <Checkbox
                                id="is_active"
                                name="is_active"
                                label="Aktifkan Metode Pembayaran"
                                bind:checked={$form.is_active}
                                error={$form.errors.is_active}
                            />
                        </div>
                    </div>
                {/snippet}
            </Card>
        </form>
    </div>
</section>
