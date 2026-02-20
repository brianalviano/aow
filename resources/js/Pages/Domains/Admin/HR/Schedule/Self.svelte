<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type Role = { id: string; name: string };
    type Employee = { id: string; name: string; role: Role | null };
    type Shift = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
    };

    let employees = $derived(($page.props.employees as Employee[]) ?? []);
    let shifts = $derived(($page.props.shifts as Shift[]) ?? []);
    let dates = $derived(($page.props.dates as string[]) ?? []);
    let scheduleMap = $derived(
        ($page.props.scheduleMap as Record<
            string,
            Record<string, string | null>
        >) ?? {},
    );
    let holidays = $derived(
        ($page.props.holidays as Array<{
            date: string;
            name: string;
            is_compulsory: boolean;
        }>) ?? [],
    );

    type Filters = {
        start_date: string;
        end_date: string;
    };
    let filters = $state<Filters>({
        start_date: ($page.props.filters?.start_date as string) ?? "",
        end_date: ($page.props.filters?.end_date as string) ?? "",
    });

    let holidaySet = $derived(new Set(holidays.map((h) => h.date)));

    function monthNameShort(m: number): string {
        const list = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "Mei",
            "Jun",
            "Jul",
            "Agu",
            "Sep",
            "Okt",
            "Nov",
            "Des",
        ];
        return list[m] ?? "";
    }
    function dayShort(d: number): string {
        const list = ["MIN", "SEN", "SEL", "RAB", "KAM", "JUM", "SAB"];
        return list[d] ?? "";
    }
    function formatHeader(dateStr: string, hs: Set<string>) {
        const d = new Date(dateStr + "T00:00:00");
        const day = d.getDay();
        const dayText = dayShort(day);
        const label = `${String(d.getDate()).padStart(2, "0")} ${monthNameShort(d.getMonth())} ${d.getFullYear()}`;
        const isWeekend = day === 0 || day === 6;
        const isHoliday = hs.has(dateStr);
        return { day: dayText, label, isWeekend, isHoliday };
    }
    function addDays(dateStr: string, days: number): string {
        const d = new Date(dateStr + "T00:00:00");
        d.setDate(d.getDate() + days);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
    }
    type HeaderInfo = {
        day: string;
        label: string;
        isWeekend: boolean;
        isHoliday: boolean;
    };
    let dateHeaders = $derived<{ dt: string; h: HeaderInfo }[]>(
        dates.map((dt) => ({ dt, h: formatHeader(dt, holidaySet) })),
    );

    type ViewMode = "week" | "month";
    let viewMode = $state<ViewMode>("week");

    type Option = { value: string; label: string };
    let offShiftId = $derived<string | null>(
        shifts.find((s) => s.is_off)?.id ?? null,
    );
    let shiftTimeLabelMap = $derived<Record<string, string>>(
        (() => {
            const m: Record<string, string> = {};
            for (const s of shifts) {
                const time =
                    s.start_time && s.end_time
                        ? `${s.start_time} - ${s.end_time}`
                        : s.start_time
                          ? `${s.start_time}`
                          : "";
                m[s.id] = s.is_off ? "Libur" : time;
            }
            m[offShiftId ?? "Libur"] = "Libur";
            return m;
        })(),
    );
    const shiftStyles = {
        libur: "bg-gray-50 dark:bg-gray-800/50 text-gray-400 dark:text-gray-500 border-gray-100 dark:border-gray-700",
        default:
            "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800",
    };
    function getShiftStyle(shiftId: string) {
        if (shiftId === (offShiftId ?? "Libur") || shiftId === "Libur") {
            return shiftStyles.libur;
        }
        return shiftStyles.default;
    }
    function dayNum(label: string): string {
        const parts = label.split(" ");
        return parts[0] ?? label;
    }
    function formatIso(d: Date): string {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
    }
    function monthRangeFrom(dateStr: string): { start: string; end: string } {
        const d = new Date(
            (dateStr || new Date().toISOString().slice(0, 10)) + "T00:00:00",
        );
        const start = new Date(d.getFullYear(), d.getMonth(), 1);
        const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        return { start: formatIso(start), end: formatIso(end) };
    }
    function startOfWeekMonday(dateStr: string): string {
        const d = new Date(dateStr + "T00:00:00");
        const day = d.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return formatIso(d);
    }
    function endOfWeekFrom(startDateStr: string): string {
        return addDays(startDateStr, 6);
    }
    function formatDateLong(dt: string): string {
        const d = new Date(dt + "T00:00:00");
        const day = String(d.getDate()).padStart(2, "0");
        const month = monthNameShort(d.getMonth());
        const year = d.getFullYear();
        return `${day} ${month} ${year}`;
    }
    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.start_date) params.set("start_date", filters.start_date);
        if (filters.end_date) params.set("end_date", filters.end_date);
        router.get(
            "/my-schedule?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
    function showMonthly() {
        const r = monthRangeFrom(
            filters.start_date || new Date().toISOString().slice(0, 10),
        );
        filters.start_date = r.start;
        filters.end_date = r.end;
        viewMode = "month";
        applyFilters();
    }
    function showWeekly() {
        const s = startOfWeekMonday(
            filters.start_date || new Date().toISOString().slice(0, 10),
        );
        const e = endOfWeekFrom(s);
        filters.start_date = s;
        filters.end_date = e;
        viewMode = "week";
        applyFilters();
    }
    function prevWeek() {
        filters.start_date = addDays(filters.start_date, -7);
        filters.end_date = addDays(filters.end_date, -7);
        applyFilters();
    }
    function nextWeek() {
        filters.start_date = addDays(filters.start_date, +7);
        filters.end_date = addDays(filters.end_date, +7);
        applyFilters();
    }
</script>

<svelte:head>
    <title>Jadwal Saya | {siteName($page.props.settings)}</title>
    <meta name="robots" content="noindex" />
</svelte:head>

<section class="pb-6 space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Jadwal Saya
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lihat jadwal kerja pribadi
            </p>
        </div>
        <div class="flex flex-wrap gap-3 items-center">
            <Button
                variant="info"
                icon={viewMode === "month"
                    ? "fa-solid fa-calendar-week"
                    : "fa-solid fa-calendar-days"}
                onclick={viewMode === "month" ? showWeekly : showMonthly}
                >{viewMode === "month" ? "Mingguan" : "Bulanan"}</Button
            >
            <Button
                variant="primary"
                onclick={applyFilters}
                icon="fa-solid fa-filter">Terapkan</Button
            >
        </div>
    </header>

    <Card title="Filter & Periode">
        {#snippet children()}
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <DateInput
                    id="start_date"
                    name="start_date"
                    label="Tanggal Mulai"
                    bind:value={filters.start_date}
                />
                <DateInput
                    id="end_date"
                    name="end_date"
                    label="Tanggal Selesai"
                    bind:value={filters.end_date}
                />
            </div>
        {/snippet}
    </Card>

    {#if holidays.length > 0}
        <Card title="Hari Libur">
            {#snippet children()}
                <div class="flex flex-wrap gap-3">
                    {#each holidays as hol}
                        <div
                            class="inline-flex gap-2 items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-50 rounded-full border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800"
                        >
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <span>{formatDateLong(hol.date)}</span>
                            <span class="text-xs opacity-80">- {hol.name}</span>
                        </div>
                    {/each}
                </div>
            {/snippet}
        </Card>
    {/if}

    <div class="flex justify-between items-center">
        <Button
            variant="secondary"
            icon="fa-solid fa-chevron-left"
            onclick={prevWeek}>Minggu sebelumnya</Button
        >
        <div class="font-semibold text-gray-700 uppercase dark:text-gray-300">
            {formatHeader(filters.start_date, holidaySet).label} -
            {formatHeader(filters.end_date, holidaySet).label}
        </div>
        <Button
            variant="secondary"
            icon="fa-solid fa-chevron-right"
            onclick={nextWeek}>Minggu berikutnya</Button
        >
    </div>

    <div
        class="overflow-x-auto rounded-lg border border-gray-200 table-container dark:border-gray-700"
    >
        <div class="min-w-max">
            <table class="custom-table w-full border-collapse">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        {#each dateHeaders as item}
                            <th
                                class={(viewMode === "month"
                                    ? "px-2 py-2"
                                    : "px-4 py-3") +
                                    " text-center border border-gray-200 dark:border-gray-700 " +
                                    (item.h.isWeekend || item.h.isHoliday
                                        ? "text-red-500 dark:text-red-400 bg-red-50/30 dark:bg-red-900/30"
                                        : "")}
                            >
                                {#if viewMode === "month"}
                                    <span
                                        class={"block text-xs font-semibold uppercase " +
                                            (item.h.isHoliday ||
                                            item.h.isWeekend
                                                ? "text-red-400 dark:text-red-300"
                                                : "text-gray-400 dark:text-gray-500")}
                                        >{item.h.day}</span
                                    >
                                    <span
                                        class={"block text-xs font-bold uppercase " +
                                            (item.h.isHoliday ||
                                            item.h.isWeekend
                                                ? "text-red-600 dark:text-red-300"
                                                : "text-gray-800 dark:text-white")}
                                        >{dayNum(item.h.label)}</span
                                    >
                                {:else}
                                    <div class="flex flex-col items-center">
                                        <span
                                            class={"text-xs font-semibold uppercase " +
                                                (item.h.isHoliday ||
                                                item.h.isWeekend
                                                    ? "text-red-400 dark:text-red-300"
                                                    : "text-gray-400 dark:text-gray-500")}
                                            >{item.h.day}</span
                                        >
                                        <span
                                            class={"text-sm font-bold uppercase " +
                                                (item.h.isHoliday ||
                                                item.h.isWeekend
                                                    ? "text-red-600 dark:text-red-300"
                                                    : "text-gray-800 dark:text-white")}
                                            >{item.h.label}</span
                                        >
                                    </div>
                                {/if}
                            </th>
                        {/each}
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    {#each employees as e}
                        <tr class="even:bg-gray-50 dark:even:bg-gray-800/50">
                            {#each dates as dt}
                                {#key `${e.id}-${dt}-${scheduleMap[e.id]?.[dt] ?? offShiftId ?? "Libur"}`}
                                    <td
                                        class="px-6 py-3 border border-gray-200 dark:border-gray-700 text-center"
                                    >
                                        <div
                                            class={"inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg border " +
                                                getShiftStyle(
                                                    scheduleMap[e.id]?.[dt] ??
                                                        offShiftId ??
                                                        "Libur",
                                                )}
                                        >
                                            <i
                                                class={(scheduleMap[e.id]?.[
                                                    dt
                                                ] ??
                                                    offShiftId ??
                                                    "Libur") ===
                                                (offShiftId ?? "Libur")
                                                    ? "fa-regular fa-calendar-times"
                                                    : "fa-regular fa-clock"}
                                            ></i>
                                            <span
                                                class={(scheduleMap[e.id]?.[
                                                    dt
                                                ] ??
                                                    offShiftId ??
                                                    "Libur") ===
                                                (offShiftId ?? "Libur")
                                                    ? "text-gray-600 dark:text-gray-400"
                                                    : "text-green-700 dark:text-green-300"}
                                            >
                                                {shiftTimeLabelMap[
                                                    scheduleMap[e.id]?.[dt] ??
                                                        offShiftId ??
                                                        "Libur"
                                                ]}
                                            </span>
                                        </div>
                                    </td>
                                {/key}
                            {/each}
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </div>
</section>
