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
        format?: "dd/mm/yyyy" | "mm/dd/yyyy" | "yyyy-mm-dd";
        showIcon?: boolean;
        autocomplete?: string;
        error?: string;
        onchange?: (value: string) => void;
        onfocus?: (event: FocusEvent) => void;
        onblur?: (event: FocusEvent) => void;
        bindValueAsIso?: boolean;
    }

    let {
        id,
        name,
        label,
        value = $bindable(),
        placeholder = "Pilih tanggal",
        required = false,
        disabled = false,
        min,
        max,
        format = "dd/mm/yyyy",
        showIcon = true,
        autocomplete = "off",
        onchange,
        onfocus,
        onblur,
        error,
        bindValueAsIso = true,
    }: Props = $props();

    let isOpen = $state(false);
    let inputElement: HTMLInputElement;
    let dropdownElement = $state<HTMLDivElement>();
    let alignRight = $state(false);

    // Current viewing month/year
    let currentMonth = $state(new Date().getMonth());
    let currentYear = $state(new Date().getFullYear());

    // Selected date components
    let selectedDate = $state<Date | null>(null);

    /**
     * Initialize selected date from value prop
     */
    function initializeDateFromValue() {
        if (value) {
            const date = parseDate(value);
            if (date) {
                selectedDate = date;
                currentMonth = date.getMonth();
                currentYear = date.getFullYear();
            }
        } else {
            selectedDate = null;
        }
    }

    /**
     * Parse date string based on format
     */
    function parseDate(dateString: string): Date | null {
        if (!dateString) return null;

        let day: number = 0,
            month: number = 0,
            year: number = 0;

        try {
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                const parts = dateString.split("-");
                year = parseInt(parts[0] ?? "", 10);
                month = parseInt(parts[1] ?? "", 10) - 1;
                day = parseInt(parts[2] ?? "", 10);
            } else if (format === "dd/mm/yyyy") {
                const parts = dateString.split("/");
                day = parseInt(parts[0] ?? "", 10);
                month = parseInt(parts[1] ?? "", 10) - 1;
                year = parseInt(parts[2] ?? "", 10);
            } else if (format === "mm/dd/yyyy") {
                const parts = dateString.split("/");
                month = parseInt(parts[0] ?? "", 10) - 1;
                day = parseInt(parts[1] ?? "", 10);
                year = parseInt(parts[2] ?? "", 10);
            } else {
                const parts = dateString.split("/");
                day = parseInt(parts[0] ?? "", 10);
                month = parseInt(parts[1] ?? "", 10) - 1;
                year = parseInt(parts[2] ?? "", 10);
            }

            // Validate parsed values
            if (isNaN(year) || isNaN(month) || isNaN(day)) return null;

            const date = new Date(year, month, day);
            // Validate the date
            if (
                date.getFullYear() === year &&
                date.getMonth() === month &&
                date.getDate() === day
            ) {
                return date;
            }
        } catch (e) {
            return null;
        }

        return null;
    }

    /**
     * Format date to string based on format
     */
    function formatDate(date: Date): string {
        const day = date.getDate().toString().padStart(2, "0");
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const year = date.getFullYear().toString();

        switch (format) {
            case "yyyy-mm-dd":
                return `${year}-${month}-${day}`;
            case "mm/dd/yyyy":
                return `${month}/${day}/${year}`;
            case "dd/mm/yyyy":
            default:
                return `${day}/${month}/${year}`;
        }
    }

    /**
     * Get display value for input
     */
    function getDisplayValue(): string {
        return selectedDate ? formatDate(selectedDate) : "";
    }

    function formatIso(date: Date): string {
        const day = date.getDate().toString().padStart(2, "0");
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const year = date.getFullYear().toString();
        return `${year}-${month}-${day}`;
    }

    /**
     * Get month name
     */
    function getMonthName(monthIndex: number): string {
        const months = [
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember",
        ];
        return months[monthIndex] || "";
    }

    /**
     * Get days in month
     */
    function getDaysInMonth(month: number, year: number): number {
        return new Date(year, month + 1, 0).getDate();
    }

    /**
     * Get first day of month (0 = Sunday, 1 = Monday, etc.)
     */
    function getFirstDayOfMonth(month: number, year: number): number {
        return new Date(year, month, 1).getDay();
    }

    /**
     * Generate calendar days
     */
    function generateCalendarDays(): Array<{
        day: number;
        isCurrentMonth: boolean;
        date: Date;
    }> {
        const daysInMonth = getDaysInMonth(currentMonth, currentYear);
        const firstDay = getFirstDayOfMonth(currentMonth, currentYear);
        const days: Array<{
            day: number;
            isCurrentMonth: boolean;
            date: Date;
        }> = [];

        // Previous month days
        const prevMonth = currentMonth === 0 ? 11 : currentMonth - 1;
        const prevYear = currentMonth === 0 ? currentYear - 1 : currentYear;
        const daysInPrevMonth = getDaysInMonth(prevMonth, prevYear);

        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            days.push({
                day,
                isCurrentMonth: false,
                date: new Date(prevYear, prevMonth, day),
            });
        }

        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            days.push({
                day,
                isCurrentMonth: true,
                date: new Date(currentYear, currentMonth, day),
            });
        }

        // Next month days
        const nextMonth = currentMonth === 11 ? 0 : currentMonth + 1;
        const nextYear = currentMonth === 11 ? currentYear + 1 : currentYear;
        const remainingDays = 42 - days.length; // 6 weeks * 7 days

        for (let day = 1; day <= remainingDays; day++) {
            days.push({
                day,
                isCurrentMonth: false,
                date: new Date(nextYear, nextMonth, day),
            });
        }

        return days;
    }

    /**
     * Check if date is selected
     */
    function isSelectedDate(date: Date): boolean {
        if (!selectedDate) return false;
        return date.getTime() === selectedDate.getTime();
    }

    /**
     * Check if date is today
     */
    function isToday(date: Date): boolean {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    }

    /**
     * Check if date is disabled
     */
    function isDateDisabled(date: Date): boolean {
        if (min) {
            const minDate = parseDate(min);
            if (minDate && date < minDate) return true;
        }
        if (max) {
            const maxDate = parseDate(max);
            if (maxDate && date > maxDate) return true;
        }
        return false;
    }

    /**
     * Select a date
     */
    function selectDate(date: Date) {
        if (isDateDisabled(date)) return;

        selectedDate = date;
        value = bindValueAsIso ? formatIso(date) : formatDate(date);
        isOpen = false;

        if (onchange) {
            onchange(value);
        }
    }

    /**
     * Navigate to previous month
     */
    function previousMonth() {
        if (currentMonth === 0) {
            currentMonth = 11;
            currentYear--;
        } else {
            currentMonth--;
        }
    }

    /**
     * Navigate to next month
     */
    function nextMonth() {
        if (currentMonth === 11) {
            currentMonth = 0;
            currentYear++;
        } else {
            currentMonth++;
        }
    }

    /**
     * Toggle calendar dropdown
     */
    function toggleCalendar() {
        if (disabled) return;
        isOpen = !isOpen;
    }
    function updateDropdownPlacement() {
        if (!inputElement) return;
        const rect = inputElement.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const dropdownWidth =
            (dropdownElement && dropdownElement.offsetWidth) || 256;

        // If left-aligned dropdown would overflow on the right
        if (rect.left + dropdownWidth > viewportWidth - 20) {
            alignRight = true;
        } else {
            alignRight = false;
        }

        // If right-aligned dropdown would overflow on the left,
        // fallback to left-aligned and hope for the best (usually screen is > 256px)
        if (alignRight && rect.right - dropdownWidth < 20) {
            alignRight = false;
        }
    }

    /**
     * Handle input focus
     */
    function handleFocus(event: FocusEvent) {
        if (onfocus) {
            onfocus(event);
        }
    }

    /**
     * Handle input blur
     */
    function handleBlur(event: FocusEvent) {
        // Delay to allow for calendar selection
        setTimeout(() => {
            if (!dropdownElement?.contains(document.activeElement as Node)) {
                isOpen = false;
                if (onblur) {
                    onblur(event);
                }
            }
        }, 150);
    }

    /**
     * Handle clicks outside
     */
    function handleClickOutside(event: MouseEvent) {
        if (
            dropdownElement &&
            !dropdownElement.contains(event.target as Node) &&
            inputElement &&
            !inputElement.contains(event.target as Node)
        ) {
            isOpen = false;
        }
    }

    /**
     * Clear date
     */
    function clearDate() {
        selectedDate = null;
        value = "";
        isOpen = false;

        if (onchange) {
            onchange("");
        }
    }

    // Initialize date when component mounts or value changes
    $effect(() => {
        initializeDateFromValue();
    });

    // Add click outside listener
    $effect(() => {
        if (isOpen) {
            document.addEventListener("click", handleClickOutside);
            setTimeout(() => {
                updateDropdownPlacement();
            }, 0);
            window.addEventListener("resize", updateDropdownPlacement);
            return () => {
                document.removeEventListener("click", handleClickOutside);
                window.removeEventListener("resize", updateDropdownPlacement);
            };
        }
        return () => {};
    });
</script>

<svelte:window on:click={handleClickOutside} />

<div class="space-y-2">
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
        <!-- Date Input -->
        <div class="relative">
            {#if showIcon}
                <div
                    class="flex absolute inset-y-0 left-0 z-10 items-center pl-3 pointer-events-none"
                >
                    <svg
                        class="w-5 h-5 {disabled
                            ? 'text-gray-300 dark:text-gray-500 cursor-not-allowed'
                            : 'text-gray-400 dark:text-gray-500'}"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
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
                class="block w-full {showIcon ? 'pl-10' : 'pl-3'} {selectedDate
                    ? 'pr-10'
                    : 'pr-3'} py-2.5 border border-gray-200 dark:border-[#212121] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0060B2] focus:border-transparent bg-white dark:bg-[#0a0a0a] backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder:text-gray-500 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer text-sm {disabled
                    ? 'bg-gray-100 dark:bg-gray-900 text-gray-400! dark:text-gray-400! placeholder-gray-400 dark:placeholder:text-gray-500 cursor-not-allowed!'
                    : ''} {error ? 'border-red-500 focus:ring-red-500' : ''}"
                readonly
                onclick={toggleCalendar}
                onfocus={handleFocus}
                onblur={handleBlur}
                aria-invalid={!!error}
                aria-describedby={error && id ? `${id}-error` : undefined}
            />

            {#if selectedDate && !disabled}
                <button
                    type="button"
                    onclick={clearDate}
                    aria-label="Hapus tanggal"
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

        <!-- Calendar Dropdown -->
        {#if isOpen}
            <div
                bind:this={dropdownElement}
                class={"absolute z-50 mt-1 bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#212121] rounded-lg shadow-lg p-2.5 w-64 " +
                    (alignRight ? "right-0" : "left-0")}
            >
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-2.5">
                    <button
                        type="button"
                        onclick={previousMonth}
                        aria-label="Bulan sebelumnya"
                        class="p-1 rounded-md transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <svg
                            class="w-4 h-4 text-gray-600 dark:text-gray-400"
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

                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        {getMonthName(currentMonth)}
                        {currentYear}
                    </h3>

                    <button
                        type="button"
                        onclick={nextMonth}
                        aria-label="Bulan selanjutnya"
                        class="p-1 rounded-md transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <svg
                            class="w-4 h-4 text-gray-600 dark:text-gray-400"
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
                </div>

                <!-- Week Days Header -->
                <div class="grid grid-cols-7 gap-0.5 mb-1.5">
                    {#each ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"] as day}
                        <div
                            class="py-1 text-xs font-medium text-center text-gray-500 dark:text-gray-400"
                        >
                            {day}
                        </div>
                    {/each}
                </div>

                <!-- Calendar Days -->
                <div class="grid grid-cols-7 gap-0.5">
                    {#each generateCalendarDays() as { day, isCurrentMonth, date }}
                        <button
                            type="button"
                            onclick={() => selectDate(date)}
                            disabled={!isCurrentMonth || isDateDisabled(date)}
                            class="relative h-7 w-7 text-xs rounded-md transition-all duration-200 {!isCurrentMonth
                                ? 'text-gray-300 cursor-not-allowed'
                                : isDateDisabled(date)
                                  ? 'text-gray-300 cursor-not-allowed'
                                  : isSelectedDate(date)
                                    ? 'bg-[#0060B2] text-white font-semibold shadow-sm'
                                    : isToday(date)
                                      ? 'bg-[#0060B2]/20 text-[#0060B2] font-medium hover:bg-[#0060B2]/30'
                                      : 'text-gray-700 dark:text-gray-300 hover:bg-[#0060B2]/10 hover:text-[#0060B2]'}"
                        >
                            {day}
                            {#if isToday(date) && !isSelectedDate(date)}
                                <div
                                    class="absolute bottom-0.5 left-1/2 transform -translate-x-1/2 w-0.5 h-0.5 bg-[#0060B2] rounded-full"
                                ></div>
                            {/if}
                        </button>
                    {/each}
                </div>

                <!-- Today Button -->
                <div
                    class="mt-2 pt-2 border-t border-gray-200 dark:border-[#212121]"
                >
                    <button
                        type="button"
                        onclick={() => selectDate(new Date())}
                        class="w-full py-1.5 px-2 text-xs text-[#0060B2] hover:bg-[#0060B2]/10 rounded-md transition-colors duration-200 font-medium"
                    >
                        Hari ini
                    </button>
                </div>
            </div>
        {/if}

        <!-- Hidden input for form submission -->
        {#if name}
            <input type="hidden" {name} {value} />
        {/if}
    </div>
    {#if error}
        <div id="{id}-error" class="mt-1 text-xs text-red-600">{error}</div>
    {/if}
</div>
