<script lang="ts">
    interface Props {
        id?: string;
        name?: string;
        label?: string;
        value: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        min?: string;
        max?: string;
        showIcon?: boolean;
        autocomplete?: string;
        error?: string;
        onchange?: (value: string) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
    }

    let {
        id,
        name,
        label,
        value = $bindable(),
        placeholder = "Pilih waktu",
        required = false,
        disabled = false,
        min,
        max,
        showIcon = true,
        autocomplete = "off",
        onchange,
        onfocus,
        onblur,
        error,
    }: Props = $props();

    let isOpen = $state(false);
    let inputElement: HTMLInputElement;
    let dropdownElement = $state<HTMLDivElement>();
    let containerElement = $state<HTMLDivElement>();
    let hourScrollContainer = $state<HTMLDivElement>();
    let minuteScrollContainer = $state<HTMLDivElement>();
    let dropdownPosition = $state<"bottom" | "top">("bottom");

    // Selected time components
    let selectedHour = $state<number | null>(null);
    let selectedMinute = $state<number | null>(null);
    let minuteManuallySelected = $state(false); // Track jika menit dipilih manual

    /**
     * Initialize time from value prop
     */
    function initializeTimeFromValue() {
        if (value) {
            const time = parseTime(value);
            if (time) {
                selectedHour = time.hour;
                selectedMinute = time.minute;
                minuteManuallySelected = true; // Set true karena value sudah ada
            }
        } else {
            selectedHour = null;
            selectedMinute = null;
            minuteManuallySelected = false; // Reset flag
        }
    }

    /**
     * Parse time string (HH:mm format)
     * Strips seconds if present
     */
    function parseTime(
        timeString: string,
    ): { hour: number; minute: number } | null {
        if (!timeString) return null;

        // Remove seconds if present (format could be HH:mm:ss)
        const parts = timeString.split(":");
        if (parts.length < 2 || !parts[0] || !parts[1]) return null;

        const hour = parseInt(parts[0], 10);
        const minute = parseInt(parts[1], 10);

        // Validate
        if (
            isNaN(hour) ||
            isNaN(minute) ||
            hour < 0 ||
            hour > 23 ||
            minute < 0 ||
            minute > 59
        ) {
            return null;
        }

        return { hour, minute };
    }

    /**
     * Format time to string (HH:mm) - always without seconds
     */
    function formatTime(hour: number, minute: number): string {
        return `${hour.toString().padStart(2, "0")}:${minute.toString().padStart(2, "0")}`;
    }

    /**
     * Normalize time value to HH:mm format (strip seconds if present)
     */
    function normalizeTimeValue(timeValue: string): string {
        if (!timeValue) return "";

        const parts = timeValue.split(":");
        if (parts.length >= 2 && parts[0] && parts[1]) {
            // Only take hour and minute, ignore seconds
            return `${parts[0].padStart(2, "0")}:${parts[1].padStart(2, "0")}`;
        }

        return timeValue;
    }

    /**
     * Get display value for input
     */
    function getDisplayValue(): string {
        if (selectedHour !== null && selectedMinute !== null) {
            return formatTime(selectedHour, selectedMinute);
        }
        return "";
    }

    /**
     * Check if time is disabled based on min/max
     */
    function isTimeDisabled(hour: number, minute: number): boolean {
        if (!min && !max) return false;

        const timeValue = hour * 60 + minute;

        if (min) {
            const minTime = parseTime(min);
            if (minTime) {
                const minValue = minTime.hour * 60 + minTime.minute;
                if (timeValue < minValue) return true;
            }
        }

        if (max) {
            const maxTime = parseTime(max);
            if (maxTime) {
                const maxValue = maxTime.hour * 60 + maxTime.minute;
                if (timeValue > maxValue) return true;
            }
        }

        return false;
    }

    /**
     * Select hour
     */
    function selectHour(hour: number, event: MouseEvent) {
        if (disabled) return;
        event.stopPropagation();

        selectedHour = hour;

        // Jangan auto-set minute, biarkan user pilih manual

        updateValue();
    }

    /**
     * Select minute
     */
    function selectMinute(minute: number, event: MouseEvent) {
        if (disabled) return;
        event.stopPropagation();

        selectedMinute = minute;
        minuteManuallySelected = true; // Tandai bahwa menit dipilih secara manual

        // Auto-select hour if not set (gunakan jam sekarang sebagai default)
        if (selectedHour === null) {
            selectedHour = new Date().getHours();
        }

        updateValue();
    }

    /**
     * Update value and close dropdown
     */
    function updateValue() {
        if (selectedHour !== null && selectedMinute !== null) {
            const timeString = formatTime(selectedHour, selectedMinute);

            // Check if time is disabled
            if (isTimeDisabled(selectedHour, selectedMinute)) {
                return;
            }

            // Ensure value is always in HH:mm format
            value = normalizeTimeValue(timeString);

            if (onchange) {
                onchange(value);
            }

            // Hanya close setelah user manual memilih menit
            // (bukan hanya jam yang dipilih)
            if (minuteManuallySelected) {
                isOpen = false;
            }
        }
    }

    /**
     * Toggle time picker
     */
    function toggleTimePicker(event: MouseEvent) {
        if (disabled) return;
        event.stopPropagation();
        isOpen = !isOpen;
    }

    /**
     * Handle click outside
     */
    function handleClickOutside(event: MouseEvent) {
        if (!isOpen) return;

        const target = event.target as Node;

        if (containerElement && !containerElement.contains(target)) {
            isOpen = false;
        }
    }

    /**
     * Handle focus
     */
    function handleFocus(event: FocusEvent) {
        if (onfocus) {
            onfocus(event);
        }
    }

    /**
     * Handle blur
     */
    function handleBlur(event: FocusEvent) {
        if (onblur) {
            onblur(event);
        }
    }

    /**
     * Set current time
     */
    function setCurrentTime(event: MouseEvent) {
        event.stopPropagation();
        const now = new Date();
        const hour = now.getHours();
        const minute = now.getMinutes();

        if (!isTimeDisabled(hour, minute)) {
            selectedHour = hour;
            selectedMinute = minute;
            minuteManuallySelected = true; // Set true karena ini pilihan lengkap
            updateValue();
        }
    }

    /**
     * Clear time
     */
    function clearTime(event: MouseEvent) {
        event.stopPropagation();
        selectedHour = null;
        selectedMinute = null;
        minuteManuallySelected = false; // Reset flag
        value = "";
        isOpen = false;

        if (onchange) {
            onchange("");
        }
    }

    /**
     * Scroll to selected time in the time picker
     */
    function scrollToSelectedTime() {
        // Small delay to ensure DOM is rendered
        setTimeout(() => {
            if (selectedHour !== null && hourScrollContainer) {
                const hourButton = hourScrollContainer.querySelector(
                    `button:nth-child(${selectedHour + 1})`,
                ) as HTMLElement;
                if (hourButton) {
                    hourButton.scrollIntoView({
                        block: "center",
                        behavior: "smooth",
                    });
                }
            }

            if (selectedMinute !== null && minuteScrollContainer) {
                const minuteButton = minuteScrollContainer.querySelector(
                    `button:nth-child(${selectedMinute + 1})`,
                ) as HTMLElement;
                if (minuteButton) {
                    minuteButton.scrollIntoView({
                        block: "center",
                        behavior: "smooth",
                    });
                }
            }
        }, 50);
    }

    /**
     * Generate hours array (00-23)
     */
    function generateHours(): number[] {
        return Array.from({ length: 24 }, (_, i) => i);
    }

    /**
     * Generate minutes array (00-59)
     */
    function generateMinutes(): number[] {
        return Array.from({ length: 60 }, (_, i) => i);
    }

    // Initialize time when component mounts or value changes
    $effect(() => {
        initializeTimeFromValue();
    });

    // Normalize value to ensure it's always HH:mm format
    $effect(() => {
        if (value) {
            const normalized = normalizeTimeValue(value);
            if (normalized !== value) {
                value = normalized;
            }
        }
    });

    // Scroll to selected time when dropdown opens
    $effect(() => {
        if (isOpen) {
            scrollToSelectedTime();
        }
    });

    // Add click outside listener with delay to avoid immediate trigger
    $effect(() => {
        if (isOpen) {
            // Use setTimeout to avoid the same click that opened the dropdown from closing it
            const timeoutId = setTimeout(() => {
                document.addEventListener("click", handleClickOutside);
            }, 0);

            return () => {
                clearTimeout(timeoutId);
                document.removeEventListener("click", handleClickOutside);
            };
        }
        return () => {};
    });

    // Detect if the dropdown should open upwards or downwards
    $effect(() => {
        if (isOpen && containerElement) {
            const rect = containerElement.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            const dropdownHeight = 320; // Estimated height with margins

            if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
                dropdownPosition = "top";
            } else {
                dropdownPosition = "bottom";
            }
        }
    });
</script>

<div class="space-y-2" bind:this={containerElement}>
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

    <div class="relative">
        <!-- Time Input -->
        <div class="relative">
            {#if showIcon}
                <div
                    class="flex absolute inset-y-0 left-0 z-10 items-center pl-3 pointer-events-none"
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
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
            {/if}

            <input
                bind:this={inputElement}
                {id}
                {name}
                type="text"
                {required}
                {disabled}
                autocomplete={autocomplete as any}
                value={getDisplayValue()}
                {placeholder}
                class="block w-full {showIcon
                    ? 'pl-10'
                    : 'pl-3'} {selectedHour !== null && selectedMinute !== null
                    ? 'pr-10'
                    : 'pr-3'} py-2.5 border border-gray-200 dark:border-[#212121] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0060B2] focus:border-transparent bg-white dark:bg-[#0a0a0a] backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder:text-gray-500 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer text-sm {disabled
                    ? 'opacity-50 cursor-not-allowed'
                    : ''} {error ? 'border-red-500 focus:ring-red-500' : ''}"
                readonly
                onclick={toggleTimePicker}
                onfocus={handleFocus}
                onblur={handleBlur}
                aria-invalid={!!error}
                aria-describedby={error && id ? `${id}-error` : undefined}
            />

            {#if selectedHour !== null && selectedMinute !== null && !disabled}
                <button
                    type="button"
                    onclick={clearTime}
                    aria-label="Hapus waktu"
                    class="flex absolute inset-y-0 right-0 items-center pr-3 text-gray-400 transition-colors duration-200 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg
                        class="w-4 h-4"
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

        <!-- Time Picker Dropdown -->
        {#if isOpen}
            <div
                bind:this={dropdownElement}
                class="absolute z-50 {dropdownPosition === 'top'
                    ? 'bottom-full mb-1'
                    : 'mt-1'} bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#212121] rounded-lg shadow-lg p-2.5 w-64"
            >
                <!-- Time Picker Header -->
                <div class="mb-2.5 text-center">
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Pilih Waktu
                    </h3>
                </div>

                <!-- Hour and Minute Selection -->
                <div class="grid grid-cols-2 gap-2">
                    <!-- Hours Column -->
                    <div>
                        <div
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 text-center"
                        >
                            Jam
                        </div>
                        <div
                            bind:this={hourScrollContainer}
                            class="max-h-36 overflow-y-auto border border-gray-200 dark:border-[#212121] rounded-md"
                        >
                            {#each generateHours() as hour}
                                <button
                                    type="button"
                                    onclick={(e) => selectHour(hour, e)}
                                    class="w-full px-2 py-1.5 text-xs text-left transition-colors duration-200 {selectedHour ===
                                    hour
                                        ? 'bg-[#0060B2] text-white font-semibold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-[#0060B2]/10 hover:text-[#0060B2]'}"
                                >
                                    {hour.toString().padStart(2, "0")}
                                </button>
                            {/each}
                        </div>
                    </div>

                    <!-- Minutes Column -->
                    <div>
                        <div
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 text-center"
                        >
                            Menit
                        </div>
                        <div
                            bind:this={minuteScrollContainer}
                            class="max-h-36 overflow-y-auto border border-gray-200 dark:border-[#212121] rounded-md"
                        >
                            {#each generateMinutes() as minute}
                                <button
                                    type="button"
                                    onclick={(e) => selectMinute(minute, e)}
                                    class="w-full px-2 py-1.5 text-xs text-left transition-colors duration-200 {selectedMinute ===
                                    minute
                                        ? 'bg-[#0060B2] text-white font-semibold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-[#0060B2]/10 hover:text-[#0060B2]'}"
                                >
                                    {minute.toString().padStart(2, "0")}
                                </button>
                            {/each}
                        </div>
                    </div>
                </div>

                <!-- Current Time Button -->
                <div
                    class="mt-2 pt-2 border-t border-gray-200 dark:border-[#212121]"
                >
                    <button
                        type="button"
                        onclick={setCurrentTime}
                        class="w-full py-1.5 px-2 text-xs text-[#0060B2] hover:bg-[#0060B2]/10 rounded-md transition-colors duration-200 font-medium"
                    >
                        Waktu Sekarang
                    </button>
                </div>
            </div>
        {/if}

        <!-- Hidden input for form submission -->
        {#if name}
            <input type="hidden" {name} value={normalizeTimeValue(value)} />
        {/if}
    </div>
    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}
</div>
