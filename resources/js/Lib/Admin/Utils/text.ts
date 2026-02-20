/**
 * Truncate a string to a maximum visible length with an ellipsis.
 *
 * - Returns empty string for null/undefined input or non-positive maxLength.
 * - Default mode truncates at the end. Middle mode preserves both ends.
 * - Does not split surrogate pairs; uses JS slice semantics.
 *
 * @param text The input string to truncate.
 * @param maxLength The maximum length of the returned string (including ellipsis).
 * @param options Optional truncation behavior.
 * @returns The truncated string if longer than maxLength, otherwise the original.
 */
export function truncateText(
    text: string | null | undefined,
    maxLength: number,
    options?: {
        ellipsis?: string;
        mode?: "end" | "middle";
    },
): string {
    const s = String(text ?? "");
    const m = Math.max(0, Math.floor(maxLength));
    if (!s || m <= 0) return "";
    if (s.length <= m) return s;

    const ellipsis = options?.ellipsis ?? "…";
    const mode = options?.mode ?? "end";

    if (m <= ellipsis.length) {
        return ellipsis.slice(0, m);
    }

    if (mode === "middle") {
        const remain = m - ellipsis.length;
        const left = Math.ceil(remain / 2);
        const right = Math.floor(remain / 2);
        return s.slice(0, left) + ellipsis + s.slice(-right);
    }

    return s.slice(0, m - ellipsis.length) + ellipsis;
}

