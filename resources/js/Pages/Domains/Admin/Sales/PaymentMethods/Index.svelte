<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type PaymentMethod = {
        id: string;
        name: string;
        description: string | null;
        image_url: string | null;
        mdr_percentage: string;
        is_active: boolean;
    };

    let paymentMethods = $derived(
        $page.props.payment_methods as PaymentMethod[],
    );
    let meta = $derived(
        $page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );

    type LocalFilters = {
        q: string;
        is_active: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        is_active:
            ($page.props.filters as { is_active?: string })?.is_active ?? "",
    });

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedPM = $state<PaymentMethod | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.is_active) params.set("is_active", filters.is_active);
        router.get(
            "/payment-methods?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.is_active = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.is_active) params.set("is_active", filters.is_active);
        params.set("page", String(pageNum));
        router.get(
            "/payment-methods?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(pm: PaymentMethod) {
        selectedPM = pm;
        showDeleteDialog = true;
    }
</script>

<svelte:head>
    <title>Metode Pembayaran | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Metode Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola metode pembayaran
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/payment-methods/create"
                icon="fa-solid fa-plus">Tambah Metode Pembayaran</Button
            >
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <TextInput
                id="q"
                name="q"
                label="Cari"
                placeholder="Nama..."
                bind:value={filters.q}
            />
            <Select
                id="is_active"
                name="is_active"
                label="Status"
                options={[
                    { value: "", label: "Semua" },
                    { value: "1", label: "Aktif" },
                    { value: "0", label: "Tidak Aktif" },
                ]}
                bind:value={filters.is_active}
            />
        </div>
    </Card>

    <Card title="Daftar Metode Pembayaran" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr
                            class="text-sm text-left text-gray-600 dark:text-gray-400"
                        >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Nama</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >MDR (%)</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if paymentMethods?.length}
                            {#each paymentMethods as pm}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            {#if pm.image_url}
                                                <img
                                                    src={pm.image_url}
                                                    alt={pm.name}
                                                    class="w-8 h-8 rounded object-cover border border-gray-200 dark:border-gray-700"
                                                />
                                            {/if}
                                            <div
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {pm.name}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {pm.mdr_percentage}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class={`px-2 py-1 text-xs rounded ${
                                                pm.is_active
                                                    ? "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                                    : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300"
                                            }`}
                                            aria-label={pm.is_active
                                                ? "Aktif"
                                                : "Tidak Aktif"}
                                            >{pm.is_active
                                                ? "Aktif"
                                                : "Tidak Aktif"}</span
                                        >
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/payment-methods/${pm.id}`}
                                                >Detail</Button
                                            >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/payment-methods/${pm.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteDialog(pm)}
                                                >Hapus</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="5"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Tidak ada data</td
                                >
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
        {#snippet footer()}
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        {/snippet}
    </Card>
</section>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Metode Pembayaran"
    message={`Apakah Anda yakin ingin menghapus metode pembayaran ${selectedPM?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedPM) {
            await router.delete(`/payment-methods/${selectedPM.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedPM = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedPM = null;
    }}
    onClose={() => {
        selectedPM = null;
        deleteProcessing = false;
    }}
/>
