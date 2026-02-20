<script lang="ts">
    import { fade, scale } from "svelte/transition";
    import { quintOut } from "svelte/easing";

    interface MediaItem {
        src: string;
        type:
            | "image"
            | "video"
            | "pdf"
            | "doc"
            | "docx"
            | "xls"
            | "xlsx"
            | "ppt"
            | "pptx";
        title?: string;
        thumbnail?: string;
    }

    type MediaInput =
        | MediaItem[]
        | string[]
        | string
        | Array<{
              src: string;
              type?: string;
              title?: string;
              thumbnail?: string;
          }>;

    interface Props {
        items: MediaInput;
        isOpen: boolean;
        initialIndex?: number;
        showThumbnails?: boolean;
        enableDownload?: boolean;
        enableZoom?: boolean;
        enableRotate?: boolean;
        enableFullscreen?: boolean;
        maxZoom?: number;
        minZoom?: number;
        onClose?: () => void;
        onNavigate?: (index: number) => void;
    }

    let {
        items = [],
        isOpen = $bindable(),
        initialIndex = 0,
        showThumbnails = true,
        enableDownload = true,
        enableZoom = true,
        enableRotate = true,
        enableFullscreen = true,
        maxZoom = 5,
        minZoom = 0.5,
        onClose,
        onNavigate,
    }: Props = $props();

    let currentIndex = $state<number>(0);
    $effect(() => {
        currentIndex = initialIndex;
    });
    let zoom = $state(1);
    let rotation = $state(0);
    let isDragging = $state(false);
    let translateX = $state(0);
    let translateY = $state(0);
    let startX = $state(0);
    let startY = $state(0);
    let isFullscreen = $state(false);
    let containerElement = $state<HTMLDivElement>();
    let contentElement = $state<HTMLDivElement>();
    let isLoading = $state(false);

    function cleanUrl(u: string): string {
        const q = u.indexOf("?");
        if (q >= 0) u = u.slice(0, q);
        const h = u.indexOf("#");
        if (h >= 0) u = u.slice(0, h);
        return u;
    }

    function detectType(url: string): MediaItem["type"] {
        const lower = (url || "").toLowerCase();
        const clean = cleanUrl(lower);
        const dot = clean.lastIndexOf(".");
        const ext = dot >= 0 ? clean.slice(dot + 1) : "";
        if (["jpg", "jpeg", "png", "gif", "webp", "bmp"].includes(ext)) {
            return "image";
        }
        if (["mp4", "webm", "ogg"].includes(ext)) {
            return "video";
        }
        if (ext === "pdf") return "pdf";
        if (ext === "doc") return "doc";
        if (ext === "docx") return "docx";
        if (ext === "xls") return "xls";
        if (ext === "xlsx") return "xlsx";
        if (ext === "ppt") return "ppt";
        if (ext === "pptx") return "pptx";
        return "image";
    }

    function getFilename(url: string): string {
        try {
            const clean = cleanUrl(url);
            const slash = clean.lastIndexOf("/");
            const name = clean.slice(slash + 1);
            return decodeURIComponent(name || "Media");
        } catch {
            return "Media";
        }
    }

    function normalizeItems(input: MediaInput): MediaItem[] {
        if (typeof input === "string") {
            return [
                {
                    src: input,
                    type: detectType(input),
                    title: getFilename(input),
                },
            ];
        }
        if (Array.isArray(input)) {
            if (input.length === 0) return [];
            if (typeof input[0] === "string") {
                const arr = input as string[];
                return arr.map((src) => ({
                    src,
                    type: detectType(src),
                    title: getFilename(src),
                }));
            }
            const arr = input as Array<{
                src: string;
                type?: string;
                title?: string;
                thumbnail?: string;
            }>;
            return arr.map((it) => {
                const base: MediaItem = {
                    src: it.src,
                    type: (it.type as MediaItem["type"]) ?? detectType(it.src),
                    title: it.title ?? getFilename(it.src),
                };
                if (typeof it.thumbnail === "string") {
                    base.thumbnail = it.thumbnail;
                }
                return base;
            });
        }
        return [];
    }

    const normalizedItems = $derived(normalizeItems(items));
    const currentItem = $derived(normalizedItems[currentIndex]);
    const hasPrevious = $derived(currentIndex > 0);
    const hasNext = $derived(currentIndex < normalizedItems.length - 1);

    /**
     * Close viewer
     */
    function close() {
        if (onClose) {
            onClose();
        } else {
            isOpen = false;
        }
        resetTransform();
    }

    /**
     * Navigate to specific index
     */
    function navigateTo(index: number) {
        if (index >= 0 && index < normalizedItems.length) {
            currentIndex = index;
            resetTransform();
            if (onNavigate) {
                onNavigate(index);
            }
        }
    }

    /**
     * Navigate to previous item
     */
    function previous() {
        if (hasPrevious) {
            navigateTo(currentIndex - 1);
        }
    }

    /**
     * Navigate to next item
     */
    function next() {
        if (hasNext) {
            navigateTo(currentIndex + 1);
        }
    }

    /**
     * Zoom in
     */
    function zoomIn() {
        if (enableZoom && zoom < maxZoom) {
            zoom = Math.min(zoom + 0.5, maxZoom);
        }
    }

    /**
     * Zoom out
     */
    function zoomOut() {
        if (enableZoom && zoom > minZoom) {
            zoom = Math.max(zoom - 0.5, minZoom);
        }
    }

    /**
     * Reset zoom
     */
    function resetZoom() {
        zoom = 1;
        translateX = 0;
        translateY = 0;
    }

    /**
     * Rotate image
     */
    function rotate() {
        if (enableRotate) {
            rotation = (rotation + 90) % 360;
        }
    }

    /**
     * Reset all transformations
     */
    function resetTransform() {
        zoom = 1;
        rotation = 0;
        translateX = 0;
        translateY = 0;
    }

    /**
     * Toggle fullscreen
     */
    function toggleFullscreen() {
        if (!enableFullscreen) return;

        if (!document.fullscreenElement) {
            containerElement?.requestFullscreen();
            isFullscreen = true;
        } else {
            document.exitFullscreen();
            isFullscreen = false;
        }
    }

    /**
     * Download current item
     */
    async function download() {
        if (!enableDownload || !currentItem) return;

        try {
            const response = await fetch(currentItem.src);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = currentItem.title || `download-${Date.now()}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error("Download failed:", error);
        }
    }

    /**
     * Handle mouse wheel for zoom
     */
    function handleWheel(event: WheelEvent) {
        if (!enableZoom || !isImageOrVideo()) return;

        event.preventDefault();
        const delta = event.deltaY > 0 ? -0.1 : 0.1;
        zoom = Math.max(minZoom, Math.min(maxZoom, zoom + delta));
    }

    /**
     * Handle mouse down for dragging
     */
    function handleMouseDown(event: MouseEvent) {
        if (!isImageOrVideo() || zoom <= 1) return;

        isDragging = true;
        startX = event.clientX - translateX;
        startY = event.clientY - translateY;
        event.preventDefault();
    }

    /**
     * Handle mouse move for dragging
     */
    function handleMouseMove(event: MouseEvent) {
        if (!isDragging) return;

        translateX = event.clientX - startX;
        translateY = event.clientY - startY;
    }

    /**
     * Handle mouse up for dragging
     */
    function handleMouseUp() {
        isDragging = false;
    }

    /**
     * Handle keyboard shortcuts
     */
    function handleKeydown(event: KeyboardEvent) {
        if (!isOpen) return;

        switch (event.key) {
            case "Escape":
                close();
                break;
            case "ArrowLeft":
                previous();
                break;
            case "ArrowRight":
                next();
                break;
            case "+":
            case "=":
                zoomIn();
                break;
            case "-":
                zoomOut();
                break;
            case "0":
                resetZoom();
                break;
            case "r":
            case "R":
                rotate();
                break;
            case "f":
            case "F":
                toggleFullscreen();
                break;
            case "d":
            case "D":
                download();
                break;
        }
    }

    /**
     * Check if current item is image or video
     */
    function isImageOrVideo(): boolean {
        return currentItem?.type === "image" || currentItem?.type === "video";
    }

    /**
     * Get document viewer URL
     */
    function getDocumentViewerUrl(src: string, type: string): string {
        const encodedUrl = encodeURIComponent(src);

        // For PDF, use direct embed
        if (type === "pdf") {
            return src;
        }

        // For Office documents, use Google Docs Viewer
        // Alternative: Microsoft Office Online Viewer
        return `https://docs.google.com/viewer?url=${encodedUrl}&embedded=true`;
    }

    /**
     * Handle backdrop click
     */
    function handleBackdropClick(event: MouseEvent) {
        if (event.target === event.currentTarget) {
            close();
        }
    }

    /**
     * Handle content click (prevent event bubbling)
     */
    function handleContentClick(event: MouseEvent) {
        event.stopPropagation();
    }

    /**
     * Handle content keydown (prevent event bubbling)
     */
    function handleContentKeydown(event: KeyboardEvent) {
        event.stopPropagation();
    }

    /**
     * Get transform style
     */
    function getTransformStyle(): string {
        return `scale(${zoom}) rotate(${rotation}deg) translate(${translateX / zoom}px, ${translateY / zoom}px)`;
    }

    /**
     * Handle loading state
     */
    function handleLoadStart() {
        isLoading = true;
    }

    function handleLoadEnd() {
        isLoading = false;
    }

    // Initialize index when opening
    $effect(() => {
        if (isOpen) {
            currentIndex = initialIndex;
            resetTransform();
        }
    });

    // Prevent body scroll when open
    $effect(() => {
        if (isOpen) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    });

    // Listen for fullscreen changes
    $effect(() => {
        const handleFullscreenChange = () => {
            isFullscreen = !!document.fullscreenElement;
        };

        document.addEventListener("fullscreenchange", handleFullscreenChange);

        return () => {
            document.removeEventListener(
                "fullscreenchange",
                handleFullscreenChange,
            );
            document.body.style.overflow = "";
        };
    });
</script>

<svelte:window onkeydown={handleKeydown} />

{#if isOpen && currentItem}
    <div
        bind:this={containerElement}
        class="fixed inset-0 z-50 backdrop-blur-sm bg-black/95"
        transition:fade={{ duration: 300, easing: quintOut }}
        onclick={handleBackdropClick}
        onkeydown={(e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                e.stopPropagation();
                close();
            }
        }}
        role="dialog"
        aria-modal="true"
        aria-label="Media Viewer"
        tabindex="-1"
    >
        <!-- Top Toolbar -->
        <div
            class="flex absolute top-0 right-0 left-0 z-10 justify-between items-center px-4 py-3 to-transparent bg-linear-to-b from-black/80"
            transition:fade={{ duration: 400, delay: 200 }}
        >
            <!-- Title and Counter -->
            <div class="flex items-center space-x-3">
                <div class="text-white">
                    <h3 class="text-lg font-semibold">
                        {currentItem.title || `Item ${currentIndex + 1}`}
                    </h3>
                    <p class="text-sm text-gray-300">
                        {currentIndex + 1} / {normalizedItems.length}
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
                {#if enableDownload}
                    <button
                        type="button"
                        onclick={download}
                        class="p-2 text-white rounded-lg transition-colors hover:bg-white/20"
                        title="Download (D)"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                    </button>
                {/if}

                {#if enableFullscreen}
                    <button
                        type="button"
                        onclick={toggleFullscreen}
                        class="p-2 text-white rounded-lg transition-colors hover:bg-white/20"
                        title="Fullscreen (F)"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            {#if isFullscreen}
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            {:else}
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
                                />
                            {/if}
                        </svg>
                    </button>
                {/if}

                <button
                    type="button"
                    onclick={close}
                    class="p-2 text-white rounded-lg transition-colors hover:bg-white/20"
                    title="Close (ESC)"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div
            class="flex absolute inset-0 justify-center items-center pt-16 pb-20"
            transition:scale={{
                duration: 400,
                start: 0.85,
                opacity: 0,
                easing: quintOut,
            }}
        >
            <!-- Loading Spinner -->
            {#if isLoading}
                <div
                    class="flex absolute inset-0 z-10 justify-center items-center"
                >
                    <div
                        class="w-16 h-16 rounded-full border-4 animate-spin border-white/20 border-t-white"
                    ></div>
                </div>
            {/if}

            <!-- Content Container -->
            <div
                bind:this={contentElement}
                class="flex overflow-hidden relative justify-center items-center w-full h-full"
                onwheel={handleWheel}
                onmousedown={handleMouseDown}
                onmousemove={handleMouseMove}
                onmouseup={handleMouseUp}
                onmouseleave={handleMouseUp}
                onclick={handleContentClick}
                onkeydown={handleContentKeydown}
                role="presentation"
                style="cursor: {isDragging
                    ? 'grabbing'
                    : zoom > 1
                      ? 'grab'
                      : 'default'}"
            >
                {#if currentItem.type === "image"}
                    <img
                        src={currentItem.src}
                        alt={currentItem.title || ""}
                        class="object-contain max-w-full max-h-full transition-transform duration-200 select-none"
                        style="transform: {getTransformStyle()}"
                        onload={handleLoadEnd}
                        onloadstart={handleLoadStart}
                        draggable="false"
                    />
                {:else if currentItem.type === "video"}
                    <video
                        src={currentItem.src}
                        controls
                        class="object-contain max-w-full max-h-full"
                        style="transform: scale({zoom}) rotate({rotation}deg)"
                        onloadstart={handleLoadStart}
                        onloadeddata={handleLoadEnd}
                    >
                        <track kind="captions" />
                    </video>
                {:else if currentItem.type === "pdf"}
                    <iframe
                        src={getDocumentViewerUrl(
                            currentItem.src,
                            currentItem.type,
                        )}
                        class="w-full h-full border-0"
                        title={currentItem.title || "PDF Document"}
                        onload={handleLoadEnd}
                    ></iframe>
                {:else if ["doc", "docx", "xls", "xlsx", "ppt", "pptx"].includes(currentItem.type)}
                    <iframe
                        src={getDocumentViewerUrl(
                            currentItem.src,
                            currentItem.type,
                        )}
                        class="w-full h-full bg-white border-0"
                        title={currentItem.title || "Document"}
                        onload={handleLoadEnd}
                    ></iframe>
                {/if}
            </div>

            <!-- Navigation Arrows -->
            {#if normalizedItems.length > 1}
                <button
                    type="button"
                    onclick={previous}
                    disabled={!hasPrevious}
                    class="absolute left-4 p-3 text-white rounded-full transition-all bg-black/50 hover:bg-black/70 disabled:opacity-30 disabled:cursor-not-allowed"
                    title="Previous (←)"
                    transition:fade={{ duration: 400, delay: 250 }}
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>

                <button
                    type="button"
                    onclick={next}
                    disabled={!hasNext}
                    class="absolute right-4 p-3 text-white rounded-full transition-all bg-black/50 hover:bg-black/70 disabled:opacity-30 disabled:cursor-not-allowed"
                    title="Next (→)"
                    transition:fade={{ duration: 400, delay: 250 }}
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </button>
            {/if}
        </div>

        <!-- Bottom Toolbar -->
        {#if isImageOrVideo()}
            <div
                class="flex absolute right-0 bottom-0 left-0 z-10 flex-col items-center px-4 py-3 space-y-3 to-transparent bg-linear-to-t from-black/80"
                transition:fade={{ duration: 400, delay: 200 }}
            >
                <!-- Zoom Controls -->
                <div
                    class="flex items-center px-4 py-2 space-x-2 rounded-lg bg-black/50"
                >
                    {#if enableZoom}
                        <button
                            type="button"
                            onclick={zoomOut}
                            disabled={zoom <= minZoom}
                            class="p-2 text-white rounded transition-colors hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed"
                            title="Zoom Out (-)"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"
                                />
                            </svg>
                        </button>

                        <div
                            class="text-sm font-medium text-center text-white min-w-15"
                        >
                            {Math.round(zoom * 100)}%
                        </div>

                        <button
                            type="button"
                            onclick={zoomIn}
                            disabled={zoom >= maxZoom}
                            class="p-2 text-white rounded transition-colors hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed"
                            title="Zoom In (+)"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                                />
                            </svg>
                        </button>

                        <button
                            type="button"
                            onclick={resetZoom}
                            class="p-2 text-white rounded transition-colors hover:bg-white/20"
                            title="Reset Zoom (0)"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                        </button>
                    {/if}

                    {#if enableRotate && currentItem.type === "image"}
                        <div class="w-px h-6 bg-white/20"></div>
                        <button
                            type="button"
                            onclick={rotate}
                            class="p-2 text-white rounded transition-colors hover:bg-white/20"
                            title="Rotate (R)"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                        </button>
                    {/if}
                </div>

                <!-- Thumbnails -->
                {#if showThumbnails && items.length > 1}
                    <div
                        class="flex overflow-x-auto items-center px-4 py-2 space-x-2 max-w-full rounded-lg bg-black/50 scrollbar-thin"
                    >
                        {#each normalizedItems as item, index}
                            <button
                                type="button"
                                onclick={() => navigateTo(index)}
                                class="shrink-0 w-16 h-16 rounded overflow-hidden border-2 transition-all {index ===
                                currentIndex
                                    ? 'border-white scale-110'
                                    : 'border-transparent opacity-60 hover:opacity-100 hover:border-white/50'}"
                            >
                                {#if item.type === "image"}
                                    <img
                                        src={item.thumbnail || item.src}
                                        alt={item.title ||
                                            `Thumbnail ${index + 1}`}
                                        class="object-cover w-full h-full"
                                    />
                                {:else if item.type === "video"}
                                    <div
                                        class="flex justify-center items-center w-full h-full bg-gray-800"
                                    >
                                        <svg
                                            class="w-8 h-8 text-white"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                {:else}
                                    <div
                                        class="flex justify-center items-center w-full h-full bg-gray-800"
                                    >
                                        <svg
                                            class="w-8 h-8 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                {/if}
                            </button>
                        {/each}
                    </div>
                {/if}
            </div>
        {/if}

        <!-- Keyboard Shortcuts Help -->
        <div
            class="hidden absolute bottom-4 left-4 px-3 py-2 text-xs rounded-lg text-white/60 bg-black/50 lg:block"
        >
            <div>
                ESC: Close | ←/→: Navigate | +/-: Zoom | 0: Reset | R: Rotate |
                F: Fullscreen | D: Download
            </div>
        </div>
    </div>
{/if}

<style>
    /* Smooth animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    :global(.animate-spin) {
        animation: spin 1s linear infinite;
    }

    /* Custom scrollbar for thumbnails */
    .scrollbar-thin::-webkit-scrollbar {
        height: 4px;
    }

    .scrollbar-thin::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 2px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Prevent text selection while dragging */
    .select-none {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    /* Smooth transitions */
    img,
    video {
        transition: transform 0.2s ease-out;
    }
</style>
