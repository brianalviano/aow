export type AppSettings = {
    name: string;
    contact_email: string;
    whatsapp_number: string;
    instagram_url: string;
    threads_url: string;
    linktree_url: string;
    x_url: string;
    meta_keywords: string[];
    meta_description: string;
    bank_name: string;
    bank_account_name: string;
    bank_account_number: string;
    email?: string;
    phone?: string;
    whatsapp?: string;
    address?: string;
    instagram?: string;
    facebook?: string;
    tiktok?: string;
};

/**
 * Sanitize a number by removing all non-digit characters.
 *
 * @param input - The input value to sanitize.
 * @returns The sanitized number as a string.
 */
export function sanitizeNumber(input: unknown): string {
    return String(input ?? "").replace(/[^0-9]/g, "");
}

/**
 * Build a WhatsApp link with a specified number and text.
 *
 * @param number - The WhatsApp number to link to.
 * @param text - The text to include in the link.
 * @returns The generated WhatsApp link.
 */
export function buildWaLink(number?: string, text?: string): string {
    const n = sanitizeNumber(number ?? "");
    const base = n ? `https://wa.me/${n}` : "https://wa.me/";
    if (!text || text.length === 0) return base;
    return `${base}?text=${encodeURIComponent(text)}`;
}

/**
 * Convert an array of keywords to a comma-separated string.
 *
 * @param input - The array of keywords to convert.
 * @returns The comma-separated string of keywords.
 */
export function keywordsString(input?: string[] | null): string {
    return Array.isArray(input) ? input.join(", ") : "";
}

/**
 * Get the site name from the application settings.
 *
 * @param settings - The application settings.
 * @param fallback - The fallback site name if not provided in settings.
 * @returns The site name.
 */
export function name(
    settings?: Partial<AppSettings> | null,
    fallback = "System",
): string {
    return settings?.name ?? fallback;
}

/**
 * Get the X (formerly Twitter) username from a URL or string.
 *
 * @param input - The input value to extract the X username from.
 * @returns The extracted X username.
 */
export function xUsername(input?: string | null): string {
    const s = String(input ?? "").trim();
    if (!s) return "";
    try {
        const u = new URL(s);
        let p = u.pathname || "";
        if (p.endsWith("/")) p = p.slice(0, -1);
        if (p.startsWith("/")) p = p.slice(1);
        if (p.startsWith("@")) p = p.slice(1);
        return p;
    } catch {
        let v = s;
        if (v.startsWith("@")) v = v.slice(1);
        return v;
    }
}
