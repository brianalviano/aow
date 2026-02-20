/**
 * Formats a number as Indonesian Rupiah currency.
 * @param amount - The amount to format.
 * @returns The formatted currency string.
 */
export function formatCurrency(amount: number | string): string {
    const n = typeof amount === "number" ? amount : Number(amount);
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(n);
}

export function getCurrencySymbol(): string {
    const parts = new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).formatToParts(0);
    const symbol = parts.find((p) => p.type === "currency")?.value ?? "Rp";
    return symbol;
}

export function formatCurrencyWithoutSymbol(amount: number | string): string {
    const n = typeof amount === "number" ? amount : Number(amount);
    const parts = new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).formatToParts(n);
    const joined = parts
        .filter((p) => p.type !== "currency")
        .map((p) => p.value)
        .join("");
    return joined.replace(/\u00A0/g, " ").trim();
}
