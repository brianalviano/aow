<script lang="ts">
    import type { Snippet } from "svelte";

    interface NavLink {
        href: string;
        label: string;
    }

    interface NavGroup {
        group: string;
        links: NavLink[];
    }

    interface Props {
        children: Snippet;
        navSections?: NavGroup[];
        tocItems?: NavLink[];
        title?: string;
    }

    let { children, navSections = [], tocItems = [] }: Props = $props();

    // ── scroll-spy ──────────────────────────────────────────────────────────
    let activeId = $state<string>("");

    $effect(() => {
        const sections = document.querySelectorAll<HTMLElement>("section[id]");

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        activeId = entry.target.id;
                    }
                });
            },
            { rootMargin: "-20% 0px -70% 0px" },
        );

        sections.forEach((s) => observer.observe(s));
        return () => observer.disconnect();
    });

    const roleColors: Record<string, string> = {
        customer: "text-blue-400",
        chef: "text-orange-400",
        pic: "text-green-400",
        admin: "text-purple-400",
    };

    function navLinkClass(href: string): string {
        const id = href.replace("#", "");
        const base =
            "relative block text-sm font-medium transition-colors px-2 py-1.5 rounded";
        const active = activeId === id;
        const roleColor = roleColors[id] ?? "";
        return active
            ? `${base} ${roleColor || "text-[#e8c547]"} nav-link-active`
            : `${base} text-[#8b91a8] hover:text-white`;
    }
</script>

<svelte:head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin="anonymous"
    />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Fira+Code:wght@300;400;500&family=Lora:ital,wght@0,400;0,500;1,400&display=swap"
        rel="stylesheet"
    />
</svelte:head>

<!-- ── TOP BAR ──────────────────────────────────────────────────────────── -->
<header
    class="fixed top-0 left-0 right-0 z-50 border-b"
    style="border-color:#1f2433;background:rgba(13,15,20,0.92);backdrop-filter:blur(12px);"
>
    <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a
                href="/"
                class="font-[Syne] font-extrabold text-lg tracking-tight text-white hover:opacity-80 transition-opacity"
            >
                AOWenak
            </a>
            <span
                class="font-['Fira_Code'] text-[0.65rem] uppercase tracking-widest font-semibold px-2 py-0.5 rounded"
                style="background:#e8c547;color:#0d0f14;"
            >
                Docs
            </span>
        </div>
        <nav
            class="hidden md:flex items-center gap-7 text-sm font-[Syne] font-medium"
            style="color:#8b91a8;"
        >
            <a href="/docs" class="hover:text-white transition-colors"
                >Dokumentasi</a
            >
            <a href="/" class="hover:text-white transition-colors">Beranda</a>
        </nav>
    </div>
</header>

<div class="max-w-7xl mx-auto flex pt-14">
    <!-- ── SIDEBAR ──────────────────────────────────────────────────────── -->
    <aside
        class="sidebar w-60 shrink-0 hidden lg:block border-r px-5 py-8"
        style="border-color:#1f2433;background:rgba(13,15,20,0.6);"
    >
        {#each navSections as section}
            <p
                class="font-['Fira_Code'] text-[0.65rem] uppercase tracking-widest font-semibold mb-3"
                style="color:#4a5068;"
            >
                {section.group}
            </p>
            <ul class="space-y-0.5 mb-6">
                {#each section.links as link}
                    <li>
                        <a href={link.href} class={navLinkClass(link.href)}>
                            {link.label}
                        </a>
                    </li>
                {/each}
            </ul>
        {/each}
    </aside>

    <!-- ── MAIN CONTENT ─────────────────────────────────────────────────── -->
    <main class="flex-1 min-w-0 px-8 lg:px-14 py-10 max-w-3xl">
        {@render children()}
    </main>

    <!-- ── RIGHT TOC ────────────────────────────────────────────────────── -->
    {#if tocItems.length > 0}
        <aside class="toc-aside w-52 shrink-0 hidden xl:block px-6 py-10">
            <p
                class="font-['Fira_Code'] text-[0.65rem] uppercase tracking-widest font-semibold mb-3"
                style="color:#4a5068;"
            >
                On This Page
            </p>
            <ul class="space-y-1 text-xs font-[Syne] font-medium">
                {#each tocItems as item}
                    <li>
                        <a
                            href={item.href}
                            class="block py-0.5 transition-colors"
                            style={activeId === item.href.replace("#", "")
                                ? "color:#e8c547;"
                                : "color:#4a5068;"}
                            onmouseenter={(e) => {
                                if (activeId !== item.href.replace("#", ""))
                                    (
                                        e.currentTarget as HTMLAnchorElement
                                    ).style.color = "#8b91a8";
                            }}
                            onmouseleave={(e) => {
                                if (activeId !== item.href.replace("#", ""))
                                    (
                                        e.currentTarget as HTMLAnchorElement
                                    ).style.color = "#4a5068";
                            }}
                        >
                            {item.label}
                        </a>
                    </li>
                {/each}
            </ul>
        </aside>
    {/if}
</div>

<style>
    :global(body) {
        background-color: #0d0f14;
        background-image: radial-gradient(circle, #1f2433 1px, transparent 1px);
        background-size: 28px 28px;
        font-family: "Lora", serif;
        color: #d4d8e8;
    }

    :global(::-webkit-scrollbar) {
        width: 6px;
    }
    :global(::-webkit-scrollbar-track) {
        background: #0d0f14;
    }
    :global(::-webkit-scrollbar-thumb) {
        background: #1f2433;
        border-radius: 4px;
    }
    :global(::-webkit-scrollbar-thumb:hover) {
        background: #e8c547;
    }

    :global(html) {
        scroll-behavior: smooth;
    }

    .nav-link-active::before {
        content: "";
        position: absolute;
        left: -1px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e8c547;
        border-radius: 2px;
    }

    :global(.doc-section) {
        opacity: 0;
        transform: translateY(18px);
        animation: fadeUp 0.5s ease forwards;
    }
    :global(.doc-section:nth-child(1)) {
        animation-delay: 0.05s;
    }
    :global(.doc-section:nth-child(2)) {
        animation-delay: 0.1s;
    }
    :global(.doc-section:nth-child(3)) {
        animation-delay: 0.15s;
    }
    :global(.doc-section:nth-child(4)) {
        animation-delay: 0.2s;
    }
    :global(.doc-section:nth-child(5)) {
        animation-delay: 0.25s;
    }
    :global(.doc-section:nth-child(6)) {
        animation-delay: 0.3s;
    }
    :global(.doc-section:nth-child(7)) {
        animation-delay: 0.35s;
    }

    @keyframes fadeUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    :global(code:not(pre code)) {
        font-family: "Fira Code", monospace;
        font-size: 0.78em;
        background: #1f2433;
        color: #e8c547;
        padding: 2px 7px;
        border-radius: 4px;
    }

    :global(pre) {
        font-family: "Fira Code", monospace;
        font-size: 0.78rem;
        line-height: 1.7;
        overflow-x: auto;
    }

    :global(table) {
        border-collapse: collapse;
        width: 100%;
    }
    :global(thead tr) {
        border-bottom: 1px solid #1f2433;
    }
    :global(tbody tr) {
        border-bottom: 1px solid #1a1d28;
    }
    :global(tbody tr:last-child) {
        border-bottom: none;
    }

    .sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
    }

    .toc-aside {
        position: sticky;
        top: 56px;
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
</style>
