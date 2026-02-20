<script lang="ts">
    import Self from "./OrgNode.svelte";

    type OrgNode = {
        id: string;
        name: string;
        role: string | null;
        email: string | null;
        kind: "role" | "position" | "employee";
        children: OrgNode[];
    };

    interface Props {
        node: OrgNode;
        onAddChild?: (id: string) => void;
    }

    let { node, onAddChild }: Props = $props();

    function getInitials(name: string): string {
        const m = name
            .match(/(\b\S)?/g)
            ?.join("")
            .match(/(^\S|\S$)?/g)
            ?.join("")
            ?.toUpperCase()
            ?.substring(0, 2);
        return m && m.length ? m : "??";
    }

    const isPlaceholder = $derived(node.kind === "position");

    let rowSize = 3;
    function chunkChildren(arr: OrgNode[], size: number): OrgNode[][] {
        const a = arr ?? [];
        const s = Math.max(1, size);
        const out: OrgNode[][] = [];
        for (let i = 0; i < a.length; i += s) {
            out.push(a.slice(i, i + s));
        }
        return out;
    }
    const childRows = $derived(chunkChildren(node.children ?? [], rowSize));
</script>

<li>
    <div class="flex flex-col items-center">
        {#if node.kind !== "role"}
            <div
                class="org-card rounded-xl border-t-4 w-56 md:w-60 lg:w-64 relative group z-10 text-left overflow-hidden transition-all duration-300 hover:shadow-xl shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-1px_rgba(0,0,0,0.06)] border-blue-500 dark:border-blue-400
                {isPlaceholder
                    ? 'dark:bg-[#0f0f0f] bg-white placeholder'
                    : 'bg-white dark:bg-slate-800 '}
                {isPlaceholder ? 'border-slate-400 dark:border-slate-500' : ''}"
                role="button"
                tabindex="0"
                aria-disabled={isPlaceholder}
            >
                <div class="p-4">
                    <div class="flex gap-4 items-start">
                        <div
                            class="{isPlaceholder
                                ? 'text-slate-500 bg-slate-200 dark:text-slate-300 dark:bg-slate-700'
                                : 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-blue-950/50'} flex justify-center items-center w-10 h-10 text-base font-bold rounded-full shrink-0"
                            aria-hidden="true"
                        >
                            {isPlaceholder ? "?" : getInitials(node.name)}
                        </div>
                        <div class="overflow-hidden">
                            <h4
                                class="{isPlaceholder
                                    ? 'text-slate-700 dark:text-slate-200'
                                    : 'text-slate-800 dark:text-slate-200'} font-bold text-[15px] truncate leading-snug"
                                title={node.name}
                            >
                                {node.name}
                            </h4>
                            <p
                                class="{isPlaceholder
                                    ? 'text-slate-500 dark:text-slate-400'
                                    : 'text-blue-500 dark:text-blue-400'} text-[11px] font-bold uppercase tracking-wide mt-0.5 truncate"
                                title={node.role || ""}
                            >
                                {node.role || ""}
                            </p>
                        </div>
                    </div>
                </div>

                {#if node.email}
                    <div
                        class="{isPlaceholder
                            ? 'bg-slate-300 dark:bg-slate-700'
                            : 'bg-slate-100 dark:bg-slate-700'} w-full h-px"
                    ></div>

                    <div
                        class="{isPlaceholder
                            ? 'bg-white/60 dark:bg-slate-800/60'
                            : 'bg-white dark:bg-slate-800'} px-4 py-2"
                    >
                        <p
                            class="flex items-center text-xs truncate transition-colors {isPlaceholder
                                ? 'text-slate-400 dark:text-slate-400'
                                : 'text-slate-400 dark:text-slate-400'} group-hover:text-slate-500 dark:group-hover:text-slate-300"
                        >
                            <i
                                class="far fa-envelope mr-2.5 {isPlaceholder
                                    ? 'text-slate-300 dark:text-slate-500'
                                    : 'text-slate-300 dark:text-slate-500'}"
                            ></i>
                            {#if node.email}
                                {node.email}
                            {:else}
                                <span
                                    class="italic opacity-50 dark:text-slate-400"
                                    >No Email</span
                                >
                            {/if}
                        </p>
                    </div>
                {/if}
            </div>
        {/if}
    </div>

    {#if node.children && node.children.length}
        {#each childRows as row, i (i)}
            <ul>
                {#each row as child (child.id)}
                    {@html ""}
                    <Self node={child} onAddChild={(id) => onAddChild?.(id)} />
                {/each}
            </ul>
        {/each}
    {/if}
</li>

<style>
    .org-card.placeholder {
        cursor: default;
    }
</style>
