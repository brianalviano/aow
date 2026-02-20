<script lang="ts">
    import type { Snippet } from "svelte";

    interface RadioOption {
        value: string;
        label: string;
        disabled?: boolean;
    }

    interface Props {
        id?: string;
        name: string;
        label?: string;
        value: string;
        options?: RadioOption[];
        disabled?: boolean;
        error?: string;
        layout?: "vertical" | "horizontal";
        onchange?: (event: Event) => void;
        onclick?: (event: MouseEvent) => void;
        children?: Snippet;
    }

    let {
        id,
        name,
        label,
        value = $bindable(),
        options = [],
        disabled = false,
        error,
        layout = "vertical",
        onchange,
        onclick,
        children,
    }: Props = $props();

    /**
     * Handle change events and call parent callback
     */
    function handleChange(event: Event) {
        if (onchange) {
            onchange(event);
        }
    }

    /**
     * Handle click events and call parent callback
     */
    function handleClick(event: MouseEvent) {
        if (onclick) {
            onclick(event);
        }
    }

    /**
     * Get unique ID for each radio option
     */
    function getOptionId(index: number): string {
        return id ? `${id}-${index}` : `${name}-${index}`;
    }
</script>

<fieldset
    class="radio-input-wrapper"
    aria-describedby={error && id ? `${id}-error` : undefined}
>
    {#if label}
        <legend
            id="{id ? `${id}-legend` : `${name}-legend`}"
            class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {label}
        </legend>
    {/if}

    <div
        class="radio-group {layout === 'horizontal'
            ? 'horizontal'
            : 'vertical'}"
    >
        {#if options.length > 0}
            {#each options as option, index}
                <label class="radio-label">
                    <div class="custom-radio">
                        <input
                            type="radio"
                            id={getOptionId(index)}
                            {name}
                            value={option.value}
                            disabled={disabled || option.disabled}
                            bind:group={value}
                            onchange={handleChange}
                            onclick={handleClick}
                            aria-describedby={error && id
                                ? `${id}-error`
                                : undefined}
                        />
                        <div class="custom-radio-circle {error ? 'error' : ''}">
                            <div class="custom-radio-dot"></div>
                        </div>
                    </div>
                    <span
                        class="radio-text {disabled || option.disabled
                            ? 'disabled'
                            : ''}"
                    >
                        {option.label}
                    </span>
                </label>
            {/each}
        {:else if children}
            {@render children()}
        {/if}
    </div>

    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}
</fieldset>

<style>
    .radio-input-wrapper {
        margin-bottom: 1rem;
    }

    .radio-group.vertical {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .radio-group.horizontal {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .radio-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        position: relative;
    }

    .custom-radio {
        position: relative;
        display: inline-block;
    }

    .custom-radio input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .custom-radio-circle {
        position: relative;
        width: 18px;
        height: 18px;
        background-color: #f9fafb;
        border: 2px solid #d1d5db;
        border-radius: 50%;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    :global(html.dark) .custom-radio-circle {
        background-color: #0a0a0a;
        border-color: #212121;
    }

    .custom-radio-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: white;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }

    .custom-radio input[type="radio"]:checked ~ .custom-radio-circle {
        background: linear-gradient(135deg, #0060b2 0%, #00559e 100%);
        border-color: #0060b2;
        transform: scale(1.05);
    }

    .custom-radio
        input[type="radio"]:checked
        ~ .custom-radio-circle
        .custom-radio-dot {
        opacity: 1;
        transform: scale(1);
    }

    .custom-radio input[type="radio"]:focus ~ .custom-radio-circle {
        box-shadow: 0 0 0 3px rgba(0, 96, 178, 0.1);
        border-color: #0060b2;
    }

    :global(html.dark)
        .custom-radio
        input[type="radio"]:focus
        ~ .custom-radio-circle {
        box-shadow: 0 0 0 3px rgba(0, 96, 178, 0.2);
        border-color: #0060b2;
    }

    .custom-radio:hover .custom-radio-circle {
        border-color: #0060b2;
        background-color: rgba(0, 96, 178, 0.05);
    }

    :global(html.dark) .custom-radio:hover .custom-radio-circle {
        background-color: #212121;
        border-color: #0060b2;
    }

    .custom-radio input[type="radio"]:disabled ~ .custom-radio-circle {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .radio-text {
        display: block;
        margin-left: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        transition: color 0.2s ease;
    }

    :global(html.dark) .radio-text {
        color: #d1d5db;
    }

    .radio-text.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .custom-radio-circle.error {
        border-color: #ef4444;
    }

    .custom-radio input[type="radio"]:focus ~ .custom-radio-circle.error {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        border-color: #ef4444;
    }
</style>
