<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        id?: string;
        name?: string;
        label?: string;
        sublabel?: string;
        checked: boolean;
        disabled?: boolean;
        value?: string;
        error?: string;
        variant?: "default" | "card";
        onchange?: (event: Event) => void;
        onclick?: (event: MouseEvent) => void;
        children?: Snippet;
    }

    let {
        id,
        name,
        label,
        sublabel,
        checked = $bindable(),
        disabled = false,
        value,
        error,
        variant = "default",
        onchange,
        onclick,
        children,
    }: Props = $props();

    function handleChange(event: Event) {
        if (onchange) onchange(event);
    }

    function handleClick(event: MouseEvent) {
        if (onclick) onclick(event);
    }
</script>

{#if variant === "card"}
    <label
        class="card-label {checked ? 'active' : ''} {error
            ? 'has-error'
            : ''} {disabled ? 'disabled' : ''}"
    >
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
        <i
            class="{checked
                ? 'fa-solid fa-square-check'
                : 'fa-regular fa-square'} card-icon"
        ></i>
        <div class="card-content">
            {#if label}
                <span class="card-title">{label}</span>
            {:else if children}
                <span class="card-title">{@render children()}</span>
            {/if}
            {#if sublabel}
                <span class="card-sublabel">{sublabel}</span>
            {/if}
        </div>
    </label>
    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}
{:else}
    <label
        class="default-label {checked ? 'active' : ''} {disabled
            ? 'disabled'
            : ''}"
    >
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
        <i
            class="{checked
                ? 'fa-solid fa-square-check'
                : 'fa-regular fa-square'} default-icon {error ? 'error' : ''}"
        ></i>
        <span class="default-text">
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
{/if}

<style>
    /* ===================== DEFAULT VARIANT ===================== */
    .default-label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        user-select: none;
    }
    .default-label.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .default-label input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .default-icon {
        font-size: 18px;
        color: #d1d5db;
        transition: color 0.2s ease;
    }
    :global(html.dark) .default-icon {
        color: #4b5563;
    }
    .default-label:hover .default-icon,
    .default-label.active .default-icon {
        color: #0060b2;
    }
    .default-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    :global(html.dark) .default-text {
        color: #d1d5db;
    }

    /* ===================== CARD VARIANT ===================== */
    .card-label {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        background-color: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    :global(html.dark) .card-label {
        border-color: #1f2937;
        background-color: #111827;
    }
    .card-label:hover {
        border-color: #9ca3af;
    }
    :global(html.dark) .card-label:hover {
        border-color: #374151;
    }
    .card-label.active {
        border-color: #0060b2;
        background-color: rgba(0, 96, 178, 0.05);
        box-shadow: 0 0 0 1px #0060b2;
    }
    .card-label.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .card-label.has-error {
        border-color: #ef4444;
    }
    .card-label input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* Icon */
    .card-icon {
        font-size: 20px;
        color: #d1d5db;
        transition: color 0.2s ease;
        flex-shrink: 0;
    }
    :global(html.dark) .card-icon {
        color: #4b5563;
    }
    .card-label.active .card-icon {
        color: #0060b2;
    }

    /* Card text */
    .card-content {
        display: flex;
        flex-direction: column;
    }
    .card-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
        transition: color 0.2s;
    }
    :global(html.dark) .card-title {
        color: #ffffff;
    }
    .card-label.active .card-title {
        color: #0060b2;
    }
    .card-sublabel {
        font-size: 0.75rem;
        color: #6b7280;
    }
</style>
