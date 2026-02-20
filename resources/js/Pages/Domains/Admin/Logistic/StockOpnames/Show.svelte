<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        getCurrencySymbol,
        formatCurrencyWithoutSymbol,
    } from "@/Lib/Admin/Utils/currency";
    import logo from "@img/logo.png";

    type Rel = { id: string | null; name: string | null; sku?: string | null };
    type OpnameHead = {
        id: string;
        number: string;
        scheduled_date?: string | null;
        status: string;
        status_label: string;
        grand_total?: number;
        warehouse: Rel;
        notes?: string | null;
    };
    type ItemSummary = {
        product: Rel;
        system_quantity: number;
        actual_total: number;
        difference: number;
        hpp?: number;
        subtotal?: number;
        assignments?: {
            assignment_id: string;
            user: { id: string; name: string };
            actual_quantity: number;
            counted_at?: string | null;
        }[];
    };
    type Assignment = {
        id: string;
        status: string;
        status_label: string;
        user: { id: string; name: string; email: string };
    };

    let opname = $derived($page.props.opname as OpnameHead as OpnameHead);
    let itemsByProduct = $derived(
        ($page.props.items_by_product as ItemSummary[]) ?? [],
    );
    let assignments = $derived(($page.props.assignments as Assignment[]) ?? []);
    let authUserId = $derived(String($page.props.auth?.user?.id ?? ""));
    let myAssignmentId = $derived(
        String(
            assignments.find((a) => String(a.user?.id ?? "") === authUserId)
                ?.id ?? "",
        ),
    );

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
    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        }).format(d);
    }

    function goBack() {
        router.get("/stock-opnames");
    }

    function formatDateTimeShort(input?: string | null): string {
        const s = String(input ?? "");
        if (!s) return "-";
        const d = new Date(s);
        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "2-digit",
            year: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false,
        }).format(d);
    }
    let canSettle = $derived(Boolean($page.props.can_settle ?? false));
    function settle() {
        const ok = window.confirm(
            "Selesaikan dokumen tanpa perubahan stok? Tindakan ini akan menandai semua penugasan selesai.",
        );
        if (!ok) return;
        router.post(
            `/stock-opnames/${opname.id}/settle`,
            {},
            { preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Detail Stok Opname | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Detail Stok Opname
                </h1>
                <Badge
                    size="sm"
                    rounded="pill"
                    variant={getOpnameStatusVariant(opname.status)}
                    title={opname.status_label}
                >
                    {#snippet children()}
                        {opname.status_label}
                    {/snippet}
                </Badge>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                {opname.number}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            {#if String(opname.status).toLowerCase() === "scheduled" && canSettle}
                <Button
                    variant="success"
                    icon="fa-solid fa-check"
                    onclick={settle}>Selesaikan</Button
                >
            {/if}
            {#if myAssignmentId}
                <Button
                    variant="warning"
                    icon="fa-solid fa-clipboard-check"
                    href={`/stock-opnames/${opname.id}/assignments/${myAssignmentId}`}
                    >Kerjakan Tugas</Button
                >
            {/if}
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={goBack}>Kembali</Button
            >
        </div>
    </header>

    <Card collapsible={false} bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="p-6">
                <div
                    class="w-full flex flex-col md:flex-row justify-between items-start gap-6"
                >
                    <div class="w-full md:w-1/2">
                        <div class="mb-2">
                            <img
                                src={logo}
                                alt="Logo"
                                class="w-90 object-contain"
                                loading="lazy"
                            />
                        </div>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 mt-1"
                        >
                            Gudang: {opname.warehouse?.name || "-"}
                        </p>
                    </div>
                    <div
                        class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                    >
                        <h2
                            class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                        >
                            STOCK OPNAME
                        </h2>
                        <div class="w-full flex justify-end gap-12 text-right">
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    No Surat
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {opname.number ?? "-"}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    Tgl Jadwal
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {formatDateIndo(opname.scheduled_date)}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="border-gray-200 dark:border-[#212121] my-4" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            STATUS
                        </p>
                        <div class="mt-1">
                            <Badge
                                size="sm"
                                rounded="pill"
                                variant={getOpnameStatusVariant(opname.status)}
                                title={opname.status_label}
                            >
                                {#snippet children()}
                                    {opname.status_label}
                                {/snippet}
                            </Badge>
                        </div>
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-1"
                        >
                            CATATAN
                        </p>
                        <div
                            class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                        >
                            {(opname.notes || "").trim() || "-"}
                        </div>
                    </div>
                </div>
                <hr class="border-gray-200 dark:border-[#212121] my-4" />
                <p
                    class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                >
                    Penugasan & Ringkasan Produk
                </p>
                <div class="overflow-x-auto">
                    <table class="custom-table min-w-full">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Produk / Petugas</th>
                                <th class="text-center">Waktu</th>
                                <th class="text-center">Stok Sistem</th>
                                <th class="text-center">Jumlah Aktual</th>
                                <th class="text-center">Selisih</th>
                                <th class="text-right">HPP</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if itemsByProduct?.length}
                                {#each itemsByProduct as it, idx}
                                    {@const rowsCount =
                                        (it.assignments?.length ?? 0) + 1}
                                    <tr>
                                        <td
                                            rowspan={rowsCount}
                                            class="text-center">{idx + 1}</td
                                        >
                                        <td>
                                            {it.product?.sku
                                                ? `${it.product?.name} (${it.product?.sku})`
                                                : it.product?.name}
                                        </td>
                                        <td class="bg-gray-50 dark:bg-[#0b0b0b]"
                                        ></td>
                                        <td
                                            rowspan={rowsCount}
                                            class="text-center"
                                            >{it.system_quantity}</td
                                        >
                                        <td class="text-center"
                                            >{it.actual_total}</td
                                        >
                                        <td
                                            rowspan={rowsCount}
                                            class="text-center"
                                            >{it.difference}</td
                                        >
                                        <td rowspan={rowsCount}>
                                            <div
                                                class="flex gap-3 justify-between"
                                            >
                                                <div>Rp</div>
                                                <div>
                                                    {formatCurrencyWithoutSymbol(
                                                        it.hpp ?? 0,
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            rowspan={rowsCount}
                                            class="text-right bg-red-50 dark:bg-[#1a0a0a] text-red-600 dark:text-red-400"
                                        >
                                            <div
                                                class="flex gap-3 justify-between"
                                            >
                                                <div>Rp</div>
                                                <div>
                                                    {formatCurrencyWithoutSymbol(
                                                        it.subtotal ?? 0,
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    {#if it.assignments?.length}
                                        {#each it.assignments as br}
                                            <tr
                                                class="bg-amber-50 dark:bg-[#1a1a1a]"
                                            >
                                                <td
                                                    class="bg-amber-50 text-orange-600 dark:bg-[#1a1a1a]"
                                                >
                                                    {br.user?.name || "-"}
                                                </td>
                                                <td
                                                    class="bg-amber-50 dark:bg-[#1a1a1a] text-orange-600 text-center"
                                                    >{formatDateTimeShort(
                                                        br.counted_at,
                                                    )}</td
                                                >
                                                <td
                                                    class="bg-amber-50 dark:bg-[#1a1a1a] text-orange-600 text-center"
                                                    >{br.actual_quantity}</td
                                                >
                                            </tr>
                                        {/each}
                                    {/if}
                                {/each}
                            {:else}
                                <tr>
                                    <td
                                        colspan="8"
                                        class="px-4 py-6 text-center text-gray-600 dark:text-gray-300"
                                        >Tidak ada data</td
                                    >
                                </tr>
                            {/if}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-4 py-2 text-right bg-gray-100 dark:bg-gray-800 font-semibold"
                                >
                                    TOTAL SELISIH
                                </td>
                                <td
                                    class="px-4 py-2 bg-red-50 dark:bg-[#1a0a0a] text-red-600 dark:text-red-400"
                                >
                                    <div class="flex gap-3 justify-between">
                                        <div>Rp</div>
                                        <div>
                                            {formatCurrencyWithoutSymbol(
                                                opname.grand_total ?? 0,
                                            )}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        {/snippet}
    </Card>
</section>
