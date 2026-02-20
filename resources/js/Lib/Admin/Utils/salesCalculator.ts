/* eslint-disable @typescript-eslint/consistent-type-definitions */
/**
 * Util kalkulator POS/Sales.
 *
 * Tanggung jawab:
 * - Menilai diskon item-specific (produk/kategori/subkategori) termasuk BOGO.
 * - Menilai diskon global dan memilih skenario terbaik (specific vs global).
 * - Menghitung subtotal, pajak, ongkir, grand total, kembalian, dan kekurangan.
 *
 * Invarian:
 * - Semua nilai Rupiah berbentuk integer (dibulatkan `Math.round`).
 * - Murni fungsional dan deterministik, tidak ada side effect.
 * - Harga per level memakai percent_adjust terhadap harga utama jika tidak ada override.
 *
 * Catatan BOGO:
 * - Nilai dihitung dari unit gratis × harga satuan (produk gratis atau item utama).
 *
 * @remarks Dipakai di frontend agar angka yang dipersist ke backend konsisten.
 * @public
 */
/**
 * Model produk minimum untuk kalkulator.
 * - price: harga utama (IDR) sebagai fallback bila peta harga kosong.
 * - product_category_id/sub_category_id: untuk kecocokan diskon specific.
 */
export type Product = {
    id: string;
    name: string;
    category?: string;
    price: number;
    product_category_id?: string | null;
    product_sub_category_id?: string | null;
};

/**
 * Level harga jual.
 * - percent_adjust: penyesuaian terhadap harga utama (%), boleh negatif/positif.
 */
export type LevelItem = {
    id: string;
    name: string;
    percent_adjust?: number | null;
};

/**
 * Item keranjang POS.
 * - price: harga satuan final untuk level terpilih (IDR).
 * - qty: kuantitas integer >= 1.
 * - levelId: id level harga asal item (opsional).
 */
export type CartItem = {
    id: string;
    name: string;
    price: number;
    qty: number;
    levelId?: string;
};

/**
 * Konfigurasi item spesifik untuk sebuah diskon.
 * - itemable_type: 'App\\Models\\Product' | 'App\\Models\\ProductCategory' | 'App\\Models\\ProductSubCategory'.
 * - itemable_id: id target sesuai tipe.
 * - custom_value: override nilai khusus pada item ini.
 * - min_qty_buy/free_qty_get/free_product_id: untuk BOGO.
 */
export type DiscountItemRow = {
    itemable_type?: string | null;
    itemable_id?: string | null;
    custom_value?: number | null;
    min_qty_buy?: number | null;
    free_qty_get?: number | null;
    free_product_id?: string | null;
    is_multiple?: boolean | null;
};

/**
 * Master diskon.
 * - scope: 'global' atau 'specific'.
 * - value_type: 'percentage' atau 'amount' (IDR).
 * - value: nilai default bila item tidak punya custom_value.
 * - type: 'bogo' atau lainnya.
 * - items: daftar target untuk scope specific.
 */
export type DiscountRow = {
    scope?: string | null; // 'global' | 'specific'
    value_type?: string | null; // 'percentage' | 'amount'
    value?: number | null;
    type?: string | null; // 'bogo' | lainnya
    items?: DiscountItemRow[];
};

/**
 * Peta harga jual per level: productId → levelId → price (IDR).
 */
export type PriceMapPerLevel = Record<string, Record<string, number>>;
/**
 * Peta harga jual utama: productId → price (IDR).
 */
export type PriceMainMap = Record<string, number>;

/**
 * Mengambil harga satuan untuk level tertentu dengan urutan fallback:
 * 1) peta harga per-level, 2) percent_adjust atas harga utama, 3) harga utama produk.
 *
 * @param product Produk target yang memuat harga utama dan kategori.
 * @param levelId Id level ('main' untuk harga utama) atau kosong untuk fallback utama.
 * @param levels Daftar level beserta percent_adjust (%), boleh negatif/positif.
 * @param sellingPriceMainMap Peta harga utama per produk (IDR).
 * @param sellingPriceMap Peta harga per-level per produk (IDR).
 * @returns Harga satuan dalam integer Rupiah (dibulatkan).
 * @throws Tidak melempar error.
 */
export function priceForLevel(
    product: Product,
    levelId: string,
    levels: LevelItem[],
    sellingPriceMainMap: PriceMainMap,
    sellingPriceMap: PriceMapPerLevel,
): number {
    if (!levelId || levelId === "main") {
        const main =
            sellingPriceMainMap[product.id] !== undefined
                ? Number(sellingPriceMainMap[product.id])
                : Number(product.price);
        return Number.isFinite(main) ? Math.round(main) : 0;
    }
    const perLevel = sellingPriceMap[product.id] ?? {};
    const stored = perLevel[levelId];
    if (typeof stored === "number" && Number.isFinite(stored)) {
        return Math.round(stored);
    }
    const level = levels.find((l) => String(l.id) === String(levelId)) ?? null;
    const adjust =
        level && typeof level.percent_adjust === "number"
            ? Number(level.percent_adjust)
            : null;
    const base =
        sellingPriceMainMap[product.id] !== undefined
            ? Number(sellingPriceMainMap[product.id])
            : Number(product.price);
    if (adjust === null || !Number.isFinite(adjust)) {
        return Number.isFinite(base) ? Math.round(base) : 0;
    }
    return Math.round(base * (1 + adjust / 100));
}

/**
 * Menghitung target nominal metode non-tunai untuk menutup sisa grand total.
 *
 * @param grandTotal Jumlah total belanja setelah diskon, pajak, dan ongkir (IDR).
 * @param otherPaymentAmount Jumlah yang sudah dibayar via metode lain (IDR).
 * @returns Nominal yang perlu dialokasikan ke metode non-tunai (>= 0).
 * @throws Tidak melempar error.
 */
export function computeNonCashTarget(
    grandTotal: number,
    otherPaymentAmount: number,
): number {
    return Math.max(
        0,
        Math.round(Number(grandTotal || 0) - Number(otherPaymentAmount || 0)),
    );
}

/**
 * Menghitung kembalian dan kekurangan terhadap grand total.
 * Kembalian hanya dihitung bila metode tunai dipilih untuk pembayaran aktif.
 *
 * @param grandTotal Grand total transaksi (IDR).
 * @param paymentTotal Total pembayaran yang masuk (IDR).
 * @param cashSelected True jika metode tunai aktif untuk pembayaran yang dihitung.
 * @returns Objek berisi changeAmount dan shortageAmount (IDR).
 * @throws Tidak melempar error.
 */
export function computeChangeAndShortage(
    grandTotal: number,
    paymentTotal: number,
    cashSelected: boolean,
): { changeAmount: number; shortageAmount: number } {
    const gt = Math.max(0, Number(grandTotal || 0));
    const pt = Math.max(0, Number(paymentTotal || 0));
    const changeAmount = cashSelected && pt > gt ? pt - gt : 0;
    const shortageAmount = gt > pt ? gt - pt : 0;
    return { changeAmount, shortageAmount };
}

/**
 * Validasi pemilihan metode pembayaran ganda.
 * Syarat:
 * - Metode pertama wajib terpilih.
 * - Bila memakai metode kedua, id harus terisi dan tidak sama dengan metode pertama.
 *
 * @param selectedPayment1Id Id metode pembayaran pertama.
 * @param selectedPayment2Id Id metode pembayaran kedua.
 * @param useSecondPayment True bila ingin memakai dua metode sekaligus.
 * @returns True bila konfigurasi valid.
 * @throws Tidak melempar error.
 */
export function computeMethodValid(
    selectedPayment1Id: string,
    selectedPayment2Id: string,
    useSecondPayment: boolean,
): boolean {
    if (!selectedPayment1Id) return false;
    if (!useSecondPayment) return true;
    return !!selectedPayment2Id && selectedPayment2Id !== selectedPayment1Id;
}

/**
 * Aturan bisnis metode pembayaran gabungan.
 * - Melarang dua metode tunai dipakai bersamaan.
 * - Jika ada kembalian (changeAmount > 0), setidaknya satu metode harus tunai.
 *
 * @param useSecondPayment True bila dua metode dipakai.
 * @param isCash1 True bila metode pertama adalah tunai.
 * @param isCash2 True bila metode kedua adalah tunai.
 * @param changeAmount Nilai kembalian hasil perhitungan (IDR).
 * @returns True bila konfigurasi lolos aturan.
 * @throws Tidak melempar error.
 */
export function computePaymentRule(
    useSecondPayment: boolean,
    isCash1: boolean,
    isCash2: boolean,
    changeAmount: number,
): boolean {
    if (useSecondPayment && isCash1 && isCash2) return false;
    if (Math.max(0, Number(changeAmount || 0)) > 0) {
        if (!(isCash1 || (useSecondPayment && isCash2))) return false;
    }
    return true;
}

/**
 * Menyelesaikan komponen diskon untuk keranjang POS.
 * Algoritme:
 * - Hitung subtotal keranjang.
 * - Cari diskon global terbaik (persentase vs nominal).
 * - Untuk scope specific, evaluasi per item termasuk BOGO, pilih nilai tertinggi per item.
 * - Pilih skenario: total specific vs global (jika sama, pilih specific).
 *
 * @param params Objek input berisi keranjang, produk, diskon, level, dan peta harga.
 * @returns Objek dengan total specific, total global, persentase global (bila ada),
 *          skenario pemenang, serta map item yang terdiskon pada skenario specific.
 * @throws Tidak melempar error.
 */
export function resolveDiscountComponents(params: {
    cart: CartItem[];
    products: Product[];
    discounts: DiscountRow[];
    levels: LevelItem[];
    sellingPriceMainMap: PriceMainMap;
    sellingPriceMap: PriceMapPerLevel;
}): {
    specific: number;
    global: number;
    globalPct: string | null;
    scenario: "specific" | "global" | "none";
    discountedItemIds: Record<string, boolean>;
} {
    const subs = params.cart.reduce((s, x) => s + x.price * x.qty, 0);
    if (subs <= 0) {
        return {
            specific: 0,
            global: 0,
            globalPct: null,
            scenario: "none",
            discountedItemIds: {},
        };
    }

    // Global best
    let bestGlobal = 0;
    let bestGlobalPct: string | null = null;
    for (const d of params.discounts) {
        if ((d.scope || "").toLowerCase() !== "global") continue;
        const vt = (d.value_type || "").toLowerCase();
        const val = Number(d.value ?? 0);
        let amt = 0;
        if (vt === "percentage") {
            amt = Math.round((subs * val) / 100);
        } else {
            amt = Math.min(Math.round(val), subs);
        }
        if (amt > bestGlobal) {
            bestGlobal = amt;
            bestGlobalPct = vt === "percentage" ? String(d.value ?? "") : null;
        }
    }

    // Build product map
    const productMap: Record<string, Product> = {};
    for (const p of params.products) {
        productMap[p.id] = p;
    }

    // Specific per item (including BOGO)
    let sumSpecific = 0;
    const discountedItemIds: Record<string, boolean> = {};
    const specific = params.discounts.filter(
        (d) => (d.scope || "").toLowerCase() === "specific",
    );
    for (const it of params.cart) {
        const p = productMap[it.id];
        const itemSubtotal = it.price * it.qty;
        let bestPerItem = 0;
        let qualifiesSpecific = false;
        for (const d of specific) {
            for (const di of d.items ?? []) {
                const t = (di.itemable_type || "").trim();
                const iid = String(di.itemable_id || "");
                let match = false;
                if (t === "App\\Models\\Product" && iid === it.id) {
                    match = true;
                } else if (t === "App\\Models\\ProductCategory") {
                    const catId = p?.product_category_id ?? null;
                    if (catId !== null && iid === String(catId)) match = true;
                } else if (t === "App\\Models\\ProductSubCategory") {
                    const subCatId = p?.product_sub_category_id ?? null;
                    if (subCatId !== null && iid === String(subCatId))
                        match = true;
                }
                if (!match) continue;

                const vt = (d.value_type || "").toLowerCase();
                const minQty = Math.max(0, Number(di.min_qty_buy ?? 0));
                const raw =
                    typeof di.custom_value === "number" &&
                    Number.isFinite(di.custom_value)
                        ? Number(di.custom_value)
                        : Number(d.value ?? 0);
                let amt = 0;
                // Gating min qty untuk non-BOGO: wajib memenuhi minQty bila diset
                const qty = Math.max(1, Number(it.qty || 0));
                if (vt !== "" && minQty > 0 && qty < minQty) {
                    amt = 0;
                } else if (vt === "percentage") {
                    // Jika minQty diset, persen dihitung per bundle minQty
                    // dengan kelipatan jika is_multiple true; jika tidak, hanya sekali.
                    if (minQty > 0) {
                        const bundles = Math.floor(qty / minQty);
                        const appliedBundles =
                            (di.is_multiple ?? false) && bundles > 0
                                ? bundles
                                : bundles >= 1
                                  ? 1
                                  : 0;
                        const baseAmount = it.price * minQty * appliedBundles;
                        amt = Math.round((baseAmount * raw) / 100);
                    } else {
                        // Tanpa minQty, persen atas seluruh subtotal item.
                        amt = Math.round((itemSubtotal * raw) / 100);
                    }
                } else if (vt !== "") {
                    // Nominal: bila minQty diset, satu kali atau kelipatan bundle; tanpa minQty, kali qty.
                    if (minQty > 0) {
                        const bundles = Math.floor(qty / minQty);
                        const appliedBundles =
                            (di.is_multiple ?? false) && bundles > 0
                                ? bundles
                                : bundles >= 1
                                  ? 1
                                  : 0;
                        amt = Math.round(raw) * Math.max(0, appliedBundles);
                    } else {
                        amt = Math.round(raw) * qty;
                    }
                }
                if (amt > bestPerItem) bestPerItem = amt;

                // BOGO valuation
                if ((d.type || "").toLowerCase() === "bogo") {
                    const min = Math.max(1, Number(di.min_qty_buy ?? 0));
                    const freeQtyGet = Math.max(
                        0,
                        Number(di.free_qty_get ?? 0),
                    );
                    if (min > 0 && freeQtyGet > 0 && it.qty >= min) {
                        const bundles = Math.floor(it.qty / min);
                        const freeUnits = bundles * freeQtyGet;
                        const freePid = String(di.free_product_id ?? "");
                        let unitPrice = 0;
                        if (freePid !== "" && freePid !== it.id) {
                            const fp = productMap[freePid];
                            unitPrice = fp
                                ? priceForLevel(
                                      fp,
                                      "main",
                                      params.levels,
                                      params.sellingPriceMainMap,
                                      params.sellingPriceMap,
                                  )
                                : 0;
                        } else {
                            unitPrice = it.price;
                        }
                        const bogoAmount = Math.max(
                            0,
                            Math.round(unitPrice) * freeUnits,
                        );
                        if (bogoAmount > bestPerItem) bestPerItem = bogoAmount;
                        if (bogoAmount > 0) {
                            qualifiesSpecific = true;
                        }
                    }
                }
            }
        }
        if (bestPerItem > 0) {
            qualifiesSpecific = true;
        }
        if (qualifiesSpecific) {
            discountedItemIds[it.id] = true;
        }
        sumSpecific += bestPerItem;
    }

    const scenario =
        sumSpecific >= bestGlobal
            ? sumSpecific > 0
                ? "specific"
                : "none"
            : "global";

    return {
        specific: sumSpecific,
        global: bestGlobal,
        globalPct: bestGlobalPct,
        scenario,
        discountedItemIds: scenario === "specific" ? discountedItemIds : {},
    };
}

/**
 * Menghitung seluruh total POS berbasis komponen diskon dan biaya.
 * Langkah:
 * - Subtotal dari harga × qty per item.
 * - Ambil komponen diskon terbaik (specific vs global).
 * - Terapkan pajak (bila aktif) atas total setelah diskon.
 * - Tambahkan ongkir; hasilkan grand total.
 *
 * @param params Objek input keranjang, produk, master diskon, level harga, peta harga, pajak, dan ongkir.
 * @returns Objek total lengkap: subtotal, total diskon item, total diskon ekstra,
 *          persentase ekstra (bila global), total setelah diskon, pajak, grand total,
 *          serta map item terdiskon.
 * @throws Tidak melempar error.
 */
export function computeTotals(params: {
    cart: CartItem[];
    products: Product[];
    discounts: DiscountRow[];
    levels: LevelItem[];
    sellingPriceMainMap: PriceMainMap;
    sellingPriceMap: PriceMapPerLevel;
    taxEnabled: boolean;
    taxRatePercent: number;
    shippingCost: number;
    voucherAmount?: number;
}): {
    subtotal: number;
    itemDiscountTotal: number;
    extraDiscountTotal: number;
    extraDiscountPercentage: string | null;
    discountAmount: number;
    totalAfterDiscount: number;
    taxAmount: number;
    grandTotal: number;
    discountedItemIds: Record<string, boolean>;
    voucherAmount: number;
} {
    const subtotal = params.cart.reduce((s, x) => s + x.price * x.qty, 0);
    const comps = resolveDiscountComponents({
        cart: params.cart,
        products: params.products,
        discounts: params.discounts,
        levels: params.levels,
        sellingPriceMainMap: params.sellingPriceMainMap,
        sellingPriceMap: params.sellingPriceMap,
    });
    const itemDiscountTotal =
        comps.scenario === "specific" ? Number(comps.specific || 0) : 0;
    const extraDiscountTotal =
        comps.scenario === "global" ? Number(comps.global || 0) : 0;
    const extraDiscountPercentage =
        comps.scenario === "global"
            ? ((comps.globalPct ?? null) as string | null)
            : null;
    const discountAmount = itemDiscountTotal + extraDiscountTotal;
    const totalAfterDiscountBeforeVoucher = Math.max(0, subtotal - discountAmount);
    const voucherAmount = Math.max(0, Number(params.voucherAmount || 0));
    const totalAfterDiscount = Math.max(0, totalAfterDiscountBeforeVoucher - voucherAmount);
    const taxAmount = params.taxEnabled
        ? Math.round(
              (totalAfterDiscount * Number(params.taxRatePercent || 0)) / 100,
          )
        : 0;
    const shipping = Math.max(0, Number(params.shippingCost || 0));
    const grandTotal = totalAfterDiscount + taxAmount + shipping;
    return {
        subtotal,
        itemDiscountTotal,
        extraDiscountTotal,
        extraDiscountPercentage,
        discountAmount,
        totalAfterDiscount,
        taxAmount,
        grandTotal,
        discountedItemIds:
            comps.scenario === "specific" ? comps.discountedItemIds : {},
        voucherAmount,
    };
}
