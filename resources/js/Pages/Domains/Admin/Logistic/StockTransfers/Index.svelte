<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";

    type Warehouse = { id: string; name: string };
    type StatusItem = { value: string; label: string };

    type StockTransfer = {
        id: string;
        number: string;
        transfer_date: string | null;
        status: string | null;
        status_label: string | null;
        from_warehouse: { id: string | null; name: string | null };
        to_warehouse: { id: string | null; name: string | null };
        to_owner_user?: { id: string | null; name: string | null } | null;
    };

    let stockTransfers = $derived(
        ($page.props.stock_transfers as StockTransfer[]) ?? [],
    );
    let meta = $derived(
        $page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        },
    );
    let statusOptions = $derived(
        ($page.props.statusOptions as StatusItem[]) ?? [],
    );
    let statusCounters = $derived(
        ($page.props.statusCounters as Record<string, number>) ?? {},
    );
    let warehouses = $derived(($page.props.warehouses as Warehouse[]) ?? []);
    let warehouseOptions = $derived([
        { value: "", label: "Semua Gudang" },
        ...warehouses.map((w) => ({ value: w.id, label: w.name })),
    ]);

    type LocalFilters = {
        q: string;
        status: string;
        from_warehouse_id: string;
        to_warehouse_id: string;
        transfer_date_from: string;
        transfer_date_to: string;
        per_page: number;
    };
    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        status: ($page.props.filters as { status?: string })?.status ?? "",
        from_warehouse_id:
            ($page.props.filters as { from_warehouse_id?: string })
                ?.from_warehouse_id ?? "",
        to_warehouse_id:
            ($page.props.filters as { to_warehouse_id?: string })
                ?.to_warehouse_id ?? "",
        transfer_date_from:
            ($page.props.filters as { transfer_date_from?: string })
                ?.transfer_date_from ?? "",
        transfer_date_to:
            ($page.props.filters as { transfer_date_to?: string })
                ?.transfer_date_to ?? "",
        per_page: ($page.props.meta as { per_page?: number })?.per_page ?? 10,
    });

    type BadgeVariant =
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple";
    type TabItemLocal = {
        id: string;
        label: string;
        badge?: number | string;
        badgeVariant?: BadgeVariant;
    };
    function toBadgeVariant(statusValue: string): BadgeVariant {
        switch (statusValue) {
            case "draft":
                return "secondary";
            case "in_transit":
                return "warning";
            case "received":
                return "success";
            case "canceled":
                return "danger";
            default:
                return "secondary";
        }
    }
    let statusTabs = $derived<TabItemLocal[]>([
        {
            id: "",
            label: "Semua",
            badge: statusCounters[""] ?? 0,
            badgeVariant: "secondary",
        },
        ...statusOptions.map((s) => ({
            id: s.value,
            label: s.label,
            badge: statusCounters[s.value] ?? 0,
            badgeVariant: toBadgeVariant(s.value),
        })),
    ]);

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedTR = $state<StockTransfer | null>(null);
    let showAdvanceDialog = $state(false);
    function getNextStatus(
        current: string | null,
    ): { value: string; label: string } | null {
        const s = String(current ?? "");
        switch (s) {
            case "draft":
                return { value: "in_transit", label: "Mulai Pengiriman" };
            case "in_transit":
                return { value: "received", label: "Terima di Gudang Tujuan" };
            default:
                return null;
        }
    }
    function advanceStatus(tr: StockTransfer) {
        selectedTR = tr;
        showAdvanceDialog = true;
    }
    function canDelete(status: string | null): boolean {
        return String(status ?? "") === "draft";
    }
    function canEdit(status: string | null): boolean {
        return String(status ?? "") === "draft";
    }
    function doDelete() {
        if (!selectedTR) return;
        deleteProcessing = true;
        router.delete(`/stock-transfers/${selectedTR.id}`, {
            preserveScroll: true,
            onFinish: () => {
                deleteProcessing = false;
                showDeleteDialog = false;
            },
        });
    }
    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.from_warehouse_id)
            params.set("from_warehouse_id", filters.from_warehouse_id);
        if (filters.to_warehouse_id)
            params.set("to_warehouse_id", filters.to_warehouse_id);
        if (filters.transfer_date_from)
            params.set("transfer_date_from", filters.transfer_date_from);
        if (filters.transfer_date_to)
            params.set("transfer_date_to", filters.transfer_date_to);
        if (filters.per_page) params.set("per_page", String(filters.per_page));
        router.get(
            "/stock-transfers?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Mutasi Stok | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Mutasi Stok
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola perpindahan stok antar gudang
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                icon="fa-solid fa-plus"
                href="/stock-transfers/create">Buat Mutasi</Button
            >
        </div>
    </header>

    <div>
        <Tab
            tabs={statusTabs}
            activeTab={filters.status}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.status = String(tabId ?? "");
                applyFilters();
            }}
        />
    </div>

    <Card title="Filter">
        {#snippet actions()}
            <Button
                variant="primary"
                icon="fa-solid fa-filter"
                onclick={applyFilters}>Terapkan</Button
            >
            <Button
                variant="secondary"
                icon="fa-solid fa-rotate-left"
                onclick={() => {
                    filters.q = "";
                    filters.status = "";
                    filters.from_warehouse_id = "";
                    filters.to_warehouse_id = "";
                    filters.transfer_date_from = "";
                    filters.transfer_date_to = "";
                    applyFilters();
                }}>Reset</Button
            >
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
            <TextInput
                id="filter-q"
                name="q"
                label="Cari"
                placeholder="Nomor / catatan"
                bind:value={filters.q}
            />
            <Select
                label="Gudang Asal"
                options={warehouseOptions}
                bind:value={filters.from_warehouse_id}
            />
            <Select
                label="Gudang Tujuan"
                options={warehouseOptions}
                bind:value={filters.to_warehouse_id}
            />
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:col-span-2 lg:col-span-2"
            >
                <DateInput
                    label="Tanggal Dari"
                    bind:value={filters.transfer_date_from}
                />
                <DateInput
                    label="Tanggal Sampai"
                    bind:value={filters.transfer_date_to}
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Mutasi Stok" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Gudang Asal</th>
                            <th>Tujuan Stok</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if stockTransfers?.length}
                            {#each stockTransfers as tr}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {tr.number}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {tr.from_warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {tr.to_owner_user?.name
                                                ? tr.to_owner_user?.name
                                                : tr.to_warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(
                                                tr.transfer_date,
                                            )}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={toBadgeVariant(
                                                String(tr.status ?? ""),
                                            )}
                                            title={tr.status_label ??
                                                tr.status ??
                                                "-"}
                                        >
                                            {#snippet children()}
                                                {tr.status_label ??
                                                    tr.status ??
                                                    "-"}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/stock-transfers/${tr.id}`}
                                                >Detail</Button
                                            >
                                            {#if canEdit(tr.status)}
                                                <Button
                                                    variant="warning"
                                                    size="sm"
                                                    icon="fa-solid fa-edit"
                                                    href={`/stock-transfers/${tr.id}/edit`}
                                                    >Edit</Button
                                                >
                                            {/if}
                                            {#if getNextStatus(tr.status)}
                                                <Button
                                                    variant="success"
                                                    size="sm"
                                                    icon="fa-solid fa-forward"
                                                    onclick={() =>
                                                        advanceStatus(tr)}
                                                    >Lanjut: {getNextStatus(
                                                        tr.status,
                                                    )?.label}</Button
                                                >
                                            {/if}
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-print"
                                                onclick={() =>
                                                    openCenteredWindow(
                                                        `/stock-transfers/${tr.id}/print`,
                                                        {
                                                            width: 960,
                                                            height: 700,
                                                            fallbackWhenBlocked: false,
                                                        },
                                                    )}>Cetak</Button
                                            >
                                            {#if canDelete(tr.status)}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-trash"
                                                    onclick={() => {
                                                        selectedTR = tr;
                                                        showDeleteDialog = true;
                                                    }}>Hapus</Button
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
                                    class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data mutasi stok
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
    </Card>

    <div>
        <Pagination
            currentPage={meta.current_page}
            totalPages={meta.last_page}
            totalItems={meta.total}
            itemsPerPage={meta.per_page}
            onPageChange={(p) => {
                const params = new URLSearchParams();
                if (filters.q) params.set("q", filters.q);
                if (filters.status) params.set("status", filters.status);
                if (filters.from_warehouse_id)
                    params.set("from_warehouse_id", filters.from_warehouse_id);
                if (filters.to_warehouse_id)
                    params.set("to_warehouse_id", filters.to_warehouse_id);
                if (filters.transfer_date_from)
                    params.set(
                        "transfer_date_from",
                        filters.transfer_date_from,
                    );
                if (filters.transfer_date_to)
                    params.set("transfer_date_to", filters.transfer_date_to);
                params.set("page", String(p));
                params.set("per_page", String(filters.per_page));
                router.get(
                    "/stock-transfers?" + params.toString(),
                    {},
                    { preserveState: true, preserveScroll: true },
                );
            }}
            onItemsPerPageChange={(pp) => {
                filters.per_page = pp;
                const params = new URLSearchParams();
                if (filters.q) params.set("q", filters.q);
                if (filters.status) params.set("status", filters.status);
                if (filters.from_warehouse_id)
                    params.set("from_warehouse_id", filters.from_warehouse_id);
                if (filters.to_warehouse_id)
                    params.set("to_warehouse_id", filters.to_warehouse_id);
                if (filters.transfer_date_from)
                    params.set(
                        "transfer_date_from",
                        filters.transfer_date_from,
                    );
                if (filters.transfer_date_to)
                    params.set("transfer_date_to", filters.transfer_date_to);
                params.set("page", "1");
                params.set("per_page", String(pp));
                router.get(
                    "/stock-transfers?" + params.toString(),
                    {},
                    { preserveState: true, preserveScroll: true },
                );
            }}
        />
    </div>
</section>

<Dialog
    isOpen={showAdvanceDialog}
    onClose={() => (showAdvanceDialog = false)}
    title="Lanjutkan Status"
    message="Konfirmasi perubahan status mutasi stok"
    showDefaultActions={false}
>
    {#if selectedTR}
        {#if getNextStatus(selectedTR.status)}
            <p class="mb-4 text-sm">
                Lanjutkan status mutasi stok <b>{selectedTR.number}</b> menjadi
                <b>{getNextStatus(selectedTR.status)?.label}</b>?
            </p>
            <div class="flex justify-end gap-2">
                <Button
                    variant="secondary"
                    onclick={() => (showAdvanceDialog = false)}>Batal</Button
                >
                <Button
                    variant="primary"
                    onclick={() =>
                        router.post(
                            `/stock-transfers/${selectedTR?.id}/advance`,
                            {},
                            {
                                preserveScroll: true,
                                onSuccess: () => (showAdvanceDialog = false),
                            },
                        )}>Lanjut</Button
                >
            </div>
        {:else}
            <p>Status tidak dapat dilanjutkan.</p>
        {/if}
    {/if}
</Dialog>

<Dialog
    isOpen={showDeleteDialog}
    onClose={() => (showDeleteDialog = false)}
    title="Hapus Mutasi Stok"
    message="Konfirmasi penghapusan mutasi stok"
    showDefaultActions={false}
>
    <p class="mb-4 text-sm">
        Anda yakin ingin menghapus mutasi stok <b>{selectedTR?.number}</b>?
    </p>
    <div class="flex justify-end gap-2">
        <Button variant="secondary" onclick={() => (showDeleteDialog = false)}
            >Batal</Button
        >
        <Button variant="danger" onclick={doDelete} disabled={deleteProcessing}
            >{deleteProcessing ? "Menghapus..." : "Hapus"}</Button
        >
    </div>
</Dialog>
