<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type Target = { id: string; name: string; type: string } | null;
    type DiscountItem = {
        id: string;
        min_qty_buy: number;
        is_multiple: boolean;
        free_product_id: string | null;
        free_qty_get: number;
        custom_value: string | null;
        itemable_type: string;
        itemable: Target;
    };

    type DiscountDetail = {
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
        items?: DiscountItem[] | null;
    };

    let discount = $derived($page.props.discount as DiscountDetail);

    function backToList() {
        router.visit("/discounts");
    }

    function editDiscount() {
        router.visit(`/discounts/${discount.id}/edit`);
    }

    function displayValue(d: DiscountDetail): string {
        if (!d.value_type || !d.value) return "-";
        if (d.value_type === "percentage") {
            return `${d.value}%`;
        }
        return formatCurrency(Number(d.value));
    }
</script>

<svelte:head>
    <title>Detail Diskon | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Diskon
            </h1>
            <p class="text-gray-600 dark:text-gray-400">{discount.name}</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                onclick={editDiscount}>Edit</Button
            >
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Diskon" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {discount.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tipe
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {discount.type === "bogo"
                                    ? "BOGO"
                                    : "Diskon Nilai"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Scope
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {discount.scope === "global"
                                    ? "Global"
                                    : "Spesifik"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nilai
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {displayValue(discount)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Mulai
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(discount.start_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Selesai
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(discount.end_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Status
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {discount.is_active ? "Aktif" : "Tidak Aktif"}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>

            <Card title="Target Diskon" collapsible={false}>
                {#snippet children()}
                    {#if discount.items && discount.items.length > 0}
                        <div class="overflow-x-auto">
                            <table
                                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Target</th
                                        >
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Min. Qty</th
                                        >
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Kelipatan</th
                                        >
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Gratis</th
                                        >
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Nilai Kustom</th
                                        >
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-200 dark:divide-gray-700"
                                >
                                    {#each discount.items as it}
                                        <tr>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {it.itemable?.name ?? "-"}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {it.itemable?.type?.includes(
                                                        "ProductCategory",
                                                    )
                                                        ? "Kategori Produk"
                                                        : it.itemable?.type?.includes(
                                                                "Product",
                                                            )
                                                          ? "Produk"
                                                          : (it.itemable
                                                                ?.type ?? "-")}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {it.min_qty_buy}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {it.is_multiple
                                                        ? "Ya"
                                                        : "Tidak"}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {it.free_qty_get > 0
                                                        ? `${it.free_qty_get} item`
                                                        : "-"}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="text-sm text-gray-900 dark:text-white"
                                                >
                                                    {it.custom_value
                                                        ? formatCurrency(
                                                              Number(
                                                                  it.custom_value,
                                                              ),
                                                          )
                                                        : "-"}
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {:else}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada target spesifik.
                        </p>
                    {/if}
                {/snippet}
            </Card>
        </div>
        <div>
            <Card title="Informasi Sistem" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dibuat Pada
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(discount.created_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(discount.updated_at)}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>
    </div>
</section>
