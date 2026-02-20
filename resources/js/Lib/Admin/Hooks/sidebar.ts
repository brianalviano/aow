import type { MenuGroup } from "@/Lib/Admin/Types/sidebar";

export function formatBadge(count: number): string {
    return count > 99 ? "99+" : count.toString();
}

export function getTextSizeClass(text: string): string {
    const length = text.length;
    if (length > 25) return "text-[0.65rem]";
    if (length > 18) return "text-[13px]";
    return "text-sm";
}

export function normalizeRoutePath(input: string): string {
    try {
        const url = new URL(input, "http://google.com");
        let p = url.pathname.toLowerCase();
        if (p.length > 1 && p.endsWith("/")) {
            p = p.slice(0, -1);
        }
        return p;
    } catch {
        const lowered = input.toLowerCase();
        if (lowered.length > 1 && lowered.endsWith("/")) {
            return lowered.slice(0, -1);
        }
        return lowered;
    }
}

export function isMenuRouteActive(
    currentUrl: string | undefined,
    currentComponent: string | undefined,
    menuRoute: string,
): boolean {
    if (!currentUrl) return false;
    const urlPath = normalizeRoutePath(currentUrl);
    const routePath = normalizeRoutePath(menuRoute);
    const routeSlug = routePath.slice(1);
    const routePathSingular =
        routePath.length > 1 && routePath.endsWith("s")
            ? routePath.slice(0, -1)
            : routePath;
    const routeSlugSingular =
        routeSlug.length > 1 && routeSlug.endsWith("s")
            ? routeSlug.slice(0, -1)
            : routeSlug;
    if (currentComponent) {
        const segments = currentComponent.toLowerCase().split("/");
        const matchesSlug =
            segments.includes(routeSlug) ||
            segments.includes(routeSlugSingular);
        if (matchesSlug) {
            if (
                (routeSlug === "leaves" || routeSlugSingular === "leave") &&
                segments.includes("manage")
            ) {
            } else {
                return true;
            }
        }
    }
    if (urlPath === routePath) return true;
    {
        const urlSegments = urlPath.split("/").filter(Boolean);
        const routeSegments = routePath.split("/").filter(Boolean);
        if (
            urlSegments.length > routeSegments.length &&
            urlPath.startsWith(`${routePath}/`)
        ) {
            return true;
        }
    }
    if (routePathSingular !== routePath) {
        if (urlPath === routePathSingular) return true;
        if (urlPath.startsWith(`${routePathSingular}/`)) return true;
    }
    return false;
}

export function getActiveMenuLink(
    groups: MenuGroup[],
    currentUrl: string | undefined,
    currentComponent: string | undefined,
): string | null {
    if (!currentUrl) return null;
    const urlPath = normalizeRoutePath(currentUrl);
    const compSegments =
        currentComponent?.toLowerCase().split("/") ?? ([] as string[]);
    const allLinks: string[] = [];
    for (const group of groups) {
        for (const item of group.items) {
            allLinks.push(item.link);
            if (item.submenu) {
                for (const sub of item.submenu) {
                    allLinks.push(sub.link);
                }
            }
        }
    }
    let best: { link: string; score: number } | null = null;
    for (const link of allLinks) {
        const routePath = normalizeRoutePath(link);
        const routeSlug = routePath.slice(1);
        const routeSlugSingular =
            routeSlug.length > 1 && routeSlug.endsWith("s")
                ? routeSlug.slice(0, -1)
                : routeSlug;
        let score = 0;
        if (urlPath === routePath) {
            score = 1000 + routePath.length;
        } else if (
            urlPath.length > routePath.length &&
            urlPath.startsWith(`${routePath}/`)
        ) {
            score = 500 + routePath.length;
        }
        if (
            compSegments.includes(routeSlug) ||
            compSegments.includes(routeSlugSingular)
        ) {
            score += 50 + routeSlug.length;
        }
        if (!best || score > best.score) {
            best = { link, score };
        }
    }
    return best && best.score > 0 ? best.link : null;
}

export function ensureActiveSubmenuExpanded(
    groups: MenuGroup[],
    expandedIds: Set<string>,
    currentUrl: string | undefined,
    currentComponent: string | undefined,
): Set<string> {
    const next = new Set(expandedIds);
    const activeLink = getActiveMenuLink(groups, currentUrl, currentComponent);
    if (!activeLink) return expandedIds;
    let changed = false;
    for (const group of groups) {
        for (const item of group.items) {
            if (
                item.submenu &&
                item.submenu.some((sub) => sub.link === activeLink)
            ) {
                if (!next.has(item.id)) {
                    next.add(item.id);
                    changed = true;
                }
            }
        }
    }
    return changed ? next : expandedIds;
}

export function filterMenuGroups(
    groups: MenuGroup[],
    query: string,
): MenuGroup[] {
    if (!query) return groups;
    const q = query.toLowerCase();
    return groups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                const matchesItem = item.label.toLowerCase().includes(q);
                const matchesSub = item.submenu?.some((sub) =>
                    sub.label.toLowerCase().includes(q),
                );
                return matchesItem || matchesSub;
            }),
        }))
        .filter((group) => group.items.length > 0);
}

export function applyThemeClass(darkMode: boolean): void {
    const printingLight =
        (window as Window & { __forceLightModeForPrint?: boolean })
            .__forceLightModeForPrint === true;
    const root = document.documentElement;
    if (printingLight) {
        root.classList.remove("dark");
    } else if (darkMode) {
        root.classList.add("dark");
    } else {
        root.classList.remove("dark");
    }
    const value = printingLight ? "light" : darkMode ? "dark" : "light";
    const meta = document.querySelector(
        'meta[name="color-scheme"]',
    ) as HTMLMetaElement | null;
    if (meta) {
        meta.setAttribute("content", value);
    } else {
        const m = document.createElement("meta");
        m.setAttribute("name", "color-scheme");
        m.setAttribute("content", value);
        document.head.appendChild(m);
    }
    (root.style as any).colorScheme = value;
}
