<script lang="ts">
    interface Props {
        id?: string;
        name?: string;
        label?: string;
        value?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        readonly?: boolean;
        rows?: number;
        cols?: number | null;
        maxlength?: number | null;
        minlength?: number | null;
        resize?: "none" | "both" | "horizontal" | "vertical";
        autocomplete?: string;
        spellcheck?: boolean;
        wrap?: "hard" | "soft" | "off";
        error?: string | undefined;
        oninput?: (event: Event) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
        onkeypress?: (event: KeyboardEvent) => void;
        onkeydown?: (event: KeyboardEvent) => void;
        onkeyup?: (event: KeyboardEvent) => void;
    }

    let {
        id = "",
        name = "",
        label = "",
        value = $bindable(""),
        placeholder = "",
        required = false,
        disabled = false,
        readonly = false,
        rows = 3,
        cols = null,
        maxlength = null,
        minlength = null,
        resize = "vertical",
        autocomplete = "off",
        spellcheck = true,
        wrap = "soft",
        error = undefined,
        oninput,
        onfocus,
        onblur,
        onkeypress,
        onkeydown,
        onkeyup,
    }: Props = $props();

    const characterCount = $derived(value ? value.length : 0);

    function handleInput(event: Event): void {
        const target = event.target as HTMLTextAreaElement;
        value = target.value;
        oninput?.(event);
    }

    const resizeClass = $derived(
        {
            none: "resize-none",
            both: "resize",
            horizontal: "resize-x",
            vertical: "resize-y",
        }[resize] || "resize-y",
    );
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
            oninput={handleInput}
            onfocus={onfocus}
            onblur={onblur}
            onkeypress={onkeypress}
            onkeydown={onkeydown}
            onkeyup={onkeyup}
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
    textarea {
        font-family: inherit;
        line-height: 1.5;
    }

    textarea.resize-none {
        overflow-y: hidden;
    }

    textarea:read-only {
        background-color: #f9fafb;
        color: #6b7280;
        cursor: default;
    }

    :global(html.dark) textarea:read-only,
    :global(.dark) textarea:read-only {
        background-color: #111827;
        color: #9ca3af;
    }

    textarea:disabled {
        opacity: 0.6;
    }
</style>
