<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { onMount, onDestroy } from "svelte";

    type Rel = { id: string | null; name: string | null; sku?: string | null };
    type OpnameHead = {
        id: string;
        number: string;
        status: string;
        status_label: string;
    };
    type AssignmentHead = { id: string; status: string; status_label: string };
    type ItemRow = {
        id: string;
        product: Rel;
        actual_quantity: number;
        status: string;
        notes?: string | null;
    };

    let opname = $derived($page.props.opname as OpnameHead as OpnameHead);
    let assignment = $derived(
        $page.props.assignment as AssignmentHead as AssignmentHead,
    );
    let items = $derived(($page.props.items as ItemRow[]) ?? []);

    const form = useForm({
        items: [] as { product_id: string; actual_quantity: string }[],
        notes_map: {} as Record<string, string>,
        actual_map: {} as Record<string, string>,
    });

    $effect(() => {
        if (items.length > 0 && $form.items.length === 0) {
            $form.items = items.map((it) => ({
                product_id: String(it.product?.id ?? ""),
                actual_quantity:
                    String(it.status ?? "").toLowerCase() === "pending"
                        ? ""
                        : String(it.actual_quantity ?? 0),
            }));
            $form.notes_map = Object.fromEntries(
                items.map((it) => [
                    String(it.product?.id ?? ""),
                    it.notes ?? "",
                ]),
            );
            $form.actual_map = Object.fromEntries(
                items.map((it) => [
                    String(it.product?.id ?? ""),
                    String(it.status ?? "").toLowerCase() === "pending"
                        ? ""
                        : String(it.actual_quantity ?? 0),
                ]),
            );
        }
    });

    let started = false;
    $effect(() => {
        if (!started) {
            const s = String(opname.status ?? "").toLowerCase();
            if (s !== "in_progress" && s !== "completed" && s !== "canceled") {
                started = true;
                router.post(
                    `/stock-opnames/${opname.id}/assignments/${assignment.id}/start`,
                    {},
                    { preserveState: true, preserveScroll: true },
                );
            }
        }
    });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        const original = $form.items.slice();
        $form.items = original.map((it) => {
            const pid = String(it.product_id ?? "");
            const raw = String($form.actual_map[pid] ?? "").trim();
            return {
                product_id: it.product_id,
                actual_quantity: raw === "" ? (null as any) : raw,
            };
        });
        $form.post(`/stock-opnames/${opname.id}/assignments/${assignment.id}`, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                $form.items = original;
            },
        });
    }

    function getAssignmentStatusVariant(
        status: string,
    ): "success" | "warning" | "danger" | "info" | "secondary" {
        const s = String(status ?? "").toLowerCase();
        if (s === "completed") return "success";
        if (s === "assigned") return "info";
        if (s === "pending") return "warning";
        return "secondary";
    }
    let actualErrors = $derived<Record<string, string>>(
        (() => {
            const arr = Array.isArray($form.errors.items)
                ? ($form.errors.items as any[])
                : [];
            const map: Record<string, string> = {};
            for (let i = 0; i < $form.items.length; i++) {
                const pid = String($form.items[i]!.product_id ?? "");
                const msg = (arr[i]?.actual_quantity as string) ?? "";
                if (msg) map[pid] = msg;
            }
            return map;
        })(),
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
            highlightTimer = null;
        }
        highlightPid = pid;
        highlightTimer = setTimeout(() => {
            if (highlightPid === pid) {
                highlightPid = "";
            }
        }, 1200) as unknown as number;
    }
    function focusActualInput(pid: string): void {
        const el = document.getElementById(
            "actual_" + pid,
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
            items.find(
                (x) =>
                    String(x.product?.name ?? "")
                        .toLowerCase()
                        .includes(ql) ||
                    (x.product?.sku
                        ? String(x.product?.sku).toLowerCase().includes(ql)
                        : false),
            ) ?? null;
        const pid = String(it?.product?.id ?? "");
        return pid || null;
    }
    function incrementActualByProductId(pid: string): void {
        if (!pid) return;
        const current = String($form.actual_map[pid] ?? "").trim();
        const num = Math.max(0, Number(current || 0));
        const next = String(num + 1);
        $form.actual_map[pid] = next;
    }
    function addBySku(code: string): string | null {
        const v = code.trim();
        if (v.length < 4) return null;
        const it =
            items.find((x) =>
                equalsIgnoreCase(String(x.product?.sku ?? ""), v),
            ) ?? null;
        if (!it) return null;
        const pid = String(it.product?.id ?? "");
        if (!pid) return null;
        incrementActualByProductId(pid);
        return pid;
    }
    const SCAN_GAP_THRESHOLD_MS = 50;
    const SCAN_END_DELAY_MS = 80;
    const MIN_SCAN_LENGTH = 4;
    let scanBuffer = $state<string>("");
    let lastKeyTs = $state<number>(0);
    let scanTimer = $state<number | null>(null);
    let inputTimer = $state<number | null>(null);
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
        return ae.id === "opname_search";
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
            focusActualInput(pid);
        }
        resetScan();
    }
    function tryAddFromSearch(e?: KeyboardEvent): void {
        const code = q.trim();
        if (code.length < MIN_SCAN_LENGTH) return;
        const pid = addBySku(code);
        if (pid) {
            q = "";
            ensureRowVisible(pid);
            focusActualInput(pid);
        } else {
            const targetPid = findPidFromQuery(code);
            if (targetPid) ensureRowVisible(targetPid);
        }
        if (e) e.preventDefault();
    }
    function scheduleSearchScroll(): void {
        if (inputTimer !== null) {
            clearTimeout(inputTimer);
            inputTimer = null;
        }
        inputTimer = setTimeout(() => {
            const pid = findPidFromQuery(q);
            if (pid) ensureRowVisible(pid);
        }, SCAN_END_DELAY_MS) as unknown as number;
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
                tryAddFromSearch(e);
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
        const el = document.getElementById(
            "opname_search",
        ) as HTMLElement | null;
        el?.focus();
    });
    $effect(() => {
        const code = q.trim();
        if (code !== lastSearchCode) {
            lastSearchCode = code;
            if (code.length >= MIN_SCAN_LENGTH) {
                scheduleSearchScroll();
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
</script>

<svelte:head>
    <title>Penugasan Stok Opname | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Penugasan: {opname.number}
            </h1>
            <Badge
                size="sm"
                rounded="pill"
                variant={getAssignmentStatusVariant(assignment.status)}
                title={assignment.status_label}
            >
                {#snippet children()}
                    {assignment.status_label}
                {/snippet}
            </Badge>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <TextInput
                id="opname_search"
                name="search"
                placeholder="Cari produk atau SKU..."
                bind:value={q}
                onkeypress={(e) => {
                    const ev = e as KeyboardEvent;
                    if (ev.key === "Enter") {
                        tryAddFromSearch(ev);
                    }
                }}
                oninput={() => {
                    scheduleSearchScroll();
                }}
                class="min-w-70 w-full sm:w-[320px]"
            />
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={() => router.get(`/stock-opnames/${opname.id}`)}
                >Kembali</Button
            >
        </div>
    </header>

    <form onsubmit={submit}>
        <Card collapsible={false} bodyWithoutPadding={true}>
            {#snippet children()}
                <div class="overflow-x-auto">
                    <table
                        class="custom-table min-w-max table-auto overflow-hidden"
                    >
                        <thead>
                            <tr>
                                <th class="text-left">Produk</th>
                                <th class="text-right">Jumlah Aktual</th>
                                <th class="text-left">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if $form.items.length >= items.length}
                                {#each items as it, i}
                                    <tr
                                        id={"row_" +
                                            String(it.product?.id ?? "")}
                                        class={"transition-colors " +
                                            (highlightPid ===
                                            String(it.product?.id ?? "")
                                                ? "bg-yellow-50 dark:bg-[#181200]"
                                                : "")}
                                    >
                                        <td>
                                            {it.product?.sku
                                                ? `${it.product?.name} (${it.product?.sku})`
                                                : it.product?.name}
                                        </td>
                                        <td class="text-right">
                                            <div class="w-40 ml-auto">
                                                <TextInput
                                                    id={"actual_" +
                                                        String(
                                                            it.product?.id ??
                                                                "",
                                                        )}
                                                    name={"actual_" +
                                                        String(
                                                            it.product?.id ??
                                                                "",
                                                        )}
                                                    type="number"
                                                    stripZeros={true}
                                                    min={0}
                                                    step={1}
                                                    bind:value={
                                                        $form.actual_map[
                                                            String(
                                                                it.product
                                                                    ?.id ?? "",
                                                            )
                                                        ]!
                                                    }
                                                    error={actualErrors[
                                                        String(
                                                            it.product?.id ??
                                                                "",
                                                        )
                                                    ]}
                                                    onkeypress={(e) => {
                                                        const ev =
                                                            e as KeyboardEvent;
                                                        if (
                                                            ev.key === "Enter"
                                                        ) {
                                                            const pid = String(
                                                                it.product
                                                                    ?.id ?? "",
                                                            );
                                                            if (pid)
                                                                incrementActualByProductId(
                                                                    pid,
                                                                );
                                                            ev.preventDefault();
                                                        }
                                                    }}
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="w-64">
                                                <TextInput
                                                    id={"note_" + String(i)}
                                                    name={"note_" + String(i)}
                                                    type="text"
                                                    bind:value={
                                                        $form.notes_map[
                                                            String(
                                                                it.product
                                                                    ?.id ?? "",
                                                            )
                                                        ]!
                                                    }
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                {/each}
                            {:else}
                                <tr>
                                    <td
                                        colspan="3"
                                        class="p-4 text-center text-gray-500"
                                    >
                                        Memuat...
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            {/snippet}
        </Card>
        <div class="flex items-center justify-end gap-2 mt-4">
            <Button
                variant="secondary"
                onclick={() => router.get(`/stock-opnames/${opname.id}`)}
                >Batal</Button
            >
            <Button variant="primary" type="submit" loading={$form.processing}
                >Simpan</Button
            >
        </div>
    </form>
</section>
