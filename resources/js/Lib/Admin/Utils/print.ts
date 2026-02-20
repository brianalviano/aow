export type PrintCleanup = () => void;

export function forceLightModeForPrint(): { restore: () => void } {
    const root = document.documentElement;
    const meta = document.querySelector(
        'meta[name="color-scheme"]',
    ) as HTMLMetaElement | null;
    const w = window as Window & { __forceLightModeForPrint?: boolean };
    const wasDark = root.classList.contains("dark");
    w.__forceLightModeForPrint = true;
    root.classList.remove("dark");
    if (meta) meta.setAttribute("content", "light");
    (root.style as any).colorScheme = "light";
    return {
        restore: () => {
            w.__forceLightModeForPrint = false;
            if (wasDark) root.classList.add("dark");
            if (meta) meta.setAttribute("content", wasDark ? "dark" : "light");
            (root.style as any).colorScheme = wasDark ? "dark" : "light";
        },
    };
}

export function setupPrintLightMode(): PrintCleanup {
    let restore: (() => void) | null = null;
    const handleBeforePrint = () => {
        restore = forceLightModeForPrint().restore;
    };
    const handleAfterPrint = () => {
        if (restore) {
            restore();
            restore = null;
        }
    };
    window.addEventListener("beforeprint", handleBeforePrint);
    window.addEventListener("afterprint", handleAfterPrint);
    return () => {
        window.removeEventListener("beforeprint", handleBeforePrint);
        window.removeEventListener("afterprint", handleAfterPrint);
        if (restore) {
            restore();
            restore = null;
        }
    };
}

export function printWithLightMode(delayMs: number = 100): PrintCleanup {
    const cleanup = setupPrintLightMode();
    setTimeout(() => {
        window.print();
    }, delayMs);
    return cleanup;
}

export function setPrintPage(options: {
    size?: string;
    orientation?: "portrait" | "landscape";
    margin?: string;
}): PrintCleanup {
    const size = options.size ?? "A4";
    const orientation = options.orientation;
    const margin = options.margin ?? "10mm";
    const style = document.createElement("style");
    style.setAttribute("data-print-page", "true");
    const orient = orientation ? ` ${orientation}` : "";
    style.textContent = `@media print { @page { size: ${size}${orient}; margin: ${margin}; } }`;
    document.head.appendChild(style);
    return () => {
        if (style.parentNode) style.parentNode.removeChild(style);
    };
}

export function openCenteredWindow(
    url: string,
    options?: {
        width?: number;
        height?: number;
        target?: string;
        disableUI?: boolean;
        scrollbars?: boolean;
        resizable?: boolean;
        focus?: boolean;
        fallbackWhenBlocked?: boolean;
    },
): Window | null {
    try {
        const w = Math.max(200, Math.floor(options?.width ?? 960));
        const h = Math.max(200, Math.floor(options?.height ?? 700));
        const screenLeft =
            (window as any).screenLeft ?? (window as any).screenX ?? 0;
        const screenTop =
            (window as any).screenTop ?? (window as any).screenY ?? 0;
        const viewportWidth =
            window.innerWidth ||
            document.documentElement.clientWidth ||
            screen.width;
        const viewportHeight =
            window.innerHeight ||
            document.documentElement.clientHeight ||
            screen.height;
        const left = Math.max(0, viewportWidth / 2 - w / 2 + screenLeft);
        const top = Math.max(0, viewportHeight / 2 - h / 2 + screenTop);
        const features = [
            `width=${w}`,
            `height=${h}`,
            `left=${Math.floor(left)}`,
            `top=${Math.floor(top)}`,
        ];
        if (options?.disableUI ?? true) {
            features.push(
                "toolbar=no",
                "location=no",
                "status=no",
                "menubar=no",
            );
        }
        features.push(
            (options?.scrollbars ?? true) ? "scrollbars=yes" : "scrollbars=no",
        );
        features.push(
            (options?.resizable ?? true) ? "resizable=yes" : "resizable=no",
        );
        features.push("noopener=yes", "noreferrer=yes");
        const win = window.open(
            url,
            options?.target ?? "_blank",
            features.join(","),
        );
        if (win && (options?.focus ?? true)) {
            try {
                win.focus();
            } catch {}
        }
        if (win) return win;
    } catch {}
    if (options?.fallbackWhenBlocked ?? true) {
        const a = document.createElement("a");
        a.href = url;
        a.target = options?.target ?? "_blank";
        a.rel = "noopener noreferrer";
        a.style.display = "none";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    return null;
}

export function enablePrintView(): PrintCleanup {
    const root = document.documentElement;
    root.setAttribute("data-print-view", "true");
    const style = document.createElement("style");
    style.setAttribute("data-print-view-style", "true");
    style.textContent = `
        html[data-print-view] aside { display: none !important; }
        html[data-print-view] main { margin-left: 0 !important; padding: 0 !important; }
        html[data-print-view] header { display: none !important; }
        html[data-print-view] .print\\:hidden { display: none !important; }
        html[data-print-view] .lg\\:hidden { display: none !important; }
    `;
    document.head.appendChild(style);
    return () => {
        root.removeAttribute("data-print-view");
        if (style.parentNode) style.parentNode.removeChild(style);
    };
}
