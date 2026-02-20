<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import RadioInput from "@/Lib/Admin/Components/Ui/RadioInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type DiscountData = {
        id: string;
        name: string;
        type: string;
        scope: string;
        value_type: string | null;
        value: string | null;
        start_at: string | null;
        end_at: string | null;
        is_active: boolean;
        created_at: string | null;
        updated_at: string | null;
        items?: Array<{
            id: string;
            min_qty_buy: number;
            free_product_id: string | null;
            free_qty_get: number;
            custom_value: string | null;
            itemable_type: string;
            itemable: { id: string; name: string; type: string } | null;
        }> | null;
    } | null;

    let discount = $derived($page.props.discount as DiscountData);
    let isEdit = $derived(discount !== null);

    type ProductItem = { id: string; name: string; sku?: string | null };
    let products = $derived(($page.props.products ?? []) as ProductItem[]);
    const productOptions = $derived(
        products.map((p) => ({
            value: String(p.id),
            label: p.sku ? `${p.name} (${p.sku})` : p.name,
        })),
    );

    type DiscountItemFormRow = {
        itemable_type: string;
        itemable_id: string;
        min_qty_buy: string;
        is_multiple: string;
        free_product_id: string;
        free_qty_get: string;
        custom_value: string;
    };

    const form = useForm(
        untrack(() => ({
            name: discount?.name ?? "",
            type: discount?.type ?? "basic",
            scope: discount?.scope ?? "global",
            value_type: discount?.value_type ?? null,
            value: discount?.value ?? "",
            start_at: discount?.start_at?.slice(0, 10) ?? "",
            end_at: discount?.end_at?.slice(0, 10) ?? "",
            is_active: discount?.is_active ? "1" : "1",
            items:
                (discount?.items ?? null)?.map((it) => ({
                    itemable_type: String(
                        it.itemable_type || "App\\Models\\Product",
                    ),
                    itemable_id: String(it.itemable?.id ?? ""),
                    min_qty_buy: String(it.min_qty_buy ?? 1),
                    is_multiple: String((it as any)?.is_multiple ? "1" : "0"),
                    free_product_id: String(it.free_product_id ?? ""),
                    free_qty_get: String(it.free_qty_get ?? 0),
                    custom_value: String(it.custom_value ?? ""),
                })) ?? ([] as DiscountItemFormRow[]),
        })),
    );

    $effect(() => {
        const type = $form.type;
        if (type === "bogo") {
            if (untrack(() => $form.value_type) !== null) {
                $form.value_type = null;
            }
            if (untrack(() => $form.value) !== "") {
                $form.value = "";
            }
        } else {
            if (!untrack(() => $form.value_type)) {
                $form.value_type = "percentage";
            }
        }
    });

    function addItemRow() {
        const row: DiscountItemFormRow = {
            itemable_type: "App\\Models\\Product",
            itemable_id: "",
            min_qty_buy: "1",
            is_multiple: "0",
            free_product_id: "",
            free_qty_get: "0",
            custom_value: "",
        };
        $form.items = [...$form.items, row];
    }
    function removeItemRow(idx: number) {
        const next = [...$form.items];
        next.splice(idx, 1);
        $form.items = next;
    }

    function backToList() {
        router.visit("/discounts");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && discount) {
            $form.put(`/discounts/${discount.id}`, {
                onSuccess: () => {
                    router.visit("/discounts");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/discounts", {
                onSuccess: () => {
                    router.visit("/discounts");
                },
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Diskon | {siteName(
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
                {isEdit ? "Edit" : "Tambah"} Diskon
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi diskon"
                    : "Tambahkan diskon penjualan baru"}
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
                form="discount-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Diskon
            </Button>
        </div>
    </header>

    <form id="discount-form" class="space-y-4" onsubmit={submitForm}>
        <Card title="Informasi Utama" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <TextInput
                        id="name"
                        name="name"
                        label="Nama"
                        placeholder="Nama diskon (unik)"
                        bind:value={$form.name}
                        error={$form.errors.name}
                        required
                    />
                    <Select
                        id="type"
                        name="type"
                        label="Tipe Diskon"
                        bind:value={$form.type}
                        error={$form.errors.type}
                        options={[
                            { value: "basic", label: "Diskon Nilai" },
                            {
                                value: "bogo",
                                label: "BOGO (Beli Gratis)",
                            },
                        ]}
                    />
                    <Select
                        id="scope"
                        name="scope"
                        label="Ruang Lingkup"
                        bind:value={$form.scope}
                        error={$form.errors.scope}
                        options={[
                            { value: "global", label: "Global" },
                            { value: "specific", label: "Spesifik" },
                        ]}
                    />
                    {#if $form.type === "basic"}
                        <Select
                            id="value_type"
                            name="value_type"
                            label="Jenis Nilai"
                            bind:value={$form.value_type as string}
                            error={$form.errors.value_type}
                            options={[
                                {
                                    value: "percentage",
                                    label: "Persentase (%)",
                                },
                                {
                                    value: "nominal",
                                    label: "Nominal (Rp)",
                                },
                            ]}
                        />
                        <TextInput
                            id="value"
                            name="value"
                            label="Nilai"
                            type="number"
                            placeholder={$form.value_type === "percentage"
                                ? "0-100"
                                : "Rupiah"}
                            bind:value={$form.value}
                            error={$form.errors.value}
                        />
                    {/if}
                    <DateInput
                        id="start_at"
                        name="start_at"
                        label="Mulai"
                        bind:value={$form.start_at}
                        error={$form.errors.start_at}
                        required
                    />
                    <DateInput
                        id="end_at"
                        name="end_at"
                        label="Selesai"
                        bind:value={$form.end_at}
                        error={$form.errors.end_at}
                        required
                    />
                    <Select
                        id="is_active"
                        name="is_active"
                        label="Status"
                        bind:value={$form.is_active}
                        error={$form.errors.is_active}
                        options={[
                            { value: "1", label: "Aktif" },
                            { value: "0", label: "Tidak Aktif" },
                        ]}
                    />
                </div>
            {/snippet}
        </Card>
        {#if $form.scope === "specific"}
            <Card title="Target Produk" collapsible={false}>
                {#snippet actions()}
                    <Button
                        variant="success"
                        icon="fa-solid fa-plus"
                        type="button"
                        disabled={$form.processing}
                        onclick={addItemRow}>Tambah Item</Button
                    >
                {/snippet}
                {#snippet children()}
                    <div class="space-y-4">
                        {#if $form.items.length === 0}
                            <div
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                Belum ada item. Klik "Tambah Item" untuk
                                menambahkan produk.
                            </div>
                        {/if}
                        {#each $form.items as it, i}
                            <div
                                class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start"
                            >
                                <div class="md:col-span-6">
                                    <Select
                                        id={"item_product_" + i}
                                        name={"items[" + i + "][itemable_id]"}
                                        label="Produk"
                                        options={productOptions}
                                        searchable={true}
                                        bind:value={it.itemable_id}
                                        error={$form.errors[
                                            "items." + i + ".itemable_id"
                                        ]}
                                        onchange={(val: string | number) => {
                                            $form.items = $form.items.map(
                                                (row, idx) =>
                                                    idx === i
                                                        ? {
                                                              ...row,
                                                              itemable_id:
                                                                  String(
                                                                      val ??
                                                                          row.itemable_id,
                                                                  ),
                                                              itemable_type:
                                                                  "App\\Models\\Product",
                                                          }
                                                        : row,
                                            );
                                        }}
                                        required
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <TextInput
                                        id={"item_min_qty_" + i}
                                        name={"items[" + i + "][min_qty_buy]"}
                                        label="Minimal Beli"
                                        type="number"
                                        min={1}
                                        bind:value={it.min_qty_buy}
                                        error={$form.errors[
                                            "items." + i + ".min_qty_buy"
                                        ]}
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <RadioInput
                                        id={"item_is_multiple_" + i}
                                        name={"items[" + i + "][is_multiple]"}
                                        label="Berlaku Kelipatan"
                                        options={[
                                            { value: "0", label: "Tidak" },
                                            { value: "1", label: "Ya" },
                                        ]}
                                        bind:value={it.is_multiple}
                                        error={$form.errors[
                                            "items." + i + ".is_multiple"
                                        ]}
                                        layout="horizontal"
                                    />
                                </div>
                                <div
                                    class="md:col-span-2 flex items-end justify-end"
                                >
                                    <div>
                                        <div
                                            class="block mb-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300"
                                        >
                                            Aksi
                                        </div>
                                        <Button
                                            variant="danger"
                                            icon="fa-solid fa-trash"
                                            disabled={$form.processing}
                                            onclick={() => removeItemRow(i)}
                                            >Hapus</Button
                                        >
                                    </div>
                                </div>
                                {#if $form.type === "bogo"}
                                    <div class="md:col-span-8">
                                        <Select
                                            id={"item_free_product_" + i}
                                            name={"items[" +
                                                i +
                                                "][free_product_id]"}
                                            label="Produk Gratis"
                                            options={productOptions}
                                            searchable={true}
                                            bind:value={it.free_product_id}
                                            error={$form.errors[
                                                "items." +
                                                    i +
                                                    ".free_product_id"
                                            ]}
                                        />
                                    </div>
                                    <div class="md:col-span-4">
                                        <TextInput
                                            id={"item_free_qty_" + i}
                                            name={"items[" +
                                                i +
                                                "][free_qty_get]"}
                                            label="Jumlah Gratis"
                                            type="number"
                                            min={0}
                                            bind:value={it.free_qty_get}
                                            error={$form.errors[
                                                "items." + i + ".free_qty_get"
                                            ]}
                                        />
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/snippet}
            </Card>
        {/if}
    </form>
</section>
