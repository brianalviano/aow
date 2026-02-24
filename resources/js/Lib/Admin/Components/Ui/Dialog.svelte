<script lang="ts">
    // Import form components
    import TextInput from "./TextInput.svelte";
    import TextArea from "./TextArea.svelte";
    import Select from "./Select.svelte";
    import DateInput from "./DateInput.svelte";
    import Checkbox from "./Checkbox.svelte";
    import Button from "./Button.svelte";

    interface FormField {
        id: string;
        name: string;
        type:
            | "text"
            | "email"
            | "password"
            | "textarea"
            | "select"
            | "number"
            | "date"
            | "checkbox";
        label: string;
        placeholder?: string;
        required?: boolean;
        options?: Array<{ value: string | number; label: string }>;
        value?: any;
        icon?: string;
        customIcon?: boolean;
        maxlength?: number;
        rows?: number;
        min?: string | number;
        max?: string | number;
        disabled?: boolean;
    }

    interface Props {
        isOpen: boolean;
        type?: "info" | "warning" | "danger" | "success";
        title: string;
        message: string;
        children?: any;
        confirmText?: string;
        cancelText?: string;
        showCancel?: boolean;
        showDefaultActions?: boolean;
        showCloseButton?: boolean;
        backdropClosable?: boolean;
        form_fields?: FormField[];
        form_data?: Record<string, any>;
        loading?: boolean;
        onConfirm?: (formData?: Record<string, any>) => void | Promise<void>;
        onCancel?: () => void;
        onClose?: () => void;
    }

    let {
        isOpen = $bindable(),
        type = "info",
        title,
        message,
        children,
        confirmText = "Ya, Saya Yakin",
        cancelText = "Batal",
        showCancel = true,
        showDefaultActions = true,
        showCloseButton = true,
        backdropClosable = true,
        form_fields = [],
        form_data = $bindable({}),
        loading = false,
        onConfirm,
        onCancel,
        onClose,
    }: Props = $props();

    let dialogElement = $state<HTMLDialogElement>();
    let internal_form_data = $state<Record<string, any>>({});
    let effectiveShowCloseButton = $state<boolean>(true);
    let effectiveBackdropClosable = $state<boolean>(true);

    $effect(() => {
        effectiveShowCloseButton = showCancel ? showCloseButton : false;
        effectiveBackdropClosable = showCancel ? backdropClosable : false;
    });

    /**
     * Initialize form data from provided form_data or default values
     */
    function initializeFormData() {
        const initial_data: Record<string, any> = {};

        // Set from provided form_data first
        if (form_data) {
            Object.assign(initial_data, form_data);
        }

        // Set default values for fields that don't have values
        form_fields.forEach((field) => {
            if (!(field.name in initial_data)) {
                switch (field.type) {
                    case "checkbox":
                        initial_data[field.name] = field.value || false;
                        break;
                    case "number":
                        initial_data[field.name] = field.value || "";
                        break;
                    default:
                        initial_data[field.name] = field.value || "";
                }
            }
        });

        internal_form_data = initial_data;
    }

    /**
     * Get icon and colors based on dialog type
     */
    function getTypeConfig() {
        const configs = {
            info: {
                icon: "M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
                iconColor: "text-blue-500",
                iconBg: "bg-blue-100",
                titleColor: "text-blue-900",
            },
            warning: {
                icon: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z",
                iconColor: "text-yellow-500",
                iconBg: "bg-yellow-100",
                titleColor: "text-yellow-900",
            },
            danger: {
                icon: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z",
                iconColor: "text-red-500",
                iconBg: "bg-red-100",
                titleColor: "text-red-900",
            },
            success: {
                icon: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",
                iconColor: "text-green-500",
                iconBg: "bg-green-100",
                titleColor: "text-green-900",
            },
        };

        return configs[type] || configs.info;
    }

    /**
     * Get confirm button variant based on type
     */
    function getConfirmButtonVariant():
        | "primary"
        | "warning"
        | "danger"
        | "success" {
        const variants = {
            info: "primary",
            warning: "warning",
            danger: "danger",
            success: "success",
        } as const;

        return variants[type] || "primary";
    }

    /**
     * Handle confirm action
     */
    async function handleConfirm() {
        if (onConfirm) {
            // Update external form_data
            if (form_fields.length > 0) {
                form_data = { ...internal_form_data };
                await onConfirm(internal_form_data);
            } else {
                await onConfirm();
            }
        }
        closeDialog();
    }

    /**
     * Handle cancel action
     */
    function handleCancel() {
        if (onCancel) {
            onCancel();
        }
        closeDialog();
    }

    /**
     * Close dialog
     */
    function closeDialog() {
        isOpen = false;
        if (onClose) {
            onClose();
        }
    }

    /**
     * Handle backdrop click
     */
    function handleBackdropClick(event: MouseEvent) {
        // Only close if clicking the backdrop (dialog element itself)
        const rect = (
            event.currentTarget as HTMLElement
        ).getBoundingClientRect();
        const isInDialog =
            event.clientX >= rect.left &&
            event.clientX <= rect.right &&
            event.clientY >= rect.top &&
            event.clientY <= rect.bottom;

        if (
            effectiveBackdropClosable &&
            isInDialog &&
            event.target === event.currentTarget
        ) {
            closeDialog();
        }
    }

    /**
     * Handle escape key
     */
    function handleKeydown(event: KeyboardEvent) {
        if (event.key === "Escape" && isOpen && showCancel) {
            closeDialog();
        }
    }

    /**
     * Validate required fields
     */
    function isFormValid(): boolean {
        if (form_fields.length === 0) return true;

        return form_fields
            .filter((field) => field.required)
            .every((field) => {
                const value = internal_form_data[field.name];
                if (field.type === "checkbox") {
                    return value === true;
                }
                return value !== undefined && value !== null && value !== "";
            });
    }

    // Initialize form data when dialog opens or form_fields change
    $effect(() => {
        if (isOpen && form_fields.length > 0) {
            initializeFormData();
        }
    });

    // Manage dialog state
    $effect(() => {
        if (isOpen && dialogElement) {
            dialogElement.showModal();
            document.body.style.overflow = "hidden";
        } else if (dialogElement) {
            dialogElement.close();
            document.body.style.overflow = "";
        }
    });

    // Cleanup on unmount
    $effect(() => {
        return () => {
            document.body.style.overflow = "";
        };
    });
</script>

<svelte:window onkeydown={handleKeydown} />

<!-- Dialog Element -->
{#if isOpen}
    <dialog
        bind:this={dialogElement}
        class="flex fixed inset-0 z-50 justify-center items-center p-4 backdrop-blur-sm bg-black/50"
        onclick={handleBackdropClick}
    >
        <div
            class="relative bg-white dark:bg-[#0f0f0f] rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] transform transition-all duration-300 scale-100"
            role="dialog"
            aria-labelledby="dialog-title"
            aria-describedby="dialog-message"
        >
            <!-- Header with Icon -->
            <div class="flex items-start p-6 pb-4 space-x-4">
                <div class="shrink-0">
                    <div
                        class="w-12 h-12 rounded-full dark:bg-[#212121] {getTypeConfig()
                            .iconBg} flex items-center justify-center"
                    >
                        <svg
                            class="w-6 h-6 {getTypeConfig().iconColor}"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d={getTypeConfig().icon}
                            />
                        </svg>
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <h3
                        id="dialog-title"
                        class="text-lg font-semibold {getTypeConfig()
                            .titleColor} dark:text-white mb-2"
                    >
                        {title}
                    </h3>
                    <p
                        id="dialog-message"
                        class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"
                    >
                        {message}
                    </p>
                </div>

                <!-- Close Button -->
                {#if effectiveShowCloseButton}
                    <button
                        type="button"
                        onclick={closeDialog}
                        class="flex justify-center items-center w-8 h-8 rounded-full transition-colors duration-200 shrink-0 hover:bg-gray-100 dark:hover:bg-gray-800"
                        aria-label="Tutup dialog"
                    >
                        <svg
                            class="w-5 h-5 text-gray-400 dark:text-gray-500"
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
                {/if}
            </div>

            <!-- Form Fields (if any) -->
            {#if form_fields.length > 0}
                <div class="px-6 pb-4 space-y-4">
                    {#each form_fields as field}
                        <div>
                            {#if field.type === "text" || field.type === "email" || field.type === "password"}
                                <TextInput
                                    id={field.id}
                                    name={field.name}
                                    type={field.type}
                                    label={field.label}
                                    placeholder={field.placeholder ?? ""}
                                    required={field.required ?? false}
                                    disabled={field.disabled ?? false}
                                    {...field.maxlength !== undefined
                                        ? { maxlength: field.maxlength }
                                        : {}}
                                    {...field.icon !== undefined
                                        ? { icon: field.icon }
                                        : {}}
                                    bind:value={internal_form_data[field.name]}
                                />
                            {:else if field.type === "textarea"}
                                <TextArea
                                    id={field.id}
                                    name={field.name}
                                    label={field.label}
                                    placeholder={field.placeholder ?? ""}
                                    required={field.required ?? false}
                                    disabled={field.disabled ?? false}
                                    rows={field.rows ?? 3}
                                    maxlength={field.maxlength ?? null}
                                    bind:value={internal_form_data[field.name]}
                                />
                            {:else if field.type === "select"}
                                <Select
                                    id={field.id}
                                    name={field.name}
                                    label={field.label}
                                    placeholder={field.placeholder ??
                                        "Pilih opsi..."}
                                    required={field.required ?? false}
                                    disabled={field.disabled ?? false}
                                    options={field.options ?? []}
                                    {...field.icon !== undefined
                                        ? { icon: field.icon }
                                        : {}}
                                    customIcon={field.customIcon ?? false}
                                    bind:value={internal_form_data[field.name]}
                                />
                            {:else if field.type === "number"}
                                <TextInput
                                    id={field.id}
                                    name={field.name}
                                    type="number"
                                    label={field.label}
                                    placeholder={field.placeholder ?? ""}
                                    required={field.required ?? false}
                                    disabled={field.disabled ?? false}
                                    bind:value={internal_form_data[field.name]}
                                />
                            {:else if field.type === "date"}
                                <DateInput
                                    id={field.id}
                                    name={field.name}
                                    label={field.label}
                                    placeholder={field.placeholder ??
                                        "Pilih tanggal"}
                                    required={field.required ?? false}
                                    disabled={field.disabled ?? false}
                                    {...field.min !== undefined
                                        ? { min: String(field.min) }
                                        : {}}
                                    {...field.max !== undefined
                                        ? { max: String(field.max) }
                                        : {}}
                                    bind:value={internal_form_data[field.name]}
                                />
                            {:else if field.type === "checkbox"}
                                <Checkbox
                                    id={field.id}
                                    name={field.name}
                                    label={field.label}
                                    disabled={field.disabled ?? false}
                                    bind:checked={
                                        internal_form_data[field.name]
                                    }
                                />
                            {/if}
                        </div>
                    {/each}
                </div>
            {/if}

            {#if children}
                <div class="px-6 pb-4 space-y-4">
                    {@render children()}
                </div>
            {/if}

            <!-- Action Buttons -->
            {#if showDefaultActions}
                <div
                    class="flex items-center justify-end space-x-3 px-6 py-4 bg-gray-50 dark:bg-[#0f0f0f] rounded-b-2xl"
                >
                    {#if showCancel}
                        <Button
                            variant="secondary"
                            size="normal"
                            disabled={loading}
                            onclick={handleCancel}
                        >
                            {cancelText}
                        </Button>
                    {/if}

                    <Button
                        variant={getConfirmButtonVariant()}
                        size="normal"
                        disabled={loading || !isFormValid()}
                        {loading}
                        onclick={handleConfirm}
                    >
                        {confirmText}
                    </Button>
                </div>
            {/if}
        </div>
    </dialog>
{/if}

<style>
    dialog {
        border: none;
        padding: 0;
        background: transparent;
        max-width: none;
        max-height: none;
        width: 100vw;
        height: 100vh;
        margin: 0;
    }

    /* Animation for dialog content */
    dialog[open] > div {
        animation: dialog-appear 0.3s ease-out;
    }

    @keyframes dialog-appear {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* Ensure dialog is scrollable on small screens */
    @media (max-height: 600px) {
        dialog > div {
            max-height: 95vh;
        }
    }
</style>
