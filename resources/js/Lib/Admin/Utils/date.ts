/**
 * Format datetime string to display format (dd/MM/yyyy HH:mm:ss)
 * @param value - Datetime string in format YYYY-MM-DD HH:mm:ss or YYYY-MM-DD
 * @returns Formatted datetime string or "-" if invalid
 */
export function formatDateTimeDisplay(value?: string | null): string {
    const s = String(value ?? "").trim();
    if (!s) return "-";
    const m = s.match(
        /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}):(\d{2}))?$/
    );
    if (m) {
        const dd = m[3]!;
        const mm = m[2]!;
        const yyyy = m[1]!;
        if (m[4] && m[5] && m[6]) {
            const HH = m[4]!;
            const ii = m[5]!;
            const ss = m[6]!;
            return `${dd}/${mm}/${yyyy} ${HH}:${ii}:${ss}`;
        }
        return `${dd}/${mm}/${yyyy}`;
    }
    const d = new Date(s);
    if (!isNaN(d.getTime())) {
        const dd = String(d.getDate()).padStart(2, "0");
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const yyyy = String(d.getFullYear());
        const HH = String(d.getHours()).padStart(2, "0");
        const ii = String(d.getMinutes()).padStart(2, "0");
        const ss = String(d.getSeconds()).padStart(2, "0");
        return `${dd}/${mm}/${yyyy} ${HH}:${ii}:${ss}`;
    }
    return s;
}

/**
 * Format date string to display format (dd/MM/yyyy)
 * @param value - Date string in format YYYY-MM-DD
 * @returns Formatted date string or "-" if invalid
 */
export function formatDateDisplay(value?: string | null): string {
    const s = String(value ?? "").trim();
    if (!s) return "-";

    // 1. Coba parsing manual menggunakan Regex (lebih aman dari timezone shift)
    const m = s.match(
        /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}):(\d{2}))?$/
    );

    if (m) {
        const dd = m[3]!;
        const mm = m[2]!;
        const yyyy = m[1]!;
        // Langsung return tanggal saja, abaikan group capture jam/menit
        return `${dd}/${mm}/${yyyy}`;
    }

    // 2. Fallback menggunakan Date Object jika format regex tidak cocok
    const d = new Date(s);
    if (!isNaN(d.getTime())) {
        const dd = String(d.getDate()).padStart(2, "0");
        const mm = String(d.getMonth() + 1).padStart(2, "0"); // Ingat bulan mulai dari 0
        const yyyy = String(d.getFullYear());

        // Return tanggal saja
        return `${dd}/${mm}/${yyyy}`;
    }

    // 3. Jika gagal semua, kembalikan string asli
    return s;
}
