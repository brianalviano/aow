import { toastStore } from "@/Lib/Admin/Stores/toast";

/**
 * Copy text to clipboard.
 *
 * @param text - The text to copy.
 * @returns Whether the text was successfully copied.
 */
export async function copyText(text: string): Promise<boolean> {
    if (!text) return false;
    if (
        typeof navigator !== "undefined" &&
        navigator.clipboard &&
        typeof navigator.clipboard.writeText === "function"
    ) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            return false;
        }
    }
    return false;
}

/**
 * Copy text to clipboard with toast notification.
 *
 * @param text - The text to copy.
 * @param successMessage - The success message to show.
 * @param errorMessage - The error message to show.
 * @returns Whether the text was successfully copied.
 */
export async function copyTextWithToast(
    text: string,
    successMessage?: string,
    errorMessage?: string
): Promise<boolean> {
    const ok = await copyText(text);
    if (ok) {
        toastStore.success(
            "Berhasil",
            successMessage ?? "Teks disalin ke clipboard."
        );
    } else {
        toastStore.error("Gagal", errorMessage ?? "Tidak dapat menyalin teks.");
    }
    return ok;
}
