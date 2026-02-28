<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface Product {
        id: string;
        name: string;
    }

    interface Chef {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        bank_name: string | null;
        account_number: string | null;
        account_name: string | null;
        note: string | null;
        fee_percentage: number;
        address: string | null;
        longitude: number | null;
        latitude: number | null;
        is_active: boolean;
        order_types: ("instant" | "preorder")[];
        products?: Product[];
    }

    let chef = $derived(
        ($page.props.chef as { data: Chef } | null)?.data ?? null,
    );
    let products = $derived(($page.props.products as Product[]) ?? []);

    let isEditMode = $derived(!!chef);

    // Build initial selected product IDs from chef's products
    let initialProductIds = $derived(
        chef?.products?.map((p: Product) => p.id) ?? [],
    );

    const form = useForm(
        untrack(() => ({
            _method: chef ? "put" : "post",
            name: chef?.name ?? "",
            email: chef?.email ?? "",
            password: "",
            phone: chef?.phone ?? "",
            bank_name: chef?.bank_name ?? "",
            account_number: chef?.account_number ?? "",
            account_name: chef?.account_name ?? "",
            note: chef?.note ?? "",
            fee_percentage: chef?.fee_percentage ?? 0,
            address: chef?.address ?? "",
            longitude: chef?.longitude ?? null,
            latitude: chef?.latitude ?? null,
            is_active: chef?.is_active ?? true,
            order_types: chef?.order_types ?? ["instant"],
            product_ids: initialProductIds,
        })),
    );

    // Product search filter
    let productSearch = $state("");
    let filteredProducts = $derived(
        products.filter((p) =>
            p.name.toLowerCase().includes(productSearch.toLowerCase()),
        ),
    );

    function toggleProduct(productId: string) {
        const currentIds = [...$form.product_ids];
        const index = currentIds.indexOf(productId);
        if (index > -1) {
            currentIds.splice(index, 1);
        } else {
            currentIds.push(productId);
        }
        $form.product_ids = currentIds;
    }

    function isProductSelected(productId: string): boolean {
        return $form.product_ids.includes(productId);
    }

    function backToIndex() {
        router.visit("/admin/chefs");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEditMode && chef) {
            $form.post(`/admin/chefs/${chef.id}`, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            $form.post("/admin/chefs", {
                preserveScroll: true,
                forceFormData: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Chef | {getSettingName(
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
                {isEditMode ? "Edit Chef" : "Tambah Chef"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} chef mitra
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
                form="chef-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="chef-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-6">
                <Card title="Informasi Umum" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama Chef"
                                placeholder="Nama lengkap chef"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />

                            <TextInput
                                id="email"
                                name="email"
                                label="Email"
                                type="email"
                                placeholder="email@example.com"
                                bind:value={$form.email}
                                error={$form.errors.email}
                                required
                            />

                            <TextInput
                                id="password"
                                name="password"
                                label={isEditMode
                                    ? "Password (kosongkan jika tidak diubah)"
                                    : "Password"}
                                type="password"
                                placeholder={isEditMode
                                    ? "••••••••"
                                    : "Minimal 8 karakter"}
                                bind:value={$form.password}
                                error={$form.errors.password}
                                required={!isEditMode}
                            />

                            <TextInput
                                id="phone"
                                name="phone"
                                label="Nomor Telepon"
                                placeholder="081234567890"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                            />

                            <TextArea
                                id="address"
                                name="address"
                                label="Alamat"
                                placeholder="Alamat lengkap chef"
                                bind:value={$form.address}
                                error={$form.errors.address}
                                rows={3}
                            />

                            <TextInput
                                id="fee_percentage"
                                name="fee_percentage"
                                label="Persentase Fee Perusahaan (%)"
                                type="number"
                                placeholder="0"
                                value={$form.fee_percentage.toString()}
                                oninput={(e) => {
                                    if (
                                        e &&
                                        typeof e === "object" &&
                                        "target" in e
                                    ) {
                                        $form.fee_percentage = Number(
                                            (e.target as HTMLInputElement)
                                                .value,
                                        );
                                    }
                                }}
                                error={$form.errors.fee_percentage}
                                required
                            />
                            <p class="text-xs text-gray-500 -mt-2!">
                                Persentase fee yang akan dipotong dari penjualan
                                chef (0–100).
                            </p>

                            <div class="space-y-2">
                                <label
                                    for="order_types"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Tipe Pesanan
                                </label>
                                <div class="grid grid-cols-2 gap-4 mt-1">
                                    <label
                                        class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all {$form.order_types.includes(
                                            'instant',
                                        )
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 bg-white dark:bg-gray-900'}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="order_types"
                                            value="instant"
                                            checked={$form.order_types.includes(
                                                "instant",
                                            )}
                                            onchange={(e) => {
                                                const checked = (
                                                    e.target as HTMLInputElement
                                                ).checked;
                                                if (checked) {
                                                    $form.order_types = [
                                                        ...$form.order_types,
                                                        "instant",
                                                    ];
                                                } else {
                                                    $form.order_types =
                                                        $form.order_types.filter(
                                                            (t) =>
                                                                t !== "instant",
                                                        );
                                                }
                                            }}
                                            class="sr-only"
                                        />
                                        <div
                                            class="flex items-center justify-center w-5 h-5 rounded border-2 {$form.order_types.includes(
                                                'instant',
                                            )
                                                ? 'border-primary bg-primary'
                                                : 'border-gray-400 font-bold'}"
                                        >
                                            {#if $form.order_types.includes("instant")}
                                                <i
                                                    class="fa-solid fa-check text-[10px] text-white"
                                                ></i>
                                            {/if}
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-semibold {$form.order_types.includes(
                                                    'instant',
                                                )
                                                    ? 'text-primary'
                                                    : 'text-gray-900 dark:text-white'}"
                                            >
                                                Instant
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                Bisa pesanan langsung
                                            </span>
                                        </div>
                                    </label>

                                    <label
                                        class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all {$form.order_types.includes(
                                            'preorder',
                                        )
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 bg-white dark:bg-gray-900'}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="order_types"
                                            value="preorder"
                                            checked={$form.order_types.includes(
                                                "preorder",
                                            )}
                                            onchange={(e) => {
                                                const checked = (
                                                    e.target as HTMLInputElement
                                                ).checked;
                                                if (checked) {
                                                    $form.order_types = [
                                                        ...$form.order_types,
                                                        "preorder",
                                                    ];
                                                } else {
                                                    $form.order_types =
                                                        $form.order_types.filter(
                                                            (t) =>
                                                                t !==
                                                                "preorder",
                                                        );
                                                }
                                            }}
                                            class="sr-only"
                                        />
                                        <div
                                            class="flex items-center justify-center w-5 h-5 rounded border-2 {$form.order_types.includes(
                                                'preorder',
                                            )
                                                ? 'border-primary bg-primary'
                                                : 'border-gray-400'}"
                                        >
                                            {#if $form.order_types.includes("preorder")}
                                                <i
                                                    class="fa-solid fa-check text-[10px] text-white"
                                                ></i>
                                            {/if}
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-semibold {$form.order_types.includes(
                                                    'preorder',
                                                )
                                                    ? 'text-primary'
                                                    : 'text-gray-900 dark:text-white'}"
                                            >
                                                Pre-Order
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                Hanya pesanan terjadwal
                                            </span>
                                        </div>
                                    </label>
                                </div>
                                {#if $form.errors.order_types}
                                    <p class="text-xs text-red-600 mt-1">
                                        {$form.errors.order_types}
                                    </p>
                                {/if}
                            </div>

                            <TextArea
                                id="note"
                                name="note"
                                label="Catatan (Opsional)"
                                placeholder="Catatan internal..."
                                bind:value={$form.note}
                                error={$form.errors.note}
                                rows={2}
                            />

                            <div class="flex items-center pt-2">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    label="Aktif"
                                    bind:checked={$form.is_active}
                                    error={$form.errors.is_active}
                                />
                                <span
                                    class="ml-2 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    (Chef bisa dinonaktifkan sementara)
                                </span>
                            </div>
                        </div>
                    {/snippet}
                </Card>

                <Card title="Informasi Bank" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="bank_name"
                                name="bank_name"
                                label="Nama Bank"
                                placeholder="Contoh: BCA, Mandiri, BRI"
                                bind:value={$form.bank_name}
                                error={$form.errors.bank_name}
                            />

                            <TextInput
                                id="account_number"
                                name="account_number"
                                label="Nomor Rekening"
                                placeholder="1234567890"
                                bind:value={$form.account_number}
                                error={$form.errors.account_number}
                            />

                            <TextInput
                                id="account_name"
                                name="account_name"
                                label="Nama Pemegang Rekening"
                                placeholder="Nama sesuai rekening"
                                bind:value={$form.account_name}
                                error={$form.errors.account_name}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Assignment Produk" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Pilih produk yang di-assign ke chef ini.
                                Terpilih: <strong
                                    >{$form.product_ids.length}</strong
                                > produk.
                            </p>

                            {#if products.length > 5}
                                <TextInput
                                    id="product-search"
                                    name="product-search"
                                    placeholder="Cari produk..."
                                    bind:value={productSearch}
                                    class="mb-0!"
                                />
                            {/if}

                            <div
                                class="max-h-80 overflow-y-auto space-y-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3"
                            >
                                {#if filteredProducts.length > 0}
                                    {#each filteredProducts as product}
                                        <label
                                            class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={isProductSelected(
                                                    product.id,
                                                )}
                                                onchange={() =>
                                                    toggleProduct(product.id)}
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span
                                                class="text-sm text-gray-900 dark:text-white"
                                            >
                                                {product.name}
                                            </span>
                                        </label>
                                    {/each}
                                {:else}
                                    <div
                                        class="py-4 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >
                                        {productSearch
                                            ? "Tidak ada produk ditemukan"
                                            : "Belum ada produk"}
                                    </div>
                                {/if}
                            </div>

                            {#if $form.errors.product_ids}
                                <p class="text-xs text-red-600">
                                    {$form.errors.product_ids}
                                </p>
                            {/if}
                        </div>
                    {/snippet}
                </Card>

                <Card title="Koordinat Lokasi (Opsional)" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Lokasi chef untuk keperluan internal.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <TextInput
                                    id="latitude"
                                    name="latitude"
                                    label="Latitude"
                                    placeholder="-6.200000"
                                    value={$form.latitude?.toString() ?? ""}
                                    oninput={(e) => {
                                        if (
                                            e &&
                                            typeof e === "object" &&
                                            "target" in e
                                        ) {
                                            const val = (
                                                e.target as HTMLInputElement
                                            ).value;
                                            $form.latitude = val
                                                ? Number(val)
                                                : null;
                                        }
                                    }}
                                    error={$form.errors.latitude}
                                />

                                <TextInput
                                    id="longitude"
                                    name="longitude"
                                    label="Longitude"
                                    placeholder="106.816666"
                                    value={$form.longitude?.toString() ?? ""}
                                    oninput={(e) => {
                                        if (
                                            e &&
                                            typeof e === "object" &&
                                            "target" in e
                                        ) {
                                            const val = (
                                                e.target as HTMLInputElement
                                            ).value;
                                            $form.longitude = val
                                                ? Number(val)
                                                : null;
                                        }
                                    }}
                                    error={$form.errors.longitude}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
