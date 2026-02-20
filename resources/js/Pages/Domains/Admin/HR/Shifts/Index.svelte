<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type Shift = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
        color?: string | null;
    };

    let shifts = $derived($page.props.shifts as Shift[]);
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
        is_overnight: string;
        is_off: string;
    };

    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        is_overnight:
            ($page.props.filters as { is_overnight?: string })?.is_overnight ??
            "",
        is_off: ($page.props.filters as { is_off?: string })?.is_off ?? "",
    });

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedShift = $state<Shift | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.is_overnight)
            params.set("is_overnight", filters.is_overnight);
        if (filters.is_off) params.set("is_off", filters.is_off);
        router.get(
            "/shifts?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.is_overnight = "";
        filters.is_off = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.is_overnight)
            params.set("is_overnight", filters.is_overnight);
        if (filters.is_off) params.set("is_off", filters.is_off);
        params.set("page", String(pageNum));
        router.get(
            "/shifts?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function openDeleteDialog(s: Shift) {
        selectedShift = s;
        showDeleteDialog = true;
    }
</script>

<svelte:head>
    <title>Shift | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Shift
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola data shift kerja
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/shifts/create"
                icon="fa-solid fa-plus">Tambah Shift</Button
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
                label="Cari shift"
                placeholder="Nama shift..."
                bind:value={filters.q}
            />
            <Select
                id="is_overnight"
                name="is_overnight"
                label="Lintas Hari"
                options={[
                    { value: "", label: "Semua" },
                    { value: "1", label: "Ya" },
                    { value: "0", label: "Tidak" },
                ]}
                searchable={false}
                bind:value={filters.is_overnight}
            />
        </div>
    </Card>

    <Card title="Daftar Shift" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="min-w-full">
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
                                >Jam Shift</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Lintas Hari</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Libur</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if shifts?.length}
                            {#each shifts as s}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {s.name}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {s.start_time} - {s.end_time}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            outlined={true}
                                            variant={s.is_overnight
                                                ? "success"
                                                : "secondary"}
                                        >
                                            {#snippet children()}{s.is_overnight
                                                    ? "Ya"
                                                    : "Tidak"}{/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            outlined={true}
                                            variant={s.is_off
                                                ? "danger"
                                                : "secondary"}
                                        >
                                            {#snippet children()}{s.is_off
                                                    ? "Ya"
                                                    : "Tidak"}{/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/shifts/${s.id}`}
                                                >Detail</Button
                                            >
                                            {#if !s.is_off}
                                                <Button
                                                    variant="warning"
                                                    size="sm"
                                                    icon="fa-solid fa-edit"
                                                    href={`/shifts/${s.id}/edit`}
                                                    >Edit</Button
                                                >
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-trash"
                                                    onclick={() =>
                                                        openDeleteDialog(s)}
                                                    >Hapus</Button
                                                >
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data
                                </td>
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
    title="Hapus Shift"
    message={`Apakah Anda yakin ingin menghapus shift ${selectedShift?.name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedShift) {
            await router.delete(`/shifts/${selectedShift.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedShift = null;
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedShift = null;
    }}
    onClose={() => {
        selectedShift = null;
        deleteProcessing = false;
    }}
/>
