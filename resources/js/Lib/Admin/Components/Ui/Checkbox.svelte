<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        id?: string;
        name?: string;
        label?: string;
        checked: boolean;
        disabled?: boolean;
        value?: string;
        error?: string;
        onchange?: (event: Event) => void;
        onclick?: (event: MouseEvent) => void;
        children?: Snippet;
    }

    let {
        id,
        name,
        label,
        checked = $bindable(),
        disabled = false,
        value,
        error,
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
</script>

<label class="flex items-center cursor-pointer">
    <div class="custom-checkbox">
        <input
            type="checkbox"
            {id}
            {name}
            {value}
            {disabled}
            bind:checked
            onchange={handleChange}
            onclick={handleClick}
            aria-invalid={!!error}
            aria-describedby={error && id ? `${id}-error` : undefined}
        />
        <div class="custom-checkbox-box {error ? 'error' : ''}">
            <svg class="checkmark" fill="white" viewBox="0 0 20 20">
                <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
    </div>
    <span
        class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-300"
    >
        {#if label}
            {label}
        {:else if children}
            {@render children()}
        {/if}
    </span>
</label>
{#if error}
    <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
{/if}

<style>
    .custom-checkbox {
        position: relative;
        display: inline-block;
    }

    .custom-checkbox input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .custom-checkbox-box {
        position: relative;
        width: 18px;
        height: 18px;
        background-color: #f9fafb;
        border: 2px solid #d1d5db;
        border-radius: 4px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    :global(html.dark) .custom-checkbox-box {
        background-color: #0a0a0a;
        border-color: #212121;
    }

    .custom-checkbox input[type="checkbox"]:checked ~ .custom-checkbox-box {
        background: linear-gradient(135deg, #0060b2 0%, #00559e 100%);
        border-color: #0060b2;
        transform: scale(1.05);
    }

    .custom-checkbox input[type="checkbox"]:focus ~ .custom-checkbox-box {
        box-shadow: 0 0 0 3px rgba(0, 96, 178, 0.1);
        border-color: #0060b2;
    }
    :global(html.dark)
        .custom-checkbox
        input[type="checkbox"]:focus
        ~ .custom-checkbox-box {
        box-shadow: 0 0 0 3px rgba(0, 96, 178, 0.2);
        border-color: #0060b2;
    }

    .custom-checkbox:hover .custom-checkbox-box {
        border-color: #0060b2;
        background-color: rgba(0, 96, 178, 0.05);
    }
    :global(html.dark) .custom-checkbox:hover .custom-checkbox-box {
        background-color: #212121;
        border-color: #0060b2;
    }

    .custom-checkbox input[type="checkbox"]:disabled ~ .custom-checkbox-box {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .checkmark {
        width: 12px;
        height: 12px;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.2s ease;
    }

    .custom-checkbox
        input[type="checkbox"]:checked
        ~ .custom-checkbox-box
        .checkmark {
        opacity: 1;
        transform: scale(1);
    }

    .custom-checkbox-box.error {
        border-color: #ef4444;
    }
    .custom-checkbox input[type="checkbox"]:focus ~ .custom-checkbox-box.error {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        border-color: #ef4444;
    }
</style>
