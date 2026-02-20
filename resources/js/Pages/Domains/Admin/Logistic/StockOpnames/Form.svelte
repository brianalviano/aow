<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import logo from "@img/logo.png";

    type IdName = { id: string; name: string };
    type ProductItem = { id: string; name: string; sku: string | null };
    type UserItem = { id: string; name: string; email: string };
    type Option = { value: string; label: string };

    type OpnamePayload = {
        id: string;
        number: string;
        scheduled_date: string | null;
        status: string;
        status_label?: string | null;
        warehouse?: { id: string | null; name: string | null } | null;
        notes?: string | null;
        user_ids?: string[];
        product_ids?: string[];
    } | null;
    let opname = $derived(($page.props.opname as OpnamePayload) ?? null);
    let isEdit = $derived(!!opname);

    let warehouses = $derived(($page.props.warehouses as IdName[]) ?? []);
    let users = $derived(($page.props.users as UserItem[]) ?? []);
    let products = $derived(($page.props.products as ProductItem[]) ?? []);

    const form = useForm({
        warehouse_id: "",
        scheduled_date: new Date().toISOString().split("T")[0],
        notes: "",
        user_ids: [] as string[],
        product_ids: [] as string[],
        status: "" as string,
    });

    let scheduledDateStr = $state<string>(String($form.scheduled_date ?? ""));
    $effect(() => {
        $form.scheduled_date = scheduledDateStr;
    });
    let initialized = $state(false);
    $effect(() => {
        if (!initialized && opname) {
            $form.warehouse_id = String(opname?.warehouse?.id ?? "") || "";
            scheduledDateStr =
                String(opname?.scheduled_date ?? "") || scheduledDateStr;
            $form.notes = String(opname?.notes ?? "");
            $form.user_ids = Array.isArray(opname?.user_ids)
                ? (opname?.user_ids as string[])
                : [];
            $form.product_ids = Array.isArray(opname?.product_ids)
                ? (opname?.product_ids as string[])
                : [];
            initialized = true;
        }
    });

    let showWarehouseModal = $state(false);
    let showInfoModal = $state(false);
    let showTeamModal = $state(false);
    let showNotesModal = $state(false);
    let showAddProductModal = $state(false);
    let addProductId = $state<string>("");
    let teamSearch = $state<string>("");
    let productSearch = $state<string>("");
    let addSelectedProductIds = $state<string[]>([]);

    const warehouseOptions = $derived<Option[]>(
        warehouses.map((w) => ({ value: w.id, label: w.name })),
    );
    const selectedWarehouse = $derived(() => {
        const wid = $form.warehouse_id ? String($form.warehouse_id) : "";
        if (!wid) return null;
        return warehouses.find((w) => String(w.id) === wid) ?? null;
    });

    const productOptions = $derived<Option[]>(
        products.map((p) => ({
            value: p.id,
            label: p.sku ? `${p.name} (${p.sku})` : p.name,
        })),
    );
    const takenProductIds = $derived(() => new Set($form.product_ids));
    const availableProductOptions = $derived<Option[]>(
        products
            .filter((p) => !takenProductIds().has(p.id))
            .map((p) => ({
                value: p.id,
                label: p.sku ? `${p.name} (${p.sku})` : p.name,
            })),
    );
    const filteredUsers = $derived(() => {
        const q = teamSearch.trim().toLowerCase();
        if (!q) return users;
        return users.filter(
            (u) =>
                u.name.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q),
        );
    });
    const availableProductsList = $derived(() => {
        const set = takenProductIds();
        const base = products.filter((p) => !set.has(p.id));
        const q = productSearch.trim().toLowerCase();
        if (!q) return base;
        return base.filter((p) => {
            const label = p.sku ? `${p.name} (${p.sku})` : p.name;
            return label.toLowerCase().includes(q);
        });
    });

    function keydownActivate(action: () => void) {
        return (e: KeyboardEvent) => {
            if (e.key === "Enter" || e.key === " ") {
                action();
            }
        };
    }

    function handleSaveDraft() {
        $form.status = "draft";
        if (isEdit && opname?.id) {
            $form.put(`/stock-opnames/${opname.id}`);
        } else {
            $form.post("/stock-opnames");
        }
    }

    function handleSaveScheduled() {
        $form.status = "scheduled";
        if (isEdit && opname?.id) {
            $form.put(`/stock-opnames/${opname.id}`);
        } else {
            $form.post("/stock-opnames");
        }
    }

    function backToList() {
        router.get("/stock-opnames");
    }

    function removeProduct(id: string) {
        $form.product_ids = $form.product_ids.filter((pid) => pid !== id);
    }

    function addProductSave() {
        const ids =
            addSelectedProductIds.length > 0
                ? addSelectedProductIds
                : (() => {
                      const id = String(addProductId).trim();
                      return id ? [id] : [];
                  })();
        if (ids.length === 0) return;
        const set = new Set($form.product_ids);
        ids.forEach((pid) => {
            if (!set.has(pid)) set.add(pid);
        });
        $form.product_ids = Array.from(set);
        addSelectedProductIds = [];
        addProductId = "";
        productSearch = "";
        showAddProductModal = false;
    }

    function toggleUserSelection(id: string) {
        const set = new Set($form.user_ids);
        if (set.has(id)) set.delete(id);
        else set.add(id);
        $form.user_ids = Array.from(set);
    }
    function toggleAddProductSelection(id: string) {
        const set = new Set(addSelectedProductIds);
        if (set.has(id)) set.delete(id);
        else set.add(id);
        addSelectedProductIds = Array.from(set);
    }
    function selectAllTeam() {
        const ids = filteredUsers().map((u) => u.id);
        const set = new Set($form.user_ids);
        ids.forEach((id) => set.add(id));
        $form.user_ids = Array.from(set);
    }
    function selectAllProductsFiltered() {
        const ids = availableProductsList().map((p) => p.id);
        addSelectedProductIds = Array.from(
            new Set([...addSelectedProductIds, ...ids]),
        );
    }
    function clearAllTeam() {
        $form.user_ids = [];
    }
    function clearAllProductsSelection() {
        addSelectedProductIds = [];
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Ubah Stok Opname" : "Buat Stok Opname"} | {siteName(
            $page.props.settings,
        )}</title
    >
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Ubah Stok Opname" : "Buat Stok Opname"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Edit rencana stok opname pada status Draft"
                    : "Tambahkan rencana stok opname baru"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                type="button"
                icon="fa-solid fa-save"
                loading={$form.processing}
                disabled={$form.processing}
                onclick={handleSaveDraft}>Simpan Draft</Button
            >
            <Button
                variant="primary"
                type="button"
                icon="fa-solid fa-save"
                loading={$form.processing}
                disabled={$form.processing}
                onclick={handleSaveScheduled}>Simpan & Jadwalkan</Button
            >
        </div>
    </header>

    <form id="stock-opname-form" onsubmit={(e) => e.preventDefault()}>
        <Card collapsible={false}>
            <div class="flex flex-col md:flex-row justify-between items-start">
                <div class="w-full md:w-1/2">
                    <div class="mb-2">
                        <img
                            src={logo}
                            alt="Logo"
                            class="w-90 object-contain"
                            loading="lazy"
                        />
                    </div>
                    <div
                        class="flex items-center gap-2 mt-1 text-sm text-gray-700 dark:text-gray-300"
                    >
                        <span
                            >Nama Gudang : {selectedWarehouse()?.name ||
                                "-"}</span
                        >
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer hover:text-orange-500"
                            role="button"
                            tabindex="0"
                            aria-label="Pilih gudang"
                            onclick={() => (showWarehouseModal = true)}
                            onkeydown={keydownActivate(
                                () => (showWarehouseModal = true),
                            )}
                        ></i>
                    </div>
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
                                Tgl Jadwal
                            </p>
                            <div class="flex items-center justify-end gap-2">
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {$form.scheduled_date || "-"}
                                </p>
                                <i
                                    class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Ubah tanggal"
                                    onclick={() => (showInfoModal = true)}
                                    onkeydown={keydownActivate(
                                        () => (showInfoModal = true),
                                    )}
                                ></i>
                            </div>
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
                        TIM STOCK OPNAME
                    </p>
                    <div class="flex items-center gap-2 mb-2">
                        <h3
                            class="text-lg font-medium text-gray-800 dark:text-white"
                        >
                            {#if $form.user_ids.length > 0}
                                {$form.user_ids
                                    .map(
                                        (id) =>
                                            users.find((u) => u.id === id)
                                                ?.name || "",
                                    )
                                    .filter((x) => x)
                                    .join(", ")}
                            {:else}
                                -
                            {/if}
                        </h3>
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer text-sm"
                            role="button"
                            tabindex="0"
                            aria-label="Pilih tim"
                            onclick={() => (showTeamModal = true)}
                            onkeydown={keydownActivate(
                                () => (showTeamModal = true),
                            )}
                        ></i>
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        {#if $form.user_ids.length > 0}
                            Dikerjakan oleh {$form.user_ids.length} orang.
                        {:else}
                            Belum ada tim yang dipilih.
                        {/if}
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            CATATAN UNTUK TIM
                        </p>
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                            role="button"
                            tabindex="0"
                            aria-label="Ubah catatan"
                            onclick={() => (showNotesModal = true)}
                            onkeydown={keydownActivate(
                                () => (showNotesModal = true),
                            )}
                        ></i>
                    </div>
                    <div
                        class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed"
                    >
                        {($form.notes || "").trim() || "-"}
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <div class="overflow-x-auto mb-6">
                <table
                    class="w-full text-sm text-left custom-table border-collapse dark:text-gray-300"
                >
                    <thead
                        class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-200 dark:bg-gray-800"
                    >
                        <tr>
                            <th scope="col" class="px-3 py-2 text-center w-12"
                                >NO</th
                            >
                            <th scope="col" class="px-3 py-2 w-1/3">PRODUK</th>
                            <th scope="col" class="px-3 py-2 w-1/3">SKU</th>
                            <th scope="col" class="px-3 py-2 text-center w-24"
                                >AKSI</th
                            >
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 dark:text-gray-300">
                        {#each $form.product_ids as pid, index}
                            <tr>
                                <td class="px-3 py-2 text-center"
                                    >{index + 1}</td
                                >
                                <td class="px-3 py-2">
                                    {(() => {
                                        const p = products.find(
                                            (pp) => pp.id === pid,
                                        );
                                        return p ? p.name : "-";
                                    })()}
                                </td>
                                <td class="px-3 py-2">
                                    {(() => {
                                        const p = products.find(
                                            (pp) => pp.id === pid,
                                        );
                                        return p?.sku ?? "-";
                                    })()}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <i
                                        class="fa-solid fa-trash-can text-red-500 cursor-pointer text-xs"
                                        role="button"
                                        tabindex="0"
                                        aria-label="Hapus produk"
                                        onclick={() => removeProduct(pid)}
                                        onkeydown={keydownActivate(() =>
                                            removeProduct(pid),
                                        )}
                                    ></i>
                                </td>
                            </tr>
                        {/each}
                        <tr>
                            <td
                                colspan="3"
                                class="px-3 py-2 bg-gray-50/50 dark:bg-gray-800/50"
                            >
                                <Button
                                    variant="link"
                                    icon="fa-solid fa-circle-plus"
                                    class="flex items-center gap-1 text-green-600 dark:text-green-400 font-bold hover:text-green-700 dark:hover:text-green-300 text-xs uppercase"
                                    onclick={() => (showAddProductModal = true)}
                                    >Tambah Produk</Button
                                >
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-800 border-none"
                            ></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </form>
</section>

<Modal bind:isOpen={showWarehouseModal} title="Pilih Gudang" size="md">
    {#snippet children()}
        <div class="space-y-3">
            <Select
                id="warehouse_id"
                name="warehouse_id"
                label="Gudang"
                options={warehouseOptions}
                searchable={true}
                required
                bind:value={$form.warehouse_id}
                error={$form.errors.warehouse_id}
            />
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showWarehouseModal = false)}
            >Tutup</Button
        >
    {/snippet}
</Modal>

<Modal bind:isOpen={showInfoModal} title="Informasi Stok Opname" size="md">
    {#snippet children()}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <DateInput
                id="scheduled_date"
                name="scheduled_date"
                label="Tanggal Jadwal"
                required
                bind:value={scheduledDateStr}
                error={$form.errors.scheduled_date}
            />
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showInfoModal = false)}
            >Tutup</Button
        >
    {/snippet}
</Modal>

<Modal bind:isOpen={showTeamModal} title="Pilih Tim Stock Opname" size="lg">
    {#snippet children()}
        <div class="space-y-3">
            <TextInput
                id="user_search"
                name="user_search"
                label="Cari Tim"
                placeholder="Nama atau email"
                bind:value={teamSearch}
            />
            <div class="space-y-2">
                {#each filteredUsers() as u}
                    <div class="flex items-center justify-between">
                        <div class="truncate">
                            <span class="text-sm text-gray-900 dark:text-white"
                                >{u.name}</span
                            >
                            <span class="ml-2 text-xs text-gray-500"
                                >{u.email}</span
                            >
                        </div>
                        <Checkbox
                            id={"user_" + u.id}
                            checked={$form.user_ids.includes(u.id)}
                            onclick={() => toggleUserSelection(u.id)}
                        />
                    </div>
                {/each}
                {#if $form.errors.user_ids}
                    <div class="mt-1 text-xs text-red-600">
                        {$form.errors.user_ids}
                    </div>
                {/if}
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button
            variant="success"
            disabled={!filteredUsers().length}
            onclick={selectAllTeam}>Pilih Semua</Button
        >
        <Button
            variant="danger"
            disabled={!$form.user_ids.length}
            onclick={clearAllTeam}>Hapus Semua</Button
        >
        <Button variant="secondary" onclick={() => (showTeamModal = false)}
            >Tutup</Button
        >
    {/snippet}
</Modal>

<Modal bind:isOpen={showNotesModal} title="Catatan untuk Tim" size="md">
    {#snippet children()}
        <TextArea
            id="notes"
            name="notes"
            label="Catatan"
            bind:value={$form.notes}
            error={$form.errors.notes}
        />
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showNotesModal = false)}
            >Tutup</Button
        >
    {/snippet}
</Modal>

<Modal
    bind:isOpen={showAddProductModal}
    title="Tambah Produk Diopname"
    size="lg"
>
    {#snippet children()}
        <div class="space-y-3">
            <TextInput
                id="product_search"
                name="product_search"
                label="Cari Produk"
                placeholder="Nama atau SKU"
                bind:value={productSearch}
            />
            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                {#each availableProductsList() as p}
                    <div class="flex items-center justify-between">
                        <div class="truncate">
                            <span class="text-sm text-gray-900 dark:text-white"
                                >{p.name}</span
                            >
                            {#if p.sku}
                                <span class="ml-2 text-xs text-gray-500"
                                    >{p.sku}</span
                                >
                            {/if}
                        </div>
                        <Checkbox
                            id={"prod_" + p.id}
                            checked={addSelectedProductIds.includes(p.id)}
                            onclick={() => toggleAddProductSelection(p.id)}
                        />
                    </div>
                {/each}
            </div>
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button
            variant="success"
            disabled={!availableProductsList().length}
            onclick={selectAllProductsFiltered}>Pilih Semua</Button
        >
        <Button
            variant="danger"
            disabled={!addSelectedProductIds.length}
            onclick={clearAllProductsSelection}>Hapus Semua</Button
        >
        <Button
            variant="secondary"
            onclick={() => (showAddProductModal = false)}>Batal</Button
        >
        <Button
            variant="success"
            disabled={!addSelectedProductIds.length}
            onclick={addProductSave}>Tambah</Button
        >
    {/snippet}
</Modal>
