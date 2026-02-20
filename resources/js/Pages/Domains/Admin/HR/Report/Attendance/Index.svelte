<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Offcanvas from "@/Lib/Admin/Components/Ui/Offcanvas.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type CellData = {
        status:
            | "hadir"
            | "telat"
            | "izin"
            | "alpha"
            | "libur"
            | "pending"
            | "off";
        label: string;
        color: string;
        check_in?: string;
        check_out?: string;
        late_duration?: number;
        is_night: boolean;
        attendance?: any;
        schedule?: any;
    };

    type EmployeeReport = {
        employee: {
            id: string;
            name: string;
            role: { name: string } | null;
            avatar: string | null;
        };
        days: Record<string, CellData>;
    };

    let report = $derived(($page.props.report as EmployeeReport[]) ?? []);
    let dates = $derived(($page.props.dates as string[]) ?? []);
    let geofence = $derived(
        ($page.props.geofence as {
            center_lat: number;
            center_long: number;
            radius_m: number;
        }) ?? { center_lat: 0, center_long: 0, radius_m: 0 },
    );
    let holidays = $derived(
        ($page.props.holidays as Array<{
            date: string;
            name: string;
            is_compulsory: boolean;
        }>) ?? [],
    );

    let holidaySet = $derived(new Set(holidays.map((h) => h.date)));

    type Filters = {
        start_date: string;
        end_date: string;
    };
    let filters = $state<Filters>({
        start_date: ($page.props.filters?.start_date as string) ?? "",
        end_date: ($page.props.filters?.end_date as string) ?? "",
    });

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
    ): { day: string; label: string; isWeekend: boolean; isHoliday: boolean } {
        const d = new Date(dateStr + "T00:00:00");
        const day = d.getDay();
        const dayText = dayShort(day);
        const label = `${String(d.getDate()).padStart(2, "0")} ${monthNameShort(d.getMonth())}`; // Removed Year to save space
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

    let dateHeaders = $derived(
        dates.map((dt) => ({ dt, h: formatHeader(dt, holidaySet) })),
    );

    // Date Range Display
    let dateRangeLabel = $derived.by(() => {
        if (!dates.length) return "";
        const first = new Date(dates[0] + "T00:00:00");
        const last = new Date(dates[dates.length - 1] + "T00:00:00");
        const f = `${dayShort(first.getDay())}, ${first.getDate()} ${monthNameShort(first.getMonth())}`;
        const l = `${dayShort(last.getDay())}, ${last.getDate()} ${monthNameShort(last.getMonth())} ${last.getFullYear()}`;
        return `${f} - ${l}`;
    });
    let viewMode = $state<"matrix" | "table">("matrix");
    type TableRow = {
        employee: EmployeeReport["employee"];
        date: string;
        status: CellData["status"];
        label: string;
        check_in: string | undefined;
        check_out: string | undefined;
        schedule?: any;
        cell: CellData;
        itemRef: EmployeeReport;
    };
    let tableRows = $derived.by<TableRow[]>(() => {
        const rows: TableRow[] = [];
        for (const item of report) {
            for (const date of dates) {
                const cell = item.days[date];
                if (!cell) continue;
                if (cell.status === "off" || cell.status === "pending")
                    continue;
                rows.push({
                    employee: item.employee,
                    date,
                    status: cell.status,
                    label: cell.label,
                    check_in: cell.check_in,
                    check_out: cell.check_out,
                    schedule: cell.schedule,
                    cell,
                    itemRef: item,
                });
            }
        }
        return rows.sort((a, b) => {
            const ad = new Date(a.date + "T00:00:00").getTime();
            const bd = new Date(b.date + "T00:00:00").getTime();
            if (ad !== bd) return bd - ad; // tanggal terbaru dulu
            const an = a.employee.name.toLowerCase();
            const bn = b.employee.name.toLowerCase();
            if (an < bn) return -1;
            if (an > bn) return 1;
            return 0;
        });
    });

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.start_date) params.set("start_date", filters.start_date);
        if (filters.end_date) params.set("end_date", filters.end_date);
        router.get(
            "/reports/absent?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function prevPeriod() {
        // Detect if weekly (7 days) or monthly (approx 30)
        const diff =
            new Date(filters.end_date).getTime() -
            new Date(filters.start_date).getTime();
        const days = Math.round(diff / (1000 * 3600 * 24)) + 1;
        const shift = days >= 28 ? 30 : 7; // Simple heuristic

        filters.start_date = addDays(filters.start_date, -shift);
        filters.end_date = addDays(filters.end_date, -shift);
        applyFilters();
    }
    function nextPeriod() {
        const diff =
            new Date(filters.end_date).getTime() -
            new Date(filters.start_date).getTime();
        const days = Math.round(diff / (1000 * 3600 * 24)) + 1;
        const shift = days >= 28 ? 30 : 7;

        filters.start_date = addDays(filters.start_date, +shift);
        filters.end_date = addDays(filters.end_date, +shift);
        applyFilters();
    }

    function setMode(mode: "week" | "month") {
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, "0");
        const d = String(today.getDate()).padStart(2, "0");
        const todayStr = `${y}-${m}-${d}`;

        if (mode === "week") {
            // Current week
            const curr = new Date(todayStr + "T00:00:00");
            const day = curr.getDay(); // 0-6
            const diff = curr.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is sunday
            const monday = new Date(curr.setDate(diff));
            const sunday = new Date(curr.setDate(monday.getDate() + 6));

            filters.start_date = monday.toISOString().split("T")[0] ?? "";
            filters.end_date = sunday.toISOString().split("T")[0] ?? "";
        } else {
            // Current month
            const first = new Date(today.getFullYear(), today.getMonth(), 1);
            const last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            filters.start_date = first.toISOString().split("T")[0] ?? "";
            filters.end_date = last.toISOString().split("T")[0] ?? "";
        }
        applyFilters();
    }

    function exportExcel() {
        const params = new URLSearchParams();
        if (filters.start_date) params.set("start_date", filters.start_date);
        if (filters.end_date) params.set("end_date", filters.end_date);
        const url = params.toString()
            ? `/reports/absent/export?${params.toString()}`
            : "/reports/absent/export";
        window.open(url, "_blank");
    }

    // Styles for statuses
    const statusStyles: Record<string, string> = {
        hadir: "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800",
        telat: "bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800",
        izin: "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800",
        alpha: "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800",
        libur: "bg-gray-50 dark:bg-gray-800/50 text-gray-400 dark:text-gray-500 border-gray-100 dark:border-gray-700",
        pending:
            "bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 border-gray-100 dark:border-gray-700",
        off: "bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 border-gray-100 dark:border-gray-700",
    };

    let drawerOpen = $state(false);
    let selectedAttendance = $state<any>(null);
    let selectedSchedule = $state<any>(null);
    let selectedDate = $state<string | null>(null);
    let selectedEmployee = $state<any>(null);

    function openDrawer(item: EmployeeReport, date: string) {
        const cell = item.days[date];
        selectedEmployee = item.employee;
        selectedDate = date;
        selectedAttendance = cell?.attendance ?? null;
        selectedSchedule = cell?.schedule ?? null;
        drawerOpen = true;
    }

    function closeDrawer() {
        drawerOpen = false;
    }

    function haversineMeters(
        lat1: number,
        lon1: number,
        lat2: number,
        lon2: number,
    ): number {
        const R = 6371e3;
        const φ1 = (lat1 * Math.PI) / 180;
        const φ2 = (lat2 * Math.PI) / 180;
        const Δφ = ((lat2 - lat1) * Math.PI) / 180;
        const Δλ = ((lon2 - lon1) * Math.PI) / 180;
        const a =
            Math.sin(Δφ / 2) ** 2 +
            Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function googleMapsUrl(lat: string, long: string) {
        return `https://www.google.com/maps/search/?api=1&query=${lat},${long}`;
    }

    async function markOnTime() {
        if (!selectedAttendance?.id) return;
        await router.post(
            `/reports/attendance/${selectedAttendance.id}/mark-on-time`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    closeDrawer();
                },
            },
        );
    }
</script>

<svelte:head>
    <title>Laporan Absensi | {siteName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col gap-6">
    <!-- Header & Controls -->
    <div class="flex flex-col gap-4 justify-between items-start md:flex-row">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                Monitoring Absensi Terpusat
            </h1>
            <div class="flex flex-wrap gap-3 mt-2 text-sm mb-2">
                <div
                    class="flex gap-1 items-center text-gray-700 dark:text-gray-300"
                >
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span> Hadir
                </div>
                <div
                    class="flex gap-1 items-center text-gray-700 dark:text-gray-300"
                >
                    <span class="w-3 h-3 bg-orange-500 rounded-full"></span> Terlambat
                </div>
                <div
                    class="flex gap-1 items-center text-gray-700 dark:text-gray-300"
                >
                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span> Izin/Sakit
                </div>
                <div
                    class="flex gap-1 items-center text-gray-700 dark:text-gray-300"
                >
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span> Alpha
                </div>
                <div
                    class="flex gap-1 items-center text-gray-700 dark:text-gray-300"
                >
                    <span
                        class="w-3 h-3 bg-gray-300 rounded-full dark:bg-gray-600"
                    ></span> Libur (Off)
                </div>
            </div>
            <div>
                <Button
                    variant="success"
                    size="sm"
                    icon="fa-solid fa-file-excel"
                    onclick={exportExcel}
                >
                    Export Excel
                </Button>
            </div>
        </div>
        <div class="flex flex-col gap-2 items-stretch md:items-end">
            <div
                class="flex gap-1 p-1 bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700"
            >
                <Button
                    variant={dates.length <= 7 ? "info" : "link"}
                    size="sm"
                    onclick={() => setMode("week")}
                >
                    Mingguan
                </Button>
                <Button
                    variant={dates.length > 7 ? "info" : "link"}
                    size="sm"
                    onclick={() => setMode("month")}
                >
                    Bulanan
                </Button>
            </div>
            <div
                class="flex gap-1 p-1 bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700"
            >
                <Button
                    variant={viewMode === "matrix" ? "info" : "link"}
                    size="sm"
                    onclick={() => (viewMode = "matrix")}
                >
                    Matriks
                </Button>
                <Button
                    variant={viewMode === "table" ? "info" : "link"}
                    size="sm"
                    onclick={() => (viewMode = "table")}
                >
                    Tabel
                </Button>
            </div>
            <div class="flex"></div>
        </div>
    </div>

    <Card
        class="overflow-hidden p-0 border-0 shadow-sm"
        bodyWithoutPadding={true}
    >
        <!-- Navigation -->
        <div
            class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50"
        >
            <Button
                variant="link"
                icon="fa-solid fa-chevron-left"
                onclick={prevPeriod}
            >
                Prev
            </Button>
            <div class="font-bold text-center text-gray-700 dark:text-gray-300">
                {dateRangeLabel}
            </div>
            <Button
                variant="link"
                icon="fa-solid fa-chevron-right"
                onclick={nextPeriod}
            >
                Next
            </Button>
        </div>

        <!-- Table -->
        {#if viewMode === "matrix"}
            <div class="overflow-x-auto relative">
                <table class="w-full text-sm text-left border-collapse">
                    <thead
                        class="text-xs font-semibold text-gray-600 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400"
                    >
                        <tr>
                            <th
                                class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 border-b border-r border-gray-200 dark:border-gray-700 p-4 min-w-50 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]"
                            >
                                Karyawan
                            </th>
                            {#each dateHeaders as { dt, h }}
                                <th
                                    class="border-b border-gray-200 dark:border-gray-700 p-3 text-center min-w-25 {h.isWeekend ||
                                    h.isHoliday
                                        ? 'text-red-500 dark:text-red-400 bg-red-50/30 dark:bg-red-900/30'
                                        : ''}"
                                >
                                    <div class="flex flex-col">
                                        <span>{h.day}</span>
                                        <span
                                            class="font-bold text-gray-400 dark:text-white"
                                            >{h.label}</span
                                        >
                                    </div>
                                </th>
                            {/each}
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-700"
                    >
                        {#each report as item (item.employee.id)}
                            <tr
                                class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50"
                            >
                                <td
                                    class="sticky left-0 z-10 bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 p-4 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]"
                                >
                                    <div class="flex gap-3 items-center">
                                        <div
                                            class="flex overflow-hidden justify-center items-center w-10 h-10 text-sm font-bold text-gray-500 bg-gray-100 rounded-full border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                        >
                                            {#if item.employee.avatar}
                                                <img
                                                    src={item.employee.avatar}
                                                    alt={item.employee.name}
                                                    class="object-cover w-full h-full"
                                                />
                                            {:else}
                                                {item.employee.name
                                                    .substring(0, 2)
                                                    .toUpperCase()}
                                            {/if}
                                        </div>
                                        <div>
                                            <div
                                                class="font-bold text-gray-800 dark:text-gray-200"
                                            >
                                                {item.employee.name}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {item.employee.role?.name ??
                                                    "-"}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                {#each dates as date}
                                    {@const cell = item.days[date] ?? {
                                        status: "off",
                                        label: "-",
                                        color: "bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 border-gray-100 dark:border-gray-700",
                                        is_night: false,
                                    }}
                                    <td
                                        class="p-2 border-l border-gray-50 dark:border-gray-700 relative align-top h-17.5"
                                    >
                                        <button
                                            class="w-full h-full text-left rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            onclick={() =>
                                                openDrawer(item, date)}
                                        >
                                            <div
                                                class="h-full w-full rounded-md border p-1 flex flex-col justify-center items-center text-center gap-0.5 {statusStyles[
                                                    cell.status
                                                ] ??
                                                    statusStyles.off} hover:brightness-95 transition-all"
                                            >
                                                {#if cell.status === "hadir" || cell.status === "telat"}
                                                    <div
                                                        class="text-lg font-bold leading-none"
                                                    >
                                                        {cell.check_in ?? "-"}
                                                    </div>
                                                    <div
                                                        class="text-[10px] font-medium opacity-80 uppercase tracking-wide"
                                                    >
                                                        {cell.label}
                                                    </div>
                                                    {#if cell.is_night}
                                                        <div
                                                            class="text-[9px] bg-indigo-900 text-white px-1.5 rounded-full mt-0.5"
                                                        >
                                                            Mlm
                                                        </div>
                                                    {/if}
                                                {:else if cell.status === "libur" || cell.status === "pending" || cell.status === "off"}
                                                    <div
                                                        class="text-sm font-bold text-gray-300 dark:text-gray-600"
                                                    >
                                                        OFF
                                                    </div>
                                                    <div
                                                        class="text-[10px] text-gray-400 dark:text-gray-500"
                                                    >
                                                        Libur
                                                    </div>
                                                {:else}
                                                    <!-- Alpha, Izin, Sakit -->
                                                    <div
                                                        class="text-xs font-bold"
                                                    >
                                                        {cell.label}
                                                    </div>
                                                    {#if cell.status === "alpha"}
                                                        <div
                                                            class="text-[10px] opacity-75"
                                                        >
                                                            Absen
                                                        </div>
                                                    {/if}
                                                {/if}
                                            </div>
                                        </button>
                                    </td>
                                {/each}
                            </tr>
                        {:else}
                            <tr>
                                <td
                                    colspan={dates.length + 1}
                                    class="p-8 text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data karyawan
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {:else}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Shift</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if tableRows.length}
                            {#each tableRows as row}
                                <tr>
                                    <td>
                                        <div class="flex gap-3 items-center">
                                            <div
                                                class="flex overflow-hidden justify-center items-center w-10 h-10 text-sm font-bold text-gray-500 bg-gray-100 rounded-full border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                            >
                                                {#if row.employee.avatar}
                                                    <img
                                                        src={row.employee
                                                            .avatar}
                                                        alt={row.employee.name}
                                                        class="object-cover w-full h-full"
                                                    />
                                                {:else}
                                                    {row.employee.name
                                                        .substring(0, 2)
                                                        .toUpperCase()}
                                                {/if}
                                            </div>
                                            <div>
                                                <div
                                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                                >
                                                    {row.employee.name}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {row.employee.role?.name ??
                                                        "-"}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatHeader(row.date, holidaySet)
                                                .label}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {row.label}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {row.check_in ?? "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {row.check_out ?? "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {row.schedule?.shift_name ?? "-"}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                onclick={() =>
                                                    openDrawer(
                                                        row.itemRef,
                                                        row.date,
                                                    )}
                                            >
                                                Detail
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="7"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/if}
    </Card>

    <Offcanvas
        bind:isOpen={drawerOpen}
        size="lg"
        position="right"
        title="Detail Harian"
        onClose={closeDrawer}
    >
        {#snippet children()}
            {#if selectedEmployee && selectedDate}
                <div class="space-y-4">
                    <div
                        class="grid grid-cols-1 gap-2 pb-4 border-b dark:border-gray-700"
                    >
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Karyawan
                        </div>
                        <div class="flex gap-3 items-center">
                            <div
                                class="flex overflow-hidden justify-center items-center w-10 h-10 text-sm font-bold text-gray-500 bg-gray-100 rounded-full border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                            >
                                {#if selectedEmployee.avatar}
                                    <img
                                        src={selectedEmployee.avatar}
                                        alt={selectedEmployee.name}
                                        class="object-cover w-full h-full"
                                    />
                                {:else}
                                    {selectedEmployee.name
                                        .substring(0, 2)
                                        .toUpperCase()}
                                {/if}
                            </div>
                            <div>
                                <div
                                    class="text-base font-semibold text-gray-900 dark:text-white"
                                >
                                    {selectedEmployee.name}
                                </div>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {selectedEmployee.role?.name ?? "-"}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Tanggal
                        </div>
                        <div
                            class="text-base font-semibold text-gray-900 dark:text-white"
                        >
                            {formatHeader(selectedDate, holidaySet).label}
                        </div>
                    </div>

                    <div class="space-y-6">
                        {#if selectedSchedule}
                            <div
                                class="p-4 bg-gray-50 rounded-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
                            >
                                <h3
                                    class="mb-3 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Jadwal Shift
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Shift
                                        </div>
                                        <div
                                            class="font-medium text-gray-900 dark:text-white"
                                        >
                                            {selectedSchedule.shift_name ?? "-"}
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Jam Kerja
                                        </div>
                                        <div
                                            class="font-medium text-gray-900 dark:text-white"
                                        >
                                            {selectedSchedule.start_time ?? "-"}
                                            - {selectedSchedule.end_time ?? "-"}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/if}

                        {#if selectedAttendance}
                            <div>
                                <h3
                                    class="pb-2 mb-4 text-lg font-bold text-gray-900 border-b dark:text-white dark:border-gray-700"
                                >
                                    Detail Absensi
                                </h3>

                                <!-- Check In -->
                                <div
                                    class="relative pl-6 mb-6 border-l-2 border-green-200 dark:border-green-800"
                                >
                                    <div
                                        class="absolute -left-2.25 top-0 w-4 h-4 rounded-full bg-green-500 border-2 border-white dark:border-gray-900"
                                    ></div>
                                    <h4
                                        class="mb-2 font-bold text-green-700 dark:text-green-400"
                                    >
                                        Check In
                                    </h4>
                                    <div class="grid grid-cols-1 gap-3">
                                        <div>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                Waktu
                                            </div>
                                            <div
                                                class="font-mono text-base font-semibold text-gray-900 dark:text-gray-100"
                                            >
                                                {selectedAttendance.check_in_at}
                                            </div>
                                        </div>
                                        {#if selectedAttendance.late_duration > 0}
                                            <div>
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300"
                                                >
                                                    Terlambat {selectedAttendance.late_duration}
                                                    menit
                                                </span>
                                            </div>
                                        {/if}
                                        {#if selectedAttendance.check_in_notes}
                                            <div>
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Catatan
                                                </div>
                                                <div
                                                    class="p-2 text-sm text-gray-700 bg-gray-50 rounded dark:text-gray-300 dark:bg-gray-800"
                                                >
                                                    {selectedAttendance.check_in_notes}
                                                </div>
                                            </div>
                                        {/if}
                                        {#if selectedAttendance.check_in_lat && selectedAttendance.check_in_long}
                                            {@const distIn = haversineMeters(
                                                geofence.center_lat,
                                                geofence.center_long,
                                                parseFloat(
                                                    selectedAttendance.check_in_lat,
                                                ),
                                                parseFloat(
                                                    selectedAttendance.check_in_long,
                                                ),
                                            )}
                                            <div>
                                                <a
                                                    href={googleMapsUrl(
                                                        selectedAttendance.check_in_lat,
                                                        selectedAttendance.check_in_long,
                                                    )}
                                                    target="_blank"
                                                    class="flex gap-1 items-center text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    <i
                                                        class="fa-solid fa-map-location-dot"
                                                    ></i> Lihat Lokasi
                                                </a>
                                                <div class="mt-1 text-xs">
                                                    <span
                                                        class="font-medium text-gray-500 dark:text-gray-400"
                                                        >Jarak:</span
                                                    >
                                                    <span
                                                        class={distIn >
                                                        geofence.radius_m
                                                            ? "text-red-600 dark:text-red-400 font-bold"
                                                            : "text-green-600 dark:text-green-400 font-bold"}
                                                    >
                                                        {Math.round(distIn)}m
                                                    </span>
                                                    {#if distIn > geofence.radius_m}
                                                        <span
                                                            class="ml-1 text-red-500 dark:text-red-400"
                                                            >(Diluar radius)</span
                                                        >
                                                    {/if}
                                                </div>
                                            </div>
                                        {/if}
                                        {#if selectedAttendance.check_in_photo_url}
                                            <div class="mt-2">
                                                <div
                                                    class="mb-1 text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Foto
                                                </div>
                                                <img
                                                    src={selectedAttendance.check_in_photo_url}
                                                    alt="Check In"
                                                    class="object-cover w-full max-h-64 rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
                                                />
                                            </div>
                                        {/if}
                                    </div>
                                </div>

                                <!-- Check Out -->
                                <div
                                    class="relative pl-6 mb-6 border-l-2 border-red-200 dark:border-red-800"
                                >
                                    <div
                                        class="absolute -left-2.25 top-0 w-4 h-4 rounded-full bg-red-500 border-2 border-white dark:border-gray-900"
                                    ></div>
                                    <h4
                                        class="mb-2 font-bold text-red-700 dark:text-red-400"
                                    >
                                        Check Out
                                    </h4>
                                    {#if selectedAttendance.check_out_at}
                                        <div class="grid grid-cols-1 gap-3">
                                            <div>
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Waktu
                                                </div>
                                                <div
                                                    class="font-mono text-base font-semibold text-gray-900 dark:text-gray-100"
                                                >
                                                    {selectedAttendance.check_out_at}
                                                </div>
                                            </div>
                                            {#if selectedAttendance.check_out_notes}
                                                <div>
                                                    <div
                                                        class="text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        Catatan
                                                    </div>
                                                    <div
                                                        class="p-2 text-sm text-gray-700 bg-gray-50 rounded dark:text-gray-300 dark:bg-gray-800"
                                                    >
                                                        {selectedAttendance.check_out_notes}
                                                    </div>
                                                </div>
                                            {/if}
                                            {#if selectedAttendance.check_out_lat && selectedAttendance.check_out_long}
                                                {@const distOut =
                                                    haversineMeters(
                                                        geofence.center_lat,
                                                        geofence.center_long,
                                                        parseFloat(
                                                            selectedAttendance.check_out_lat,
                                                        ),
                                                        parseFloat(
                                                            selectedAttendance.check_out_long,
                                                        ),
                                                    )}
                                                <div>
                                                    <a
                                                        href={googleMapsUrl(
                                                            selectedAttendance.check_out_lat,
                                                            selectedAttendance.check_out_long,
                                                        )}
                                                        target="_blank"
                                                        class="flex gap-1 items-center text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                                    >
                                                        <i
                                                            class="fa-solid fa-map-location-dot"
                                                        ></i>
                                                        Lihat Lokasi
                                                    </a>
                                                    <div class="mt-1 text-xs">
                                                        <span
                                                            class="font-medium text-gray-500 dark:text-gray-400"
                                                            >Jarak:</span
                                                        >
                                                        <span
                                                            class={distOut >
                                                            geofence.radius_m
                                                                ? "text-red-600 dark:text-red-400 font-bold"
                                                                : "text-green-600 dark:text-green-400 font-bold"}
                                                        >
                                                            {Math.round(
                                                                distOut,
                                                            )}m
                                                        </span>
                                                        {#if distOut > geofence.radius_m}
                                                            <span
                                                                class="ml-1 text-red-500 dark:text-red-400"
                                                                >(Diluar radius)</span
                                                            >
                                                        {/if}
                                                    </div>
                                                </div>
                                            {/if}
                                            {#if selectedAttendance.check_out_photo_url}
                                                <div class="mt-2">
                                                    <div
                                                        class="mb-1 text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        Foto
                                                    </div>
                                                    <img
                                                        src={selectedAttendance.check_out_photo_url}
                                                        alt="Check Out"
                                                        class="object-cover w-full max-h-64 rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
                                                    />
                                                </div>
                                            {/if}
                                        </div>
                                    {:else}
                                        <div
                                            class="text-sm italic text-gray-400 dark:text-gray-500"
                                        >
                                            Belum melakukan check out
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        {:else}
                            <div
                                class="py-10 text-center text-gray-500 dark:text-gray-400"
                            >
                                <i
                                    class="mb-3 text-4xl text-gray-300 fa-solid fa-user-xmark dark:text-gray-600"
                                ></i>
                                <p>Tidak ada data absensi untuk tanggal ini.</p>
                            </div>
                        {/if}
                    </div>
                </div>
            {/if}
        {/snippet}
        {#snippet footerSlot()}
            {#if selectedAttendance && selectedAttendance.status === "late" && !!selectedAttendance.check_in_notes}
                <Button
                    variant="primary"
                    icon="fa-solid fa-check"
                    onclick={markOnTime}
                >
                    Ubah ke Tepat Waktu
                </Button>
            {/if}
            <Button variant="secondary" onclick={closeDrawer}>Tutup</Button>
        {/snippet}
    </Offcanvas>
</div>
