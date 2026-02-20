<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";

    type Option = { value: string; label: string };
    type LeaveItem = {
        id: string;
        start_date: string;
        end_date: string;
        formatted_period?: string;
        type: { value: string; label: string };
        reason: string | null;
        status: { value: string; label: string };
    };

    let leaves = $derived(($page.props.leaves as LeaveItem[]) ?? []);
    let meta = $derived(
        ($page.props.meta as {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
        }) ?? { current_page: 1, per_page: 10, total: 0, last_page: 1 },
    );
    let types = $derived(($page.props.types as Option[]) ?? []);
    let statuses = $derived(($page.props.statuses as Option[]) ?? []);
    let filters = $state(
        ($page.props.filters as {
            q: string;
            type: string;
            status: string;
        }) ?? { q: "", type: "", status: "" },
    );

    function getLeaveStatusVariant(
        status: string,
    ): "success" | "warning" | "danger" | "info" | "secondary" {
        const s = status.toLowerCase();
        if (s === "approved") return "success";
        if (s === "pending") return "warning";
        if (s === "rejected") return "danger";
        return "secondary";
    }

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.status) params.set("status", filters.status);
        router.get(
            "/leaves" + (params.toString() ? `?${params}` : ""),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        filters.type = "";
        filters.status = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.status) params.set("status", filters.status);
        params.set("page", String(pageNum));
        router.get(
            "/leaves?" + params.toString(),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }
</script>

<svelte:head>
    <title>Izin Saya | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Izin
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Riwayat pengajuan izin
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="success"
                href="/leaves/create"
                icon="fa-solid fa-plus">Ajukan Izin</Button
            >
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <TextInput
                id="q"
                name="q"
                label="Cari alasan"
                placeholder="Alasan..."
                bind:value={filters.q}
            />
            <Select
                id="type"
                name="type"
                label="Tipe"
                options={types}
                bind:value={filters.type}
            />
            <Select
                id="status"
                name="status"
                label="Status"
                options={statuses}
                bind:value={filters.status}
            />
        </div>
    </Card>

    <Card title="Data Izin" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Alasan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if leaves?.length}
                            {#each leaves as l}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {l.formatted_period ||
                                                `${formatDateDisplay(l.start_date)} - ${formatDateDisplay(l.end_date)}`}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {l.type.label}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getLeaveStatusVariant(
                                                l.status.value,
                                            )}
                                        >
                                            {#snippet children()}
                                                {l.status.label}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {l.reason || "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/leaves/${l.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    router.delete(
                                                        `/leaves/${l.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )}>Hapus</Button
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
