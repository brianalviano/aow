<script lang="ts">
    // Button Configuration Interface (sesuai dengan Button.svelte yang ada)
    interface ButtonConfig {
        id?: string;
        type?: "button" | "submit" | "reset";
        text?: string;
        icon?: string;
        iconPosition?: "left" | "right";
        variant?:
            | "primary"
            | "success"
            | "warning"
            | "secondary"
            | "info"
            | "danger"
            | "light"
            | "dark"
            | "link"
            | "outline-primary"
            | "outline-success"
            | "outline-warning"
            | "outline-secondary"
            | "outline-info"
            | "outline-danger"
            | "outline-light"
            | "outline-dark";
        size?: "xs" | "sm" | "normal" | "lg" | "xl";
        disabled?: boolean;
        loading?: boolean;
        customColors?: {
            from: string;
            to: string;
            hoverFrom: string;
            hoverTo: string;
            focusRing: string;
            text: string;
        };
        fullWidth?: boolean;
        onclick?: (event: MouseEvent) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
    }

    interface Props {
        // Basic Input Props
        id: string;
        name: string;
        label?: string;
        type?: string;
        value: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        readonly?: boolean;
        error?: string | undefined;
        autocomplete?: string;
        maxlength?: number;
        class?: string;
        autofocus?: boolean;

        // Icon Props
        icon?: string;

        // Number Formatting Props
        currency?: boolean;
        currencySymbol?: string;
        thousandSeparator?: string;
        decimalSeparator?: string;
        maxDecimals?: number;
        stripZeros?: boolean;
        min?: number | null;
        max?: number | null;
        step?: number | null;

        // Addon Props (prefix/suffix)
        prefix?: string;
        suffix?: string;
        prefixIcon?: string;
        suffixIcon?: string;

        // Button Group Props
        leftButtons?: ButtonConfig[];
        rightButtons?: ButtonConfig[];
        leftButton?: ButtonConfig;
        rightButton?: ButtonConfig;

        // Password Props
        showPasswordToggle?: boolean;

        // Event Handlers
        onkeypress?: (event: KeyboardEvent) => void;
        oninput?: (
            event: Event | { value: string; numericValue: number | null },
        ) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
    }

    let {
        id,
        name,
        label,
        type = "text",
        value = $bindable(),
        placeholder = "",
        required = false,
        disabled = false,
        readonly = false,
        autocomplete,
        maxlength,
        class: inputClass = "",
        autofocus = false,

        icon,

        currency = false,
        currencySymbol = "Rp",
        thousandSeparator = ".",
        decimalSeparator = ",",
        maxDecimals = 2,
        stripZeros = false,
        min = null,
        max = null,
        step = null,

        prefix,
        suffix,
        prefixIcon,
        suffixIcon,

        leftButtons = [],
        rightButtons = [],
        leftButton,
        rightButton,
        showPasswordToggle = true,

        onkeypress,
        oninput,
        onfocus,
        onblur,
        error,
    }: Props = $props();

    // Import Button component
    import Button from "./Button.svelte";
    import { onMount, tick } from "svelte";

    // Internal State
    let displayValue = $state("");
    let isFocused = $state(false);
    let showPassword = $state(false);
    let inputElement = $state<HTMLInputElement>();

    // Computed Properties
    const isNumberType = $derived(type === "number" || currency);
    const isPasswordType = $derived(type === "password");
    const isTelType = $derived(type === "tel");
    const hasButtons = $derived(
        !!(
            leftButtons.length ||
            rightButtons.length ||
            leftButton ||
            rightButton
        ),
    );

    // Button Arrays
    const allLeftButtons = $derived(() => {
        const buttons = [...leftButtons];
        if (leftButton) buttons.unshift(leftButton);
        return buttons;
    });

    const allRightButtons = $derived(() => {
        const buttons = [...rightButtons];
        if (rightButton) buttons.push(rightButton);
        return buttons;
    });

    // ===== UTILITY FUNCTIONS =====

    /**
     * Strip leading zeros from a number string
     */
    function stripLeadingZeros(str: string): string {
        if (!stripZeros || !str) return str;

        // Handle decimal numbers
        if (str.includes(".") || str.includes(decimalSeparator)) {
            const parts = str.split(str.includes(".") ? "." : decimalSeparator);
            const integerPart = parts[0] || "";
            const decimalPart = parts[1] || "";

            // Strip leading zeros from integer part, but keep at least one digit
            const strippedInteger = integerPart.replace(/^0+/, "") || "0";

            return decimalPart
                ? `${strippedInteger}${str.includes(".") ? "." : decimalSeparator}${decimalPart}`
                : strippedInteger;
        }

        // Handle integer numbers
        return str.replace(/^0+/, "") || "0";
    }

    /**
     * Format number with currency and separators
     */
    function formatNumber(num: number | string | null): string {
        if (!num && num !== 0) return "";

        let numStr = num.toString();

        // Apply strip zeros if enabled
        if (stripZeros) {
            numStr = stripLeadingZeros(numStr);
        }

        const parts = numStr.split(".");
        const integerPart = parts[0] || "";
        const decimalPart = parts[1] || "";

        const formattedInteger = integerPart.replace(
            /\B(?=(\d{3})+(?!\d))/g,
            thousandSeparator,
        );

        let result = formattedInteger;
        if (decimalPart && maxDecimals > 0) {
            const truncatedDecimals = decimalPart.substring(0, maxDecimals);
            result += decimalSeparator + truncatedDecimals;
        }

        return currency ? `${currencySymbol} ${result}` : result;
    }

    /**
     * Parse display value to clean numeric string
     */
    function parseNumber(str: string): string {
        if (!str) return "";

        let cleanStr = str.replace(new RegExp(`^${currencySymbol}\\s*`), "");
        cleanStr = cleanStr.replace(
            new RegExp(`\\${thousandSeparator}`, "g"),
            "",
        );
        cleanStr = cleanStr.replace(decimalSeparator, ".");

        // Apply strip zeros after parsing
        if (stripZeros && cleanStr) {
            cleanStr = stripLeadingZeros(cleanStr);
        }

        return cleanStr;
    }

    /**
     * Calculate input padding based on addons
     */
    function getInputPadding() {
        let paddingLeft = "0.75rem";
        let paddingRight = "0.75rem";

        // Left padding calculation
        if (prefix && prefixIcon) {
            paddingLeft = "5.5rem";
        } else if (prefix) {
            paddingLeft = "4rem";
        } else if (prefixIcon) {
            paddingLeft = "2.5rem";
        } else if (icon) {
            paddingLeft = "2.5rem";
        }

        // Right padding calculation
        if (suffix && suffixIcon) {
            paddingRight = "5.5rem";
        } else if (suffix) {
            paddingRight = "4rem";
        } else if (suffixIcon) {
            paddingRight = "2.5rem";
        } else if (isPasswordType && showPasswordToggle) {
            paddingRight = "2.5rem";
        }

        return { paddingLeft, paddingRight };
    }

    /**
     * Get input classes based on layout type
     */
    function getInputClasses() {
        const baseClasses =
            "block w-full py-2.5 border rounded-lg focus:outline-none text-sm transition-all duration-200";

        let paddingClasses = "";

        if (hasButtons) {
            // Button group layout
            paddingClasses = icon ? "pl-10 pr-3" : "px-3";
            const borderClasses = "border-0 focus:ring-0 bg-transparent";

            if (disabled || readonly) {
                return `${baseClasses} ${paddingClasses} ${borderClasses} text-gray-500 dark:text-gray-400 placeholder-gray-400 dark:placeholder:text-gray-500 cursor-not-allowed`;
            }
            return `${baseClasses} ${paddingClasses} ${borderClasses} text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder:text-gray-500`;
        } else {
            // Standard or addon layout
            const { paddingLeft, paddingRight } = getInputPadding();
            paddingClasses = `pl-[${paddingLeft}] pr-[${paddingRight}]`;

            if (disabled || readonly) {
                return `${baseClasses} ${paddingClasses} bg-gray-100 dark:bg-gray-900 border-gray-200 dark:border-[#212121] text-gray-500 dark:text-gray-400 placeholder-gray-400 dark:placeholder:text-gray-500 cursor-not-allowed`;
            }
            if (error) {
                return `${baseClasses} ${paddingClasses} border-red-500 dark:border-red-500 focus:ring-2 focus:ring-red-500 focus:border-transparent bg-white dark:bg-[#0a0a0a] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder:text-gray-500 hover:bg-gray-50 dark:hover:bg-[#0a0a0a]`;
            }
            return `${baseClasses} ${paddingClasses} border-gray-200 dark:border-[#212121] focus:ring-2 focus:ring-[#0060B2] focus:border-transparent dark:focus:border-[#0060B2] bg-white dark:bg-[#0a0a0a] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder:text-gray-500 hover:bg-gray-50 dark:hover:bg-[#0a0a0a]`;
        }
    }

    /**
     * Get container classes for button group
     */
    function getContainerClasses() {
        if (!hasButtons) return "";

        const baseClasses = error
            ? "flex border rounded-lg focus-within:ring-2 focus-within:ring-red-500 focus-within:border-transparent transition-all duration-200"
            : "flex border rounded-lg focus-within:ring-2 focus-within:ring-[#0060B2] focus-within:border-transparent transition-all duration-200";

        if (disabled || readonly) {
            return `${baseClasses} bg-gray-100 dark:bg-gray-900 border-gray-200 dark:border-[#212121]`;
        }
        if (error) {
            return `${baseClasses} bg-white dark:bg-[#0a0a0a] border-red-500 dark:border-red-500 hover:bg-gray-50 dark:hover:bg-gray-800`;
        }
        return `${baseClasses} bg-white dark:bg-[#0a0a0a] border-gray-200 dark:border-[#212121] hover:bg-gray-50 dark:hover:bg-gray-800`;
    }

    /**
     * Get button classes for seamless integration
     */
    function getButtonClasses(
        isFirst: boolean,
        isLast: boolean,
        isLeft: boolean,
    ) {
        let roundingClasses = "";

        if (isLeft) {
            roundingClasses = isLast
                ? "rounded-l-lg rounded-r-none"
                : "rounded-l-lg rounded-r-none";
        } else {
            roundingClasses = isFirst
                ? "rounded-r-lg rounded-l-none"
                : "rounded-r-lg rounded-l-none";
        }

        const borderClasses = isLeft ? "border-r-0" : "border-l-0";
        return `${roundingClasses} ${borderClasses}`;
    }

    /**
     * Render icon - Auto-detect Font Awesome atau SVG path
     * Supports:
     * - Font Awesome: "fa-solid fa-search" atau "fa-solid fa-search text-sm text-red-300"
     * - SVG path: "M21 21l-5.197-5.197..."
     */
    function renderIcon(iconPath: string) {
        if (!iconPath) return "";

        // Auto-detect Font Awesome (contains 'fa-')
        if (iconPath.includes("fa-")) {
            return `<i class="${iconPath}"></i>`;
        }

        // Default to SVG path rendering
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="${iconPath}"/></svg>`;
    }

    // ===== EFFECTS =====

    // Update display value for number inputs
    $effect(() => {
        if (isNumberType && !isFocused) {
            displayValue = formatNumber(value);
        } else if (!isNumberType) {
            // For non-number types, apply stripZeros directly if enabled
            if (stripZeros && value) {
                displayValue = stripLeadingZeros(value);
            } else {
                displayValue = value;
            }
        }
    });

    $effect(() => {
        if (isNumberType) {
            displayValue = formatNumber(value);
        }
    });

    onMount(() => {
        if (autofocus && !disabled && !readonly) {
            tick().then(() => {
                inputElement?.focus();
            });
        }
    });

    // ===== EVENT HANDLERS =====

    function handleKeyPress(event: KeyboardEvent) {
        // Handle telephone input - only allow digits
        if (isTelType) {
            const allowedKeys = [
                "Backspace",
                "Delete",
                "Tab",
                "Escape",
                "Enter",
                "ArrowLeft",
                "ArrowRight",
                "ArrowUp",
                "ArrowDown",
            ];
            const char = event.key;

            // Allow control keys
            if (allowedKeys.includes(char)) {
                onkeypress?.(event);
                return;
            }

            // Only allow digits for tel type
            if (!/\d/.test(char)) {
                event.preventDefault();
                return;
            }

            onkeypress?.(event);
            return;
        }

        // Handle number input
        if (isNumberType) {
            const allowedKeys = [
                "Backspace",
                "Delete",
                "Tab",
                "Escape",
                "Enter",
                "ArrowLeft",
                "ArrowRight",
                "ArrowUp",
                "ArrowDown",
            ];
            const char = event.key;

            if (allowedKeys.includes(char)) {
                onkeypress?.(event);
                return;
            }

            if (char === decimalSeparator || char === ".") {
                const currentValue = isNumberType ? displayValue : value;
                if (
                    currentValue.includes(decimalSeparator) ||
                    currentValue.includes(".")
                ) {
                    event.preventDefault();
                    return;
                }
            } else if (!/\d/.test(char)) {
                event.preventDefault();
                return;
            }
        }
        onkeypress?.(event);
    }

    function handleInput(event: Event) {
        const target = event.target as HTMLInputElement;
        const inputValue = target.value;

        // Handle telephone input - strip non-digits
        if (isTelType) {
            const cleanValue = inputValue.replace(/\D/g, "");
            if (cleanValue !== inputValue) {
                target.value = cleanValue;
            }
            value = cleanValue;
            oninput?.(event);
            return;
        }

        if (isNumberType) {
            displayValue = inputValue;
            const numericValue = parseNumber(inputValue);
            const parsedValue = parseFloat(numericValue);

            if (!isNaN(parsedValue)) {
                // Apply stripZeros to the final value
                const finalValue = stripZeros
                    ? stripLeadingZeros(numericValue)
                    : numericValue;
                value = finalValue;
                oninput?.({ value: finalValue, numericValue: parsedValue });
            } else if (inputValue === "") {
                value = "";
                oninput?.({ value: "", numericValue: null });
            }
        } else {
            // For non-number types, apply stripZeros if enabled
            let finalValue = inputValue;
            if (stripZeros && inputValue) {
                finalValue = stripLeadingZeros(inputValue);
                // Update the input field if value changed
                if (finalValue !== inputValue) {
                    target.value = finalValue;
                }
            }

            value = finalValue;
            oninput?.(event);
        }
    }

    function handleFocus(event: FocusEvent) {
        if (isNumberType) {
            isFocused = true;
            displayValue = stripZeros
                ? stripLeadingZeros(value.toString())
                : value.toString();
        }
        onfocus?.(event);
    }

    function handleBlur(event: FocusEvent) {
        if (isNumberType) {
            isFocused = false;
            // Apply stripZeros when formatting on blur
            const cleanValue = stripZeros
                ? stripLeadingZeros(value.toString())
                : value.toString();
            value = cleanValue;
            displayValue = formatNumber(cleanValue);
        } else if (stripZeros && value) {
            // Apply stripZeros on blur for non-number types
            const cleanValue = stripLeadingZeros(value);
            if (cleanValue !== value) {
                value = cleanValue;
                const target = event.target as HTMLInputElement;
                target.value = cleanValue;
            }
        }
        onblur?.(event);
    }

    function togglePasswordVisibility() {
        showPassword = !showPassword;
    }
</script>

<div class="space-y-2">
    <!-- Label -->
    {#if label}
        <label
            for={id}
            class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
        >
            {label}
            {#if required}
                <span class="ml-1 text-red-500">*</span>
            {/if}
        </label>
    {/if}

    {#if hasButtons}
        <!-- Button Group Layout -->
        <div class={getContainerClasses()}>
            <!-- Left Buttons -->
            {#each allLeftButtons() as button, index (index)}
                <Button
                    {...button.id !== undefined ? { id: button.id } : {}}
                    type={button.type || "button"}
                    variant={button.variant || "secondary"}
                    size={button.size || "normal"}
                    disabled={!!(button.disabled || disabled || readonly)}
                    loading={button.loading ?? false}
                    icon={button.icon ?? ""}
                    iconPosition={button.iconPosition || "left"}
                    {...button.customColors !== undefined
                        ? { customColors: button.customColors }
                        : {}}
                    fullWidth={button.fullWidth ?? false}
                    onclick={button.onclick ?? (() => {})}
                    {...button.onfocus !== undefined
                        ? { onfocus: button.onfocus }
                        : {}}
                    {...button.onblur !== undefined
                        ? { onblur: button.onblur }
                        : {}}
                    hasText={!!button.text}
                    class={getButtonClasses(
                        index === 0,
                        index === allLeftButtons().length - 1,
                        true,
                    )}
                >
                    {#if button.text}{button.text}{/if}
                </Button>
            {/each}

            <!-- Input Container -->
            <div class="relative flex-1">
                <!-- Input Icon -->
                {#if icon}
                    <div
                        class="flex absolute inset-y-0 left-0 z-10 items-center pl-3 pointer-events-none"
                    >
                        <div
                            class={disabled || readonly
                                ? "text-gray-300 dark:text-gray-500"
                                : "text-gray-400 dark:text-gray-500"}
                        >
                            {@html renderIcon(icon)}
                        </div>
                    </div>
                {/if}

                <input
                    {id}
                    {name}
                    type={isNumberType
                        ? "text"
                        : isPasswordType
                          ? showPassword
                              ? "text"
                              : "password"
                          : type}
                    inputmode={isNumberType || isTelType
                        ? "decimal"
                        : undefined}
                    autocomplete={autocomplete as any}
                    {required}
                    {disabled}
                    {readonly}
                    {maxlength}
                    {min}
                    {max}
                    {step}
                    bind:this={inputElement}
                    value={isNumberType
                        ? displayValue
                        : stripZeros && value
                          ? stripLeadingZeros(value)
                          : value}
                    onkeypress={handleKeyPress}
                    oninput={handleInput}
                    onfocus={handleFocus}
                    onblur={handleBlur}
                    class="{getInputClasses()} {inputClass}"
                    {placeholder}
                    aria-invalid={!!error}
                    aria-describedby={error ? `${id}-error` : undefined}
                />
            </div>

            <!-- Right Buttons -->
            {#each allRightButtons() as button, index (index)}
                <Button
                    {...button.id !== undefined ? { id: button.id } : {}}
                    type={button.type || "button"}
                    variant={button.variant || "secondary"}
                    size={button.size || "normal"}
                    disabled={!!(button.disabled || disabled || readonly)}
                    loading={button.loading ?? false}
                    icon={button.icon ?? ""}
                    iconPosition={button.iconPosition || "left"}
                    {...button.customColors !== undefined
                        ? { customColors: button.customColors }
                        : {}}
                    fullWidth={button.fullWidth ?? false}
                    onclick={button.onclick ?? (() => {})}
                    {...button.onfocus !== undefined
                        ? { onfocus: button.onfocus }
                        : {}}
                    {...button.onblur !== undefined
                        ? { onblur: button.onblur }
                        : {}}
                    hasText={!!button.text}
                    class={getButtonClasses(
                        index === 0,
                        index === allRightButtons().length - 1,
                        false,
                    )}
                >
                    {#if button.text}{button.text}{/if}
                </Button>
            {/each}
        </div>
    {:else}
        <!-- Standard/Addon Layout -->
        <div class="relative">
            <!-- Prefix Elements -->
            {#if prefix || prefixIcon}
                <div
                    class="flex absolute inset-y-0 left-0 z-10 items-center pointer-events-none"
                >
                    <div class="flex items-center pr-1 pl-3">
                        {#if prefixIcon}
                            <div
                                class={disabled || readonly
                                    ? "text-gray-300 dark:text-gray-500"
                                    : "text-gray-400 dark:text-gray-500"}
                            >
                                {@html renderIcon(prefixIcon)}
                            </div>
                        {/if}
                        {#if prefix}
                            <span
                                class={(disabled || readonly
                                    ? "text-gray-300 dark:text-gray-500"
                                    : "text-gray-500 dark:text-gray-400") +
                                    " text-sm font-medium" +
                                    (prefixIcon ? " ml-2" : "")}>{prefix}</span
                            >
                        {/if}
                    </div>
                </div>
            {:else if icon}
                <div
                    class="flex absolute inset-y-0 left-0 z-10 items-center pl-3 pointer-events-none"
                >
                    <div
                        class={disabled || readonly
                            ? "text-gray-300 dark:text-gray-500"
                            : "text-gray-400 dark:text-gray-500"}
                    >
                        {@html renderIcon(icon)}
                    </div>
                </div>
            {/if}

            <!-- Input Element -->
            <input
                {id}
                {name}
                type={isNumberType
                    ? "text"
                    : isPasswordType
                      ? showPassword
                          ? "text"
                          : "password"
                      : type}
                inputmode={isNumberType || isTelType ? "decimal" : undefined}
                autocomplete={autocomplete as any}
                {required}
                {disabled}
                {readonly}
                {maxlength}
                {min}
                {max}
                {step}
                bind:this={inputElement}
                value={isNumberType
                    ? displayValue
                    : stripZeros && value
                      ? stripLeadingZeros(value)
                      : value}
                onkeypress={handleKeyPress}
                oninput={handleInput}
                onfocus={handleFocus}
                onblur={handleBlur}
                class="{getInputClasses()} {inputClass}"
                {placeholder}
                aria-invalid={!!error}
                aria-describedby={error ? `${id}-error` : undefined}
            />

            <!-- Suffix Elements -->
            {#if suffix || suffixIcon}
                <div
                    class="flex absolute inset-y-0 right-0 z-10 items-center pointer-events-none"
                >
                    <div class="flex items-center pr-3 pl-1">
                        {#if suffix}
                            <span
                                class={(disabled || readonly
                                    ? "text-gray-300 dark:text-gray-500"
                                    : "text-gray-500 dark:text-gray-400") +
                                    " text-sm font-medium" +
                                    (suffixIcon ? " mr-2" : "")}>{suffix}</span
                            >
                        {/if}
                        {#if suffixIcon}
                            <div
                                class={disabled || readonly
                                    ? "text-gray-300 dark:text-gray-500"
                                    : "text-gray-400 dark:text-gray-500"}
                            >
                                {@html renderIcon(suffixIcon)}
                            </div>
                        {/if}
                    </div>
                </div>
            {:else if isPasswordType && showPasswordToggle}
                <button
                    type="button"
                    onclick={togglePasswordVisibility}
                    class="flex absolute inset-y-0 right-0 items-center pr-3 text-gray-400 transition-colors duration-200 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                    aria-label="togglePasswordVisibility"
                >
                    {#if showPassword}
                        <i class="fas fa-eye-slash"></i>
                    {:else}
                        <i class="fas fa-eye"></i>
                    {/if}
                </button>
            {/if}
        </div>
    {/if}
    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}
</div>

<style>
    /* Hide number input spinners */
    input[type="text"]::-webkit-outer-spin-button,
    input[type="text"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="text"] {
        -moz-appearance: textfield;
        appearance: textfield;
    }

    /* Button group integration */
    :global(.input-group input) {
        border: none !important;
        box-shadow: none !important;
    }
    :global(.input-group input:focus) {
        border: none !important;
        box-shadow: none !important;
    }
</style>
