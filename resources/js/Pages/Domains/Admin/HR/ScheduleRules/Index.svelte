<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
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
    type RuleDetail = {
        day_of_week: number;
        shift: Shift | null;
    };
    type RuleItem = {
        id: string;
        user_id: string;
        user_name: string;
        user_email: string;
        start_date: string | null;
        end_date: string | null;
        is_active: boolean;
        details: RuleDetail[];
        rotation_even_shift?: Shift | null;
        rotation_odd_shift?: Shift | null;
        rotation_off_day?: number | null;
    };

    let rules = $derived(($page.props.rules as RuleItem[]) ?? []);
    let shifts = $derived(($page.props.shifts as Shift[]) ?? []);
    let filters = $derived(($page.props.filters as { q: string }) ?? { q: "" });

    let showDeleteDialog = $state(false);
    let deleteProcessing = $state(false);
    let selectedRule = $state<RuleItem | null>(null);

    const dayNames = [
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
        "Minggu",
    ];

    function formatDate(dt: string | null): string {
        if (!dt) return "-";
        const d = new Date(dt + "T00:00:00");
        const day = String(d.getDate()).padStart(2, "0");
        const month = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "Mei",
            "Jun",
            "Jul",
            "Agu",
            "Sep",
            "Okt",
            "Nov",
            "Des",
        ][d.getMonth()];
        return `${day} ${month} ${d.getFullYear()}`;
    }

    function applyFilters() {
        const params: Record<string, string | number> = {};
        if (filters.q) params.q = filters.q;
        router.get("/schedule-rules", params, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function resetFilters() {
        filters.q = "";
        applyFilters();
    }

    function openDeleteDialog(rule: RuleItem) {
        selectedRule = rule;
        showDeleteDialog = true;
    }
</script>

<svelte:head>
    <title>Aturan Jadwal | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Aturan Jadwal
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola aturan jadwal mingguan
            </p>
        </div>
        <div class="flex gap-3 items-center">
            <Button
                variant="success"
                icon="fa-solid fa-plus"
                href="/schedule-rules/create">Buat Aturan</Button
            >
        </div>
    </header>

    <Card title="Filter">
        {#snippet actions()}
            <Button variant="primary" onclick={applyFilters}>Terapkan</Button>
            <Button variant="secondary" onclick={resetFilters}>Reset</Button>
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-1">
            <TextInput
                id="q"
                name="q"
                label="Cari karyawan"
                placeholder="Nama karyawan…"
                bind:value={filters.q}
            />
        </div>
    </Card>

    <Card title="Daftar Aturan" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full table-fixed">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            {#each dayNames as dn}
                                <th>{dn}</th>
                            {/each}
                            <th>Berlaku</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if rules.length}
                            {#each rules as r}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap"
                                        >
                                            {r.user_name}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                        >
                                            {r.user_email}
                                        </div>
                                    </td>
                                    {#each [0, 1, 2, 3, 4, 5, 6] as d}
                                        {@const det = r.details.find(
                                            (x) => x.day_of_week === d,
                                        )}
                                        <td class="align-top">
                                            {#if det?.shift}
                                                <div
                                                    class="text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                                                >
                                                    {det.shift.name}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                                >
                                                    {det.shift.start_time &&
                                                    det.shift.end_time
                                                        ? `${det.shift.start_time} - ${det.shift.end_time}`
                                                        : det.shift.is_off
                                                          ? "Libur"
                                                          : "-"}
                                                </div>
                                            {:else if r.rotation_even_shift && r.rotation_odd_shift}
                                                {#if r.rotation_off_day === d}
                                                    <div
                                                        class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 whitespace-nowrap"
                                                    >
                                                        Libur
                                                    </div>
                                                {:else}
                                                    <div class="space-y-1">
                                                        <div
                                                            class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200 whitespace-nowrap"
                                                        >
                                                            Genap: <span
                                                                class="ml-1 font-medium"
                                                                >{r
                                                                    .rotation_even_shift
                                                                    .name}</span
                                                            >
                                                        </div>
                                                        <div
                                                            class="text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                                        >
                                                            {r
                                                                .rotation_even_shift
                                                                .start_time &&
                                                            r
                                                                .rotation_even_shift
                                                                .end_time
                                                                ? `${r.rotation_even_shift.start_time} - ${r.rotation_even_shift.end_time}`
                                                                : ""}
                                                        </div>
                                                        <div
                                                            class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200 whitespace-nowrap"
                                                        >
                                                            Ganjil: <span
                                                                class="ml-1 font-medium"
                                                                >{r
                                                                    .rotation_odd_shift
                                                                    .name}</span
                                                            >
                                                        </div>
                                                        <div
                                                            class="text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                                        >
                                                            {r
                                                                .rotation_odd_shift
                                                                .start_time &&
                                                            r.rotation_odd_shift
                                                                .end_time
                                                                ? `${r.rotation_odd_shift.start_time} - ${r.rotation_odd_shift.end_time}`
                                                                : ""}
                                                        </div>
                                                    </div>
                                                {/if}
                                            {:else}
                                                <div
                                                    class="text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                                >
                                                    -
                                                </div>
                                            {/if}
                                        </td>
                                    {/each}
                                    <td
                                        class="px-4 py-3 whitespace-nowrap border-r last:border-r-0 border-gray-200 dark:border-gray-700"
                                    >
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDate(r.start_date)} - {formatDate(
                                                r.end_date,
                                            )}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap border-r last:border-r-0 border-gray-200 dark:border-gray-700"
                                    >
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/schedule-rules/${r.id}/edit`}
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-trash"
                                                onclick={() =>
                                                    openDeleteDialog(r)}
                                                >Hapus</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="10"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Belum ada aturan.</td
                                >
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
    </Card>
</section>

<Dialog
    bind:isOpen={showDeleteDialog}
    type="danger"
    title="Hapus Aturan Jadwal"
    message={`Apakah Anda yakin ingin menghapus aturan untuk ${selectedRule?.user_name ?? ""}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Ya, Hapus"
    cancelText="Batal"
    showCancel={true}
    loading={deleteProcessing}
    onConfirm={async () => {
        deleteProcessing = true;
        if (selectedRule) {
            await router.delete(`/schedule-rules/${selectedRule.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deleteProcessing = false;
                    selectedRule = null;
                    applyFilters();
                },
            });
        } else {
            deleteProcessing = false;
        }
    }}
    onCancel={() => {
        selectedRule = null;
    }}
    onClose={() => {
        selectedRule = null;
        deleteProcessing = false;
    }}
/>
