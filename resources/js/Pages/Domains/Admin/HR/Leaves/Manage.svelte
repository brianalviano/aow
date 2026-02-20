<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatDateDisplay,
        formatDateTimeDisplay,
    } from "@/Lib/Admin/Utils/date";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";

    type Option = { value: string; label: string };
    type LeaveItem = {
        id: string;
        user: { id: string; name: string } | null;
        start_date: string;
        end_date: string;
        formatted_period?: string;
        type: { value: string; label: string };
        reason: string | null;
        status: { value: string; label: string };
        approved_by?: string | null;
        created_at?: string | null;
        updated_at?: string | null;
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

    let showDecisionDialog = $state(false);
    let decisionProcessing = $state(false);
    let selectedLeave = $state<LeaveItem | null>(null);
    let decisionType = $state<"approve" | "reject">("approve");
    let showDetailModal = $state(false);
    let selectedDetailLeave = $state<LeaveItem | null>(null);

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        if (filters.type) params.set("type", filters.type);
        if (filters.status) params.set("status", filters.status);
        router.get(
            "/leaves/manage" + (params.toString() ? `?${params}` : ""),
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
            "/leaves/manage?" + params.toString(),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    function openApproveDialog(leave: LeaveItem) {
        selectedLeave = leave;
        decisionType = "approve";
        showDecisionDialog = true;
    }
    function openRejectDialog(leave: LeaveItem) {
        selectedLeave = leave;
        decisionType = "reject";
        showDecisionDialog = true;
    }
    function openDetailModal(leave: LeaveItem) {
        selectedDetailLeave = leave;
        showDetailModal = true;
    }
</script>

<svelte:head>
    <title>Permohonan Izin | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Permohonan Izin
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola permohonan izin karyawan
            </p>
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
                label="Cari nama/alasan"
                placeholder="Nama karyawan atau alasan..."
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

    <Card title="Data Permohonan" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr
                            class="text-sm text-left text-gray-600 dark:text-gray-400"
                        >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Karyawan</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Periode</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Tipe</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Alasan</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Aksi</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if leaves?.length}
                            {#each leaves as l}
                                <tr
                                    class="border-b border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {l.user?.name || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {l.formatted_period ||
                                                `${formatDateDisplay(l.start_date)} - ${formatDateDisplay(l.end_date)}`}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {l.type.label}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
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
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {l.reason || "-"}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                onclick={() =>
                                                    openDetailModal(l)}
                                                >Detail</Button
                                            >
                                            <Button
                                                variant="success"
                                                size="sm"
                                                icon="fa-solid fa-check"
                                                disabled={l.status.value !==
                                                    "pending"}
                                                onclick={() =>
                                                    openApproveDialog(l)}
                                                >Setujui</Button
                                            >
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                icon="fa-solid fa-xmark"
                                                disabled={l.status.value !==
                                                    "pending"}
                                                onclick={() =>
                                                    openRejectDialog(l)}
                                                >Tolak</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
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
    bind:isOpen={showDecisionDialog}
    type={decisionType === "approve" ? "success" : "danger"}
    title={decisionType === "approve"
        ? "Setujui Permohonan"
        : "Tolak Permohonan"}
    message={selectedLeave
        ? `Apakah Anda yakin ingin ${decisionType === "approve" ? "menyetujui" : "menolak"} permohonan izin ${selectedLeave.user?.name ?? "-"} untuk periode ${formatDateDisplay(selectedLeave.start_date)} - ${formatDateDisplay(selectedLeave.end_date)}?`
        : ""}
    confirmText={decisionType === "approve" ? "Ya, Setujui" : "Ya, Tolak"}
    cancelText="Batal"
    showCancel={true}
    loading={decisionProcessing}
    onConfirm={async () => {
        decisionProcessing = true;
        if (selectedLeave) {
            const id = selectedLeave.id;
            if (decisionType === "approve") {
                await router.put(
                    `/leaves/${id}/approve`,
                    { status: "approved" },
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            decisionProcessing = false;
                            selectedLeave = null;
                        },
                    },
                );
            } else {
                await router.put(
                    `/leaves/${id}/reject`,
                    { status: "rejected" },
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            decisionProcessing = false;
                            selectedLeave = null;
                        },
                    },
                );
            }
        } else {
            decisionProcessing = false;
        }
    }}
    onCancel={() => {
        selectedLeave = null;
    }}
    onClose={() => {
        selectedLeave = null;
        decisionProcessing = false;
    }}
/>

<Modal
    bind:isOpen={showDetailModal}
    title="Detail Permohonan Izin"
    onClose={() => {
        selectedDetailLeave = null;
        showDetailModal = false;
    }}
>
    {#snippet children()}
        {#if selectedDetailLeave}
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Karyawan
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {selectedDetailLeave.user?.name || "-"}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Periode
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {formatDateDisplay(selectedDetailLeave.start_date)}
                            {" "}
                            -
                            {" "}
                            {formatDateDisplay(selectedDetailLeave.end_date)}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Tipe
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {selectedDetailLeave.type.label}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Status
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            <Badge
                                size="sm"
                                rounded="pill"
                                variant={getLeaveStatusVariant(
                                    selectedDetailLeave?.status.value ?? "",
                                )}
                            >
                                {#snippet children()}
                                    {selectedDetailLeave?.status.label ?? "-"}
                                {/snippet}
                            </Badge>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Alasan
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {selectedDetailLeave.reason || "-"}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Diajukan
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {formatDateTimeDisplay(
                                selectedDetailLeave.created_at,
                            )}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Diubah
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            {formatDateTimeDisplay(
                                selectedDetailLeave.updated_at,
                            )}
                        </div>
                    </div>
                </div>
                {#if selectedDetailLeave?.status.value === "pending"}
                    <div class="flex justify-end gap-2">
                        <Button
                            variant="success"
                            size="sm"
                            icon="fa-solid fa-check"
                            onclick={() => {
                                if (selectedDetailLeave) {
                                    openApproveDialog(selectedDetailLeave);
                                    showDetailModal = false;
                                }
                            }}
                        >
                            Setujui
                        </Button>
                        <Button
                            variant="danger"
                            size="sm"
                            icon="fa-solid fa-xmark"
                            onclick={() => {
                                if (selectedDetailLeave) {
                                    openRejectDialog(selectedDetailLeave);
                                    showDetailModal = false;
                                }
                            }}
                        >
                            Tolak
                        </Button>
                    </div>
                {/if}
            </div>
        {:else}
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Data tidak tersedia.
            </div>
        {/if}
    {/snippet}
</Modal>
