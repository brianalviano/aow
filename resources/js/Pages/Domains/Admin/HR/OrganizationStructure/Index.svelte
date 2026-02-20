<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import OrgNode from "./OrgNode.svelte";

    type OrgNodeType = {
        id: string;
        name: string;
        role: string | null;
        email: string | null;
        kind: "role" | "position" | "employee";
        children: OrgNodeType[];
    };

    function hydrateUsersOnly(raw: OrgNodeType[]): OrgNodeType[] {
        function hydrate(node: OrgNodeType): OrgNodeType {
            return {
                ...node,
                role: node.role ?? null,
                email: node.email ?? null,
                children: (node.children ?? []).map((c) => hydrate(c)),
            };
        }
        return (raw ?? []).map((n) => hydrate(n));
    }

    let orgTree = $state<OrgNodeType[]>(
        hydrateUsersOnly(($page.props.structure as OrgNodeType[]) ?? []),
    );

    let targetParentId = $state<string | null>(null);

    function findNodeAndParent(
        id: string,
        nodes: OrgNodeType[] = orgTree,
        parent: OrgNodeType | null = null,
    ): { node: OrgNodeType | null; parent: OrgNodeType | null } {
        for (const n of nodes) {
            if (n.id === id) return { node: n, parent };
            if (n.children?.length) {
                const res = findNodeAndParent(id, n.children, n);
                if (res.node) return res;
            }
        }
        return { node: null, parent: null };
    }

    function onAddChild(id: string) {
        const { node: parent } = findNodeAndParent(id);
        if (!parent) return;
        targetParentId = id;
    }
</script>

<svelte:head>
    <title>Struktur Organisasi | {siteName($page.props.settings)}</title>
</svelte:head>

<div class="space-y-6">
    <!-- Header -->
    <header
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
    >
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Struktur Organisasi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola struktur organisasi karyawan
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end"></div>
    </header>

    <div class="overflow-auto pb-20 mx-auto mt-4">
        <ul
            class="flex justify-center w-full min-w-max transform origin-top tree"
        >
            {#each orgTree as node (node.id)}
                <OrgNode {node} onAddChild={(id) => onAddChild(id)} />
            {/each}
        </ul>
    </div>
</div>

<style>
    :global(.tree) {
        --connector-color: #cbd5e1;
    }
    :global(.dark .tree) {
        --connector-color: #475569;
    }
    :global(.tree ul) {
        padding-top: 20px;
        position: relative;
        transition: all 0.5s;
        display: flex;
        justify-content: center;
    }
    :global(.tree li) {
        float: left;
        text-align: center;
        list-style-type: none;
        position: relative;
        padding: 16px 6px 0 6px;
        transition: all 0.5s;
    }
    /* Pusatkan anak di bawah parent dan hilangkan float agar tidak geser ke kanan */
    :global(.tree li > ul) {
        display: flex;
        justify-content: center;
        width: 100%;
        position: relative;
    }
    :global(.tree li > ul > li) {
        float: none;
        display: inline-block;
    }
    :global(.tree li::before),
    :global(.tree li::after) {
        content: "";
        position: absolute;
        top: 0;
        right: 50%;
        border-top: 2px solid var(--connector-color);
        width: 50%;
        height: 20px;
        z-index: -1;
    }
    :global(.tree li::after) {
        right: auto;
        left: 50%;
        border-left: 2px solid var(--connector-color);
    }
    :global(.tree li:only-child::after),
    :global(.tree li:only-child::before) {
        display: none;
    }
    :global(.tree li:only-child) {
        padding-top: 0;
    }
    :global(.tree li:first-child::before),
    :global(.tree li:last-child::after) {
        border: 0 none;
    }
    :global(.tree li:last-child::before) {
        border-right: 2px solid var(--connector-color);
        border-radius: 0 5px 0 0;
    }
    :global(.tree li:first-child::after) {
        border-radius: 5px 0 0 0;
    }
    /* Garis vertikal untuk semua ul di dalam tree */
    :global(.tree li > ul::before) {
        content: "";
        position: absolute;
        top: 0;
        left: 50%;
        border-left: 2px solid var(--connector-color);
        width: 0;
        height: 20px;
        z-index: -1;
    }
    /* Sembunyikan garis konektor horizontal untuk node level pertama (root) */
    :global(.tree > li::before),
    :global(.tree > li::after) {
        display: none;
    }
    :global(.tree > li) {
        padding-top: 0;
    }
</style>
