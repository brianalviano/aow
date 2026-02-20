import { router } from "@inertiajs/svelte";

export type QueryParams = Record<string, string | number | null | undefined>;

export interface VisitOptions {
    method?: "get" | "post" | "put" | "patch" | "delete";
    data?: Record<string, unknown>;
    preserveScroll?: boolean;
    preserveState?: boolean;
    replace?: boolean;
    onCancel?: () => void;
    onFinish?: () => void;
    onSuccess?: () => void;
    onError?: (errors: unknown) => void;
}

/**
 * Build a URL with query parameters.
 *
 * @param basePath - The base path of the URL.
 * @param params - The query parameters.
 * @returns The URL with query parameters.
 */
export function buildUrl(basePath: string, params: QueryParams): URL {
    const url = new URL(basePath, window.location.origin);
    for (const [key, value] of Object.entries(params)) {
        if (value === undefined || value === null) continue;
        const str = String(value);
        if (str.trim() === "") continue;
        url.searchParams.set(key, str);
    }
    return url;
}

/**
 * Visit a URL with preserved focus.
 *
 * @param url - The URL to visit.
 * @param focusId - The ID of the element to focus after the visit.
 * @param options - The visit options.
 */
export function visitPreserveFocus(
    url: URL | string,
    focusId?: string,
    options?: VisitOptions
): void {
    const href = typeof url === "string" ? url : url.toString();
    const wasFocused =
        focusId &&
        (document.activeElement as HTMLElement | null)?.id === focusId;
    router.visit(href, {
        preserveScroll: options?.preserveScroll ?? true,
        preserveState: options?.preserveState ?? true,
        replace: options?.replace ?? true,
        method: options?.method,
        data: options?.data,
        onCancel: options?.onCancel,
        onFinish: options?.onFinish,
        onError: options?.onError,
        onSuccess: () => {
            options?.onSuccess?.();
            if (wasFocused && focusId) {
                const el = document.getElementById(
                    focusId
                ) as HTMLInputElement | null;
                if (el) {
                    el.focus();
                    const pos = el.value?.length ?? 0;
                    try {
                        el.setSelectionRange(pos, pos);
                    } catch {}
                }
            }
        },
    });
}
