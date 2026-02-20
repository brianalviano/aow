<script lang="ts">
    // Props
    export let id: string = "";
    export let name: string = "";
    export let label: string = "";
    export let value: string = "";
    export let placeholder: string = "";
    export let required: boolean = false;
    export let disabled: boolean = false;
    export let readonly: boolean = false;
    export let rows: number = 3;
    export let cols: number | null = null;
    export let maxlength: number | null = null;
    export let minlength: number | null = null;
    export let resize: "none" | "both" | "horizontal" | "vertical" = "vertical";
    export let autocomplete: string = "off";
    export let spellcheck: boolean = true;
    export let wrap: "hard" | "soft" | "off" = "soft";
    export let error: string | undefined = undefined;

    // Event callbacks
    export let oninput: ((event: Event) => void) | undefined = undefined;
    export let onfocus: ((event: FocusEvent) => void) | undefined = undefined;
    export let onblur: ((event: FocusEvent) => void) | undefined = undefined;
    export let onkeypress: ((event: KeyboardEvent) => void) | undefined =
        undefined;
    export let onkeydown: ((event: KeyboardEvent) => void) | undefined =
        undefined;
    export let onkeyup: ((event: KeyboardEvent) => void) | undefined =
        undefined;

    // Internal state
    let characterCount = 0;

    // Update character count
    $: characterCount = value ? value.length : 0;

    // Handle input events
    function handleInput(event: Event): void {
        const target = event.target as HTMLTextAreaElement;
        value = target.value;
        oninput?.(event);
    }

    function handleFocus(event: FocusEvent): void {
        onfocus?.(event);
    }

    function handleBlur(event: FocusEvent): void {
        onblur?.(event);
    }

    function handleKeypress(event: KeyboardEvent): void {
        onkeypress?.(event);
    }

    function handleKeydown(event: KeyboardEvent): void {
        onkeydown?.(event);
    }

    function handleKeyup(event: KeyboardEvent): void {
        onkeyup?.(event);
    }

    // Auto-resize functionality (removed as 'auto' is not a valid CSS resize value)

    // Resize classes
    $: resizeClass =
        {
            none: "resize-none",
            both: "resize",
            horizontal: "resize-x",
            vertical: "resize-y",
            auto: "resize-none",
        }[resize] || "resize-y";
</script>

<div class="w-full">
    {#if label}
        <label
            for={id}
            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {label}
            {#if required}
                <span class="text-red-500">*</span>
            {/if}
        </label>
    {/if}

    <div class="relative">
        <textarea
            {id}
            {name}
            bind:value
            {placeholder}
            {required}
            {disabled}
            {readonly}
            {rows}
            {cols}
            {maxlength}
            {minlength}
            autocomplete={autocomplete === "off" ? "off" : undefined}
            {spellcheck}
            wrap={wrap === "off" ? undefined : wrap}
            on:input={handleInput}
            on:focus={handleFocus}
            on:blur={handleBlur}
            on:keypress={handleKeypress}
            on:keydown={handleKeydown}
            on:keyup={handleKeyup}
            class="w-full px-3 py-2 border border-gray-200 dark:border-[#212121] rounded-md placeholder-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#0060B2] focus:border-transparent focus:outline-none bg-white dark:bg-[#0a0a0a] text-gray-900 dark:text-white disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-900 dark:disabled:text-gray-400 readonly:bg-gray-50 readonly:text-gray-500 dark:readonly:bg-gray-900 dark:readonly:text-gray-400 text-sm {resizeClass} {error
                ? 'border-red-500 focus:ring-red-500'
                : ''}"
            aria-invalid={!!error}
            aria-describedby={error && id ? `${id}-error` : undefined}
        ></textarea>
    </div>

    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}

    {#if maxlength}
        <div class="flex justify-end mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {characterCount}/{maxlength}
            </span>
        </div>
    {/if}
</div>

<style>
    /* Custom styles for textarea */
    textarea {
        font-family: inherit;
        line-height: 1.5;
    }

    /* Auto-resize specific styles */
    textarea.resize-none {
        overflow-y: hidden;
    }

    /* Readonly styles */
    textarea:read-only {
        background-color: #f9fafb;
        color: #6b7280;
        cursor: default;
    }

    /* Disabled styles */
    textarea:disabled {
        opacity: 0.6;
    }
</style>
