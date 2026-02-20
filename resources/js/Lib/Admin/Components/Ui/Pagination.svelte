<script lang="ts">
    interface Props {
        currentPage?: number;
        totalPages?: number;
        totalItems?: number;
        itemsPerPage?: number;
        onPageChange?: (page: number) => void;
        onItemsPerPageChange?: (itemsPerPage: number) => void;
        showItemsPerPage?: boolean;
        itemsPerPageOptions?: number[];
        class?: string;
    }

    let {
        currentPage = 1,
        totalPages = 1,
        totalItems = 0,
        itemsPerPage = 10,
        onPageChange,
        onItemsPerPageChange,
        showItemsPerPage = true,
        itemsPerPageOptions = [10, 25, 50, 100],
        class: className,
    }: Props = $props();

    const startItem = $derived((currentPage - 1) * itemsPerPage + 1);
    const endItem = $derived(Math.min(currentPage * itemsPerPage, totalItems));

    function getPageNumbers() {
        const pages: (number | string)[] = [];
        const maxVisible = 7; // Maximum page numbers to show

        if (totalPages <= maxVisible) {
            // Show all pages
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            // Always show first page
            pages.push(1);

            if (currentPage <= 3) {
                // Near start
                for (let i = 2; i <= 4; i++) {
                    pages.push(i);
                }
                pages.push("...");
                pages.push(totalPages);
            } else if (currentPage >= totalPages - 2) {
                // Near end
                pages.push("...");
                for (let i = totalPages - 3; i <= totalPages; i++) {
                    pages.push(i);
                }
            } else {
                // Middle
                pages.push("...");
                for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                    pages.push(i);
                }
                pages.push("...");
                pages.push(totalPages);
            }
        }

        return pages;
    }

    function goToPage(page: number) {
        if (page < 1 || page > totalPages || page === currentPage) return;
        onPageChange?.(page);
    }

    function handleItemsPerPageChange(e: Event) {
        const target = e.target as HTMLSelectElement;
        const newItemsPerPage = parseInt(target.value);
        onItemsPerPageChange?.(newItemsPerPage);
    }
</script>

<div
    class="flex flex-col sm:flex-row items-center justify-between gap-4 {className ||
        ''}"
>
    <!-- Left: Items info -->
    <div
        class="flex gap-4 items-center text-sm text-gray-700 dark:text-gray-300"
    >
        {#if totalItems > 0}
            <span>
                Menampilkan <span class="font-semibold">{startItem}</span> -
                <span class="font-semibold">{endItem}</span>
                dari <span class="font-semibold">{totalItems}</span> data
            </span>
        {:else}
            <span>Tidak ada data</span>
        {/if}

        {#if showItemsPerPage}
            <div class="flex gap-2 items-center">
                <label for="items-per-page" class="whitespace-nowrap">
                    Per halaman:
                </label>
                <select
                    id="items-per-page"
                    value={itemsPerPage}
                    onchange={handleItemsPerPageChange}
                    class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#0a0a0a] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0060B2] transition-colors"
                >
                    {#each itemsPerPageOptions as option}
                        <option value={option}>{option}</option>
                    {/each}
                </select>
            </div>
        {/if}
    </div>

    <!-- Right: Pagination controls -->
    {#if totalPages > 1}
        <nav class="flex gap-1 items-center" aria-label="Pagination">
            <!-- Previous button -->
            <button
                type="button"
                onclick={() => goToPage(currentPage - 1)}
                disabled={currentPage === 1}
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-medium transition-all duration-200
                    {currentPage === 1
                    ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed'
                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0060B2]'}"
                aria-label="Previous page"
            >
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <!-- Page numbers -->
            {#each getPageNumbers() as page}
                {#if page === "..."}
                    <span
                        class="inline-flex justify-center items-center w-9 h-9 text-gray-500 dark:text-gray-400"
                    >
                        {page}
                    </span>
                {:else}
                    <button
                        type="button"
                        onclick={() => goToPage(page as number)}
                        class="inline-flex items-center justify-center min-w-9 h-9 px-3 rounded-lg text-sm font-medium transition-all duration-200
                            {currentPage === page
                            ? 'bg-[#0060B2] text-white shadow-sm'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0060B2]'}"
                        aria-label="Go to page {page}"
                        aria-current={currentPage === page ? "page" : undefined}
                    >
                        {page}
                    </button>
                {/if}
            {/each}

            <!-- Next button -->
            <button
                type="button"
                onclick={() => goToPage(currentPage + 1)}
                disabled={currentPage === totalPages}
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-medium transition-all duration-200
                    {currentPage === totalPages
                    ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed'
                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0060B2]'}"
                aria-label="Next page"
            >
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </nav>
    {/if}
</div>
