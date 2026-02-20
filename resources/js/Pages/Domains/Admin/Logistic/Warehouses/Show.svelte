<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";

    type WarehouseDetail = {
        id: string;
        name: string;
        code: string;
        address: string | null;
        is_central: boolean;
        phone: string | null;
        is_active: boolean;
        created_at: string | null;
        updated_at: string | null;
    };

    let warehouse = $derived($page.props.warehouse as WarehouseDetail);

    function backToList() {
        router.visit("/warehouses");
    }

    function editWarehouse() {
        router.visit(`/warehouses/${warehouse.id}/edit`);
    }
</script>

<svelte:head>
    <title>Detail Gudang | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Gudang
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {warehouse.name} - {warehouse.code}
            </p>
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
                onclick={editWarehouse}>Edit</Button
            >
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Gudang" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {warehouse.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Kode
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {warehouse.code}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Telepon
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {warehouse.phone || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Status
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {warehouse.is_active ? "Aktif" : "Nonaktif"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tipe
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {warehouse.is_central ? "Pusat" : "Biasa"}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Alamat
                            </p>
                            {#if warehouse.address}
                                <p
                                    class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {warehouse.address}
                                </p>
                            {:else}
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada alamat.
                                </p>
                            {/if}
                        </div>
                    </div>
                {/snippet}
            </Card>

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
                                {formatDateTimeDisplay(warehouse.created_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(warehouse.updated_at)}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>
    </div>
</section>

