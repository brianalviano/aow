<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type PaymentMethod = {
        id: string;
        name: string;
        description: string | null;
        image_url: string | null;
        mdr_percentage: string;
        is_active: boolean;
        created_at: string | null;
        updated_at: string | null;
    };

    let paymentMethod = $derived($page.props.payment_method as PaymentMethod);

    function backToList() {
        router.visit("/payment-methods");
    }
</script>

<svelte:head>
    <title>Detail Metode Pembayaran | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Metode Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Informasi lengkap metode pembayaran
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                href={`/payment-methods/${paymentMethod.id}/edit`}>Edit</Button
            >
        </div>
    </header>

    <Card title="Informasi" collapsible={false}>
        {#snippet children()}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Nama
                    </div>
                    <div
                        class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                        {paymentMethod.name}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        MDR (%)
                    </div>
                    <div class="text-sm text-gray-900 dark:text-white">
                        {paymentMethod.mdr_percentage}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Status
                    </div>
                    <div class="text-sm text-gray-900 dark:text-white">
                        {paymentMethod.is_active ? "Aktif" : "Tidak Aktif"}
                    </div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Deskripsi
                    </div>
                    <div class="text-sm text-gray-900 dark:text-white">
                        {paymentMethod.description || "-"}
                    </div>
                </div>
                {#if paymentMethod.image_url}
                    <div class="md:col-span-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Gambar
                        </div>
                        <img
                            src={paymentMethod.image_url}
                            alt={paymentMethod.name}
                            class="mt-2 w-48 h-48 object-cover rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
                        />
                    </div>
                {/if}
            </div>
        {/snippet}
    </Card>
</section>
