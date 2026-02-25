<script lang="ts">
    interface Props {
        // Basic Props
        id: string;
        name: string;
        label?: string;
        value?: File[] | File | null;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        error?: string;
        class?: string;

        // File Upload Specific
        accept?: string; // e.g., "image/*", ".pdf,.doc,.docx"
        capture?: "user" | "environment" | boolean;
        multiple?: boolean;
        maxSize?: number; // in bytes (e.g., 5 * 1024 * 1024 for 5MB)
        maxFiles?: number;
        showPreview?: boolean;
        showFileList?: boolean;
        allowDragDrop?: boolean;

        // Display Options
        uploadIcon?: string;
        uploadText?: string;
        uploadSubtext?: string;
        variant?: "box" | "button" | "minimal";

        // Event Handlers
        onchange?: (files: File[]) => void;
        onremove?: (file: File, index: number) => void;
        onerror?: (error: string) => void;
        onupload?: (file: File) => Promise<void>; // Async upload handler
    }

    let {
        id,
        name,
        label,
        value = $bindable(null),
        placeholder = "Choose files or drag and drop",
        required = false,
        disabled = false,
        error,
        class: className = "",

        accept,
        capture,
        multiple = false,
        maxSize = 10 * 1024 * 1024, // 10MB default
        maxFiles = 10,
        showPreview = true,
        showFileList = true,
        allowDragDrop = true,

        uploadIcon = "fa-solid fa-cloud-arrow-up",
        uploadText = "Upload a file",
        uploadSubtext,
        variant = "box",

        onchange,
        onremove,
        onerror,
        onupload,
    }: Props = $props();

    // Import components
    import Button from "./Button.svelte";
    import MediaViewer from "./MediaViewer.svelte";

    // Internal State
    let fileInput: HTMLInputElement;
    let isDragging = $state(false);
    let files = $state<File[]>([]);
    let previews = $state<{ [key: string]: string }>({});
    let uploadingFiles = $state<Set<string>>(new Set());
    let uploadedFiles = $state<Set<string>>(new Set());
    let uploadErrors = $state<{ [key: string]: string }>({});

    // Media Viewer State
    let viewerOpen = $state(false);
    let viewerItems = $state<any[]>([]);
    let viewerInitialIndex = $state(0);

    function filesEqual(a: File[], b: File[]): boolean {
        if (a.length !== b.length) return false;
        for (let i = 0; i < a.length; i++) {
            if (a[i] !== b[i]) return false;
        }
        return true;
    }

    // Initialize files from value
    $effect(() => {
        if (value) {
            if (Array.isArray(value)) {
                if (!filesEqual(files, value)) {
                    files = value;
                }
            } else if (typeof File !== "undefined" && value instanceof File) {
                if (!(files.length === 1 && files[0] === value)) {
                    files = [value];
                }
            }
        } else if (files.length !== 0) {
            files = [];
        }
    });

    /**
     * Update the bound value from internal files array
     */
    function updateValueFromFiles() {
        if (multiple) {
            value = files;
        } else {
            value = (files.length > 0 ? files[0] : null) as File | null;
        }
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes: number): string {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (
            Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i]
        );
    }

    /**
     * Get file extension
     */
    function getFileExtension(filename: string): string {
        return filename.slice(((filename.lastIndexOf(".") - 1) >>> 0) + 2);
    }

    /**
     * Get file type for MediaViewer
     */
    function getMediaType(
        file: File,
    ):
        | "image"
        | "video"
        | "pdf"
        | "doc"
        | "docx"
        | "xls"
        | "xlsx"
        | "ppt"
        | "pptx"
        | null {
        const ext = getFileExtension(file.name).toLowerCase();
        const type = file.type;

        if (type.startsWith("image/")) return "image";
        if (type.startsWith("video/")) return "video";
        if (type === "application/pdf" || ext === "pdf") return "pdf";
        if (type === "application/msword" || ext === "doc") return "doc";
        if (
            type ===
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ||
            ext === "docx"
        )
            return "docx";
        if (type === "application/vnd.ms-excel" || ext === "xls") return "xls";
        if (
            type ===
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
            ext === "xlsx"
        )
            return "xlsx";
        if (type === "application/vnd.ms-powerpoint" || ext === "ppt")
            return "ppt";
        if (
            type ===
                "application/vnd.openxmlformats-officedocument.presentationml.presentation" ||
            ext === "pptx"
        )
            return "pptx";

        return null;
    }

    /**
     * Get file type icon (Font Awesome)
     */
    function getFileIcon(file: File): string {
        const ext = getFileExtension(file.name).toLowerCase();
        const type = file.type;

        // Images
        if (type.startsWith("image/")) {
            return "fa-solid fa-file-image";
        }

        // PDFs
        if (type === "application/pdf" || ext === "pdf") {
            return "fa-solid fa-file-pdf";
        }

        // Word Documents
        if (
            type === "application/msword" ||
            type ===
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ||
            ext === "doc" ||
            ext === "docx"
        ) {
            return "fa-solid fa-file-word";
        }

        // Excel
        if (
            type === "application/vnd.ms-excel" ||
            type ===
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
            ext === "xls" ||
            ext === "xlsx"
        ) {
            return "fa-solid fa-file-excel";
        }

        // PowerPoint
        if (
            type === "application/vnd.ms-powerpoint" ||
            type ===
                "application/vnd.openxmlformats-officedocument.presentationml.presentation" ||
            ext === "ppt" ||
            ext === "pptx"
        ) {
            return "fa-solid fa-file-powerpoint";
        }

        // Archives
        if (
            ext === "zip" ||
            ext === "rar" ||
            ext === "7z" ||
            ext === "tar" ||
            ext === "gz"
        ) {
            return "fa-solid fa-file-zipper";
        }

        // Videos
        if (type.startsWith("video/")) {
            return "fa-solid fa-file-video";
        }

        // Audio
        if (type.startsWith("audio/")) {
            return "fa-solid fa-file-audio";
        }

        // Code files
        if (
            ext === "js" ||
            ext === "ts" ||
            ext === "jsx" ||
            ext === "tsx" ||
            ext === "html" ||
            ext === "css" ||
            ext === "json" ||
            ext === "xml" ||
            ext === "php" ||
            ext === "py" ||
            ext === "java" ||
            ext === "cpp" ||
            ext === "c" ||
            ext === "cs"
        ) {
            return "fa-solid fa-file-code";
        }

        // Default file icon
        return "fa-solid fa-file";
    }

    /**
     * Validate file
     */
    function validateFile(file: File): string | null {
        // Check file size
        if (maxSize && file.size > maxSize) {
            return `File "${file.name}" exceeds maximum size of ${formatFileSize(maxSize)}`;
        }

        // Check file type if accept is specified
        if (accept) {
            const acceptedTypes = accept.split(",").map((t) => t.trim());
            const fileExt = "." + getFileExtension(file.name);
            const fileType = file.type;

            const isAccepted = acceptedTypes.some((acceptedType) => {
                if (acceptedType.startsWith(".")) {
                    return fileExt.toLowerCase() === acceptedType.toLowerCase();
                }
                if (acceptedType.endsWith("/*")) {
                    const baseType = acceptedType.split("/")[0];
                    return fileType.startsWith(baseType + "/");
                }
                return fileType === acceptedType;
            });

            if (!isAccepted) {
                return `File type "${fileExt}" is not accepted`;
            }
        }

        return null;
    }

    /**
     * Create preview URL for file
     */
    function createPreview(file: File) {
        if (file.type.startsWith("image/") && showPreview) {
            const url = URL.createObjectURL(file);
            previews[file.name] = url;
        }
    }

    /**
     * Cleanup preview URL
     */
    function cleanupPreview(filename: string) {
        if (previews[filename]) {
            URL.revokeObjectURL(previews[filename]);
            delete previews[filename];
        }
    }

    /**
     * Upload single file
     */
    async function uploadFile(file: File) {
        const fileKey = file.name;

        // Mark as uploading
        uploadingFiles.add(fileKey);
        uploadingFiles = uploadingFiles;
        delete uploadErrors[fileKey];

        try {
            // If there's an upload handler, call it
            if (onupload) {
                await onupload(file);
            } else {
                // Simulate upload delay
                await new Promise((resolve) => setTimeout(resolve, 1000));
            }

            // Mark as uploaded
            uploadedFiles.add(fileKey);
            uploadedFiles = uploadedFiles;
        } catch (err: any) {
            // Handle upload error
            uploadErrors[fileKey] = err.message || "Upload failed";
            if (onerror) {
                onerror(uploadErrors[fileKey] ?? "Upload failed");
            }
        } finally {
            // Remove from uploading
            uploadingFiles.delete(fileKey);
            uploadingFiles = uploadingFiles;
        }
    }

    /**
     * Handle file selection
     */
    async function handleFileSelect(fileList: FileList | null) {
        if (!fileList || fileList.length === 0) return;

        const newFiles: File[] = [];
        const errors: string[] = [];

        // Convert FileList to array
        const selectedFiles = Array.from(fileList);

        // Check max files limit
        if (multiple && maxFiles) {
            const totalFiles = files.length + selectedFiles.length;
            if (totalFiles > maxFiles) {
                const errorMsg = `Cannot upload more than ${maxFiles} files`;
                errors.push(errorMsg);
                if (onerror) onerror(errorMsg);
                return;
            }
        }

        // Validate and collect files
        for (const file of selectedFiles) {
            const validationError = validateFile(file);

            if (validationError) {
                errors.push(validationError);
            } else {
                newFiles.push(file);
                createPreview(file);
            }
        }

        // Show validation errors
        if (errors.length > 0) {
            const errorMsg = errors.join("; ");
            if (onerror) onerror(errorMsg);
        }

        // Update files
        if (newFiles.length > 0) {
            if (multiple) {
                files = [...files, ...newFiles];
            } else {
                // Clean up old preview
                if (files.length > 0) {
                    const first = files[0];
                    if (first) cleanupPreview(first.name);
                }
                const nf = newFiles[0];
                files = nf ? [nf] : [];
            }

            // Sync with bounded value
            updateValueFromFiles();

            // Trigger onchange event
            if (onchange) {
                onchange(files);
            }

            // Auto-upload only when an upload handler is provided
            if (onupload) {
                for (const file of newFiles) {
                    uploadFile(file);
                }
            }
        }

        // Reset file input
        if (fileInput) {
            fileInput.value = "";
        }
    }

    /**
     * Handle file removal
     */
    function handleRemoveFile(file: File, index: number) {
        // Clean up preview
        cleanupPreview(file.name);

        // Remove from uploaded/uploading sets
        uploadingFiles.delete(file.name);
        uploadedFiles.delete(file.name);
        delete uploadErrors[file.name];

        // Remove from files array
        files = files.filter((_, i) => i !== index);

        // Sync with bounded value
        updateValueFromFiles();

        // Trigger onremove event
        if (onremove) {
            onremove(file, index);
        }
    }

    /**
     * Open file in MediaViewer
     */
    function openViewer(index: number) {
        const items = files
            .map((file) => {
                const mediaType = getMediaType(file);
                if (!mediaType) return null;

                const url = URL.createObjectURL(file);
                return {
                    src: url,
                    type: mediaType,
                    title: file.name,
                    thumbnail: previews[file.name] || url,
                };
            })
            .filter((item) => item !== null);

        if (items.length === 0) return;

        // Find the actual index in filtered items
        let actualIndex = 0;
        let count = 0;
        for (let i = 0; i <= index; i++) {
            const f = files[i];
            if (f && getMediaType(f)) {
                if (i === index) {
                    actualIndex = count;
                }
                count++;
            }
        }

        viewerItems = items;
        viewerInitialIndex = actualIndex;
        viewerOpen = true;
    }

    /**
     * Check if file can be previewed
     */
    function canPreview(file: File): boolean {
        return getMediaType(file) !== null;
    }

    /**
     * Handle drag enter
     */
    function handleDragEnter(e: DragEvent) {
        if (!allowDragDrop || disabled) return;
        e.preventDefault();
        isDragging = true;
    }

    /**
     * Handle drag over
     */
    function handleDragOver(e: DragEvent) {
        if (!allowDragDrop || disabled) return;
        e.preventDefault();
        isDragging = true;
    }

    /**
     * Handle drag leave
     */
    function handleDragLeave(e: DragEvent) {
        if (!allowDragDrop || disabled) return;
        e.preventDefault();
        isDragging = false;
    }

    /**
     * Handle drop
     */
    function handleDrop(e: DragEvent) {
        if (!allowDragDrop || disabled) return;
        e.preventDefault();
        isDragging = false;

        const droppedFiles = e.dataTransfer?.files;
        if (droppedFiles) {
            handleFileSelect(droppedFiles);
        }
    }

    /**
     * Trigger file input click
     */
    function triggerFileInput() {
        if (!disabled && fileInput) {
            fileInput.click();
        }
    }

    /**
     * Get container classes
     */
    function getContainerClasses(): string {
        let classes = "relative";

        if (variant === "box") {
            classes += " border-2 border-dashed rounded-lg p-6";

            if (isDragging) {
                classes +=
                    " border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400";
            } else if (error) {
                classes +=
                    " border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-500";
            } else {
                classes +=
                    " border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500";
            }

            if (disabled) {
                classes +=
                    " opacity-50 cursor-not-allowed bg-gray-50 dark:bg-gray-800";
            } else {
                classes += " cursor-pointer transition-colors duration-200";
            }
        }

        return classes;
    }

    /**
     * Get default subtext
     */
    function getDefaultSubtext(): string {
        const parts: string[] = [];

        if (accept) {
            const types = accept.split(",").map((t) => t.trim());
            if (types.length <= 3) {
                parts.push(types.join(", "));
            } else {
                parts.push(
                    "Accepted file types: " +
                        types.slice(0, 2).join(", ") +
                        "...",
                );
            }
        }

        if (maxSize) {
            parts.push(`Max ${formatFileSize(maxSize)}`);
        }

        if (multiple && maxFiles) {
            parts.push(`Up to ${maxFiles} files`);
        }

        return parts.join(" • ");
    }

    /**
     * Get upload status for file
     */
    function getUploadStatus(
        file: File,
    ): "uploading" | "uploaded" | "error" | "idle" {
        const fileKey = file.name;
        if (uploadingFiles.has(fileKey)) return "uploading";
        if (uploadErrors[fileKey]) return "error";
        if (uploadedFiles.has(fileKey)) return "uploaded";
        return "idle";
    }
</script>

<div class="w-full {className}">
    {#if label}
        <label
            for={id}
            class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {label}
            {#if required}
                <span class="text-red-500">*</span>
            {/if}
        </label>
    {/if}

    <!-- Hidden file input -->
    <input
        bind:this={fileInput}
        type="file"
        {id}
        {name}
        {accept}
        {capture}
        {multiple}
        required={required && files.length === 0}
        {disabled}
        onchange={(e) => handleFileSelect(e.currentTarget.files)}
        class="sr-only"
        aria-invalid={!!error}
        aria-describedby={error ? `${id}-error` : undefined}
    />

    {#if variant === "box"}
        <!-- Box Variant -->
        <div
            class={getContainerClasses()}
            onclick={triggerFileInput}
            ondragenter={handleDragEnter}
            ondragover={handleDragOver}
            ondragleave={handleDragLeave}
            ondrop={handleDrop}
            role="button"
            tabindex={disabled ? -1 : 0}
            onkeydown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    triggerFileInput();
                }
            }}
        >
            <div class="flex flex-col justify-center items-center text-center">
                <div
                    class="mb-3 {disabled
                        ? 'text-gray-300 dark:text-gray-600'
                        : isDragging
                          ? 'text-blue-500 dark:text-blue-400'
                          : 'text-gray-400 dark:text-gray-500'}"
                >
                    <i class="{uploadIcon} text-5xl"></i>
                </div>

                <p
                    class="mb-1 text-sm font-medium {disabled
                        ? 'text-gray-400 dark:text-gray-600'
                        : 'text-gray-700 dark:text-gray-300'}"
                >
                    {uploadText}
                </p>

                {#if uploadSubtext || getDefaultSubtext()}
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {uploadSubtext || getDefaultSubtext()}
                    </p>
                {/if}

                {#if placeholder && files.length === 0}
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        {placeholder}
                    </p>
                {/if}
            </div>
        </div>
    {:else if variant === "button"}
        <!-- Button Variant -->
        <Button
            type="button"
            variant="secondary"
            icon={uploadIcon}
            {disabled}
            onclick={triggerFileInput}
            fullWidth={true}
        >
            {uploadText}
        </Button>

        {#if uploadSubtext || getDefaultSubtext()}
            <p
                class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400"
            >
                {uploadSubtext || getDefaultSubtext()}
            </p>
        {/if}
    {:else}
        <!-- Minimal Variant -->
        <button
            type="button"
            onclick={triggerFileInput}
            {disabled}
            class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            {uploadText}
        </button>

        {#if uploadSubtext || getDefaultSubtext()}
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {uploadSubtext || getDefaultSubtext()}
            </p>
        {/if}
    {/if}

    <!-- File List -->
    {#if showFileList && files.length > 0}
        <div class="mt-4 space-y-2">
            {#each files as file, index (file.name + index)}
                {@const status = getUploadStatus(file)}
                <div
                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border dark:bg-gray-800 transition-all {status ===
                    'error'
                        ? 'border-red-300 dark:border-red-700'
                        : 'border-gray-200 dark:border-gray-700'}"
                >
                    <div class="flex flex-1 items-center min-w-0">
                        <!-- Preview or Icon with Click Handler -->
                        <button
                            type="button"
                            onclick={() =>
                                canPreview(file) && openViewer(index)}
                            disabled={!canPreview(file)}
                            class="shrink-0 {canPreview(file)
                                ? 'cursor-pointer hover:opacity-80'
                                : 'cursor-default'} transition-opacity"
                        >
                            {#if previews[file.name]}
                                <div class="relative">
                                    <img
                                        src={previews[file.name]}
                                        alt={file.name}
                                        class="object-cover w-12 h-12 rounded"
                                    />
                                    {#if canPreview(file)}
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-black/0 hover:bg-black/30 rounded transition-colors"
                                        >
                                            <i
                                                class="fa-solid fa-eye text-white opacity-0 hover:opacity-100 transition-opacity"
                                            ></i>
                                        </div>
                                    {/if}
                                </div>
                            {:else}
                                <div
                                    class="text-gray-400 dark:text-gray-500 relative"
                                >
                                    <i class="{getFileIcon(file)} text-3xl"></i>
                                    {#if canPreview(file)}
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <i
                                                class="fa-solid fa-eye text-xs text-blue-500 opacity-0 hover:opacity-100 transition-opacity"
                                            ></i>
                                        </div>
                                    {/if}
                                </div>
                            {/if}
                        </button>

                        <!-- File Info -->
                        <div class="flex-1 ml-3 min-w-0">
                            <p
                                class="text-sm font-medium text-gray-700 truncate dark:text-gray-300"
                            >
                                {file.name}
                            </p>
                            <div class="flex items-center gap-2">
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {formatFileSize(file.size)}
                                </p>

                                <!-- Upload Status -->
                                {#if status === "uploading"}
                                    <span
                                        class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400"
                                    >
                                        <i class="fa-solid fa-spinner fa-spin"
                                        ></i>
                                        Uploading...
                                    </span>
                                {:else if status === "uploaded"}
                                    <span
                                        class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400"
                                    >
                                        <i class="fa-solid fa-check"></i>
                                        Uploaded
                                    </span>
                                {:else if status === "error"}
                                    <span
                                        class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400"
                                        title={uploadErrors[file.name]}
                                    >
                                        <i
                                            class="fa-solid fa-exclamation-circle"
                                        ></i>
                                        Failed
                                    </span>
                                {/if}
                            </div>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <button
                        type="button"
                        onclick={() => handleRemoveFile(file, index)}
                        disabled={disabled || status === "uploading"}
                        class="ml-3 text-red-500 transition-colors duration-200 shrink-0 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Remove file"
                        title="Remove file"
                    >
                        <i class="text-lg fa-solid fa-xmark"></i>
                    </button>
                </div>
            {/each}
        </div>
    {/if}

    <!-- Error Message -->
    {#if error}
        <div
            id="{id}-error"
            class="mt-1 text-xs text-red-600 dark:text-red-400"
        >
            {error}
        </div>
    {/if}
</div>

<!-- MediaViewer Component -->
<MediaViewer
    items={viewerItems}
    bind:isOpen={viewerOpen}
    initialIndex={viewerInitialIndex}
    enableDownload={true}
    enableZoom={true}
    enableRotate={true}
    enableFullscreen={true}
/>

<style>
    /* Prevent text selection on drag and drop */
    [role="button"] {
        user-select: none;
        -webkit-user-select: none;
    }

    /* Focus styles for accessibility */
    [role="button"]:focus-visible {
        outline: 2px solid #0060b2;
        outline-offset: 2px;
    }

    /* Smooth transitions */
    img {
        transition: transform 0.2s ease-out;
    }
</style>
