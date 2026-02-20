<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Offcanvas from "@/Lib/Admin/Components/Ui/Offcanvas.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
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
        color?: string | null;
    };

    let roles = $derived(($page.props.roles as Role[]) ?? []);
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
        role_id: string | null;
        start_date: string;
        end_date: string;
    };
    let filters = $state<Filters>({
        role_id: ($page.props.filters?.role_id as string | null) ?? null,
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
    function formatHeader(
        dateStr: string,
        hs: Set<string>,
    ): {
        day: string;
        label: string;
        isWeekend: boolean;
        isHoliday: boolean;
    } {
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
    type EditMode = "view" | "edit";
    let mode = $state<EditMode>("edit");
    function toggleMode() {
        mode = mode === "view" ? "edit" : "view";
    }

    type Option = { value: string; label: string };
    let shiftOptions = $derived<Option[]>([
        ...shifts
            .filter((s) => !s.is_off)
            .map((s) => {
                const time =
                    s.start_time && s.end_time
                        ? ` (${s.start_time} - ${s.end_time})`
                        : s.start_time
                          ? ` (${s.start_time})`
                          : "";
                return { value: s.id, label: `${s.name}${time}` };
            }),
        {
            value: shifts.find((s) => s.is_off)?.id ?? "Libur",
            label: "Libur",
        },
    ]);

    let offShiftId = $derived<string | null>(
        shifts.find((s) => s.is_off)?.id ?? null,
    );
    let fullTimeShiftOptions = $derived<Option[]>(
        shifts
            .filter((s) => !s.is_off)
            .map((s) => {
                const time =
                    s.start_time && s.end_time
                        ? ` (${s.start_time} - ${s.end_time})`
                        : s.start_time
                          ? ` (${s.start_time})`
                          : "";
                return { value: s.id, label: `${s.name}${time}` };
            }),
    );

    let localMap = $state<Record<string, Record<string, string>>>({});
    function initLocalMap() {
        const m: Record<string, Record<string, string>> = {};
        for (const e of employees) {
            const row: Record<string, string> = {};
            for (const dt of dates) {
                const v = scheduleMap[e.id]?.[dt] ?? null;
                row[dt] = v ?? offShiftId ?? "Libur";
            }
            m[e.id] = row;
        }
        localMap = m;
    }
    $effect(() => {
        employees;
        dates;
        scheduleMap;
        initLocalMap();
    });

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.role_id) params.set("role_id", filters.role_id);
        if (filters.start_date) params.set("start_date", filters.start_date);
        if (filters.end_date) params.set("end_date", filters.end_date);
        params.set("mode", mode);
        router.get(
            "/schedules?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function formatDateLong(dt: string): string {
        const d = new Date(dt + "T00:00:00");
        const day = String(d.getDate()).padStart(2, "0");
        const month = monthNameShort(d.getMonth());
        const year = d.getFullYear();
        return `${day} ${month} ${year}`;
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

    let saving = $state(false);
    function saveSchedules() {
        const entries: Array<{
            user_id: string;
            date: string;
            shift_id: string | null;
        }> = [];
        for (const e of employees) {
            for (const dt of dates) {
                const initial = scheduleMap[e.id]?.[dt] ?? null;
                const current = localMap[e.id]?.[dt] ?? offShiftId ?? "Libur";
                const normalized =
                    current === (offShiftId ?? "Libur")
                        ? (offShiftId ?? null)
                        : current;
                const same = initial === normalized;
                if (!same) {
                    entries.push({
                        user_id: e.id,
                        date: dt,
                        shift_id: normalized,
                    });
                }
            }
        }
        if (entries.length === 0) return;
        saving = true;
        router.post(
            "/schedules",
            {
                role_id: filters.role_id,
                start_date: filters.start_date,
                end_date: filters.end_date,
                entries,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    saving = false;
                },
            },
        );
    }

    let batchOpen = $state(false);
    let batchLoading = $state(false);
    let batchSelected: string[] = $state([]);
    let batchSearch = $state("");
    let batchStartDate = $state<string>("");
    let batchEndDate = $state<string>("");
    let batchShift = $state<string>("");
    let filteredEmployees = $derived(
        employees.filter((e) =>
            (e.name || "").toLowerCase().includes(batchSearch.toLowerCase()),
        ),
    );
    let allSelected = $derived(
        filteredEmployees.length > 0 &&
            filteredEmployees.every((e) => batchSelected.includes(e.id)),
    );
    function openBatch() {
        batchOpen = true;
        batchSelected = [];
        batchSearch = "";
        batchStartDate =
            filters.start_date || new Date().toISOString().slice(0, 10);
        batchEndDate = filters.end_date || addDays(batchStartDate, 6);
        batchShift = shiftOptions[0]?.value ?? offShiftId ?? "Libur";
    }
    function toggleBatchSelect(id: string, checked: boolean) {
        const set = new Set(batchSelected);
        if (checked) {
            set.add(id);
        } else {
            set.delete(id);
        }
        batchSelected = Array.from(set);
    }
    function dateRange(start: string, end: string): string[] {
        const res: string[] = [];
        const sd = new Date(start + "T00:00:00");
        const ed = new Date(end + "T00:00:00");
        const d = new Date(sd);
        while (d.getTime() <= ed.getTime()) {
            res.push(formatIso(d));
            d.setDate(d.getDate() + 1);
        }
        return res;
    }
    function applyBatch() {
        if (!batchStartDate || !batchEndDate) return;
        if (batchSelected.length === 0) return;
        const datesAll = dateRange(batchStartDate, batchEndDate);
        const normalizedShift =
            batchShift === (offShiftId ?? "Libur") || batchShift === "Libur"
                ? (offShiftId ?? null)
                : batchShift;
        const entries: Array<{
            user_id: string;
            date: string;
            shift_id: string | null;
        }> = [];
        for (const uid of batchSelected) {
            for (const dt of datesAll) {
                entries.push({
                    user_id: uid,
                    date: dt,
                    shift_id: normalizedShift,
                });
            }
        }
        if (entries.length === 0) return;
        batchLoading = true;
        router.post(
            "/schedules",
            {
                role_id: filters.role_id,
                start_date: batchStartDate,
                end_date: batchEndDate,
                entries,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    batchLoading = false;
                    batchOpen = false;
                },
            },
        );
    }

    function getCellValue(userId: string, date: string): string {
        const row = localMap[userId];
        const v = row ? row[date] : undefined;
        return v ?? offShiftId ?? "Libur";
    }
    function setCellValue(userId: string, date: string, value: string) {
        const row = localMap[userId] ?? {};
        row[date] = value;
        localMap[userId] = row;
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
        const day = d.getDay(); // 0 Sun .. 6 Sat
        const diff = day === 0 ? -6 : 1 - day; // move to Monday
        d.setDate(d.getDate() + diff);
        return formatIso(d);
    }
    function endOfWeekFrom(startDateStr: string): string {
        return addDays(startDateStr, 6);
    }
    function dayNum(label: string): string {
        const parts = label.split(" ");
        return parts[0] ?? label;
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
    function prevPeriod() {
        if (viewMode === "month") {
            const r = monthRangeFrom(
                filters.start_date || new Date().toISOString().slice(0, 10),
            );
            const d = new Date(r.start + "T00:00:00");
            d.setMonth(d.getMonth() - 1);
            const start = formatIso(new Date(d.getFullYear(), d.getMonth(), 1));
            const end = formatIso(
                new Date(d.getFullYear(), d.getMonth() + 1, 0),
            );
            filters.start_date = start;
            filters.end_date = end;
            applyFilters();
        } else {
            prevWeek();
        }
    }
    function nextPeriod() {
        if (viewMode === "month") {
            const r = monthRangeFrom(
                filters.start_date || new Date().toISOString().slice(0, 10),
            );
            const d = new Date(r.start + "T00:00:00");
            d.setMonth(d.getMonth() + 1);
            const start = formatIso(new Date(d.getFullYear(), d.getMonth(), 1));
            const end = formatIso(
                new Date(d.getFullYear(), d.getMonth() + 1, 0),
            );
            filters.start_date = start;
            filters.end_date = end;
            applyFilters();
        } else {
            nextWeek();
        }
    }

    let shiftShortLabelMap = $derived<Record<string, string>>(
        (() => {
            const m: Record<string, string> = {};
            for (const s of shifts) {
                m[s.id] = s.name;
            }
            m[offShiftId ?? "Libur"] = "Libur";
            return m;
        })(),
    );

    const colorStyles: Record<string, string> = {
        indigo: "bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800",
        purple: "bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800",
        blue: "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800",
        green: "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800",
        red: "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800",
        orange: "bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800",
        pink: "bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-800",
        teal: "bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800",
        cyan: "bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800",
        yellow: "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800",
        libur: "bg-gray-50 dark:bg-gray-800/50 text-gray-400 dark:text-gray-500 border-gray-100 dark:border-gray-700",
    };

    const colorTextStyles: Record<string, string> = {
        indigo: "text-indigo-700 dark:text-indigo-300",
        purple: "text-purple-700 dark:text-purple-300",
        blue: "text-blue-700 dark:text-blue-300",
        green: "text-green-700 dark:text-green-300",
        red: "text-red-700 dark:text-red-300",
        orange: "text-orange-700 dark:text-orange-300",
        pink: "text-pink-700 dark:text-pink-300",
        teal: "text-teal-700 dark:text-teal-300",
        cyan: "text-cyan-700 dark:text-cyan-300",
        yellow: "text-yellow-700 dark:text-yellow-300",
        libur: "text-gray-300 dark:text-gray-600",
    };

    function getShiftStyle(shiftId: string) {
        if (shiftId === (offShiftId ?? "Libur") || shiftId === "Libur") {
            return colorStyles.libur;
        }
        const s = getShiftById(shiftId);
        let key = s?.color ?? "green";
        if (key === "amber") {
            key = "yellow";
        }
        return colorStyles[key] ?? colorStyles.green;
    }

    function getShiftTextClass(shiftId: string) {
        if (shiftId === (offShiftId ?? "Libur") || shiftId === "Libur") {
            return colorTextStyles.libur;
        }
        const s = getShiftById(shiftId);
        let key = s?.color ?? "green";
        if (key === "amber") {
            key = "yellow";
        }
        return colorTextStyles[key] ?? colorTextStyles.green;
    }

    function getShiftById(id: string) {
        return shifts.find((s) => s.id === id);
    }

    let drawerOpen = $state(false);
    type DrawerMode = "cell" | "row";
    let drawerMode = $state<DrawerMode>("cell");
    let drawerUserId = $state<string | null>(null);
    let drawerDate = $state<string | null>(null);
    let drawerShift = $state<string>("");
    function openDrawer(userId: string, date: string) {
        drawerMode = "cell";
        drawerUserId = userId;
        drawerDate = date;
        drawerShift = getCellValue(userId, date);
        drawerOpen = true;
    }
    function openRowDrawer(userId: string) {
        drawerMode = "row";
        drawerUserId = userId;
        drawerDate = null;
        drawerShift = fullTimeShiftOptions[0]?.value ?? "";
        drawerOpen = true;
    }
    function closeDrawer() {
        drawerOpen = false;
    }
    function applyDrawer() {
        const uid = drawerUserId;
        const dt = drawerDate;
        if (uid) {
            if (drawerMode === "cell" && dt) {
                setCellValue(uid, dt, drawerShift);
            } else if (drawerMode === "row") {
                for (const dtt of dates) {
                    setCellValue(uid, dtt, drawerShift);
                }
            }
        }
        drawerOpen = false;
    }
</script>

<svelte:head>
    <title>Jadwal Karyawan | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="pb-6 space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Jadwal Karyawan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola data jadwal karyawan
            </p>
        </div>
        <div class="flex flex-wrap gap-3 items-center">
            {#if mode === "edit"}
                <Button
                    variant="success"
                    icon="fa-solid fa-save"
                    onclick={saveSchedules}
                    loading={saving}>Simpan Jadwal</Button
                >
                <Button
                    variant="warning"
                    icon="fa-solid fa-layer-group"
                    onclick={openBatch}>Batch Jadwal</Button
                >
                <Button
                    variant="secondary"
                    icon="fa-solid fa-eye"
                    onclick={toggleMode}>Ubah ke View</Button
                >
            {:else}
                <Button
                    variant="secondary"
                    icon="fa-solid fa-eye"
                    onclick={toggleMode}>Ubah ke Edit</Button
                >
            {/if}
        </div>
    </header>

    <Card title="Filter & Periode">
        {#snippet actions()}
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
        {/snippet}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <Select
                id="role"
                name="role_id"
                label="Role"
                options={[
                    { value: "", label: "Semua Role" },
                    ...roles.map((r) => ({ value: r.id, label: r.name })),
                ]}
                bind:value={filters.role_id as any}
                onchange={(v) => {
                    const vv = String(v);
                    filters.role_id = vv === "" ? null : vv;
                }}
            />
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
            {formatHeader(filters.start_date, holidaySet).label} - {formatHeader(
                filters.end_date,
                holidaySet,
            ).label}
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
                        <th
                            class="px-6 py-3 text-left border border-gray-200 sticky-col dark:border-gray-700"
                        >
                            <span
                                class="text-xs font-medium tracking-wide text-gray-700 uppercase dark:text-gray-300"
                                >Nama / Role</span
                            >
                        </th>
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
                                    <span
                                        class={"block text-xs font-semibold uppercase " +
                                            (item.h.isHoliday ||
                                            item.h.isWeekend
                                                ? "text-red-400 dark:text-red-300"
                                                : "text-gray-400 dark:text-gray-500")}
                                        >{item.h.day}</span
                                    >
                                    <span
                                        class={"block text-sm font-bold uppercase " +
                                            (item.h.isHoliday ||
                                            item.h.isWeekend
                                                ? "text-red-600 dark:text-red-300"
                                                : "text-gray-800 dark:text-white")}
                                        >{item.h.label}</span
                                    >
                                {/if}
                            </th>
                        {/each}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    {#if employees.length}
                        {#each employees as e}
                            <tr>
                                <td
                                    class="px-6 py-4 whitespace-nowrap border border-gray-200 sticky-col dark:border-gray-700"
                                >
                                    <div class="flex items-center">
                                        <div class="ml-0">
                                            <div
                                                class="text-sm font-bold text-gray-900 dark:text-white"
                                            >
                                                {e.name}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {e.role?.name || "-"}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 items-center mt-2">
                                        {#if mode === "edit"}
                                            <Button
                                                variant="link"
                                                icon="fa-solid fa-pencil"
                                                onclick={() =>
                                                    openRowDrawer(e.id)}
                                            >
                                                Terapkan 1 baris
                                            </Button>
                                        {/if}
                                    </div>
                                </td>
                                {#each dates as dt}
                                    {@const val = getCellValue(e.id, dt)}
                                    {@const label =
                                        shiftShortLabelMap[val] ?? "Libur"}
                                    {@const style = getShiftStyle(val)}
                                    {@const shift = getShiftById(val)}
                                    {@const time =
                                        shift && !shift.is_off
                                            ? shift.start_time && shift.end_time
                                                ? `${shift.start_time} - ${shift.end_time}`
                                                : (shift.start_time ?? "")
                                            : ""}
                                    <td
                                        class={"p-2 relative align-top h-17.5 text-center border border-gray-200 dark:border-gray-700"}
                                    >
                                        {#if mode === "view"}
                                            <div
                                                class="h-full w-full p-1 flex flex-col justify-center items-center text-center gap-0.5"
                                            >
                                                {#if val === (offShiftId ?? "Libur") || val === "Libur"}
                                                    <div
                                                        class="text-base font-bold text-gray-300 dark:text-gray-600"
                                                    >
                                                        Libur
                                                    </div>
                                                {:else}
                                                    <div
                                                        class={"text-base font-medium leading-none " +
                                                            getShiftTextClass(
                                                                val,
                                                            )}
                                                    >
                                                        {time}
                                                    </div>
                                                {/if}
                                            </div>
                                        {:else}
                                            <div
                                                class="h-full w-full p-1 flex flex-col justify-center items-center text-center gap-0.5 transition-all"
                                            >
                                                {#if val === (offShiftId ?? "Libur") || val === "Libur"}
                                                    <button
                                                        type="button"
                                                        class={"inline-flex items-center gap-1 font-bold underline underline-offset-2 bg-transparent p-0 m-0 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm " +
                                                            getShiftTextClass(
                                                                val,
                                                            )}
                                                        aria-label="Edit jadwal hari ini"
                                                        onclick={() =>
                                                            openDrawer(
                                                                e.id,
                                                                dt,
                                                            )}
                                                    >
                                                        <i
                                                            class="fa-regular fa-pen-to-square"
                                                        ></i>
                                                        <span>Libur</span>
                                                    </button>
                                                {:else}
                                                    <button
                                                        type="button"
                                                        class={"inline-flex items-center gap-1 font-medium leading-none underline underline-offset-2 bg-transparent p-0 m-0 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm " +
                                                            getShiftTextClass(
                                                                val,
                                                            )}
                                                        aria-label="Edit jadwal hari ini"
                                                        onclick={() =>
                                                            openDrawer(
                                                                e.id,
                                                                dt,
                                                            )}
                                                    >
                                                        <i
                                                            class="fa-regular fa-pen-to-square"
                                                        ></i>
                                                        <span>{time}</span>
                                                    </button>
                                                {/if}
                                            </div>
                                        {/if}
                                    </td>
                                {/each}
                            </tr>
                        {/each}
                    {:else}
                        <tr>
                            <td
                                colspan={1 + dates.length}
                                class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >Tidak ada data</td
                            >
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
    </div>

    <Offcanvas
        bind:isOpen={drawerOpen}
        size="lg"
        position="right"
        title={drawerMode === "cell" ? "Edit Jadwal" : "Terapkan 1 baris"}
        onClose={closeDrawer}
    >
        {#snippet children()}
            {#if drawerUserId}
                <div class="space-y-4">
                    {#if drawerMode === "cell" && drawerDate}
                        <div class="grid grid-cols-1 gap-2">
                            <div
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Tanggal
                            </div>
                            <div
                                class="text-base font-semibold text-gray-900 dark:text-white"
                            >
                                {formatHeader(drawerDate, holidaySet).label}
                            </div>
                        </div>
                    {/if}
                    {#if drawerMode === "row"}
                        <div class="grid grid-cols-1 gap-2">
                            <div
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Rentang tanggal
                            </div>
                            <div
                                class="text-base font-semibold text-gray-900 dark:text-white"
                            >
                                {formatHeader(filters.start_date, holidaySet)
                                    .label} - {formatHeader(
                                    filters.end_date,
                                    holidaySet,
                                ).label}
                            </div>
                        </div>
                    {/if}
                    <div class="grid grid-cols-1 gap-2">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Karyawan
                        </div>
                        <div
                            class="text-base font-semibold text-gray-900 dark:text-white"
                        >
                            {employees.find((x) => x.id === drawerUserId)?.name}
                        </div>
                    </div>
                    {#if drawerMode === "cell"}
                        <Select
                            id="drawer_shift"
                            name="drawer_shift"
                            label="Shift"
                            options={shiftOptions}
                            bind:value={drawerShift}
                        />
                    {:else}
                        <Select
                            id="drawer_shift_row"
                            name="drawer_shift_row"
                            label="Shift untuk baris penuh"
                            options={fullTimeShiftOptions}
                            bind:value={drawerShift}
                        />
                    {/if}
                </div>
            {/if}
        {/snippet}
        {#snippet footerSlot()}
            <Button variant="secondary" onclick={closeDrawer}>Batal</Button>
            <Button
                variant="primary"
                icon="fa-solid fa-save"
                onclick={applyDrawer}>Simpan</Button
            >
        {/snippet}
    </Offcanvas>
    <Offcanvas
        bind:isOpen={batchOpen}
        size="lg"
        position="right"
        title="Batch Jadwal"
        onClose={() => {
            batchOpen = false;
        }}
    >
        {#snippet children()}
            <div class="space-y-6">
                <div class="space-y-2">
                    <div
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        Pilih Karyawan
                    </div>
                    <TextInput
                        id="batch_search"
                        name="batch_search"
                        placeholder="Cari karyawan..."
                        prefixIcon="fa-solid fa-search"
                        bind:value={batchSearch}
                    />
                    <div class="flex items-center justify-between mt-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Dipilih: {batchSelected.length} / {employees.length}
                        </div>
                        <Checkbox
                            id="batch_select_all"
                            label="Pilih semua"
                            checked={allSelected}
                            onclick={() => {
                                if (allSelected) {
                                    const keep = employees
                                        .filter(
                                            (e) =>
                                                !filteredEmployees.some(
                                                    (fe) => fe.id === e.id,
                                                ),
                                        )
                                        .map((e) => e.id);
                                    batchSelected = keep;
                                } else {
                                    const set = new Set(batchSelected);
                                    for (const e of filteredEmployees)
                                        set.add(e.id);
                                    batchSelected = Array.from(set);
                                }
                            }}
                        />
                    </div>
                    <div
                        class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3 dark:border-gray-700"
                    >
                        {#if filteredEmployees.length > 0}
                            {#each filteredEmployees as e (e.id)}
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <div
                                            class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                                        >
                                            {e.name}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {e.role?.name || "-"}
                                        </div>
                                    </div>
                                    <Checkbox
                                        id={"emp_" + e.id}
                                        checked={batchSelected.includes(e.id)}
                                        onclick={() =>
                                            toggleBatchSelect(
                                                e.id,
                                                !batchSelected.includes(e.id),
                                            )}
                                    />
                                </div>
                            {/each}
                        {:else}
                            <div
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                Tidak ada karyawan yang cocok
                            </div>
                        {/if}
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <DateInput
                        id="batch_start_date"
                        name="batch_start_date"
                        label="Tanggal Mulai"
                        bind:value={batchStartDate}
                    />
                    <DateInput
                        id="batch_end_date"
                        name="batch_end_date"
                        label="Tanggal Selesai"
                        bind:value={batchEndDate}
                    />
                </div>
                <div class="space-y-2">
                    <Select
                        id="batch_shift"
                        name="batch_shift"
                        label="Shift untuk semua hari"
                        options={shiftOptions}
                        bind:value={batchShift}
                    />
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Pilih "Libur" untuk mengosongkan jadwal di rentang
                        tersebut.
                    </div>
                </div>
            </div>
        {/snippet}
        {#snippet footerSlot()}
            <Button variant="secondary" onclick={() => (batchOpen = false)}>
                Batal
            </Button>
            <Button
                variant="primary"
                icon="fa-solid fa-paper-plane"
                onclick={applyBatch}
                loading={batchLoading}
                disabled={batchSelected.length === 0 ||
                    !batchStartDate ||
                    !batchEndDate}>Terapkan Batch</Button
            >
        {/snippet}
    </Offcanvas>
</section>

<style>
    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 2;
        background-color: white;
        box-shadow: 3px 0 5px -2px rgba(0, 0, 0, 0.15);
    }

    :global(.dark) .sticky-col {
        background-color: rgb(31 41 55);
        box-shadow: 3px 0 5px -2px rgba(0, 0, 0, 0.5);
    }

    thead .sticky-col {
        z-index: 3;
        background-color: rgb(249 250 251);
    }

    :global(.dark) thead .sticky-col {
        background-color: rgb(31 41 55);
    }

    .table-container {
        position: relative;
    }
</style>
