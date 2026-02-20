<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatCurrency } from "@/Lib/Admin/Utils/currency";

    type Product = {
        id: string;
        name: string;
        sku?: string | null;
        stock_quantity: number;
    };
    type LevelItem = {
        id: string;
        name: string;
        percent_adjust?: number | null;
    };
    let products = $derived<Product[]>(
        (($page.props as any)?.products as Product[]) ?? [],
    );
    let levels = $derived<LevelItem[]>(
        (($page.props as any)?.levels as LevelItem[]) ?? [],
    );
    let sellingPriceMap = $derived(
        (($page.props as any)?.sellingPriceMap as Record<
            string,
            Record<string, number>
        >) ?? {},
    );
    let sellingPriceMainMap = $derived(
        (($page.props as any)?.sellingPriceMainMap as Record<string, number>) ??
            {},
    );

    type LocalFilters = { q: string };
    let filters = $state<LocalFilters>({
        q: (($page.props as any)?.filters?.q as string) ?? "",
    });
    let __autoSearchInitialized = false;
    let __autoSearchTimer: ReturnType<typeof setTimeout> | null = null;
    $effect(() => {
        const q = filters.q;
        if (!__autoSearchInitialized) {
            __autoSearchInitialized = true;
            return;
        }
        if (__autoSearchTimer) {
            clearTimeout(__autoSearchTimer);
        }
        __autoSearchTimer = setTimeout(() => {
            const params = new URLSearchParams();
            if (q) params.set("q", q);
            router.get(
                "/pos/products?" + params.toString(),
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);
    });

    function priceForLevel(pid: string, lid: string): number {
        if (!lid || lid === "main") {
            const main = Number(
                sellingPriceMainMap[pid] !== undefined
                    ? sellingPriceMainMap[pid]
                    : 0,
            );
            return Number.isFinite(main) ? main : 0;
        }
        const perLevel = sellingPriceMap[pid] ?? {};
        const stored = perLevel[lid];
        if (typeof stored === "number" && Number.isFinite(stored)) {
            return stored;
        }
        const level = levels.find((l) => String(l.id) === String(lid)) ?? null;
        const adjust =
            level && typeof level.percent_adjust === "number"
                ? level.percent_adjust
                : null;
        const base = Number(
            sellingPriceMainMap[pid] !== undefined
                ? sellingPriceMainMap[pid]
                : 0,
        );
        if (adjust === null) return Number.isFinite(base) ? base : 0;
        return Math.round(base * (1 + Number(adjust) / 100));
    }
</script>

<svelte:head>
    <title>POS Product | {siteName(($page.props as any).settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                POS Product
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Daftar produk untuk POS: harga per level, stok, dan SKU
            </p>
        </div>
    </header>

    <Card title="Filter">
        <TextInput
            id="q"
            name="q"
            label="Cari produk"
            placeholder="Nama atau SKU..."
            bind:value={filters.q}
        />
    </Card>

    <Card title="Daftar Produk POS" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Stok</th>
                            <th>Harga Utama</th>
                            {#each levels as lvl}
                                <th>Harga {lvl.name}</th>
                            {/each}
                        </tr>
                    </thead>
                    <tbody>
                        {#if products?.length}
                            {#each products as p}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {p.name}
                                        </div>
                                        <div
                                            class="text-xs text-gray-900 dark:text-white"
                                        >
                                            SKU: {p.sku ?? "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {p.stock_quantity}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {formatCurrency(
                                                priceForLevel(p.id, "main"),
                                            )}
                                        </div>
                                    </td>
                                    {#each levels as lvl}
                                        <td>
                                            <div
                                                class="text-sm text-gray-700 dark:text-gray-300"
                                            >
                                                {formatCurrency(
                                                    priceForLevel(
                                                        p.id,
                                                        String(lvl.id),
                                                    ),
                                                )}
                                            </div>
                                        </td>
                                    {/each}
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan={4 + levels.length}
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Tidak ada data</td
                                >
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
    </Card>
</section>
