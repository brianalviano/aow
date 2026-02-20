<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";

    type Rel = { id: string | null; name: string | null };
    type Warehouse = { id: string; name: string };
    type StatusItem = { value: string; label: string };
    type Opname = {
        id: string;
        number: string;
        scheduled_date: string | null;
        status: string;
        status_label?: string | null;
        warehouse: Rel;
        my_assignment_id?: string | null;
    };

    let opnames = $derived(($page.props.opnames as Opname[]) ?? []);
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
        status: string;
        warehouse_id: string;
        scheduled_date_from: string;
        scheduled_date_to: string;
    };
    let filters = $state<LocalFilters>({
        q: ($page.props.filters as { q?: string })?.q ?? "",
        status: ($page.props.filters as { status?: string })?.status ?? "",
        warehouse_id:
            ($page.props.filters as { warehouse_id?: string })?.warehouse_id ??
            "",
        scheduled_date_from:
            ($page.props.filters as { scheduled_date_from?: string })
                ?.scheduled_date_from ?? "",
        scheduled_date_to:
            ($page.props.filters as { scheduled_date_to?: string })
                ?.scheduled_date_to ?? "",
    });
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

    function getOpnameStatusVariant(
        status: string,
    ):
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple" {
        const s = String(status ?? "").toLowerCase();
        if (!s || s === "draft") return "secondary";
        if (s === "scheduled") return "info";
        if (s === "in_progress") return "warning";
        if (s === "completed") return "success";
        if (s === "canceled") return "danger";
        return "secondary";
    }
    function toBadgeVariant(statusValue: string): BadgeVariant {
        const v = getOpnameStatusVariant(statusValue);
        return v as BadgeVariant;
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

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.scheduled_date_from)
            params.set("scheduled_date_from", filters.scheduled_date_from);
        if (filters.scheduled_date_to)
            params.set("scheduled_date_to", filters.scheduled_date_to);
        router.get(
            "/stock-opnames?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
    function resetFilters() {
        filters.q = "";
        filters.status = "";
        filters.warehouse_id = "";
        filters.scheduled_date_from = "";
        filters.scheduled_date_to = "";
        applyFilters();
    }
    let canSettle = $derived(Boolean($page.props.can_settle ?? false));
    function settleOpname(id: string) {
        if (!id) return;
        if (!canSettle) return;
        const ok = window.confirm(
            "Selesaikan dokumen tanpa perubahan stok? Tindakan ini akan menandai semua penugasan selesai.",
        );
        if (!ok) return;
        router.post(
            `/stock-opnames/${id}/settle`,
            {},
            { preserveScroll: true },
        );
    }
    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.warehouse_id)
            params.set("warehouse_id", filters.warehouse_id);
        if (filters.scheduled_date_from)
            params.set("scheduled_date_from", filters.scheduled_date_from);
        if (filters.scheduled_date_to)
            params.set("scheduled_date_to", filters.scheduled_date_to);
        params.set("page", String(pageNum));
        router.get(
            "/stock-opnames?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Stok Opname | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Stok Opname
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola dokumen Stok Opname
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/stock-opnames/create"
                icon="fa-solid fa-plus">Buat Stok Opname</Button
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
                filters.status = tabId;
                applyFilters();
            }}
        />
    </div>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <TextInput
                id="q"
                name="q"
                label="Cari stok opname"
                placeholder="Nomor atau gudang..."
                bind:value={filters.q}
            />
            <Select
                id="warehouse_id"
                name="warehouse_id"
                label="Gudang"
                options={warehouseOptions}
                searchable={true}
                bind:value={filters.warehouse_id}
            />
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <DateInput
                    id="scheduled_date_from"
                    name="scheduled_date_from"
                    label="Jadwal dari"
                    bind:value={filters.scheduled_date_from}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
                <DateInput
                    id="scheduled_date_to"
                    name="scheduled_date_to"
                    label="Jadwal sampai"
                    bind:value={filters.scheduled_date_to}
                    placeholder="YYYY-MM-DD"
                    format="yyyy-mm-dd"
                />
            </div>
        </div>
    </Card>

    <Card title="Daftar Stok Opname" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Gudang</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if opnames?.length}
                            {#each opnames as o}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {o.number}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {o.warehouse?.name || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(
                                                o.scheduled_date,
                                            )}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getOpnameStatusVariant(
                                                o.status,
                                            )}
                                            title={o.status_label ?? o.status}
                                        >
                                            {#snippet children()}
                                                {o.status_label ?? o.status}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td>
                                        <div class="flex gap-2 items-center">
                                            {#if String(o.status).toLowerCase() === "scheduled" && canSettle}
                                                <Button
                                                    variant="success"
                                                    size="sm"
                                                    icon="fa-solid fa-check"
                                                    onclick={() =>
                                                        settleOpname(o.id)}
                                                    >Selesaikan</Button
                                                >
                                            {/if}
                                            {#if o.my_assignment_id}
                                                <Button
                                                    variant="warning"
                                                    size="sm"
                                                    icon="fa-solid fa-clipboard-check"
                                                    href={`/stock-opnames/${o.id}/assignments/${o.my_assignment_id}`}
                                                    >Kerjakan Tugas</Button
                                                >
                                            {/if}
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                href={`/stock-opnames/${o.id}`}
                                                >Detail</Button
                                            >
                                            {#if String(o.status).toLowerCase() === "draft"}
                                                <Button
                                                    variant="warning"
                                                    size="sm"
                                                    icon="fa-solid fa-pen"
                                                    href={`/stock-opnames/${o.id}/edit`}
                                                    >Edit</Button
                                                >
                                            {/if}
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
