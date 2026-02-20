<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import Modal from "@/Lib/Admin/Components/Ui/Modal.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import MediaViewer from "@/Lib/Admin/Components/Ui/MediaViewer.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack, onDestroy, onMount } from "svelte";
    import logo from "@img/logo.png";

    type PurchaseOrderInfo = {
        id: string;
        number: string;
        supplier: { name: string | null };
        warehouse: { name: string | null };
        status: string | null;
        status_label: string | null;
    };
    type PurchaseReturnInfo = {
        id: string;
        number: string | null;
        return_date: string | null;
        supplier: { name: string | null };
        warehouse: { name: string | null };
        purchase_order: { id: string | null; number: string | null };
        status: string | null;
        status_label: string | null;
        resolution: string | null;
    };
    type ItemRow = {
        product_id: string;
        product_name: string;
        sku?: string | null;
        sdo_quantity?: number;
        return_quantity?: number;
        received_quantity: number;
        remaining_quantity: number;
        quantity?: string;
        notes?: string;
    };
    type Option = { value: string; label: string };

    let po = $derived(
        ($page.props.purchase_order ?? null) as PurchaseOrderInfo | null,
    );
    let pr = $derived(
        ($page.props.purchase_return ?? null) as PurchaseReturnInfo | null,
    );
    let isReturn = $derived<boolean>(!!pr);
    type SdoInfo = {
        id: string;
        number: string | null;
        delivery_date: string | null;
    };
    let sdo = $derived(
        ($page.props.supplier_delivery_order ?? null) as SdoInfo | null,
    );
    let items = $derived(($page.props.items ?? []) as ItemRow[]);
    let reasonOptions = $derived(
        ($page.props.reason_options ?? []) as Option[],
    );
    let resolutionOptions = $derived(
        ($page.props.resolution_options ?? []) as Option[],
    );

    let isInvoiceViewerOpen = $state(false);
    let showFillAllDialog = $state(false);
    type MediaViewerItem = {
        src: string;
        type?:
            | "image"
            | "video"
            | "pdf"
            | "doc"
            | "docx"
            | "xls"
            | "xlsx"
            | "ppt"
            | "pptx";
        title?: string;
        thumbnail?: string;
    };
    let invoicePreviewItems = $state<MediaViewerItem[]>([]);
    let invoicePreviewUrl: string | null = null;

    onDestroy(() => {
        if (invoicePreviewUrl) {
            URL.revokeObjectURL(invoicePreviewUrl);
            invoicePreviewUrl = null;
        }
    });

    type FormItem = { product_id: string; quantity: string; notes: string };
    type ExceptionRow = {
        product_id: string;
        quantity: string;
        reason: string;
        resolution: string;
        notes: string;
    };
    type FormData = {
        sdo_id: string;
        sender_name: string;
        vehicle_plate_number: string;
        supplier_invoice_number: string;
        supplier_invoice_date: string;
        supplier_invoice_file: File | null;
        items: FormItem[];
        receive_map: Record<string, string>;
        exceptions: ExceptionRow[];
        exceptions_map: Record<
            string,
            {
                quantity: string;
                reason: string;
                resolution: string;
                notes: string;
            }
        >;
    };

    const form = useForm<FormData>(
        untrack(() => ({
            sdo_id: String(sdo?.id ?? ""),
            sender_name: "",
            vehicle_plate_number: "",
            supplier_invoice_number: "",
            supplier_invoice_date: "",
            supplier_invoice_file: null,
            items: items.map((it) => ({
                product_id: it.product_id,
                quantity: "",
                notes: "",
            })),
            receive_map: Object.fromEntries(
                items.map((it) => [String(it.product_id), ""]),
            ),
            exceptions: [],
            exceptions_map: Object.fromEntries(
                items.map((it) => [
                    String(it.product_id),
                    { quantity: "", reason: "", resolution: "", notes: "" },
                ]),
            ),
        })),
    );

    $effect(() => {
        $form.items = items.map((it) => ({
            product_id: it.product_id,
            quantity: "",
            notes: "",
        }));
        $form.receive_map = Object.fromEntries(
            items.map((it) => [String(it.product_id), ""]),
        );
        $form.exceptions = [];
        $form.exceptions_map = Object.fromEntries(
            items.map((it) => [
                String(it.product_id),
                { quantity: "", reason: "", resolution: "", notes: "" },
            ]),
        );
    });

    let indexMap = $derived<Record<string, number>>(
        Object.fromEntries(
            items.map((it, idx) => [String(it.product_id), idx]),
        ),
    );
    let q = $state<string>("");
    function equalsIgnoreCase(a: string, b: string): boolean {
        return a.trim().toLowerCase() === b.trim().toLowerCase();
    }
    let highlightPid = $state<string>("");
    let highlightTimer = $state<number | null>(null);
    function ensureRowVisible(pid: string): void {
        const row = document.getElementById("row_" + pid);
        row?.scrollIntoView({ behavior: "smooth", block: "center" });
        if (highlightTimer !== null) {
            clearTimeout(highlightTimer);
        }
        highlightPid = pid;
        highlightTimer = setTimeout(() => {
            if (highlightPid === pid) {
                highlightPid = "";
            }
        }, 1200) as unknown as number;
    }
    function focusQtyInput(pid: string): void {
        const el = document.getElementById(
            "qty_" + pid,
        ) as HTMLInputElement | null;
        el?.focus();
        try {
            (el as any)?.select?.();
        } catch {}
    }
    function findPidFromQuery(s: string): string | null {
        const ql = s.trim().toLowerCase();
        if (!ql) return null;
        const it =
            items.find((x) => {
                const nameMatch = String(x.product_name ?? "")
                    .toLowerCase()
                    .includes(ql);
                const skuMatch = x.sku
                    ? String(x.sku).toLowerCase().includes(ql)
                    : false;
                return nameMatch || skuMatch;
            }) ?? null;
        const pid = String(it?.product_id ?? "");
        return pid || null;
    }
    const SCAN_GAP_THRESHOLD_MS = 50;
    const SCAN_END_DELAY_MS = 80;
    const MIN_SCAN_LENGTH = 4;
    let inputTimer = $state<number | null>(null);
    function addBySku(code: string): string | null {
        const v = code.trim();
        if (v.length < MIN_SCAN_LENGTH) return null;
        const it =
            items.find((x) => equalsIgnoreCase(String(x.sku ?? ""), v)) ?? null;
        if (!it) return null;
        const pid = String(it.product_id ?? "");
        if (!pid) return null;
        incrementQuantityByProductId(pid);
        return pid;
    }
    function scheduleQuickScan(): void {
        if (inputTimer !== null) {
            clearTimeout(inputTimer);
            inputTimer = null;
        }
        inputTimer = setTimeout(() => {
            const code = q.trim();
            if (!code) return;
            const exactPid = addBySku(code);
            if (exactPid) {
                q = "";
                ensureRowVisible(exactPid);
                focusQtyInput(exactPid);
                return;
            }
            const pid = findPidFromQuery(code);
            if (pid) ensureRowVisible(pid);
        }, SCAN_END_DELAY_MS) as unknown as number;
    }
    function incrementQuantityByProductId(pid: string): void {
        if (!pid) return;
        const idx = indexMap[pid];
        if (typeof idx !== "number") return;
        const currentRaw = String($form.receive_map[pid] ?? "").trim();
        const current = Math.max(0, parseInt(currentRaw || "0", 10) || 0);
        const remaining = Math.max(
            0,
            Number(items[idx]?.remaining_quantity ?? 0),
        );
        const next = Math.min(current + 1, remaining);
        $form.receive_map[pid] = String(next);
    }
    function clampReceiveInput(pid: string, remaining: number): void {
        const raw = String($form.receive_map[pid] ?? "");
        const sanitized = raw.replace(/[^\d]/g, "");
        if (sanitized === "") {
            $form.receive_map[pid] = "";
            // Hapus pengecualian ketika qty diterima kosong
            clearException(pid);
            return;
        }
        let val = parseInt(sanitized, 10) || 0;
        const maxVal = Math.max(0, Number(remaining ?? 0));
        if (val > maxVal) val = maxVal;
        $form.receive_map[pid] = String(val);

        // Update logika pengecualian
        updateExceptionForProduct(pid, val, maxVal);
    }

    // Fungsi untuk menentukan apakah perlu menampilkan form pengecualian
    function shouldShowException(pid: string, remaining: number): boolean {
        const qtyStr = String($form.receive_map[pid] ?? "").trim();
        if (qtyStr === "") return false;

        const qty = parseInt(qtyStr, 10) || 0;
        const maxVal = Math.max(0, Number(remaining ?? 0));

        return qty < maxVal;
    }

    // Fungsi untuk update pengecualian otomatis
    function updateExceptionForProduct(
        pid: string,
        receivedQty: number,
        remaining: number,
    ): void {
        if (!$form.exceptions_map[pid]) return;

        const maxVal = Math.max(0, Number(remaining ?? 0));

        // Jika qty diterima sama dengan sisa, hapus pengecualian
        if (receivedQty >= maxVal) {
            clearException(pid);
            return;
        }

        // Jika qty diterima kurang dari sisa, isi qty pengecualian otomatis
        if (receivedQty < maxVal) {
            const exceptionQty = maxVal - receivedQty;
            $form.exceptions_map[pid]!.quantity = String(exceptionQty);
        }
    }

    // Fungsi untuk menghapus pengecualian
    function clearException(pid: string): void {
        if (!$form.exceptions_map[pid]) return;

        $form.exceptions_map[pid]!.quantity = "";
        $form.exceptions_map[pid]!.reason = "";
        $form.exceptions_map[pid]!.resolution = "";
        $form.exceptions_map[pid]!.notes = "";
    }
    function clampExceptionInput(pid: string, remaining: number): void {
        const raw = String($form.exceptions_map[pid]?.quantity ?? "");
        const sanitized = raw.replace(/[^\d]/g, "");
        if (!$form.exceptions_map[pid]) return;
        if (sanitized === "") {
            $form.exceptions_map[pid]!.quantity = "";
            return;
        }
        let val = parseInt(sanitized, 10) || 0;
        const maxVal = Math.max(0, Number(remaining ?? 0));
        if (val > maxVal) val = maxVal;
        $form.exceptions_map[pid]!.quantity = String(val);
    }
    function fillAllByRemaining(): void {
        items.forEach((row) => {
            const pid = String(row.product_id ?? "");
            if (!pid) return;
            const remaining = Math.max(0, Number(row.remaining_quantity ?? 0));
            $form.receive_map[pid] = String(remaining);
        });
        showFillAllDialog = false;
    }
    function tryReceiveFromSearch(e?: KeyboardEvent): void {
        const code = q.trim();
        if (code.length < MIN_SCAN_LENGTH) {
            if (e) e.preventDefault();
            return;
        }
        const pid = addBySku(code);
        if (pid) {
            q = "";
            ensureRowVisible(pid);
            focusQtyInput(pid);
            if (e) e.preventDefault();
        } else {
            const targetPid = findPidFromQuery(code);
            if (targetPid) ensureRowVisible(targetPid);
            if (e) e.preventDefault();
        }
    }
    let scanBuffer = $state<string>("");
    let lastKeyTs = $state<number>(0);
    let scanTimer = $state<number | null>(null);
    let lastSearchCode = $state<string>("");
    function resetScan(): void {
        scanBuffer = "";
        lastKeyTs = 0;
        if (scanTimer !== null) {
            clearTimeout(scanTimer);
            scanTimer = null;
        }
    }
    function isCaptureAllowed(): boolean {
        const ae = document.activeElement as HTMLElement | null;
        if (!ae) return true;
        const tag = (ae.tagName || "").toLowerCase();
        const isTypingField =
            tag === "input" ||
            tag === "textarea" ||
            ae.getAttribute("contenteditable") === "true";
        if (!isTypingField) return true;
        return ae.id === "quick_scan";
    }
    function processScan(code: string): void {
        const v = code.trim();
        if (v.length < MIN_SCAN_LENGTH) {
            resetScan();
            return;
        }
        const pid = addBySku(v);
        if (pid) {
            q = "";
            ensureRowVisible(pid);
            focusQtyInput(pid);
        }
        resetScan();
    }
    function handleGlobalKeydown(e: KeyboardEvent): void {
        const allowed = isCaptureAllowed();
        if (!allowed) return;
        if (e.ctrlKey || e.altKey || e.metaKey) return;
        const now = performance.now();
        const key = e.key;
        if (key === "Enter") {
            if (scanBuffer.length >= MIN_SCAN_LENGTH) {
                processScan(scanBuffer);
                e.preventDefault();
            }
            if (q.trim().length >= MIN_SCAN_LENGTH) {
                tryReceiveFromSearch(e);
            }
            return;
        }
        if (key === "Backspace") {
            if (scanBuffer.length > 0) {
                scanBuffer = scanBuffer.slice(0, -1);
                lastKeyTs = now;
            }
            return;
        }
        if (key.length === 1) {
            if (lastKeyTs > 0 && now - lastKeyTs > SCAN_GAP_THRESHOLD_MS) {
                resetScan();
            }
            scanBuffer += key;
            lastKeyTs = now;
            if (scanTimer !== null) {
                clearTimeout(scanTimer);
                scanTimer = null;
            }
            scanTimer = setTimeout(() => {
                if (scanBuffer.length >= MIN_SCAN_LENGTH) {
                    processScan(scanBuffer);
                }
            }, SCAN_END_DELAY_MS) as unknown as number;
        }
    }
    onMount(() => {
        window.addEventListener("keydown", handleGlobalKeydown);
        const el = document.getElementById("quick_scan") as HTMLElement | null;
        el?.focus();
    });
    $effect(() => {
        const code = q.trim();
        if (code !== lastSearchCode) {
            lastSearchCode = code;
            if (code.length >= MIN_SCAN_LENGTH) {
                scheduleQuickScan();
            }
        }
    });
    onDestroy(() => {
        window.removeEventListener("keydown", handleGlobalKeydown);
        if (scanTimer !== null) {
            clearTimeout(scanTimer);
            scanTimer = null;
        }
        if (inputTimer !== null) {
            clearTimeout(inputTimer);
            inputTimer = null;
        }
    });
    function handleSubmit(): void {
        const finalItems: FormItem[] = [];
        items.forEach((row, idx) => {
            const pid = String(row.product_id ?? "");
            const qtyStr = String($form.receive_map[pid] ?? "").trim();
            if (!qtyStr || qtyStr === "0") return;
            const qty = parseInt(qtyStr, 10);
            if (isNaN(qty) || qty <= 0) return;
            finalItems.push({
                product_id: pid,
                quantity: String(qty),
                notes: String($form.items[idx]?.notes ?? "").trim(),
            });
        });
        $form.items = finalItems;
        const finalExceptions: ExceptionRow[] = [];
        Object.entries($form.exceptions_map).forEach(([pid, ex]) => {
            const qStr = String(ex.quantity ?? "").trim();
            const qty = parseInt(qStr || "0", 10) || 0;
            const reason = String(ex.reason ?? "").trim();
            const resolution = String(ex.resolution ?? "").trim();
            if (qty > 0 && reason && resolution) {
                finalExceptions.push({
                    product_id: String(pid),
                    quantity: String(qty),
                    reason,
                    resolution,
                    notes: String(ex.notes ?? "").trim(),
                });
            }
        });
        $form.exceptions = finalExceptions;
        const storeUrl = isReturn
            ? `/goods-receipts/returns/${pr?.id}`
            : `/purchase-orders/${po?.id}/receivings`;
        $form.post(storeUrl, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                // ensure any modal state is closed after submit finishes
                showSenderModal = false;
            },
        });
    }

    let showSenderModal = $state(false);
    function keydownActivate(action: () => void) {
        return (e: KeyboardEvent) => {
            if (e.key === "Enter" || e.key === " ") {
                action();
            }
        };
    }

    // Format date indo
    function formatDateIndo(input?: string | null): string {
        const s = String(input ?? "");
        const d = s ? new Date(s) : new Date();
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(d);
    }

    let warehouseData = $derived(() => {
        if (isReturn && pr?.warehouse) {
            return {
                name: pr.warehouse.name ?? "",
                address: "",
                phone: "",
            };
        }
        if (po?.warehouse) {
            return {
                name: po.warehouse.name ?? "",
                address: "",
                phone: "",
            };
        }
        return null;
    });

    let supplierData = $derived(() => {
        if (isReturn && pr?.supplier) {
            return {
                name: pr.supplier.name ?? "",
            };
        }
        if (po?.supplier) {
            return {
                name: po.supplier.name ?? "",
            };
        }
        return null;
    });

    let documentDate = $derived(() => {
        if (isReturn) return pr?.return_date ?? "";
        return sdo?.delivery_date ?? "";
    });

    // Computed totals
    let totalReceiving = $derived(() => {
        let total = 0;
        items.forEach((row) => {
            const pid = String(row.product_id ?? "");
            const qtyStr = String($form.receive_map[pid] ?? "").trim();
            const qty = parseInt(qtyStr || "0", 10) || 0;
            total += qty;
        });
        return total;
    });

    let totalRemaining = $derived(() => {
        let total = 0;
        items.forEach((row) => {
            total += Number(row.remaining_quantity ?? 0);
        });
        return total;
    });

    $effect(() => {
        const file = $form.supplier_invoice_file;
        if (file instanceof File) {
            if (invoicePreviewUrl) {
                URL.revokeObjectURL(invoicePreviewUrl);
                invoicePreviewUrl = null;
            }
            invoicePreviewUrl = URL.createObjectURL(file);
            const ext = file.name.split(".").pop()?.toLowerCase() || "";
            let type:
                | "image"
                | "video"
                | "pdf"
                | "doc"
                | "docx"
                | "xls"
                | "xlsx"
                | "ppt"
                | "pptx" = "image";
            if (["pdf"].includes(ext)) type = "pdf";
            if (["doc"].includes(ext)) type = "doc";
            if (["docx"].includes(ext)) type = "docx";
            if (["xls"].includes(ext)) type = "xls";
            if (["xlsx"].includes(ext)) type = "xlsx";
            if (["ppt"].includes(ext)) type = "ppt";
            if (["pptx"].includes(ext)) type = "pptx";
            invoicePreviewItems = [
                {
                    src: invoicePreviewUrl,
                    type,
                    title: file.name,
                },
            ];
        } else {
            if (invoicePreviewUrl) {
                URL.revokeObjectURL(invoicePreviewUrl);
                invoicePreviewUrl = null;
            }
            invoicePreviewItems = [];
        }
    });

    let canSubmit = $derived(() => {
        if (!isReturn && !$form.sdo_id) return false;
        if (!String($form.sender_name ?? "").trim()) return false;
        if (!String($form.vehicle_plate_number ?? "").trim()) return false;
        const hasQty = items.some((row) => {
            const pid = String(row.product_id ?? "");
            const qtyStr = String($form.receive_map[pid] ?? "").trim();
            const qty = parseInt(qtyStr || "0", 10) || 0;
            return qty > 0;
        });
        return hasQty;
    });
</script>

<svelte:head>
    <title
        >{isReturn ? "Terima Retur" : "Terima Barang"} | {siteName(
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
                TANDA TERIMA BARANG
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isReturn
                    ? "Terima barang penggantian dari supplier"
                    : "Terima barang dari purchase order"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={() =>
                    router.visit(
                        isReturn ? "/purchase-returns" : "/purchase-orders",
                    )}
            >
                Kembali
            </Button>
            <Button
                variant="success"
                type="button"
                loading={$form.processing}
                disabled={$form.processing || !canSubmit()}
                icon="fa-solid fa-save"
                onclick={handleSubmit}
                class="w-full"
            >
                Simpan Penerimaan
            </Button>
        </div>
    </header>

    <form class="space-y-6" onsubmit={(e) => e.preventDefault()}>
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
                    {#if warehouseData()}
                        <div
                            class="flex items-center gap-2 mt-1 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <span>Nama Gudang : {warehouseData()?.name}</span>
                        </div>
                    {/if}
                </div>

                <div
                    class="w-full md:w-1/2 flex flex-col items-end mt-4 md:mt-0"
                >
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                    >
                        TANDA TERIMA BARANG
                    </h2>
                    <div class="w-full flex justify-end gap-12 text-right">
                        <div class="flex gap-12">
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    Tgl {isReturn ? "Retur" : "PO"}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {formatDateIndo(documentDate())}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                                >
                                    No {isReturn ? "Retur" : "PO"}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {isReturn
                                        ? (pr?.number ?? "-")
                                        : (sdo?.number ?? "-")}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-[#212121] my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    {#if supplierData()}
                        <p
                            class="text-xs font-bold text-gray-400 uppercase mb-1 dark:text-gray-500"
                        >
                            SUPPLIER
                        </p>
                        <div class="flex items-center gap-2 mb-2">
                            <h3
                                class="text-lg font-medium text-gray-800 dark:text-white"
                            >
                                {supplierData()?.name}
                            </h3>
                        </div>
                    {/if}
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <p
                            class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500"
                        >
                            DETAIL PENGIRIMAN
                        </p>
                        <i
                            class="fa-solid fa-pen text-orange-400 cursor-pointer text-xs"
                            role="button"
                            tabindex="0"
                            aria-label="Ubah detail pengiriman"
                            onclick={() => (showSenderModal = true)}
                            onkeydown={keydownActivate(
                                () => (showSenderModal = true),
                            )}
                        ></i>
                    </div>
                    <div
                        class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed space-y-1"
                    >
                        <div class="flex">
                            <span class="w-40">Nama Pengirim</span>
                            <span
                                >:
                                {($form.sender_name || "").trim() || "-"}</span
                            >
                        </div>
                        <div class="flex">
                            <span class="w-40">Plat Nomor</span>
                            <span
                                >:
                                {($form.vehicle_plate_number || "").trim() ||
                                    "-"}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 my-6">
                <div>
                    <p
                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-2"
                    >
                        Aksi
                    </p>
                    <Button
                        variant="success"
                        icon="fa-solid fa-circle-plus"
                        onclick={() => (showFillAllDialog = true)}
                        >Isi semua sesuai sisa</Button
                    >
                </div>
                <div>
                    <p
                        class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500 mb-2"
                    >
                        SCAN CEPAT
                    </p>
                    <TextInput
                        id="quick_scan"
                        name="quick_scan"
                        label=""
                        placeholder="Scan barcode / ketik nama produk / SKU lalu tekan Enter"
                        bind:value={q}
                        onkeypress={(e) => {
                            const ev = e as KeyboardEvent;
                            if (ev.key === "Enter") {
                                tryReceiveFromSearch(ev);
                            }
                        }}
                        oninput={() => {
                            scheduleQuickScan();
                        }}
                    />
                </div>
            </div>

            <div class="overflow-x-auto mb-6">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center w-12">NO</th>
                            <th scope="col" class="w-1/4">PRODUK</th>
                            <th scope="col" class="text-center w-20"
                                >{isReturn ? "QTY RETUR" : "QTY SDO"}</th
                            >
                            <th scope="col" class="text-center w-20"
                                >SUDAH DITERIMA</th
                            >
                            <th scope="col" class="text-center w-20">SISA</th>
                            <th scope="col" class="text-center w-28"
                                >QTY DITERIMA</th
                            >
                            <th scope="col" class="w-1/4">CATATAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each items as row, idx}
                            <tr
                                id={"row_" + String(row.product_id ?? "")}
                                class={highlightPid ===
                                String(row.product_id ?? "")
                                    ? "bg-yellow-50 dark:bg-[#181200]"
                                    : ""}
                            >
                                <td class="text-center">{idx + 1}</td>
                                <td>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span>
                                            {row.product_name}
                                        </span>
                                    </div>
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {row.sku || "-"}
                                    </div>
                                </td>
                                <td class="text-center">
                                    {isReturn
                                        ? (row.return_quantity ?? 0)
                                        : (row.sdo_quantity ?? 0)}
                                </td>
                                <td class="text-center">
                                    {row.received_quantity}
                                </td>
                                <td class="text-center font-semibold">
                                    {row.remaining_quantity}
                                </td>
                                <td>
                                    <TextInput
                                        id={"qty_" +
                                            String(row.product_id ?? "")}
                                        name={`items[${idx}][quantity]`}
                                        label=""
                                        type="number"
                                        min={0}
                                        max={row.remaining_quantity}
                                        bind:value={
                                            $form.receive_map[
                                                String(row.product_id ?? "")
                                            ]!
                                        }
                                        oninput={() => {
                                            const pid = String(
                                                row.product_id ?? "",
                                            );
                                            clampReceiveInput(
                                                pid,
                                                Number(
                                                    row.remaining_quantity ?? 0,
                                                ),
                                            );
                                        }}
                                        onkeypress={(e) => {
                                            const ev = e as KeyboardEvent;
                                            if (ev.key === "Enter") {
                                                const pid = String(
                                                    row.product_id ?? "",
                                                );
                                                if (pid)
                                                    incrementQuantityByProductId(
                                                        pid,
                                                    );
                                                ev.preventDefault();
                                            }
                                        }}
                                    />
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">
                                    <TextInput
                                        id={`receive-notes-${idx}`}
                                        name={`items[${idx}][notes]`}
                                        label=""
                                        bind:value={$form.items[idx]!.notes}
                                        placeholder="Catatan"
                                    />
                                </td>
                            </tr>
                            {#if shouldShowException(String(row.product_id ?? ""), Number(row.remaining_quantity ?? 0))}
                                <tr>
                                    <td colspan="7" class="px-3 py-2">
                                        <div
                                            class="grid grid-cols-1 md:grid-cols-4 gap-3"
                                        >
                                            <TextInput
                                                id={"exqty_" +
                                                    String(
                                                        row.product_id ?? "",
                                                    )}
                                                name={`exceptions_map[${String(row.product_id ?? "")}][quantity]`}
                                                label="Qty Pengecualian"
                                                type="number"
                                                min={0}
                                                readonly={true}
                                                disabled={true}
                                                bind:value={
                                                    $form.exceptions_map[
                                                        String(
                                                            row.product_id ??
                                                                "",
                                                        )
                                                    ]!.quantity
                                                }
                                            />
                                            <Select
                                                id={"exreason_" +
                                                    String(
                                                        row.product_id ?? "",
                                                    )}
                                                name={`exceptions_map[${String(row.product_id ?? "")}][reason]`}
                                                label="Alasan"
                                                options={reasonOptions}
                                                bind:value={
                                                    $form.exceptions_map[
                                                        String(
                                                            row.product_id ??
                                                                "",
                                                        )
                                                    ]!.reason
                                                }
                                            />
                                            <Select
                                                id={"exresolution_" +
                                                    String(
                                                        row.product_id ?? "",
                                                    )}
                                                name={`exceptions_map[${String(row.product_id ?? "")}][resolution]`}
                                                label="Resolusi"
                                                options={resolutionOptions}
                                                bind:value={
                                                    $form.exceptions_map[
                                                        String(
                                                            row.product_id ??
                                                                "",
                                                        )
                                                    ]!.resolution
                                                }
                                            />
                                            <TextInput
                                                id={"exnotes_" +
                                                    String(
                                                        row.product_id ?? "",
                                                    )}
                                                name={`exceptions_map[${String(row.product_id ?? "")}][notes]`}
                                                label="Catatan Pengecualian"
                                                bind:value={
                                                    $form.exceptions_map[
                                                        String(
                                                            row.product_id ??
                                                                "",
                                                        )
                                                    ]!.notes
                                                }
                                                placeholder="Contoh: rusak, salah kirim"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                        {/each}
                        <tr class="font-bold">
                            <td
                                colspan="5"
                                class="px-3 py-2 text-right bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700"
                                >TOTAL PENERIMAAN</td
                            >
                            <td
                                class="px-3 py-2 bg-white dark:bg-[#0a0a0a] border border-gray-300 dark:border-gray-700"
                            >
                                <div class="text-right font-bold">
                                    {totalReceiving()}
                                </div>
                            </td>
                            <td
                                class="px-3 py-2 text-right bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700"
                            ></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col md:flex-row justify-between items-center gap-4"
            >
                <div class="flex gap-6">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Total Sisa: <span class="font-semibold"
                            >{totalRemaining()}</span
                        >
                    </div>
                </div>
            </div>
        </Card>
        <Card title="Faktur Supplier" collapsible={false}>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <TextInput
                    id="supplier_invoice_number"
                    name="supplier_invoice_number"
                    label="Nomor Invoice Supplier"
                    bind:value={$form.supplier_invoice_number}
                />
                <DateInput
                    id="supplier_invoice_date"
                    name="supplier_invoice_date"
                    label="Tanggal Invoice"
                    bind:value={$form.supplier_invoice_date}
                />
                <div>
                    <FileUpload
                        id="supplier_invoice_file"
                        name="supplier_invoice_file"
                        label="File Invoice"
                        accept="image/*,application/pdf"
                        bind:value={$form.supplier_invoice_file}
                    />
                    {#if $form.supplier_invoice_file}
                        <Button
                            variant="link"
                            size="sm"
                            onclick={() => (isInvoiceViewerOpen = true)}
                            class="mt-2"
                        >
                            <i class="fa-solid fa-eye mr-2"></i>
                            Preview
                        </Button>
                    {/if}
                </div>
            </div>
        </Card>
    </form>
</section>

<Dialog
    bind:isOpen={showFillAllDialog}
    type="warning"
    title="Isi Semua Qty Sesuai Sisa"
    message={`Semua kolom qty akan diisi otomatis sesuai sisa untuk ${items.filter((r) => (r.remaining_quantity ?? 0) > 0).length} item. Lanjutkan?`}
    confirmText="Isi Sekarang"
    cancelText="Batal"
    showCancel={true}
    onConfirm={() => {
        fillAllByRemaining();
    }}
    onClose={() => {
        showFillAllDialog = false;
    }}
/>

<Modal
    bind:isOpen={showSenderModal}
    title="Ubah Detail Pengiriman"
    onClose={() => (showSenderModal = false)}
>
    {#snippet children()}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <TextInput
                id="sender_name_modal"
                name="sender_name_modal"
                label="Nama Pengirim"
                required={true}
                bind:value={$form.sender_name}
            />
            <TextInput
                id="vehicle_plate_number_modal"
                name="vehicle_plate_number_modal"
                label="Plat Nomor Kendaraan"
                required={true}
                bind:value={$form.vehicle_plate_number}
            />
        </div>
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showSenderModal = false)}>
            Tutup
        </Button>
    {/snippet}
</Modal>

{#if isInvoiceViewerOpen}
    <MediaViewer
        items={invoicePreviewItems}
        bind:isOpen={isInvoiceViewerOpen}
    />
{/if}
