<script lang="ts">
    import DocsLayout from "@/Lib/Docs/Layouts/Default.svelte";

    // ── Types ─────────────────────────────────────────────────────────────
    interface RouteEntry {
        method: string;
        path: string;
        desc: string;
    }

    interface DocSection {
        id: string;
        title: string;
        desc: string;
        routes: RouteEntry[];
        notes?: string[];
        flow?: string[];
    }

    type RoleColor = "blue" | "orange" | "green" | "purple";
    type FlowColor =
        | "blue"
        | "yellow"
        | "orange"
        | "purple"
        | "indigo"
        | "green"
        | "red";

    interface Role {
        id: string;
        name: string;
        color: RoleColor;
        desc: string;
        sections: DocSection[];
    }

    interface OrderFlowStep {
        status: string;
        actor: string;
        color: FlowColor;
        desc: string;
    }

    interface Props {
        roles: Role[];
        orderFlow: OrderFlowStep[];
    }

    let { roles, orderFlow }: Props = $props();

    // ── Nav & TOC data ────────────────────────────────────────────────────
    const navSections = [
        {
            group: "Overview",
            links: [
                { href: "#introduction", label: "Pendahuluan" },
                { href: "#order-flow", label: "Alur Order" },
            ],
        },
        {
            group: "Customer",
            links: [
                { href: "#customer", label: "Overview" },
                { href: "#cust-auth", label: "Autentikasi & Profil" },
                { href: "#cust-browse", label: "Browsing & Menu" },
                { href: "#cust-checkout", label: "Checkout & Pembayaran" },
                { href: "#cust-orders", label: "Pesanan & Testimoni" },
                { href: "#cust-engage", label: "Feedback & Notifikasi" },
            ],
        },
        {
            group: "Chef",
            links: [
                { href: "#chef", label: "Overview" },
                { href: "#chef-dashboard", label: "Dashboard" },
                { href: "#chef-orders", label: "Manajemen Item" },
                { href: "#chef-report", label: "Laporan Keuangan" },
            ],
        },
        {
            group: "PIC Pickup Point",
            links: [
                { href: "#pic", label: "Overview" },
                { href: "#pic-dashboard", label: "Dashboard" },
                { href: "#pic-orders", label: "Operasi Order" },
            ],
        },
        {
            group: "Admin",
            links: [
                { href: "#admin", label: "Overview" },
                { href: "#admin-dashboard", label: "Dashboard" },
                { href: "#admin-orders", label: "Manajemen Order" },
                { href: "#admin-products", label: "Produk & Kategori" },
                { href: "#admin-chefs", label: "Manajemen Chef" },
                { href: "#admin-locations", label: "Drop & Pickup Point" },
                { href: "#admin-payments", label: "Metode Pembayaran" },
                { href: "#admin-customers", label: "Customer" },
                { href: "#admin-requests", label: "Food Request" },
                { href: "#admin-reports", label: "Laporan" },
                { href: "#admin-settings", label: "Pengaturan & User" },
            ],
        },
    ];

    const tocItems = [
        { href: "#introduction", label: "Pendahuluan" },
        { href: "#order-flow", label: "Alur Order" },
        { href: "#customer", label: "Customer" },
        { href: "#chef", label: "Chef" },
        { href: "#pic", label: "PIC Pickup Point" },
        { href: "#admin", label: "Admin" },
    ];

    // ── Helpers ───────────────────────────────────────────────────────────
    const methodStyle: Record<string, string> = {
        GET: "background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);",
        POST: "background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3);",
        PUT: "background:rgba(234,179,8,0.15);color:#facc15;border:1px solid rgba(234,179,8,0.3);",
        PATCH: "background:rgba(234,179,8,0.15);color:#facc15;border:1px solid rgba(234,179,8,0.3);",
        DELETE: "background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);",
    };

    const roleStyle: Record<
        RoleColor,
        {
            badge: string;
            borderLeft: string;
            title: string;
            dot: string;
            callout: string;
        }
    > = {
        blue: {
            badge: "background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3);",
            borderLeft: "#3b82f6",
            title: "color:#60a5fa;",
            dot: "background:#3b82f6;",
            callout: "rgba(59,130,246,0.5)",
        },
        orange: {
            badge: "background:rgba(249,115,22,0.15);color:#fb923c;border:1px solid rgba(249,115,22,0.3);",
            borderLeft: "#f97316",
            title: "color:#fb923c;",
            dot: "background:#f97316;",
            callout: "rgba(249,115,22,0.5)",
        },
        green: {
            badge: "background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);",
            borderLeft: "#22c55e",
            title: "color:#4ade80;",
            dot: "background:#22c55e;",
            callout: "rgba(34,197,94,0.5)",
        },
        purple: {
            badge: "background:rgba(168,85,247,0.15);color:#c084fc;border:1px solid rgba(168,85,247,0.3);",
            borderLeft: "#a855f7",
            title: "color:#c084fc;",
            dot: "background:#a855f7;",
            callout: "rgba(168,85,247,0.5)",
        },
    };

    const flowColorStyle: Record<FlowColor, string> = {
        blue: "background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3);",
        yellow: "background:rgba(234,179,8,0.15);color:#facc15;border:1px solid rgba(234,179,8,0.3);",
        orange: "background:rgba(249,115,22,0.15);color:#fb923c;border:1px solid rgba(249,115,22,0.3);",
        purple: "background:rgba(168,85,247,0.15);color:#c084fc;border:1px solid rgba(168,85,247,0.3);",
        indigo: "background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);",
        green: "background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);",
        red: "background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);",
    };
</script>

<svelte:head>
    <title>Dokumentasi | AOWenak</title>
</svelte:head>

<DocsLayout {navSections} {tocItems} title="Dokumentasi">
    <!-- ═══════════════════════════════════════════════════════════════════
         INTRODUCTION
    ════════════════════════════════════════════════════════════════════ -->
    <section id="introduction" class="doc-section mb-16">
        <div class="flex items-center gap-3 mb-5">
            <span
                class="font-['Fira_Code'] text-[0.65rem] uppercase tracking-widest font-semibold px-3 py-1 rounded-full"
                style="background:#13161e;border:1px solid #1f2433;color:#e8c547;"
            >
                Pendahuluan
            </span>
        </div>

        <h1
            class="font-[Syne] text-4xl font-extrabold text-white leading-tight mb-4 tracking-tight"
        >
            Dokumentasi <span style="color:#e8c547;">AOWenak</span>
        </h1>

        <p
            class="font-[Lora] text-base leading-relaxed mb-6"
            style="color:#8b91a8;"
        >
            AOWenak adalah platform pre-order dan instant order makanan berbasis
            web yang menghubungkan customer, chef mitra, petugas pickup point,
            dan admin dalam satu ekosistem terintegrasi. Dokumentasi ini
            menjelaskan kemampuan dan alur kerja masing-masing dari 4 role yang
            ada.
        </p>

        <!-- Role Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
            {#each roles as role}
                <a
                    href="#{role.id}"
                    class="rounded-xl px-4 py-4 border transition-all hover:scale-[1.03]"
                    style="background:#13161e;border-color:{roleStyle[
                        role.color
                    ].borderLeft}40;"
                >
                    <div
                        class="w-2 h-2 rounded-full mb-3"
                        style={roleStyle[role.color].dot}
                    ></div>
                    <p class="font-[Syne] font-semibold text-white text-sm">
                        {role.name}
                    </p>
                    <p class="text-xs mt-1" style="color:#4a5068;">
                        {role.sections.length} section
                    </p>
                </a>
            {/each}
        </div>

        <!-- Tech stack callout -->
        <div
            class="border-l-2 rounded-r-xl px-5 py-4"
            style="border-color:#e8c547;background:#13161e;"
        >
            <p class="font-[Syne] font-semibold text-white text-sm mb-2">
                Tech Stack
            </p>
            <div class="flex flex-wrap gap-2">
                {#each ["Laravel 12", "Svelte 5", "Inertia.js", "PostgreSQL", "Tailwind CSS v4", "Midtrans", "Biteship", "Fonnte (WhatsApp)"] as tech}
                    <span
                        class="font-['Fira_Code'] text-xs px-2.5 py-1 rounded"
                        style="background:#1f2433;color:#8b91a8;"
                    >
                        {tech}
                    </span>
                {/each}
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════
         ORDER FLOW
    ════════════════════════════════════════════════════════════════════ -->
    <section id="order-flow" class="doc-section mb-16">
        <h2
            class="font-[Syne] text-2xl font-bold text-white mb-2 tracking-tight"
        >
            Alur Status Order
        </h2>
        <p
            class="font-[Lora] text-sm leading-relaxed mb-6"
            style="color:#8b91a8;"
        >
            Setiap order melewati status berikut secara berurutan. Masing-masing
            status dipicu oleh role yang berbeda.
        </p>

        <!-- Flow diagram -->
        <div
            class="flex flex-wrap items-center gap-2 mb-8 p-5 rounded-xl border"
            style="background:#13161e;border-color:#1f2433;"
        >
            {#each orderFlow.filter((s) => s.status !== "CANCELLED") as step, i}
                <span
                    class="font-['Fira_Code'] text-xs font-semibold px-3 py-1.5 rounded-lg"
                    style={flowColorStyle[step.color]}
                >
                    {step.status}
                </span>
                {#if i < orderFlow.filter((s) => s.status !== "CANCELLED").length - 1}
                    <span style="color:#4a5068;">→</span>
                {/if}
            {/each}
            <span style="color:#4a5068;" class="mx-1">│</span>
            <span
                class="font-['Fira_Code'] text-xs font-semibold px-3 py-1.5 rounded-lg"
                style={flowColorStyle["red"]}
            >
                CANCELLED
            </span>
            <span class="text-xs ml-1" style="color:#4a5068;"
                >(kapan saja sebelum DELIVERED)</span
            >
        </div>

        <!-- Flow table -->
        <div
            class="rounded-xl overflow-hidden border"
            style="border-color:#1f2433;"
        >
            <table>
                <thead>
                    <tr style="background:#13161e;">
                        <th
                            class="text-left px-5 py-3 text-xs font-[Syne] font-semibold uppercase tracking-wider"
                            style="color:#8b91a8;">Status</th
                        >
                        <th
                            class="text-left px-5 py-3 text-xs font-[Syne] font-semibold uppercase tracking-wider"
                            style="color:#8b91a8;">Dilakukan Oleh</th
                        >
                        <th
                            class="text-left px-5 py-3 text-xs font-[Syne] font-semibold uppercase tracking-wider"
                            style="color:#8b91a8;">Keterangan</th
                        >
                    </tr>
                </thead>
                <tbody>
                    {#each orderFlow as step}
                        <tr
                            class="transition-colors"
                            style="background:transparent;"
                            onmouseenter={(e) =>
                                ((
                                    e.currentTarget as HTMLElement
                                ).style.background = "rgba(255,255,255,0.02)")}
                            onmouseleave={(e) =>
                                ((
                                    e.currentTarget as HTMLElement
                                ).style.background = "transparent")}
                        >
                            <td class="px-5 py-3">
                                <span
                                    class="font-['Fira_Code'] text-xs font-semibold px-2.5 py-1 rounded"
                                    style={flowColorStyle[step.color]}
                                >
                                    {step.status}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 text-sm font-[Syne] font-medium"
                                style="color:#d4d8e8;">{step.actor}</td
                            >
                            <td
                                class="px-5 py-3 text-sm font-[Lora] leading-relaxed"
                                style="color:#8b91a8;">{step.desc}</td
                            >
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════
         ROLE SECTIONS (generated from roles prop)
    ════════════════════════════════════════════════════════════════════ -->
    {#each roles as role, roleIdx}
        <!-- Role header section -->
        <section id={role.id} class="doc-section mb-6">
            <div
                class="rounded-xl px-6 py-6 border-l-4"
                style="background:#13161e;border-left-color:{roleStyle[
                    role.color
                ].borderLeft};"
            >
                <div class="flex items-center gap-3 mb-3">
                    <span
                        class="font-['Fira_Code'] text-[0.65rem] uppercase tracking-widest font-semibold px-3 py-1 rounded-full"
                        style={roleStyle[role.color].badge}
                    >
                        {role.name}
                    </span>
                    <span class="text-xs font-[Syne]" style="color:#4a5068;">
                        {role.sections.length} section · {role.sections.reduce(
                            (a, s) => a + (s.routes?.length ?? 0),
                            0,
                        )} routes
                    </span>
                </div>
                <h2
                    class="font-[Syne] text-2xl font-bold tracking-tight mb-2"
                    style={roleStyle[role.color].title}
                >
                    {role.name}
                </h2>
                <p
                    class="font-[Lora] text-sm leading-relaxed"
                    style="color:#8b91a8;"
                >
                    {role.desc}
                </p>
            </div>
        </section>

        <!-- Sub-sections -->
        {#each role.sections as section}
            <section id={section.id} class="doc-section mb-10">
                <h3
                    class="font-[Syne] text-lg font-semibold text-white mb-1 tracking-tight"
                >
                    {section.title}
                </h3>
                <p
                    class="font-[Lora] text-sm leading-relaxed mb-5"
                    style="color:#8b91a8;"
                >
                    {section.desc}
                </p>

                <!-- Routes table -->
                {#if section.routes && section.routes.length > 0}
                    <div
                        class="rounded-xl overflow-hidden border mb-4"
                        style="border-color:#1f2433;"
                    >
                        <table>
                            <thead>
                                <tr style="background:#13161e;">
                                    <th
                                        class="text-left px-4 py-3 text-xs font-[Syne] font-semibold uppercase tracking-wider w-24"
                                        style="color:#8b91a8;">Method</th
                                    >
                                    <th
                                        class="text-left px-4 py-3 text-xs font-[Syne] font-semibold uppercase tracking-wider"
                                        style="color:#8b91a8;">Path</th
                                    >
                                    <th
                                        class="text-left px-4 py-3 text-xs font-[Syne] font-semibold uppercase tracking-wider"
                                        style="color:#8b91a8;">Deskripsi</th
                                    >
                                </tr>
                            </thead>
                            <tbody>
                                {#each section.routes as route}
                                    <tr
                                        style="background:transparent;"
                                        onmouseenter={(e) =>
                                            ((
                                                e.currentTarget as HTMLElement
                                            ).style.background =
                                                "rgba(255,255,255,0.025)")}
                                        onmouseleave={(e) =>
                                            ((
                                                e.currentTarget as HTMLElement
                                            ).style.background = "transparent")}
                                    >
                                        <td class="px-4 py-3">
                                            <span
                                                class="font-['Fira_Code'] text-[0.65rem] font-semibold uppercase tracking-wider px-2 py-0.5 rounded"
                                                style={methodStyle[
                                                    route.method
                                                ] ?? methodStyle["GET"]}
                                            >
                                                {route.method}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <code
                                                class="font-['Fira_Code'] text-xs"
                                                style="background:#1f2433;color:#e8c547;padding:2px 7px;border-radius:4px;"
                                            >
                                                {route.path}
                                            </code>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm font-[Lora] leading-relaxed"
                                            style="color:#8b91a8;"
                                        >
                                            {route.desc}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                {/if}

                <!-- Status flow -->
                {#if section.flow && section.flow.length > 0}
                    <div
                        class="rounded-xl border px-5 py-4 mb-4"
                        style="background:#13161e;border-color:#1f2433;"
                    >
                        <p
                            class="font-[Syne] font-semibold text-white text-xs uppercase tracking-wider mb-3"
                        >
                            Alur Status
                        </p>
                        <div class="space-y-2">
                            {#each section.flow as step}
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full shrink-0"
                                        style={roleStyle[role.color].dot}
                                    ></span>
                                    <code
                                        class="font-['Fira_Code'] text-xs"
                                        style="color:#d4d8e8;">{step}</code
                                    >
                                </div>
                            {/each}
                        </div>
                    </div>
                {/if}

                <!-- Notes callout -->
                {#if section.notes && section.notes.length > 0}
                    <div
                        class="border-l-2 rounded-r-xl px-5 py-4"
                        style="border-color:{roleStyle[role.color]
                            .callout};background:#13161e;"
                    >
                        <p
                            class="font-[Syne] font-semibold text-white text-xs uppercase tracking-wider mb-2"
                        >
                            Catatan
                        </p>
                        <ul class="space-y-1.5">
                            {#each section.notes as note}
                                <li
                                    class="text-sm font-[Lora] leading-relaxed flex items-start gap-2"
                                    style="color:#8b91a8;"
                                >
                                    <span
                                        class="mt-2 w-1 h-1 rounded-full shrink-0"
                                        style="background:#4a5068;"
                                    ></span>
                                    {note}
                                </li>
                            {/each}
                        </ul>
                    </div>
                {/if}
            </section>
        {/each}

        <!-- Divider between roles (not after last) -->
        {#if roleIdx < roles.length - 1}
            <div class="my-12 border-t" style="border-color:#1f2433;"></div>
        {/if}
    {/each}

    <!-- ── FOOTER ──────────────────────────────────────────────────────── -->
    <footer
        class="border-t pt-8 pb-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mt-8"
        style="border-color:#1f2433;"
    >
        <p class="font-['Fira_Code'] text-xs" style="color:#4a5068;">
            © 2025 AOWenak — Semua hak dilindungi.
        </p>
        <div class="flex gap-5 text-xs font-[Syne] font-medium">
            <a
                href="/privacy-policy"
                class="transition-colors hover:text-white"
                style="color:#4a5068;">Privasi</a
            >
            <a
                href="/terms-and-conditions"
                class="transition-colors hover:text-white"
                style="color:#4a5068;">Ketentuan</a
            >
            <a
                href="/"
                class="transition-colors hover:text-white"
                style="color:#4a5068;">Beranda</a
            >
        </div>
    </footer>
</DocsLayout>
