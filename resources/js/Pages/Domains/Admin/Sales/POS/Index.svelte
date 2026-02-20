<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";
    import CashierSession from "./CashierSession.svelte";
    import { onMount, onDestroy } from "svelte";
    import CartPanel from "./CartPanel.svelte";
    import ConfirmModal from "./ConfirmModal.svelte";
    import SuccessModal from "./SuccessModal.svelte";
    import { openCenteredWindow } from "@/Lib/Admin/Utils/print";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import {
        priceForLevel as priceForLevelUtil,
        computeTotals,
        computeNonCashTarget,
        computeChangeAndShortage,
        computeMethodValid,
        computePaymentRule,
    } from "@/Lib/Admin/Utils/salesCalculator";

    type Product = {
        id: string;
        name: string;
        category: string;
        category_label: string;
        price: number;
        image_url?: string | null;
        sku?: string | null;
        product_category_id?: string | null;
        product_sub_category_id?: string | null;
    };

    type CartItem = {
        id: string;
        name: string;
        category: string;
        price: number;
        qty: number;
        note?: string | null;
        levelId?: string;
    };
    type LevelItem = {
        id: string;
        name: string;
        percent_adjust?: number | null;
    };
    type SelectOption = {
        value: string | number;
        label: string;
        disabled?: boolean;
    };
    type CustomerItem = {
        id: string;
        name: string;
        phone?: string | null;
        address?: string | null;
    };
    type PaymentMethodItem = {
        id: string;
        name: string;
    };
    type VoucherRow = {
        id: string;
        code: string;
        name: string;
        value_type: string;
        value?: number | null;
        min_order_amount?: number | null;
        usage_limit?: number | null;
        used_count?: number | null;
        max_uses_per_customer?: number | null;
        start_at?: string | null;
        end_at?: string | null;
    };
    type DiscountItemRef = {
        itemable_type: string;
        itemable_id: string;
        min_qty_buy?: number | null;
        free_product_id?: string | null;
        free_qty_get?: number | null;
        custom_value?: number | null;
        is_multiple?: boolean | null;
    };
    type DiscountRow = {
        id: string;
        name: string;
        type: string;
        scope: string;
        value_type: string;
        value?: number | null;
        items?: DiscountItemRef[];
    };
    type WarehouseItem = {
        id: string;
        name: string;
    };
    type ActiveShift = {
        id: string;
        number: string;
        opened_at: string;
        opening_balance: number;
        expected_closing_balance: number;
        total_sales: number;
        total_cash_in: number;
    } | null;
    type IdName = { id: string; name: string };
    type SaleCompleted = {
        id: string;
        receipt_number: string;
        invoice_number: string;
        sale_datetime: string | null;
        warehouse: IdName | null;
        customer: {
            id: string | null;
            name: string | null;
            phone?: string | null;
            address?: string | null;
        } | null;
        payment_status: string | null;
        payment_status_label: string | null;
        subtotal: number;
        discount_percentage: string | null;
        discount_amount: number;
        total_after_discount: number;
        is_value_added_tax_enabled: boolean;
        value_added_tax_percentage: string | null;
        value_added_tax_amount: number;
        grand_total: number;
        outstanding_amount: number;
        requires_delivery: boolean;
        items?: Array<{
            id: string;
            name: string;
            unit_price: number;
            subtotal: number;
            qty: number;
            note?: string | null;
            discounted?: boolean;
            discount_text?: string | null;
        }>;
        shipping_amount?: number;
        shipping_recipient_name?: string | null;
        shipping_recipient_phone?: string | null;
        shipping_address?: string | null;
        shipping_note?: string | null;
        payments?: Array<{
            payment_method_id: string | null;
            payment_method_name: string | null;
            amount: number;
            notes?: string | null;
        }>;
        payment_total?: number;
        change_amount?: number;
        shortage_amount?: number;
    };

    let settings = $derived(($page.props as any).settings);
    let products = $derived<Product[]>(
        (($page.props as any)?.products as Product[]) ?? [],
    );
    let activeTab = $state<string>("all");
    let q = $state<string>("");
    let levels = $derived<LevelItem[]>(
        (($page.props as any)?.levels as LevelItem[]) ?? [],
    );
    let sellingPriceMap = $derived(
        (($page.props as any)?.sellingPriceMap as Record<
            string,
            Record<string, number>
        >) ?? {},
    );
    let sellingPriceMainMap = $derived(
        (($page.props as any)?.sellingPriceMainMap as Record<string, number>) ??
            {},
    );
    let selectedLevelByProduct = $state<Record<string, string>>({});
    const customersFromServer = $derived<CustomerItem[]>(
        (($page.props as any)?.customers as CustomerItem[]) ?? [],
    );
    let customersExtra = $state<CustomerItem[]>([]);
    const customers = $derived<CustomerItem[]>([
        ...customersFromServer,
        ...customersExtra.filter(
            (c) => !customersFromServer.some((x) => x.id === c.id),
        ),
    ]);
    let paymentMethods = $derived<PaymentMethodItem[]>(
        (($page.props as any)?.payment_methods as PaymentMethodItem[]) ?? [],
    );
    let warehouses = $derived<WarehouseItem[]>(
        (($page.props as any)?.warehouses as WarehouseItem[]) ?? [],
    );
    let activeShift = $derived<ActiveShift>(
        ((($page.props as any)?.active_shift as ActiveShift) ??
            null) as ActiveShift,
    );
    let discounts = $derived<DiscountRow[]>(
        ((($page.props as any)?.discounts as DiscountRow[]) ??
            []) as DiscountRow[],
    );
    let vouchers = $derived<VoucherRow[]>(
        ((($page.props as any)?.vouchers as VoucherRow[]) ??
            []) as VoucherRow[],
    );
    let canOpenShiftToday = $derived<boolean>(
        Boolean(
            (($page.props as any)?.can_open_shift_today ?? true) as boolean,
        ),
    );
    const posBlocked = $derived<boolean>(
        activeShift === null && !canOpenShiftToday,
    );
    let selectedCustomerId = $state<string>("");
    let selectedWarehouseId = $state<string>("");
    let scanBuffer = $state<string>("");
    let lastKeyTs = $state<number>(0);
    let scanTimer = $state<number | null>(null);
    let inputTimer = $state<number | null>(null);
    let lastSearchCode = $state<string>("");
    let cart = $state<CartItem[]>([]);
    let taxEnabled = $state<boolean>(false);
    let vatPercentSelected = $state<number>(0);
    let taxInitDone = $state<boolean>(false);
    let vatOptions = $derived<number[]>(
        ((($page.props as any)?.vat_options as number[]) ?? []).map((n) =>
            Number(n),
        ),
    );
    let shippingStr = $state<string>("");
    let useShipping = $state<boolean>(false);
    let useSecondPayment = $state<boolean>(false);
    let selectedPayment1Id = $state<string>("");
    let selectedPayment2Id = $state<string>("");
    let payment1Str = $state<string>("");
    let payment2Str = $state<string>("");
    let payment1ManualOverride = $state<boolean>(false);
    let payment2ManualOverride = $state<boolean>(false);
    let showPaymentNoteModal = $state<boolean>(false);
    let paymentNote1 = $state<string>("");
    let paymentNote2 = $state<string>("");
    let paymentNoteDraft = $state<string>("");
    let paymentNoteModalMethodIndex = $state<number>(1);
    let showShippingModal = $state<boolean>(false);
    let useCustomerShipping = $state<boolean>(false);
    let useCustomerShippingModal = $state<boolean>(false);
    let shippingRecipientName = $state<string>("");
    let shippingRecipientPhone = $state<string>("");
    let shippingAddress = $state<string>("");
    let shippingNote = $state<string>("");
    let voucherCode = $state<string>("");
    let shippingRecipientNameDraft = $state<string>("");
    let shippingRecipientPhoneDraft = $state<string>("");
    let shippingAddressDraft = $state<string>("");
    let shippingNoteDraft = $state<string>("");
    let showConfirmModal = $state<boolean>(false);
    let showReceivableDialog = $state<boolean>(false);
    let receivableTitle = $state<string>("");
    let receivableMessage = $state<string>("");
    let receivableProceedAfterConfirm = $state<boolean>(false);
    let confirmProcessing = $state<boolean>(false);
    let showSuccessModal = $state<boolean>(false);
    let saleCompleted = $state<SaleCompleted | null>(null);
    let consumedSaleId = $state<string>("");

    const initialTaxRate = $derived<number>(
        Number(($page.props as any)?.vat_percent ?? 0),
    );
    const categories = $derived(
        ((($page.props as any)?.categories as {
            id: string;
            label: string;
        }[]) ?? []) as { id: string; label: string }[],
    );
    const tabs = $derived([
        { id: "all", label: "All", icon: "fa-solid fa-bars" },
        ...categories,
    ]);
    const SCAN_GAP_THRESHOLD_MS = 50;
    const SCAN_END_DELAY_MS = 80;
    const MIN_SCAN_LENGTH = 4;
    const payment1Amount = $derived<number>(
        Math.max(0, Number(payment1Str || 0)),
    );
    const payment2Amount = $derived<number>(
        Math.max(0, Number(payment2Str || 0)),
    );
    const paymentTotal = $derived<number>(
        useSecondPayment ? payment1Amount + payment2Amount : payment1Amount,
    );
    const methodValid = $derived<boolean>(
        computeMethodValid(
            selectedPayment1Id,
            selectedPayment2Id,
            useSecondPayment,
        ),
    );
    const taxRate = $derived<number>(taxEnabled ? vatPercentSelected : 0);
    const subtotal = $derived<number>(
        cart.reduce((s, x) => s + x.price * x.qty, 0),
    );
    const shippingCost = $derived<number>(
        useShipping ? Math.max(0, Number(shippingStr || 0)) : 0,
    );
    const totalsNoVoucher = $derived(
        computeTotals({
            cart,
            products,
            discounts,
            levels,
            sellingPriceMainMap,
            sellingPriceMap,
            taxEnabled,
            taxRatePercent: taxRate,
            shippingCost,
        }),
    );
    const totalAfterDiscountBeforeVoucher = $derived<number>(
        Math.max(0, subtotal - totalsNoVoucher.discountAmount),
    );
    const selectedVoucher = $derived<VoucherRow | null>(
        (() => {
            const code = (voucherCode || "").trim().toLowerCase();
            if (code === "") return null;
            const vlist = vouchers || [];
            const found =
                vlist.find(
                    (v) => String(v.code).trim().toLowerCase() === code,
                ) ?? null;
            return found ?? null;
        })(),
    );
    const voucherAmount = $derived<number>(
        (() => {
            const v = selectedVoucher;
            if (!v) return 0;
            const minOrder = Math.max(0, Number(v.min_order_amount ?? 0));
            if (totalAfterDiscountBeforeVoucher < minOrder) return 0;
            const now = new Date();
            const startOk = !v.start_at || new Date(String(v.start_at)) <= now;
            const endOk = !v.end_at || new Date(String(v.end_at)) >= now;
            if (!startOk || !endOk) return 0;
            const vt = String(v.value_type || "").toLowerCase();
            const val = Number(v.value ?? 0);
            if (vt === "percentage") {
                return Math.max(
                    0,
                    Math.round(totalAfterDiscountBeforeVoucher * (val / 100)),
                );
            }
            return Math.max(
                0,
                Math.min(Math.round(val), totalAfterDiscountBeforeVoucher),
            );
        })(),
    );
    const totals = $derived(
        computeTotals({
            cart,
            products,
            discounts,
            levels,
            sellingPriceMainMap,
            sellingPriceMap,
            taxEnabled,
            taxRatePercent: taxRate,
            shippingCost,
            voucherAmount,
        }),
    );
    const itemDiscountTotal = $derived<number>(totals.itemDiscountTotal);
    const extraDiscountTotal = $derived<number>(totals.extraDiscountTotal);
    const extraDiscountPercentage = $derived<string | null>(
        totals.extraDiscountPercentage,
    );
    function resolveItemDiscountTextMap(): Record<string, string> {
        const map: Record<string, string> = {};
        const specific = discounts.filter(
            (d) => (d.scope || "").toLowerCase() === "specific",
        );
        if (specific.length === 0) return map;
        const productMap: Record<string, Product> = {};
        for (const p of products) {
            productMap[p.id] = p;
        }
        for (const it of cart) {
            const prod = productMap[it.id];
            let bestAmount = 0;
            let bestText: string | null = null;
            for (const d of specific) {
                for (const di of d.items ?? []) {
                    const t = String(di.itemable_type || "");
                    const iid = String(di.itemable_id || "");
                    let match = false;
                    if (t === "App\\Models\\Product" && iid === it.id) {
                        match = true;
                    } else if (t === "App\\Models\\ProductCategory") {
                        const catId = prod?.product_category_id ?? null;
                        if (catId !== null && iid === String(catId))
                            match = true;
                    } else if (t === "App\\Models\\ProductSubCategory") {
                        const subCatId = prod?.product_sub_category_id ?? null;
                        if (subCatId !== null && iid === String(subCatId))
                            match = true;
                    }
                    if (!match) continue;
                    const type = String(d.type || "").toLowerCase();
                    const minQty = Math.max(0, Number(di.min_qty_buy ?? 0));
                    if (type === "bogo") {
                        continue;
                    }
                    const vt = String(d.value_type || "").toLowerCase();
                    const raw =
                        typeof di.custom_value === "number" &&
                        Number.isFinite(di.custom_value)
                            ? Number(di.custom_value)
                            : Number(d.value ?? 0);
                    if (!(raw > 0)) continue;
                    const qty = Math.max(1, Number(it.qty || 0));
                    if (minQty > 0 && qty < minQty) continue;
                    let amt = 0;
                    if (vt === "percentage") {
                        const bundles =
                            minQty > 0
                                ? Math.floor(qty / minQty) *
                                  (di.is_multiple ? 1 : qty >= minQty ? 1 : 0)
                                : qty;
                        const baseQty =
                            minQty > 0
                                ? minQty * (di.is_multiple ? bundles : 1)
                                : bundles;
                        const baseAmount = Math.max(0, it.price * baseQty);
                        amt = Math.round((baseAmount * raw) / 100);
                    } else if (vt !== "") {
                        const bundles =
                            minQty > 0
                                ? di.is_multiple
                                    ? Math.floor(qty / minQty)
                                    : qty >= minQty
                                      ? 1
                                      : 0
                                : qty;
                        amt = Math.round(raw) * Math.max(0, bundles);
                    }
                    if (amt > bestAmount) {
                        bestAmount = amt;
                        if (vt === "percentage") {
                            const s = String(raw);
                            const fixed = Number.isFinite(raw)
                                ? Number(raw).toFixed(2)
                                : s;
                            const trimmed = fixed.replace(/\.?0+$/, "");
                            const bundles =
                                minQty > 0
                                    ? di.is_multiple
                                        ? Math.floor(qty / minQty)
                                        : qty >= minQty
                                          ? 1
                                          : 0
                                    : 0;
                            const base =
                                minQty > 0
                                    ? `${trimmed}% per ${minQty} qty`
                                    : `${trimmed}%`;
                            bestText =
                                bundles > 1 ? `${base} × ${bundles}` : base;
                        } else {
                            const n = Math.round(raw);
                            const f = new Intl.NumberFormat("id-ID").format(n);
                            const bundles =
                                minQty > 0
                                    ? di.is_multiple
                                        ? Math.floor(qty / minQty)
                                        : qty >= minQty
                                          ? 1
                                          : 0
                                    : Math.max(1, qty);
                            const base =
                                minQty > 0 ? `${f} per ${minQty} qty` : f;
                            bestText =
                                bundles > 1 ? `${base} × ${bundles}` : base;
                        }
                    }
                }
            }
            if (bestText) {
                map[it.id] = bestText;
            }
        }
        return map;
    }
    const discountTextByItemId = $derived<Record<string, string>>(
        resolveItemDiscountTextMap(),
    );
    const totalAfterDiscount = $derived<number>(totals.totalAfterDiscount);
    const taxAmount = $derived<number>(totals.taxAmount);
    const grandTotal = $derived<number>(totals.grandTotal);
    const cashSelected = $derived<boolean>(
        isCashMethod(selectedPayment1Id) ||
            (useSecondPayment && isCashMethod(selectedPayment2Id)),
    );
    const paymentDelta = $derived(
        computeChangeAndShortage(grandTotal, paymentTotal, cashSelected),
    );
    const changeAmount = $derived<number>(paymentDelta.changeAmount);
    const shortageAmount = $derived<number>(paymentDelta.shortageAmount);
    const methodRuleValid = $derived<boolean>(
        computePaymentRule(
            useSecondPayment,
            isCashMethod(selectedPayment1Id),
            useSecondPayment ? isCashMethod(selectedPayment2Id) : false,
            changeAmount,
        ),
    );
    const canPay = $derived<boolean>(
        grandTotal > 0 &&
            activeShift !== null &&
            ((paymentTotal >= grandTotal && methodValid && methodRuleValid) ||
                (paymentTotal < grandTotal && selectedCustomerId !== "")),
    );

    function customerOptions(): SelectOption[] {
        const opts: SelectOption[] = [
            { value: "", label: "Tanpa Customer" },
            { value: "add", label: "Tambah Customer" },
        ];
        for (const c of customers) {
            const phone = c.phone ? ` (${c.phone})` : "";
            opts.push({ value: c.id, label: `${c.name}${phone}` });
        }
        return opts;
    }
    function paymentMethodOptions(): SelectOption[] {
        const opts: SelectOption[] = [{ value: "", label: "Pilih metode" }];
        for (const m of paymentMethods) {
            opts.push({ value: m.id, label: m.name });
        }
        return opts;
    }

    function tabLabel(): string {
        const t = tabs.find((x) => x.id === activeTab);
        return t?.label ?? "All";
    }

    function filtered(): Product[] {
        const base =
            activeTab === "all"
                ? products
                : products.filter((p) => p.category === activeTab);
        const s = q.trim().toLowerCase();
        if (!s) return base;
        return base.filter(
            (p) =>
                p.name.toLowerCase().includes(s) ||
                p.category.toLowerCase().includes(s) ||
                (p.sku ? String(p.sku).toLowerCase().includes(s) : false),
        );
    }

    function resetScan(): void {
        scanBuffer = "";
        lastKeyTs = 0;
        if (scanTimer !== null) {
            clearTimeout(scanTimer);
            scanTimer = null;
        }
    }
    function equalsIgnoreCase(a: string, b: string): boolean {
        return a.trim().toLowerCase() === b.trim().toLowerCase();
    }
    function addBySku(code: string): boolean {
        const v = code.trim();
        if (v.length < MIN_SCAN_LENGTH) return false;
        const p =
            products.find((x) => equalsIgnoreCase(String(x.sku ?? ""), v)) ??
            null;
        if (p) {
            addToCart(p);
            return true;
        }
        return false;
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
        return ae.id === "pos_search";
    }
    function processScan(code: string): void {
        const v = code.trim();
        if (v.length < MIN_SCAN_LENGTH) {
            resetScan();
            return;
        }
        const added = addBySku(v);
        if (added) {
            q = v;
        }
        resetScan();
    }
    function tryAddFromSearch(e?: KeyboardEvent): void {
        const code = q.trim();
        if (code.length < MIN_SCAN_LENGTH) return;
        const added = addBySku(code);
        if (!added) {
            const list = filtered();
            if (list.length === 1) {
                addToCart(list[0]!);
            }
        }
        if (e) e.preventDefault();
    }
    function scheduleTryFromSearch(): void {
        if (inputTimer !== null) {
            clearTimeout(inputTimer);
            inputTimer = null;
        }
        inputTimer = setTimeout(() => {
            tryAddFromSearch();
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

    function optionsFor(p: Product): SelectOption[] {
        const mainPrice = priceForLevel(p, "main");
        const opts: SelectOption[] = [
            { value: "main", label: `Utama (${formatCurrency(mainPrice)})` },
        ];
        for (const l of levels) {
            const lp = priceForLevel(p, l.id);
            opts.push({
                value: l.id,
                label: `${l.name} (${formatCurrency(lp)})`,
            });
        }
        return opts;
    }
    function addToCart(p: Product): void {
        const idx = cart.findIndex((x) => x.id === p.id);
        if (idx !== -1) {
            const current = cart[idx]!;
            cart[idx] = { ...current, qty: current.qty + 1 };
            cart = [...cart];
        } else {
            const lvl = selectedLevelByProduct[p.id] ?? "main";
            cart = [
                ...cart,
                {
                    id: p.id,
                    name: p.name,
                    category: p.category,
                    price: priceForLevel(p, String(lvl)),
                    qty: 1,
                    levelId: String(lvl),
                },
            ];
        }
    }
    function clearCart(): void {
        cart = [];
    }
    function resetPosForm(): void {
        cart = [];
        selectedCustomerId = "";
        selectedLevelByProduct = {};
        taxEnabled = false;
        vatPercentSelected = initialTaxRate;
        shippingStr = "";
        useShipping = false;
        useCustomerShipping = false;
        useCustomerShippingModal = false;
        showShippingModal = false;
        shippingRecipientName = "";
        shippingRecipientPhone = "";
        shippingAddress = "";
        shippingNote = "";
        shippingRecipientNameDraft = "";
        shippingRecipientPhoneDraft = "";
        shippingAddressDraft = "";
        shippingNoteDraft = "";
        useSecondPayment = false;
        selectedPayment1Id = "";
        selectedPayment2Id = "";
        payment1Str = "";
        payment2Str = "";
        paymentNote1 = "";
        paymentNote2 = "";
        showPaymentNoteModal = false;
        paymentNoteDraft = "";
        paymentNoteModalMethodIndex = 1;
    }
    function inc(id: string): void {
        const idx = cart.findIndex((x) => x.id === id);
        if (idx !== -1) {
            const current = cart[idx]!;
            cart[idx] = { ...current, qty: current.qty + 1 };
            cart = [...cart];
        }
    }
    function dec(id: string): void {
        const idx = cart.findIndex((x) => x.id === id);
        if (idx !== -1) {
            const current = cart[idx]!;
            const next = current.qty - 1;
            if (next <= 0) {
                cart = cart.filter((x) => x.id !== id);
            } else {
                cart[idx] = { ...current, qty: next };
                cart = [...cart];
            }
        }
    }

    function vatSelectOptions(): SelectOption[] {
        const opts: SelectOption[] = vatOptions.map((p) => ({
            value: p,
            label: `${p}%`,
        }));
        if (opts.length === 0) {
            opts.push({ value: 0, label: "0%" });
        }
        return opts;
    }

    function priceForLevel(p: Product, lid: string): number {
        return priceForLevelUtil(
            p,
            lid,
            levels,
            sellingPriceMainMap,
            sellingPriceMap,
        );
    }

    function isCashMethod(id: string): boolean {
        if (!id) return false;
        const pm = paymentMethods.find((m) => m.id === id) || null;
        if (!pm) return false;
        const n = pm.name.trim().toLowerCase();
        return n.includes("tunai") || n.includes("cash");
    }
    function isNonCashMethod(id: string): boolean {
        if (!id) return false;
        return !isCashMethod(id);
    }
    function setPayment1ManualOverride(v: boolean): void {
        payment1ManualOverride = v;
    }
    function setPayment2ManualOverride(v: boolean): void {
        payment2ManualOverride = v;
    }

    function toggleSecondPayment(): void {
        useSecondPayment = !useSecondPayment;
        if (useSecondPayment) {
            const target = computeNonCashTarget(grandTotal, payment1Amount);
            payment2Str = String(target);
        } else {
            selectedPayment2Id = "";
            payment2Str = "";
            paymentNote2 = "";
        }
    }

    function openPaymentNoteModal(idx: number): void {
        paymentNoteModalMethodIndex = idx;
        paymentNoteDraft = idx === 1 ? paymentNote1 : paymentNote2;
        showPaymentNoteModal = true;
    }
    function closePaymentNoteModal(): void {
        showPaymentNoteModal = false;
    }
    function savePaymentNote(): void {
        if (paymentNoteModalMethodIndex === 1) {
            paymentNote1 = paymentNoteDraft.trim();
        } else {
            paymentNote2 = paymentNoteDraft.trim();
        }
        showPaymentNoteModal = false;
    }

    function findCustomer(cid: string | null): CustomerItem | null {
        if (!cid) return null;
        return customers.find((x) => x.id === cid) ?? null;
    }

    function openShippingModal(): void {
        const cust = findCustomer(selectedCustomerId);
        const noExistingData =
            (shippingRecipientName ?? "") === "" &&
            (shippingRecipientPhone ?? "") === "" &&
            (shippingAddress ?? "") === "";
        if (cust && noExistingData) {
            useCustomerShippingModal = true;
            shippingRecipientNameDraft = cust.name ?? "";
            shippingRecipientPhoneDraft = cust.phone ?? "";
            shippingAddressDraft = cust.address ?? "";
        } else {
            useCustomerShippingModal =
                selectedCustomerId !== "" ? useCustomerShipping : false;
            if (useCustomerShippingModal && cust) {
                shippingRecipientNameDraft = cust.name ?? "";
                shippingRecipientPhoneDraft = cust.phone ?? "";
                shippingAddressDraft = cust.address ?? "";
            } else {
                shippingRecipientNameDraft = shippingRecipientName;
                shippingRecipientPhoneDraft = shippingRecipientPhone;
                shippingAddressDraft = shippingAddress;
            }
        }
        shippingNoteDraft = shippingNote;
        showShippingModal = true;
    }
    function closeShippingModal(): void {
        showShippingModal = false;
    }
    function saveShippingModal(): void {
        useCustomerShipping =
            selectedCustomerId !== "" && useCustomerShippingModal;
        if (useCustomerShipping) {
            const cust = findCustomer(selectedCustomerId);
            shippingRecipientName = cust?.name ?? "";
            shippingRecipientPhone = cust?.phone ?? "";
            shippingAddress = cust?.address ?? "";
        } else {
            shippingRecipientName = shippingRecipientNameDraft.trim();
            shippingRecipientPhone = shippingRecipientPhoneDraft.trim();
            shippingAddress = shippingAddressDraft.trim();
        }
        shippingNote = shippingNoteDraft.trim();
        showShippingModal = false;
    }

    function openConfirmModal(): void {
        confirmProcessing = false;
        const outstanding = shortageAmount;
        if (outstanding > 0) {
            if (selectedCustomerId === "") {
                receivableProceedAfterConfirm = false;
                receivableTitle = "Customer Wajib Dipilih";
                receivableMessage =
                    "Transaksi ini memiliki kurang bayar dan akan dicatat sebagai PIUTANG. Pilih customer terlebih dahulu sebelum melanjutkan.";
                showReceivableDialog = true;
                return;
            }
            const cust = findCustomer(selectedCustomerId);
            const name = cust?.name ?? "Customer";
            receivableProceedAfterConfirm = true;
            receivableTitle = "Konfirmasi Piutang";
            receivableMessage =
                `Transaksi ini akan dicatat sebagai PIUTANG untuk ${name}. ` +
                `Sisa yang harus dibayar kemudian: ${formatCurrency(outstanding)}. ` +
                `Lanjutkan ke konfirmasi?`;
            showReceivableDialog = true;
            return;
        }
        showConfirmModal = true;
    }
    function closeConfirmModal(): void {
        showConfirmModal = false;
    }

    function closeSuccessModal(): void {
        showSuccessModal = false;
    }
    function findProduct(pid: string | null): Product | null {
        if (!pid) return null;
        return products.find((x) => x.id === pid) ?? null;
    }
    function optionsForCart(pid: string | null): SelectOption[] {
        const p = findProduct(pid);
        if (p) return optionsFor(p);
        const opts: SelectOption[] = [{ value: "main", label: "Utama" }];
        for (const l of levels) {
            opts.push({ value: l.id, label: l.name });
        }
        return opts;
    }
    function setItemLevel(id: string, levelId: string): void {
        const idx = cart.findIndex((x) => x.id === id);
        if (idx === -1) return;
        const p = findProduct(id);
        const newPrice = p ? priceForLevel(p, levelId) : cart[idx]!.price;
        const current = cart[idx]!;
        cart[idx] = { ...current, levelId: levelId, price: newPrice };
        cart = [...cart];
    }
    function updateCartItemNote(id: string, note: string | null): void {
        const idx = cart.findIndex((x) => x.id === id);
        if (idx !== -1) {
            const current = cart[idx]!;
            cart[idx] = { ...current, note };
            cart = [...cart];
        }
    }
    function findWarehouse(id: string): WarehouseItem | null {
        if (!id) return null;
        return warehouses.find((x) => x.id === id) ?? null;
    }
    function submitTransaction(): void {
        confirmProcessing = true;
        const items = cart.map((it) => ({
            product_id: it.id,
            quantity: it.qty,
            price: it.price,
            notes: it.note ?? null,
        }));
        const payments: Array<{
            amount: number;
            payment_method_id: string;
            notes?: string | null;
        }> = [];
        if (selectedCustomerId !== "") {
            if (selectedPayment1Id !== "" && payment1Amount > 0) {
                payments.push({
                    amount: payment1Amount,
                    payment_method_id: selectedPayment1Id,
                    notes: paymentNote1 || null,
                });
            }
            if (
                useSecondPayment &&
                selectedPayment2Id !== "" &&
                payment2Amount > 0
            ) {
                payments.push({
                    amount: payment2Amount,
                    payment_method_id: selectedPayment2Id,
                    notes: paymentNote2 || null,
                });
            }
        }
        const payload: Record<string, unknown> = {
            warehouse_id: selectedWarehouseId,
            cashier_session_id: activeShift ? activeShift.id : null,
            sale_datetime: new Date().toISOString(),
            customer_id: selectedCustomerId || null,
            delivery_type: useShipping ? "delivery" : "walk_in",
            requires_delivery: useShipping,
            shipping_recipient_name: useShipping
                ? shippingRecipientName || null
                : null,
            shipping_recipient_phone: useShipping
                ? shippingRecipientPhone || null
                : null,
            shipping_address: useShipping ? shippingAddress || null : null,
            shipping_note: useShipping ? shippingNote || null : null,
            subtotal,
            discount_percentage: extraDiscountPercentage,
            item_discount_total: itemDiscountTotal,
            extra_discount_total: extraDiscountTotal,
            total_after_discount: totalAfterDiscount,
            is_value_added_tax_enabled: taxEnabled,
            value_added_tax_id: null,
            value_added_tax_percentage: taxEnabled ? String(taxRate) : null,
            value_added_tax_amount: taxEnabled ? taxAmount : 0,
            voucher_code: voucherCode || null,
            voucher_amount: voucherAmount,
            shipping_amount: useShipping ? shippingCost : 0,
            rounding_amount: null,
            grand_total: grandTotal,
            change_amount: changeAmount,
            notes: null,
            items,
        };
        if (payments.length > 0) {
            (payload as any).payments = payments;
        }
        router.post("/pos", payload, {
            preserveScroll: true,
            onSuccess: () => {
                showConfirmModal = false;
                resetPosForm();
            },
            onFinish: () => {
                confirmProcessing = false;
            },
        });
    }

    onMount(() => {
        window.addEventListener("keydown", handleGlobalKeydown);
        const el = document.getElementById("pos_search") as HTMLElement | null;
        el?.focus();
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
    $effect(() => {
        const flashRoot = (($page as any)?.flash ??
            ($page.props as any)?.flash ??
            {}) as any;
        const sc = (flashRoot?.sale_completed ?? null) as SaleCompleted | null;
        const sid = sc?.id ? String(sc.id) : "";
        if (sid && sid !== consumedSaleId) {
            saleCompleted = sc;
            showSuccessModal = true;
            consumedSaleId = sid;
            resetPosForm();
            try {
                openCenteredWindow(`/pos/${sid}/receipt`, {
                    width: 480,
                    height: 720,
                    fallbackWhenBlocked: false,
                });
            } catch {}
        }
    });
    $effect(() => {
        const flashObj = (($page as any)?.flash ??
            ($page.props as any)?.flash ??
            {}) as any;
        const cid = String((flashObj?.created_customer_id ?? "") || "");
        const createdPayload = (flashObj?.created_customer ??
            null) as CustomerItem | null;
        if (createdPayload) {
            const existsFromServer = customersFromServer.some(
                (x) => x.id === createdPayload.id,
            );
            const existsExtra = customersExtra.some(
                (x) => x.id === createdPayload.id,
            );
            if (!existsFromServer && !existsExtra) {
                customersExtra = [
                    ...customersExtra,
                    {
                        id: String(createdPayload.id),
                        name: String(createdPayload.name ?? "Customer"),
                        phone:
                            createdPayload.phone !== undefined
                                ? createdPayload.phone
                                : null,
                        address:
                            createdPayload.address !== undefined
                                ? createdPayload.address
                                : null,
                    },
                ];
            }
        }
        if (cid && selectedCustomerId !== cid) {
            selectedCustomerId = cid;
        }
    });
    $effect(() => {
        if (!useShipping) return;
        if (selectedCustomerId === "") return;
        const cust = findCustomer(selectedCustomerId);
        if (!cust) return;
        const noExistingData =
            (shippingRecipientName ?? "") === "" &&
            (shippingRecipientPhone ?? "") === "" &&
            (shippingAddress ?? "") === "";
        if (useCustomerShipping || noExistingData) {
            useCustomerShipping = true;
            shippingRecipientName = cust.name ?? "";
            shippingRecipientPhone = cust.phone ?? "";
            shippingAddress = cust.address ?? "";
        }
    });
    $effect(() => {
        if (!taxInitDone) {
            taxEnabled = false;
            vatPercentSelected = initialTaxRate;
            taxInitDone = true;
        }
    });
    $effect(() => {
        const code = q.trim();
        if (code !== lastSearchCode) {
            lastSearchCode = code;
            if (code.length >= MIN_SCAN_LENGTH) {
                scheduleTryFromSearch();
            }
        }
    });
    $effect(() => {
        if (selectedWarehouseId === "" && warehouses.length > 0) {
            selectedWarehouseId = String(warehouses[0]!.id);
        }
    });
    $effect(() => {
        if (
            selectedPayment1Id !== "" &&
            isNonCashMethod(selectedPayment1Id) &&
            !payment1ManualOverride
        ) {
            const target = computeNonCashTarget(
                grandTotal,
                useSecondPayment ? payment2Amount : 0,
            );
            const current = Math.max(0, Number(payment1Str || 0));
            if (current !== target) {
                payment1Str = String(target);
            }
        }
    });
    $effect(() => {
        if (
            useSecondPayment &&
            selectedPayment2Id !== "" &&
            isNonCashMethod(selectedPayment2Id) &&
            !payment2ManualOverride
        ) {
            const target = computeNonCashTarget(grandTotal, payment1Amount);
            const current = Math.max(0, Number(payment2Str || 0));
            if (current !== target) {
                payment2Str = String(target);
            }
        }
    });
    function gotoHome(): void {
        router.get("/dashboard/sales", { preserveScroll: true });
    }
</script>

<svelte:head>
    <title>POS | {siteName(settings)}</title>
</svelte:head>

<section class="space-y-5">
    <div class="flex gap-6">
        <div class="flex-1 space-y-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="space-y-0.5">
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {tabLabel()}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {filtered().length} item tersedia
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <CashierSession {activeShift} {canOpenShiftToday} />
                    <TextInput
                        id="pos_search"
                        name="search"
                        placeholder="Cari menu..."
                        bind:value={q}
                        onkeypress={(e) => {
                            const ev = e as KeyboardEvent;
                            if (ev.key === "Enter") {
                                tryAddFromSearch(ev);
                            }
                        }}
                        oninput={() => {
                            scheduleTryFromSearch();
                        }}
                        class="min-w-70 w-full sm:w-[320px]"
                    />
                </div>
            </div>
            <Tab
                {tabs}
                bind:activeTab
                variant="pills"
                size="normal"
                class="mb-2"
            />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {#each filtered() as p (p.id)}
                    <button
                        class="text-left bg-white dark:bg-[#0f0f0f] rounded-xl border border-gray-200 dark:border-[#2c2c2c] shadow-sm hover:shadow-md transition-all duration-200 p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0060B2]"
                        onclick={(e) => {
                            const t = e.target as Element | null;
                            if (t && t.closest(".select-container")) {
                                return;
                            }
                            addToCart(p);
                        }}
                    >
                        <div class="flex items-start justify-between">
                            <Badge variant="warning" rounded="pill" size="sm"
                                >{p.category_label.toUpperCase()}</Badge
                            >
                        </div>
                        <div class="mt-3 space-y-1">
                            <div
                                class="text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {p.name}
                            </div>
                            <div role="group" aria-label="Level harga">
                                <Select
                                    minimal
                                    placeholder="Level harga"
                                    value={selectedLevelByProduct[p.id] ??
                                        "main"}
                                    options={optionsFor(p)}
                                    onchange={(v) =>
                                        (selectedLevelByProduct[p.id] =
                                            String(v))}
                                />
                            </div>
                        </div>
                    </button>
                {/each}
            </div>
        </div>

        <CartPanel
            {cart}
            {clearCart}
            {dec}
            {inc}
            {customerOptions}
            bind:selectedCustomerId
            bind:taxEnabled
            bind:vatPercentSelected
            {vatSelectOptions}
            {taxAmount}
            bind:useShipping
            bind:shippingStr
            {shippingNote}
            {openShippingModal}
            bind:showShippingModal
            bind:useCustomerShippingModal
            bind:shippingRecipientNameDraft
            bind:shippingRecipientPhoneDraft
            bind:shippingAddressDraft
            bind:shippingNoteDraft
            {findCustomer}
            {closeShippingModal}
            {saveShippingModal}
            {subtotal}
            {itemDiscountTotal}
            {extraDiscountTotal}
            {extraDiscountPercentage}
            {totalAfterDiscount}
            {voucherAmount}
            {grandTotal}
            {discountTextByItemId}
            bind:voucherCode
            {paymentMethodOptions}
            bind:selectedPayment1Id
            bind:selectedPayment2Id
            bind:payment1Str
            bind:payment2Str
            {paymentNote1}
            {paymentNote2}
            {openPaymentNoteModal}
            bind:showPaymentNoteModal
            bind:paymentNoteModalMethodIndex
            bind:paymentNoteDraft
            {closePaymentNoteModal}
            {savePaymentNote}
            {useSecondPayment}
            {toggleSecondPayment}
            {shortageAmount}
            {changeAmount}
            {canPay}
            {openConfirmModal}
            {optionsForCart}
            {findProduct}
            {priceForLevel}
            {setItemLevel}
            {updateCartItemNote}
            {setPayment1ManualOverride}
            {setPayment2ManualOverride}
            onCustomerCreated={(c: CustomerItem) => {
                const existsFromServer = customersFromServer.some(
                    (x) => x.id === c.id,
                );
                const existsExtra = customersExtra.some((x) => x.id === c.id);
                if (!existsFromServer && !existsExtra) {
                    customersExtra = [...customersExtra, c];
                }
                selectedCustomerId = c.id;
            }}
        />
    </div>
</section>

<Dialog
    isOpen={posBlocked}
    type="warning"
    title="Kasir Ditutup"
    message="Anda sudah tidak bisa melakukan penjualan. Silahkan pindah ke beranda."
    confirmText="Ke Beranda"
    showCancel={false}
    onConfirm={gotoHome}
/>

<ConfirmModal
    bind:isOpen={showConfirmModal}
    onClose={closeConfirmModal}
    onConfirm={submitTransaction}
    processing={confirmProcessing}
    {cart}
    {selectedCustomerId}
    {findCustomer}
    {selectedWarehouseId}
    {findWarehouse}
    {subtotal}
    {itemDiscountTotal}
    {extraDiscountTotal}
    {extraDiscountPercentage}
    {totalAfterDiscount}
    {taxEnabled}
    {taxRate}
    {taxAmount}
    {shippingCost}
    {grandTotal}
    {useShipping}
    {shippingRecipientName}
    {shippingRecipientPhone}
    {shippingAddress}
    {shippingNote}
    {voucherCode}
    {voucherAmount}
    {paymentMethods}
    {selectedPayment1Id}
    {selectedPayment2Id}
    {payment1Amount}
    {payment2Amount}
    {paymentNote1}
    {paymentNote2}
    {paymentTotal}
    {changeAmount}
    {shortageAmount}
    {discountTextByItemId}
/>

{#if saleCompleted}
    <SuccessModal
        bind:isOpen={showSuccessModal}
        onClose={closeSuccessModal}
        sale={saleCompleted}
    />
{/if}

<Dialog
    bind:isOpen={showReceivableDialog}
    type="warning"
    title={receivableTitle}
    message={receivableMessage}
    confirmText="Ya, Lanjutkan"
    cancelText="Batal"
    showCancel={true}
    onConfirm={() => {
        showReceivableDialog = false;
        if (receivableProceedAfterConfirm) {
            showConfirmModal = true;
        }
    }}
    onCancel={() => {
        showReceivableDialog = false;
    }}
    onClose={() => {
        showReceivableDialog = false;
    }}
/>
