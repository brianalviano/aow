<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Offcanvas from "@/Lib/Admin/Components/Ui/Offcanvas.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatDateTimeDisplay,
        formatDateDisplay,
    } from "@/Lib/Admin/Utils/date";

    type Shift = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
    };
    type Schedule = {
        id: string;
        date: string;
        shift: Shift | null;
    } | null;
    type Attendance = {
        id: string;
        check_in_at: string | null;
        check_out_at: string | null;
        status: { value: string; label: string };
        late_duration: number;
        late_info?: string | null;
        check_in_notes: string | null;
        check_out_notes: string | null;
        check_in: {
            lat: number | null;
            long: number | null;
            photo_url: string | null;
        };
        check_out: {
            lat: number | null;
            long: number | null;
            photo_url: string | null;
        };
        schedule: {
            id: string;
            date: string | null;
            shift: Shift | null;
        } | null;
    };
    type TodaySummary = {
        schedule: { id: string; date: string; shift: Shift | null };
        attendance: Attendance | null;
    } | null;
    type ActiveSummary = (Attendance & { is_cross_day: boolean }) | null;

    let schedule = $derived($page.props.schedule as Schedule);
    let today = $derived($page.props.today as TodaySummary);
    let active = $derived($page.props.active as ActiveSummary);
    let attendances = $derived(
        $page.props.attendances as Attendance[] | undefined,
    );
    let meta = $derived(
        $page.props.meta as
            | {
                  current_page: number;
                  per_page: number;
                  total: number;
                  last_page: number;
              }
            | undefined,
    );
    let filters = $state<{ q: string }>({
        q: ($page.props.filters as { q?: string } | undefined)?.q ?? "",
    });
    let selected: Attendance | null = $state(null);
    let showDetailModal = $state(false);

    let geofence = $derived(
        ($page.props.geofence as {
            center_lat: number;
            center_long: number;
            radius_m: number;
        }) ?? { center_lat: 0, center_long: 0, radius_m: 0 },
    );

    function applyFilters() {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        router.get(
            "/absents" + (params.toString() ? `?${params.toString()}` : ""),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    function resetFilters() {
        filters.q = "";
        applyFilters();
    }

    function goToPage(pageNum: number) {
        const params = new URLSearchParams();
        if (filters.q) params.set("q", filters.q);
        params.set("page", String(pageNum));
        router.get(
            "/absents?" + params.toString(),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    function openDetail(a: Attendance) {
        selected = a;
        showDetailModal = true;
    }

    type AttendanceState = { has_active_check_in: boolean } | undefined;
    let attendanceState = $derived(
        $page.props.attendance_state as AttendanceState,
    );
    let hasActiveCheckIn = $derived(
        Boolean(attendanceState?.has_active_check_in),
    );
    function goSmart() {
        if (!canAct) return;
        if (hasActiveCheckIn) {
            router.get("/absents/check-out");
        } else {
            router.get("/absents/check-in");
        }
    }

    function getStatusVariant(
        status: string,
    ): "success" | "warning" | "danger" | "info" | "secondary" {
        const s = status.toLowerCase();
        if (s === "present") return "success";
        if (s === "late") return "warning";
        if (s === "absent") return "danger";
        if (s === "permit") return "info";
        return "secondary";
    }
    import { canCheckout } from "@/Lib/Admin/Composables/attendance";
    type AuthProps = { auth?: { user?: { role?: string | null } } };
    let roleRaw = $derived(
        ($page.props as AuthProps).auth?.user?.role ?? null,
    );
    let isFlexible = $derived(
        (roleRaw ? roleRaw.trim().toLowerCase() : null) === "manager",
    );
    let canCheckoutNow = $derived(() =>
        isFlexible
            ? true
            : canCheckout(
                  active?.schedule?.date ?? null,
                  active?.schedule?.shift ?? null,
              ),
    );
    let canAct = $derived(
        Boolean(
            isFlexible ||
                (schedule && schedule.shift && !schedule.shift.is_off) ||
                hasActiveCheckIn,
        ),
    );

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

    function googleMapsUrl(lat: number, long: number) {
        return `https://www.google.com/maps/search/?api=1&query=${lat},${long}`;
    }
</script>

<svelte:head>
    <title>Absensi | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Absensi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Ringkasan dan riwayat absensi Anda.
            </p>
        </div>
        <div class="flex gap-2">
            {#if true}
                <Button
                    variant={hasActiveCheckIn ? "secondary" : "primary"}
                    icon={hasActiveCheckIn
                        ? "fa-solid fa-door-open"
                        : "fa-solid fa-right-to-bracket"}
                    disabled={!canAct || (hasActiveCheckIn && !canCheckoutNow)}
                    onclick={goSmart}
                >
                    {hasActiveCheckIn ? "Absen Keluar" : "Absen Masuk"}
                </Button>
            {/if}
        </div>
    </header>

    <div
        class="rounded-xl border border-gray-200 bg-white shadow-sm dark:bg-[#131313] dark:border-gray-800 overflow-hidden"
    >
        <div
            class="border-b border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-[#1a1a1a]"
        >
            <div class="flex justify-between items-center">
                <div class="flex gap-3 items-center">
                    <div
                        class="flex justify-center items-center w-9 h-9 text-blue-600 bg-blue-100 rounded-lg dark:bg-blue-900/30 dark:text-blue-400"
                    >
                        <i class="text-lg fa-solid fa-calendar-day"></i>
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-gray-100"
                        >
                            Hari Ini
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {formatDateTimeDisplay(today?.schedule?.date)}
                        </p>
                    </div>
                </div>
                {#if today?.schedule?.shift?.is_overnight}
                    <Badge
                        size="sm"
                        rounded="pill"
                        variant="purple"
                        class="shadow-sm"
                    >
                        {#snippet children()}
                            <i class="mr-1 fa-solid fa-moon"></i> Lintas Hari
                        {/snippet}
                    </Badge>
                {/if}
                {#if (today?.attendance?.late_duration ?? 0) > 0 || today?.attendance?.status?.value === "late"}
                    <Badge
                        size="sm"
                        rounded="pill"
                        variant="warning"
                        class="ml-2 shadow-sm"
                    >
                        {#snippet children()}
                            <i class="mr-1 fa-solid fa-clock"></i> Terlambat
                            {#if today?.attendance?.late_info}
                                <span class="font-normal opacity-90">
                                    ({today.attendance.late_info})
                                </span>
                            {/if}
                        {/snippet}
                    </Badge>
                {/if}
            </div>
        </div>

        <div class="p-4">
            <div class="flex flex-col gap-1 mb-4">
                <span
                    class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                >
                    Jadwal Shift
                </span>
                <div class="flex justify-between items-baseline">
                    <span
                        class="text-lg font-bold text-gray-900 dark:text-white"
                    >
                        {today?.schedule?.shift?.name ?? "-"}
                    </span>
                    <div
                        class="flex gap-2 items-center px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-gray-300"
                    >
                        <i
                            class="text-gray-500 fa-regular fa-clock dark:text-gray-400"
                        ></i>
                        <span>
                            {today?.schedule?.shift?.start_time ?? "-"} - {today
                                ?.schedule?.shift?.end_time ?? "-"}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div
                    class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-3 transition-all
    /* Light Mode Hover */
    hover:border-emerald-200 hover:bg-emerald-50/50
    /* Dark Mode Base */
    dark:border-gray-800 dark:bg-[#0b0b0b]
    /* PERBAIKAN DARK MODE HOVER DI SINI */
    dark:hover:border-emerald-500/30 dark:hover:bg-emerald-500/5 dark:hover:shadow-[0_0_20px_rgba(16,185,129,0.05)]"
                >
                    <div
                        class="flex gap-2 items-center mb-1 text-xs text-gray-500 transition-colors dark:text-gray-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
                    >
                        <i
                            class="text-emerald-500 fa-solid fa-arrow-right-to-bracket"
                        ></i>
                        Check In
                    </div>

                    <div
                        class="relative z-10 font-mono text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-100"
                    >
                        {today?.attendance?.check_in_at
                            ? formatDateTimeDisplay(
                                  today.attendance.check_in_at,
                              )
                            : "--:--"}
                    </div>

                    <i
                        class="absolute -right-1 -bottom-3 text-5xl transition-all duration-300 fa-solid fa-stopwatch text-emerald-500/10 dark:text-emerald-500/5 group-hover:scale-110 group-hover:-rotate-12 group-hover:text-emerald-500/20 dark:group-hover:text-emerald-500/10"
                    >
                    </i>
                </div>

                <div
                    class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-3 transition-all
    /* Light Mode Hover */
    hover:border-rose-200 hover:bg-rose-50/50
    /* Dark Mode Base */
    dark:border-gray-800 dark:bg-[#0b0b0b]
    /* PERBAIKAN DARK MODE HOVER DI SINI */
    dark:hover:border-rose-500/30 dark:hover:bg-rose-500/5 dark:hover:shadow-[0_0_20px_rgba(244,63,94,0.05)]"
                >
                    <div
                        class="flex gap-2 items-center mb-1 text-xs text-gray-500 transition-colors dark:text-gray-400 group-hover:text-rose-600 dark:group-hover:text-rose-400"
                    >
                        <i
                            class="text-rose-500 fa-solid fa-arrow-right-from-bracket"
                        ></i>
                        Check Out
                    </div>

                    <div
                        class="relative z-10 font-mono text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-100"
                    >
                        {today?.attendance?.check_out_at
                            ? formatDateTimeDisplay(
                                  today.attendance.check_out_at,
                              )
                            : "--:--"}
                    </div>

                    <i
                        class="absolute -right-1 -bottom-3 text-5xl transition-all duration-300 fa-solid fa-flag-checkered text-rose-500/10 dark:text-rose-500/5 group-hover:scale-110 group-hover:-rotate-12 group-hover:text-rose-500/20 dark:group-hover:text-rose-500/10"
                    >
                    </i>
                </div>
            </div>
        </div>
    </div>

    <Card title="Riwayat Absensi" bodyWithoutPadding={true}>
        {#snippet children()}
            <div
                class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#131313]"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <TextInput
                            id="q"
                            name="q"
                            label="Pencarian"
                            placeholder="Tanggal, shift, status, catatan..."
                            bind:value={filters.q}
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button variant="primary" onclick={applyFilters}
                            >Terapkan</Button
                        >
                        <Button variant="secondary" onclick={resetFilters}
                            >Reset</Button
                        >
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if attendances && attendances.length}
                            {#each attendances as a}
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {a.schedule?.date
                                                ? formatDateDisplay(
                                                      a.schedule.date,
                                                  )
                                                : "-"}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {a.schedule?.shift?.name ?? "-"}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {a.check_in_at
                                                ? formatDateTimeDisplay(
                                                      a.check_in_at,
                                                  )
                                                : "-"}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {a.check_out_at
                                                ? formatDateTimeDisplay(
                                                      a.check_out_at,
                                                  )
                                                : "-"}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getStatusVariant(
                                                a.status.value,
                                            )}
                                        >
                                            {#snippet children()}
                                                {a.status.label}
                                                {#if a.late_info}
                                                    ({a.late_info})
                                                {/if}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-eye"
                                                onclick={() => openDetail(a)}
                                                >Detail</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="7"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                    >Tidak ada data</td
                                >
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
        {#snippet footer()}
            {#if meta}
                <Pagination
                    currentPage={meta.current_page}
                    totalPages={meta.last_page}
                    totalItems={meta.total}
                    itemsPerPage={meta.per_page}
                    onPageChange={goToPage}
                    showItemsPerPage={false}
                />
            {/if}
        {/snippet}
    </Card>
</section>

<Offcanvas
    bind:isOpen={showDetailModal}
    size="lg"
    position="right"
    title="Detail Absensi"
    onClose={() => (showDetailModal = false)}
>
    {#snippet children()}
        {#if selected}
            <div class="space-y-6">
                <!-- Info Utama -->
                <div
                    class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-800"
                >
                    <div>
                        <div class="mb-1 text-xs text-gray-500">Tanggal</div>
                        <div
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {selected.schedule?.date
                                ? formatDateDisplay(selected.schedule.date)
                                : "-"}
                        </div>
                    </div>
                    <div>
                        <div class="mb-1 text-xs text-gray-500">Status</div>
                        <Badge
                            size="sm"
                            rounded="pill"
                            variant={getStatusVariant(selected.status.value)}
                        >
                            {#snippet children()}
                                {selected?.status.label}
                                {#if selected?.late_info}
                                    ({selected?.late_info})
                                {/if}
                            {/snippet}
                        </Badge>
                    </div>
                </div>

                <!-- Timeline Check In / Out -->
                <div
                    class="relative pl-4 ml-2 space-y-8 border-l-2 border-gray-200 dark:border-gray-700"
                >
                    <!-- Check In -->
                    <div class="relative">
                        <div
                            class="absolute -left-5.25 top-0 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-900"
                        ></div>
                        <h4
                            class="flex gap-2 items-center mb-3 font-bold text-emerald-600"
                        >
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Check
                            In
                        </h4>

                        {#if selected.check_in_at}
                            <div class="space-y-3">
                                <div>
                                    <div class="text-xs text-gray-500">
                                        Waktu
                                    </div>
                                    <div
                                        class="font-mono text-base font-semibold dark:text-white"
                                    >
                                        {formatDateTimeDisplay(
                                            selected.check_in_at,
                                        )}
                                    </div>
                                </div>

                                {#if selected.check_in_notes}
                                    <div>
                                        <div class="text-xs text-gray-500">
                                            Catatan
                                        </div>
                                        <div
                                            class="p-2 text-sm text-gray-700 bg-gray-50 rounded dark:text-gray-300 dark:bg-gray-800"
                                        >
                                            {selected.check_in_notes}
                                        </div>
                                    </div>
                                {/if}

                                {#if selected.check_in.lat && selected.check_in.long}
                                    {@const distIn = haversineMeters(
                                        geofence.center_lat,
                                        geofence.center_long,
                                        selected.check_in.lat,
                                        selected.check_in.long,
                                    )}
                                    <div>
                                        <a
                                            href={googleMapsUrl(
                                                selected.check_in.lat,
                                                selected.check_in.long,
                                            )}
                                            target="_blank"
                                            class="flex gap-1 items-center text-xs text-blue-600 hover:underline"
                                        >
                                            <i
                                                class="fa-solid fa-map-location-dot"
                                            ></i> Lihat Lokasi
                                        </a>
                                        <div class="mt-1 text-xs">
                                            <span
                                                class="font-medium text-gray-500"
                                                >Jarak:</span
                                            >
                                            <span
                                                class={distIn >
                                                geofence.radius_m
                                                    ? "text-red-600 font-bold"
                                                    : "text-green-600 font-bold"}
                                            >
                                                {Math.round(distIn)}m
                                            </span>
                                            {#if distIn > geofence.radius_m}
                                                <span class="ml-1 text-red-500"
                                                    >(Diluar radius)</span
                                                >
                                            {/if}
                                        </div>
                                    </div>
                                {/if}

                                {#if selected.check_in.photo_url}
                                    <div class="mt-2">
                                        <div class="mb-1 text-xs text-gray-500">
                                            Foto
                                        </div>
                                        <img
                                            src={selected.check_in.photo_url}
                                            alt="Check In"
                                            class="object-cover w-full max-h-64 rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
                                        />
                                    </div>
                                {/if}
                            </div>
                        {:else}
                            <div class="text-sm italic text-gray-400">
                                Belum melakukan check in
                            </div>
                        {/if}
                    </div>

                    <!-- Check Out -->
                    <div class="relative">
                        <div
                            class="absolute -left-5.25 top-0 w-4 h-4 rounded-full bg-rose-500 border-2 border-white dark:border-gray-900"
                        ></div>
                        <h4
                            class="flex gap-2 items-center mb-3 font-bold text-rose-600"
                        >
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Check Out
                        </h4>

                        {#if selected.check_out_at}
                            <div class="space-y-3">
                                <div>
                                    <div class="text-xs text-gray-500">
                                        Waktu
                                    </div>
                                    <div
                                        class="font-mono text-base font-semibold dark:text-white"
                                    >
                                        {formatDateTimeDisplay(
                                            selected.check_out_at,
                                        )}
                                    </div>
                                </div>

                                {#if selected.check_out_notes}
                                    <div>
                                        <div class="text-xs text-gray-500">
                                            Catatan
                                        </div>
                                        <div
                                            class="p-2 text-sm text-gray-700 bg-gray-50 rounded dark:text-gray-300 dark:bg-gray-800"
                                        >
                                            {selected.check_out_notes}
                                        </div>
                                    </div>
                                {/if}

                                {#if selected.check_out.lat && selected.check_out.long}
                                    {@const distOut = haversineMeters(
                                        geofence.center_lat,
                                        geofence.center_long,
                                        selected.check_out.lat,
                                        selected.check_out.long,
                                    )}
                                    <div>
                                        <a
                                            href={googleMapsUrl(
                                                selected.check_out.lat,
                                                selected.check_out.long,
                                            )}
                                            target="_blank"
                                            class="flex gap-1 items-center text-xs text-blue-600 hover:underline"
                                        >
                                            <i
                                                class="fa-solid fa-map-location-dot"
                                            ></i> Lihat Lokasi
                                        </a>
                                        <div class="mt-1 text-xs">
                                            <span
                                                class="font-medium text-gray-500"
                                                >Jarak:</span
                                            >
                                            <span
                                                class={distOut >
                                                geofence.radius_m
                                                    ? "text-red-600 font-bold"
                                                    : "text-green-600 font-bold"}
                                            >
                                                {Math.round(distOut)}m
                                            </span>
                                            {#if distOut > geofence.radius_m}
                                                <span class="ml-1 text-red-500"
                                                    >(Diluar radius)</span
                                                >
                                            {/if}
                                        </div>
                                    </div>
                                {/if}

                                {#if selected.check_out.photo_url}
                                    <div class="mt-2">
                                        <div class="mb-1 text-xs text-gray-500">
                                            Foto
                                        </div>
                                        <img
                                            src={selected.check_out.photo_url}
                                            alt="Check Out"
                                            class="object-cover w-full max-h-64 rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
                                        />
                                    </div>
                                {/if}
                            </div>
                        {:else}
                            <div class="text-sm italic text-gray-400">
                                Belum melakukan check out
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}
    {/snippet}
    {#snippet footerSlot()}
        <Button variant="secondary" onclick={() => (showDetailModal = false)}
            >Tutup</Button
        >
    {/snippet}
</Offcanvas>
