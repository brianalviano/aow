<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatDateDisplay,
        formatDateTimeDisplay,
    } from "@/Lib/Admin/Utils/date";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";

    type CustomerDetail = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        address: string | null;
        is_active: boolean;
        is_visible_in_pos?: boolean;
        is_visible_in_marketing?: boolean;
        source_label?: string | null;
        created_by_name?: string | null;
        marketers?: { id: string; name: string }[] | null;
        last_transaction_at: string | null;
        created_at: string | null;
        updated_at: string | null;
    };

    let customer = $derived($page.props.customer as CustomerDetail);
    type CustomerSale = {
        id: string;
        receipt_number: string;
        invoice_number: string;
        sale_datetime: string | null;
        grand_total: number;
        outstanding_amount: number;
        payment_status: string;
        payment_status_label: string;
        payment_total?: number;
        shortage_amount?: number;
    };
    let sales = $derived(($page.props.sales as CustomerSale[]) ?? []);
    let salesTotalOutstanding = $derived(
        Number($page.props.sales_total_outstanding ?? 0),
    );
    type Meta = {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
    type Filters = {
        payment_status?: string;
        sale_date_from?: string;
        sale_date_to?: string;
        per_page?: number;
    };
    type StatusOption = { value: string; label: string };
    let meta = $derived($page.props.meta as Meta);
    let filters = $derived(($page.props.filters as Filters) ?? {});
    let statusOptions = $derived(
        ($page.props.statusOptions as StatusOption[]) ?? [],
    );
    const perPageOptions = [
        { value: 10, label: "10" },
        { value: 25, label: "25" },
        { value: 50, label: "50" },
    ];
    function visitWithParams(params: Record<string, string | number | null>) {
        const url = new URL(
            `/customers/${customer.id}`,
            window.location.origin,
        );
        for (const [k, v] of Object.entries(params)) {
            if (v === null || v === undefined) continue;
            const s = String(v);
            if (s.trim() === "") continue;
            url.searchParams.set(k, s);
        }
        router.visit(url.toString(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }
    function updateFilter<K extends keyof Filters>(key: K, value: Filters[K]) {
        const params: Record<string, string | number | null> = {
            payment_status: filters.payment_status ?? "",
            sale_date_from: filters.sale_date_from ?? "",
            sale_date_to: filters.sale_date_to ?? "",
            per_page: filters.per_page ?? meta?.per_page ?? 10,
        };
        if (key === "per_page") {
            params.per_page = Number(value ?? 10);
        } else {
            params[key as string] = (value as string) ?? "";
        }
        params.page = null;
        visitWithParams(params);
    }
    function gotoPage(pageNum: number) {
        const params: Record<string, string | number | null> = {
            payment_status: filters.payment_status ?? "",
            sale_date_from: filters.sale_date_from ?? "",
            sale_date_to: filters.sale_date_to ?? "",
            per_page: filters.per_page ?? meta?.per_page ?? 10,
            page: pageNum,
        };
        visitWithParams(params);
    }
    function getInputValue(
        e: Event | { value: string; numericValue: number | null },
    ): string {
        const any = e as any;
        if (typeof any?.value === "string") return any.value as string;
        const t = (e as Event).target as HTMLInputElement | null;
        return t?.value ?? "";
    }

    function backToList() {
        router.visit("/customers");
    }

    function editCustomer() {
        router.visit(`/customers/${customer.id}/edit`);
    }

    function openSale(id: string) {
        router.visit(`/sales/${id}`);
    }
</script>

<svelte:head>
    <title>Detail Pelanggan | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Pelanggan
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {customer.name} - {customer.email}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                onclick={editCustomer}>Edit</Button
            >
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Pelanggan" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {customer.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Email
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {customer.email}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Telepon
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {customer.phone || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Transaksi
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateDisplay(
                                    customer.last_transaction_at,
                                )}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Status
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {customer.is_active ? "Aktif" : "Tidak Aktif"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Sumber
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {customer.source_label ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dibuat Oleh
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {customer.created_by_name ?? "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Visibilitas
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {[
                                    customer.is_visible_in_pos ? "POS" : "",
                                    customer.is_visible_in_marketing
                                        ? "Marketing"
                                        : "",
                                ]
                                    .filter(Boolean)
                                    .join(", ") || "-"}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Assign ke Marketer
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {(customer.marketers ?? [])
                                    .map((m) => m.name)
                                    .join(", ") || "-"}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Alamat
                            </p>
                            {#if customer.address}
                                <p
                                    class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {customer.address}
                                </p>
                            {:else}
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada alamat.
                                </p>
                            {/if}
                        </div>
                    </div>
                {/snippet}
            </Card>

            <Card title="Informasi Sistem" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dibuat Pada
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(customer.created_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(customer.updated_at)}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>
    </div>
    <div class="space-y-4 lg:col-span-1">
        <Card title="Riwayat Penjualan" collapsible={false}>
            {#snippet children()}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Total Piutang
                        </p>
                        <p
                            class="text-base font-semibold text-gray-900 dark:text-white"
                        >
                            {formatCurrency(salesTotalOutstanding)}
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <Select
                            id="payment_status"
                            label="Status Pembayaran"
                            value={filters.payment_status ?? ""}
                            options={[
                                { value: "", label: "Semua" },
                                ...statusOptions,
                            ]}
                            onchange={(val) =>
                                updateFilter("payment_status", String(val))}
                        />
                        <Select
                            id="per_page"
                            label="Per Halaman"
                            value={filters.per_page ?? meta?.per_page ?? 10}
                            options={perPageOptions}
                            onchange={(val) =>
                                updateFilter("per_page", Number(val))}
                        />
                        <TextInput
                            id="sale_date_from"
                            name="sale_date_from"
                            label="Tanggal Dari"
                            type="date"
                            value={filters.sale_date_from ?? ""}
                            oninput={(e) =>
                                updateFilter(
                                    "sale_date_from",
                                    getInputValue(e),
                                )}
                        />
                        <TextInput
                            id="sale_date_to"
                            name="sale_date_to"
                            label="Tanggal Sampai"
                            type="date"
                            value={filters.sale_date_to ?? ""}
                            oninput={(e) =>
                                updateFilter("sale_date_to", getInputValue(e))}
                        />
                    </div>
                    {#if sales.length === 0}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Belum ada riwayat penjualan.
                        </p>
                    {:else}
                        <div class="overflow-x-auto">
                            <table
                                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Nomor
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Tanggal
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Status
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Total
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Pembayaran
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Kekurangan
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700"
                                >
                                    {#each sales as s}
                                        <tr
                                            class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                            onclick={() => openSale(s.id)}
                                        >
                                            <td
                                                class="px-3 py-2 text-sm text-gray-900 dark:text-white"
                                            >
                                                {s.invoice_number ||
                                                    s.receipt_number ||
                                                    "-"}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300"
                                            >
                                                {formatDateTimeDisplay(
                                                    s.sale_datetime,
                                                )}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300"
                                            >
                                                {s.payment_status_label}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm text-right text-gray-900 dark:text-white"
                                            >
                                                {formatCurrency(s.grand_total)}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm text-right text-gray-900 dark:text-white"
                                            >
                                                {formatCurrency(
                                                    s.payment_total ?? 0,
                                                )}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm text-right text-gray-900 dark:text-white"
                                            >
                                                {formatCurrency(
                                                    s.shortage_amount ??
                                                        s.outstanding_amount,
                                                )}
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                            <div class="flex items-center justify-between mt-3">
                                <p
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    Halaman {meta.current_page} dari {meta.last_page}
                                    — Total {meta.total}
                                </p>
                                <div class="flex gap-2">
                                    <Button
                                        variant="secondary"
                                        disabled={meta.current_page <= 1}
                                        onclick={() =>
                                            gotoPage(meta.current_page - 1)}
                                        icon="fa-solid fa-chevron-left"
                                        >Sebelumnya</Button
                                    >
                                    <Button
                                        variant="secondary"
                                        disabled={meta.current_page >=
                                            meta.last_page}
                                        onclick={() =>
                                            gotoPage(meta.current_page + 1)}
                                        icon="fa-solid fa-chevron-right"
                                        >Berikutnya</Button
                                    >
                                </div>
                            </div>
                        </div>
                    {/if}
                </div>
            {/snippet}
        </Card>
    </div>
</section>
