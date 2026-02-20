<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type WarehouseData = {
        id: string;
        name: string;
        code: string;
        address: string | null;
        is_central: boolean;
        phone: string | null;
        is_active: boolean;
    };

    let warehouse = $derived($page.props.warehouse as WarehouseData | null);
    let isEdit = $derived(warehouse !== null);

    const form = useForm(
        untrack(() => ({
            name: warehouse?.name ?? "",
            code: warehouse?.code ?? "",
            address: warehouse?.address ?? "",
            is_central: warehouse?.is_central ?? false,
            phone: warehouse?.phone ?? "",
            is_active: warehouse?.is_active ?? true,
        })),
    );

    function backToList() {
        router.visit("/warehouses");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && warehouse) {
            $form.put(`/warehouses/${warehouse.id}`, {
                onSuccess: () => {
                    router.visit("/warehouses");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/warehouses", {
                onSuccess: () => {
                    router.visit("/warehouses");
                },
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Gudang | {siteName(
            $page.props.settings,
        )}</title
    >
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Gudang
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit ? "Perbarui informasi gudang" : "Tambahkan gudang baru"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant={isEdit ? "warning" : "success"}
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="warehouse-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Gudang
            </Button>
        </div>
    </header>

    <form id="warehouse-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Informasi Gudang" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama"
                                placeholder="Nama gudang"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                                disabled={Boolean(
                                    isEdit && warehouse?.is_central,
                                )}
                            />
                            <TextInput
                                id="code"
                                name="code"
                                label="Kode"
                                placeholder="Kode unik gudang"
                                bind:value={$form.code}
                                error={$form.errors.code}
                                required
                            />
                            <TextInput
                                id="phone"
                                name="phone"
                                label="Telepon"
                                placeholder="08xxxxxxxxxx"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                            />
                            <div class="flex items-center gap-6">
                                <Checkbox
                                    id="is_central"
                                    name="is_central"
                                    label="Gudang Pusat"
                                    bind:checked={$form.is_central}
                                />
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    label="Aktif"
                                    bind:checked={$form.is_active}
                                />
                            </div>
                            <div class="md:col-span-2">
                                <TextArea
                                    id="address"
                                    name="address"
                                    label="Alamat"
                                    placeholder="Alamat lengkap"
                                    error={$form.errors.address}
                                    bind:value={$form.address}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
