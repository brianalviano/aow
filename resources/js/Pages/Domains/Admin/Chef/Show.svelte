<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    interface Transfer {
        id: string;
        amount: number;
        fee_percentage: number;
        fee_amount: number;
        gross_amount: number;
        note: string | null;
        transfer_proof: string | null;
        transferred_at: string;
        created_at: string;
    }

    interface Product {
        id: string;
        name: string;
        price: number;
    }

    interface Chef {
        id: string;
        name: string;
        business_name: string | null;
        email: string;
        phone: string | null;
        bank_name: string | null;
        account_number: string | null;
        account_name: string | null;
        note: string | null;
        fee_percentage: number;
        address: string | null;
        is_active: boolean;
        order_types: ("instant" | "preorder")[];
        total_sales: number;
        total_fee_amount: number;
        net_sales: number;
        total_transferred: number;
        outstanding_balance: number;
        products: Product[];
        transfers: Transfer[];
        created_at: string;
        updated_at: string;
    }

    let chefProp = $derived($page.props.chef as { data: Chef });
    let chef = $derived(chefProp.data);

    // Transfer form
    let showTransferForm = $state(false);

    const transferForm = useForm({
        gross_amount: 0,
        note: "",
        transfer_proof: null as File | null,
        transferred_at: new Date().toISOString().split("T")[0],
    });

    // Preview calculation
    let previewFeeAmount = $derived(
        Math.round(($transferForm.gross_amount * chef.fee_percentage) / 100),
    );
    let previewNetAmount = $derived(
        $transferForm.gross_amount - previewFeeAmount,
    );

    // Proof image viewer
    let proofDialogOpen = $state(false);
    let proofImageUrl = $state("");

    function openProofDialog(url: string) {
        proofImageUrl = url;
        proofDialogOpen = true;
    }

    function submitTransfer(e: SubmitEvent) {
        e.preventDefault();

        $transferForm.post(`/admin/chefs/${chef.id}/transfers`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                showTransferForm = false;
                $transferForm.reset();
            },
        });
    }

    function formatCurrency(amount: number): string {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }

    function formatDate(dateStr: string): string {
        return new Date(dateStr).toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    function formatDateTime(dateStr: string): string {
        return new Date(dateStr).toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }
</script>

<svelte:head>
    <title>Detail Chef — {chef.name} | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Chef
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Informasi lengkap, penjualan, dan riwayat transfer
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                href="/admin/chefs"
            >
                Kembali
            </Button>
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                href={`/admin/chefs/${chef.id}/edit`}
            >
                Edit
            </Button>
        </div>
    </header>

    <!-- Sales Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <StatCard
            label="Total Penjualan"
            value={formatCurrency(chef.total_sales)}
            icon="fa-solid fa-chart-line"
            color="blue"
        />
        <StatCard
            label="Fee Perusahaan"
            value={formatCurrency(chef.total_fee_amount)}
            icon="fa-solid fa-percent"
            color="purple"
        />
        <StatCard
            label="Penjualan Bersih"
            value={formatCurrency(chef.net_sales)}
            icon="fa-solid fa-coins"
            color="green"
        />
        <StatCard
            label="Sudah Ditransfer"
            value={formatCurrency(chef.total_transferred)}
            icon="fa-solid fa-paper-plane"
            color="teal"
        />
        <StatCard
            label="Sisa Belum Transfer"
            value={formatCurrency(chef.outstanding_balance)}
            icon="fa-solid fa-clock"
            color={chef.outstanding_balance > 0 ? "orange" : "green"}
        />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Profile Info -->
        <Card title="Profil Chef">
            {#snippet children()}
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Nama
                        </div>
                        <div
                            class="mt-1 text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                        >
                            {chef.name}
                            {#if chef.is_active}
                                <Badge
                                    size="sm"
                                    rounded="pill"
                                    variant="success"
                                >
                                    {#snippet children()}Aktif{/snippet}
                                </Badge>
                            {:else}
                                <Badge
                                    size="sm"
                                    rounded="pill"
                                    variant="danger"
                                >
                                    {#snippet children()}Nonaktif{/snippet}
                                </Badge>
                            {/if}
                        </div>
                    </div>
                    {#if chef.business_name}
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Nama Usaha
                            </div>
                            <div
                                class="mt-1 text-base text-primary font-semibold"
                            >
                                <i class="fa-solid fa-shop mr-1"></i>
                                {chef.business_name}
                            </div>
                        </div>
                    {/if}
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Email
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {chef.email}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Telepon
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {chef.phone || "-"}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Alamat
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {chef.address || "-"}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Fee Perusahaan
                        </div>
                        <div
                            class="mt-1 text-base font-semibold text-blue-600 dark:text-blue-400"
                        >
                            {chef.fee_percentage}%
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Tipe Pesanan
                        </div>
                        <div class="mt-1 flex flex-wrap gap-1">
                            {#each chef.order_types as type}
                                <Badge
                                    size="sm"
                                    rounded="pill"
                                    variant={type === "instant"
                                        ? "success"
                                        : "warning"}
                                >
                                    {#snippet children()}
                                        {type === "instant"
                                            ? "Bisa Instant & Pre-Order"
                                            : "Hanya Pre-Order (PO Only)"}
                                    {/snippet}
                                </Badge>
                            {/each}
                        </div>
                    </div>
                    {#if chef.note}
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Catatan
                            </div>
                            <div
                                class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                            >
                                {chef.note}
                            </div>
                        </div>
                    {/if}
                </div>
            {/snippet}
        </Card>

        <!-- Bank Info & Products -->
        <div class="space-y-6">
            <Card title="Informasi Bank">
                {#snippet children()}
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Bank
                            </div>
                            <div
                                class="mt-1 text-base text-gray-900 dark:text-white"
                            >
                                {chef.bank_name || "-"}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                No. Rekening
                            </div>
                            <div
                                class="mt-1 text-base text-gray-900 dark:text-white font-mono"
                            >
                                {chef.account_number || "-"}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Nama Pemilik Rekening
                            </div>
                            <div
                                class="mt-1 text-base text-gray-900 dark:text-white"
                            >
                                {chef.account_name || "-"}
                            </div>
                        </div>
                    </div>
                {/snippet}
            </Card>

            <Card title="Produk yang Di-assign">
                {#snippet children()}
                    {#if chef.products && chef.products.length > 0}
                        <div class="space-y-2">
                            {#each chef.products as product}
                                <div
                                    class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                >
                                    <span
                                        class="text-sm text-gray-900 dark:text-white"
                                    >
                                        {product.name}
                                    </span>
                                    {#if product.price}
                                        <span
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {formatCurrency(product.price)}
                                        </span>
                                    {/if}
                                </div>
                            {/each}
                        </div>
                    {:else}
                        <p
                            class="text-sm text-gray-500 dark:text-gray-400 text-center py-4"
                        >
                            Belum ada produk yang di-assign
                        </p>
                    {/if}
                {/snippet}
            </Card>
        </div>
    </div>

    <!-- Transfer Section -->
    <Card title="Riwayat Transfer" bodyWithoutPadding={!showTransferForm}>
        {#snippet actions()}
            <Button
                variant={showTransferForm ? "secondary" : "primary"}
                size="sm"
                icon={showTransferForm
                    ? "fa-solid fa-times"
                    : "fa-solid fa-plus"}
                onclick={() => (showTransferForm = !showTransferForm)}
            >
                {showTransferForm ? "Batal" : "Catat Transfer"}
            </Button>
        {/snippet}
        {#snippet children()}
            <!-- Transfer Form -->
            {#if showTransferForm}
                <form
                    onsubmit={submitTransfer}
                    class="mb-6 p-4 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg"
                >
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white mb-4"
                    >
                        Catat Transfer Baru
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <TextInput
                                id="gross_amount"
                                name="gross_amount"
                                label="Jumlah Kotor (Rp)"
                                currency
                                placeholder="0"
                                value={$transferForm.gross_amount.toString()}
                                oninput={(e: any) => {
                                    if (e.numericValue !== undefined) {
                                        $transferForm.gross_amount =
                                            e.numericValue ?? 0;
                                    }
                                }}
                                error={$transferForm.errors.gross_amount}
                                required
                            />

                            <!-- Preview Calculation -->
                            {#if $transferForm.gross_amount > 0}
                                <div
                                    class="text-xs space-y-1 p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700"
                                >
                                    <div
                                        class="flex justify-between text-gray-600 dark:text-gray-400"
                                    >
                                        <span>Jumlah Kotor</span>
                                        <span
                                            >{formatCurrency(
                                                $transferForm.gross_amount,
                                            )}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between text-red-600 dark:text-red-400"
                                    >
                                        <span
                                            >Fee Perusahaan ({chef.fee_percentage}%)</span
                                        >
                                        <span
                                            >- {formatCurrency(
                                                previewFeeAmount,
                                            )}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between font-semibold text-green-700 dark:text-green-400 border-t border-gray-200 dark:border-gray-700 pt-1"
                                    >
                                        <span>Nett Transfer ke Chef</span>
                                        <span
                                            >{formatCurrency(
                                                previewNetAmount,
                                            )}</span
                                        >
                                    </div>
                                </div>
                            {/if}

                            <TextInput
                                id="transferred_at"
                                name="transferred_at"
                                label="Tanggal Transfer"
                                type="date"
                                value={$transferForm.transferred_at ?? ""}
                                oninput={(e) => {
                                    if (
                                        e &&
                                        typeof e === "object" &&
                                        "target" in e
                                    ) {
                                        $transferForm.transferred_at = (
                                            e.target as HTMLInputElement
                                        ).value;
                                    }
                                }}
                                error={$transferForm.errors.transferred_at}
                                required
                            />
                        </div>
                        <div class="space-y-4">
                            <TextArea
                                id="transfer_note"
                                name="note"
                                label="Catatan (Opsional)"
                                placeholder="Catatan transfer..."
                                bind:value={$transferForm.note}
                                error={$transferForm.errors.note}
                                rows={2}
                            />

                            <FileUpload
                                id="transfer_proof"
                                name="transfer_proof"
                                label="Bukti Transfer (Opsional)"
                                accept="image/*"
                                bind:value={$transferForm.transfer_proof}
                                error={$transferForm.errors.transfer_proof}
                                uploadText="Pilih atau seret foto bukti transfer"
                                uploadSubtext="Batas maksimal 2MB. Format: JPG, PNG."
                                maxSize={2 * 1024 * 1024}
                            />
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <Button
                            variant="success"
                            type="submit"
                            loading={$transferForm.processing}
                            disabled={$transferForm.processing}
                            icon="fa-solid fa-save"
                        >
                            Simpan Transfer
                        </Button>
                    </div>
                </form>
            {/if}

            <!-- Transfer History Table -->
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kotor</th>
                            <th>Fee</th>
                            <th>Nett</th>
                            <th>Catatan</th>
                            <th class="w-20 text-center">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if chef.transfers && chef.transfers.length > 0}
                            {#each chef.transfers as transfer}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDate(
                                                transfer.transferred_at,
                                            )}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatCurrency(
                                                transfer.gross_amount,
                                            )}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-red-600 dark:text-red-400"
                                        >
                                            -{formatCurrency(
                                                transfer.fee_amount,
                                            )}
                                            <span class="text-xs text-gray-400"
                                                >({transfer.fee_percentage}%)</span
                                            >
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-green-700 dark:text-green-400"
                                        >
                                            {formatCurrency(transfer.amount)}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate"
                                        >
                                            {transfer.note || "-"}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {#if transfer.transfer_proof}
                                            <button
                                                type="button"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm"
                                                aria-label="Lihat bukti transfer"
                                                onclick={() =>
                                                    openProofDialog(
                                                        transfer.transfer_proof!,
                                                    )}
                                            >
                                                <i class="fa-solid fa-image"
                                                ></i>
                                            </button>
                                        {:else}
                                            <span class="text-gray-400 text-sm"
                                                >-</span
                                            >
                                        {/if}
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Belum ada riwayat transfer
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
    </Card>

    <!-- Timestamps -->
    <Card title="Informasi Lainnya">
        {#snippet children()}
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400"
            >
                <div>
                    <span class="font-medium">Terdaftar:</span>
                    {formatDateTime(chef.created_at)}
                </div>
                <div>
                    <span class="font-medium">Terakhir Diperbarui:</span>
                    {formatDateTime(chef.updated_at)}
                </div>
            </div>
        {/snippet}
    </Card>
</section>

<!-- Transfer Proof Image Viewer -->
{#if proofDialogOpen}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
        role="dialog"
        aria-label="Bukti Transfer"
    >
        <div
            class="relative bg-white dark:bg-[#0f0f0f] rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
        >
            <div
                class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Bukti Transfer
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    aria-label="Tutup"
                    onclick={() => {
                        proofDialogOpen = false;
                    }}
                >
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-4">
                <img
                    src={proofImageUrl}
                    alt="Bukti Transfer"
                    class="w-full rounded-lg"
                />
            </div>
        </div>
    </div>
{/if}
