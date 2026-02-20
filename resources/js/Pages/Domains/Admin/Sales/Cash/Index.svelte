<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type User = { id: string | null; name: string | null };
    type Option = { value: string; label: string };
    type SessionItem = {
        id: string;
        opened_at: string | null;
        closed_at: string | null;
        starting_cash: number;
        expected_cash: number;
        actual_cash: number;
        variance: number;
        status: string | null;
        status_label: string | null;
        user: User;
    };

    let sessions = $derived(($page.props.sessions as SessionItem[]) ?? []);
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
        user_id: string;
        opened_from: string;
        opened_to: string;
        per_page: string;
    };
    let filters = $state<LocalFilters>({
        q: ($page.props.filters as any)?.q ?? "",
        status: ($page.props.filters as any)?.status ?? "",
        user_id: ($page.props.filters as any)?.user_id ?? "",
        opened_from: ($page.props.filters as any)?.opened_from ?? "",
        opened_to: ($page.props.filters as any)?.opened_to ?? "",
        per_page: String(($page.props.filters as any)?.per_page ?? "10"),
    });
    let statusOptions = $derived(($page.props.statusOptions as Option[]) ?? []);
    let userOptions = $derived(($page.props.userOptions as Option[]) ?? []);

    function goToPage(pageNumber: number) {
        const params = new URLSearchParams();
        params.set("page", String(pageNumber));
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.user_id) params.set("user_id", filters.user_id);
        if (filters.opened_from) params.set("opened_from", filters.opened_from);
        if (filters.opened_to) params.set("opened_to", filters.opened_to);
        if (filters.per_page) params.set("per_page", filters.per_page);
        router.get(
            "/cashier-sessions?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.user_id) params.set("user_id", filters.user_id);
        if (filters.opened_from) params.set("opened_from", filters.opened_from);
        if (filters.opened_to) params.set("opened_to", filters.opened_to);
        if (filters.per_page) params.set("per_page", filters.per_page);
        router.get(
            "/cashier-sessions?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function getStatusPillClass(status: string | null): string {
        if (status === "open")
            return "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300";
        if (status === "closed")
            return "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";
        return "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";
    }
</script>

<svelte:head>
    <title>Riwayat Kas | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Riwayat Kas
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Daftar shift kasir dan ringkasan saldo
            </p>
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <div class="flex gap-2">
                <Button
                    variant="secondary"
                    icon="fa-solid fa-arrow-rotate-left"
                    onclick={() => {
                        filters.q = "";
                        filters.status = "";
                        filters.opened_from = "";
                        filters.opened_to = "";
                        applyFilters();
                    }}>Reset</Button
                >
                <Button
                    variant="primary"
                    icon="fa-solid fa-filter"
                    onclick={applyFilters}>Terapkan</Button
                >
            </div>
        {/snippet}
        {#snippet children()}
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="col-span-4">
                    <TextInput
                        id="q"
                        name="q"
                        label="Cari"
                        placeholder="Nama kasir atau catatan"
                        bind:value={filters.q}
                    />
                </div>
                <div class="col-span-4">
                    <Select
                        id="status"
                        name="status"
                        label="Status"
                        bind:value={filters.status}
                        options={[
                            { value: "", label: "Semua" },
                            ...statusOptions,
                        ]}
                    />
                </div>
                <div class="col-span-4">
                    <Select
                        id="user_id"
                        name="user_id"
                        label="Kasir"
                        bind:value={filters.user_id}
                        options={[
                            { value: "", label: "Semua" },
                            ...userOptions,
                        ]}
                    />
                </div>
                <div class="col-span-6">
                    <DateInput
                        id="opened_from"
                        name="opened_from"
                        label="Tanggal Buka dari"
                        bind:value={filters.opened_from}
                    />
                </div>
                <div class="col-span-6">
                    <DateInput
                        id="opened_to"
                        name="opened_to"
                        label="Tanggal Buka sampai"
                        bind:value={filters.opened_to}
                    />
                </div>
            </div>
        {/snippet}
    </Card>

    <Card title="Riwayat" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr
                            class="text-sm text-left text-gray-600 dark:text-gray-400"
                        >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Kasir</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Tanggal Buka</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Tanggal Tutup</th
                            >
                            <th
                                class="px-4 py-3 text-right font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Saldo Awal</th
                            >
                            <th
                                class="px-4 py-3 text-right font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Ekspektasi</th
                            >
                            <th
                                class="px-4 py-3 text-right font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aktual</th
                            >
                            <th
                                class="px-4 py-3 text-right font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Selisih</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if sessions.length > 0}
                            {#each sessions as s}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {s.user?.name ?? "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(s.opened_at)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(s.closed_at)}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(s.starting_cash)}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(s.expected_cash)}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(s.actual_cash)}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(s.variance)}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class={`px-2 py-1 text-xs rounded ${getStatusPillClass(s.status)}`}
                                            aria-label={s.status_label ?? "-"}
                                        >
                                            {s.status_label ?? "-"}
                                        </span>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="8"
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
