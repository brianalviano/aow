<script lang="ts">
    import { page, router, Link } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import Chart from "chart.js/auto";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import StatCard from "@/Lib/Admin/Components/Ui/StatCard.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";

    let { role, dashboardData } = $props();
    let user = $derived($page.props.auth?.user);
    type UpcomingSchedule = { date: string; shift_name: string; time: string };
    type RecentAttendanceItem = {
        time: string;
        name: string;
        position: string;
        type: string;
        status_color: string;
        status: string;
    };
    type RecentActivityItem = {
        date: string;
        check_in: string;
        check_out: string;
        status_color: string;
        status: string;
    };
    let upcomingSchedules = $derived(
        (dashboardData?.upcoming_schedules ?? []) as UpcomingSchedule[],
    );
    let recentAttendance = $derived(
        (dashboardData?.recent_attendance ?? []) as RecentAttendanceItem[],
    );
    let recentActivity = $derived(
        (dashboardData?.recent_activity ?? []) as RecentActivityItem[],
    );
    let posRaw = $derived(user?.position ?? null);
    let isFlexible = $derived(
        (posRaw ? posRaw.trim().toLowerCase() : null) === "supervisor",
    );
    let chartCanvas = $state<HTMLCanvasElement>();
    let activeAlertTab = $state<string>("pending_leaves"); // missing, permit, absent, pending_leaves
    $effect(() => {
        activeAlertTab =
            (dashboardData?.alerts?.pending_leaves?.length ?? 0) > 0
                ? "pending_leaves"
                : "missing";
    });
    type Shift = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
    };
    type AttendanceBrief = {
        check_in_at: string | null;
        check_out_at: string | null;
        status: { value: string; label: string };
        late_duration: number;
        late_info?: string | null;
    };
    type TodaySummary = {
        schedule: { id: string; date: string; shift: Shift | null } | null;
        attendance: AttendanceBrief | null;
    } | null;
    let today = $derived($page.props.today as TodaySummary);
    type AttendanceState = { has_active_check_in: boolean } | undefined;
    let attendanceState = $derived(
        $page.props.attendance_state as AttendanceState,
    );
    let hasActiveCheckIn = $derived(
        Boolean(attendanceState?.has_active_check_in),
    );
    let canAct = $derived(
        Boolean(
            isFlexible ||
                (today?.schedule?.shift && !today?.schedule?.shift?.is_off) ||
                hasActiveCheckIn,
        ),
    );
    import { canCheckout } from "@/Lib/Admin/Composables/attendance";
    let canCheckoutNow = $derived(() =>
        isFlexible
            ? true
            : canCheckout(
                  today?.schedule?.date ?? null,
                  today?.schedule?.shift ?? null,
              ),
    );
    function goSmart() {
        if (!canAct) return;
        if (hasActiveCheckIn) {
            router.get("/absents/check-out");
        } else {
            router.get("/absents/check-in");
        }
    }

    function getBadgeVariantFromColor(colorClass: string) {
        if (colorClass.includes("green")) return "success";
        if (colorClass.includes("yellow") || colorClass.includes("orange"))
            return "warning";
        if (colorClass.includes("red")) return "danger";
        if (colorClass.includes("blue")) return "info";
        return "secondary";
    }

    onMount(() => {
        if (["Admin", "Direktur", "Manager"].includes(role) && chartCanvas) {
            // 1. Membuat Context untuk Gradasi Warna
            const ctx = chartCanvas.getContext("2d");
            if (!ctx) return;
            // Gradasi dari Biru (atas) ke Transparan (bawah)
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, "rgba(59, 130, 246, 0.5)"); // Warna awal (lebih pekat)
            gradient.addColorStop(1, "rgba(59, 130, 246, 0.0)"); // Warna akhir (transparan)

            new Chart(chartCanvas, {
                type: "line",
                data: {
                    labels: dashboardData.charts.trend.labels,
                    datasets: [
                        {
                            label: "Kehadiran",
                            data: dashboardData.charts.trend.data,
                            // Styling Garis
                            borderColor: "#3B82F6", // Tailwind Blue-500
                            borderWidth: 3, // Garis sedikit lebih tebal
                            tension: 0.4, // Lebih melengkung (smooth)

                            // Styling Area Bawah (Gradasi)
                            backgroundColor: gradient,
                            fill: true,

                            // Styling Titik (Points)
                            pointBackgroundColor: "#ffffff", // Titik putih
                            pointBorderColor: "#3B82F6", // Pinggiran biru
                            pointBorderWidth: 2,
                            pointRadius: 0, // Sembunyikan titik saat normal (bersih)
                            pointHoverRadius: 6, // Munculkan titik besar saat di-hover
                            pointHoverBorderWidth: 3,
                            pointHitRadius: 30, // Memudahkan hover
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: "index",
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            // Styling Tooltip Modern
                            backgroundColor: "rgba(17, 24, 39, 0.9)", // Dark bg
                            titleColor: "#fff",
                            bodyColor: "#cbd5e1",
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false, // Hapus kotak warna kecil di tooltip
                            callbacks: {
                                label: function (context) {
                                    return `Total: ${context.parsed.y} Karyawan`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            border: {
                                display: false,
                            },
                            grid: {
                                display: false, // Hapus grid vertikal (lebih bersih)
                            },
                            ticks: {
                                color: "#9ca3af", // Gray-400
                                font: {
                                    size: 11,
                                },
                            },
                        },
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false, // Hapus garis border sumbu Y
                            },
                            grid: {
                                color: "rgba(156, 163, 175, 0.1)", // Grid sangat transparan
                                tickBorderDash: [5, 5], // Grid putus-putus
                            },
                            ticks: {
                                stepSize: 1,
                                color: "#9ca3af", // Gray-400
                                padding: 10,
                                font: {
                                    size: 11,
                                },
                            },
                        },
                    },
                },
            });
        }
    });
</script>

<svelte:head>
    <title>Dashboard HR | {siteName($page.props.settings)}</title>
</svelte:head>

<div class="space-y-6">
    <!-- Header -->
    <header
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
    >
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Dashboard HR
            </h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                Selamat datang kembali, <span
                    class="font-semibold text-gray-900 dark:text-white"
                    >{user?.name || "User"}</span
                >!
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2"
                >
                    {role}
                </span>
            </p>
        </div>
        <div class="text-sm text-gray-500">
            {new Date().toLocaleDateString("id-ID", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
            })}
        </div>
    </header>

    {#if role !== "Manager" || isFlexible}
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
                    {#if !today?.attendance?.check_out_at}
                        <Button
                            variant={hasActiveCheckIn ? "secondary" : "primary"}
                            icon={hasActiveCheckIn
                                ? "fa-solid fa-door-open"
                                : "fa-solid fa-right-to-bracket"}
                            disabled={!canAct ||
                                (hasActiveCheckIn && !canCheckoutNow)}
                            onclick={goSmart}
                            class="ml-2"
                        >
                            {hasActiveCheckIn ? "Absen Keluar" : "Absen Masuk"}
                        </Button>
                    {/if}
                </div>
            </div>
        </div>
    {/if}

    {#if ["Admin", "Direktur"].includes(role)}
        <!-- Admin Dashboard -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Stat Cards -->
            <StatCard
                label="Total Karyawan"
                value={dashboardData.summary.total_employees}
                icon="fa-solid fa-users"
                color="blue"
            />
            <StatCard
                label="Hadir Hari Ini"
                value={dashboardData.summary.present}
                icon="fa-solid fa-user-check"
                color="green"
            />
            <StatCard
                label="Terlambat"
                value={dashboardData.summary.late}
                icon="fa-solid fa-user-clock"
                color="orange"
            />
            <StatCard
                label="Absen / Tanpa Ket."
                value={dashboardData.summary.absent}
                icon="fa-solid fa-user-xmark"
                color="red"
            />
        </div>

        <!-- Charts & Alerts Section -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Chart -->
            <Card title="Tren Kehadiran (7 Hari Terakhir)" collapsible={false}>
                {#snippet children()}
                    <div class="h-64">
                        <canvas bind:this={chartCanvas}></canvas>
                    </div>
                {/snippet}
            </Card>

            <!-- Alerts / Notifications -->
            <Card title="Pemberitahuan" class="flex flex-col h-full">
                <div
                    class="flex p-1 mb-3 space-x-1 bg-gray-100 rounded-lg dark:bg-gray-700"
                >
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'pending_leaves'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "pending_leaves")}
                    >
                        Pengajuan
                        {#if dashboardData.alerts?.pending_leaves && dashboardData.alerts?.pending_leaves?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'pending_leaves'
                                    ? 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.pending_leaves?.length}
                            </span>
                        {/if}
                    </button>
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'missing'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "missing")}
                    >
                        Belum Absen
                        {#if dashboardData.alerts?.missing?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'missing'
                                    ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.missing?.length}
                            </span>
                        {/if}
                    </button>
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'permit'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "permit")}
                    >
                        Izin
                        {#if dashboardData.alerts?.permits?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'permit'
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.permits?.length}
                            </span>
                        {/if}
                    </button>
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'absent'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "absent")}
                    >
                        Bolos
                        {#if dashboardData.alerts?.absents?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'absent'
                                    ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.absents?.length}
                            </span>
                        {/if}
                    </button>
                </div>

                <div
                    class="overflow-y-auto flex-1 pr-2 space-y-3 max-h-75 custom-scrollbar"
                >
                    {#if activeAlertTab === "pending_leaves"}
                        {#if dashboardData.alerts?.pending_leaves && dashboardData.alerts?.pending_leaves?.length > 0}
                            {#each dashboardData.alerts.pending_leaves as item}
                                <div
                                    class="p-3 bg-violet-50 rounded-lg border-l-4 border-violet-500 dark:bg-violet-900/20"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="text-violet-600 font-bold"
                                            >{item.type}</span
                                        >
                                    </div>
                                    <div
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        <div class="font-medium">
                                            {item.date_full}
                                        </div>
                                        <div
                                            class="italic text-gray-400 mt-0.5 text-[10px] line-clamp-2"
                                        >
                                            {item.reason}
                                        </div>
                                    </div>
                                    <div class="mt-2 text-right">
                                        <Link
                                            href="/leaves/manage"
                                            class="px-2 py-1 text-xs font-semibold text-white bg-violet-500 rounded hover:bg-violet-600 transition-colors inline-block"
                                        >
                                            Review
                                        </Link>
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <div
                                class="flex flex-col items-center justify-center py-8 text-gray-400"
                            >
                                <i
                                    class="fa-solid fa-clipboard-check text-3xl mb-2 opacity-30"
                                ></i>
                                <p class="text-xs">
                                    Tidak ada pengajuan izin pending.
                                </p>
                            </div>
                        {/if}
                    {:else if activeAlertTab === "missing"}
                        {#if dashboardData.alerts?.missing?.length > 0}
                            {#each dashboardData.alerts.missing as item}
                                <div
                                    class="p-3 bg-gray-50 rounded-lg border-l-4 border-gray-400 dark:bg-gray-700/50"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="font-medium text-gray-600"
                                            >{item.shift}</span
                                        >
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p class="py-8 text-sm text-center text-gray-500">
                                Semua karyawan terjadwal sudah absen.
                            </p>
                        {/if}
                    {:else if activeAlertTab === "permit"}
                        {#if dashboardData.alerts?.permits?.length > 0}
                            {#each dashboardData.alerts.permits as item}
                                <div
                                    class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500 dark:bg-blue-900/20"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="text-blue-600"
                                            >{item.reason}</span
                                        >
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p class="py-8 text-sm text-center text-gray-500">
                                Tidak ada izin hari ini.
                            </p>
                        {/if}
                    {:else if activeAlertTab === "absent"}
                        {#if dashboardData.alerts?.absents?.length > 0}
                            {#each dashboardData.alerts.absents as item}
                                <div
                                    class="p-3 bg-red-50 rounded-lg border-l-4 border-red-500 dark:bg-red-900/20"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="font-bold text-red-600"
                                            >ALPHA</span
                                        >
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p class="py-8 text-sm text-center text-gray-500">
                                Tidak ada data bolos/alpha.
                            </p>
                        {/if}
                    {/if}
                </div>
            </Card>
        </div>

        <!-- Recent Attendance Feed -->
        <Card title="Aktivitas Absensi Terkini" bodyWithoutPadding={true}>
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Tipe</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if dashboardData.recent_attendance.length > 0}
                            {#each dashboardData.recent_attendance as activity}
                                <tr
                                    class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="font-mono text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {activity.time}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {activity.name}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {activity.position}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={activity.type === "Masuk"
                                                ? "success"
                                                : "warning"}
                                        >
                                            {#snippet children()}
                                                {activity.type}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getBadgeVariantFromColor(
                                                activity.status_color,
                                            )}
                                        >
                                            {#snippet children()}
                                                {activity.status}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="5"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Belum ada aktivitas absensi hari ini.
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </Card>
    {:else if role === "Manager"}
        <!-- Manager Dashboard -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Total Staff"
                value={dashboardData.summary.total_staff}
                icon="fa-solid fa-users"
                color="blue"
            />
            <StatCard
                label="Hadir Hari Ini"
                value={dashboardData.summary.present}
                icon="fa-solid fa-user-check"
                color="green"
            />
            <StatCard
                label="Terlambat"
                value={dashboardData.summary.late}
                icon="fa-solid fa-user-clock"
                color="orange"
            />
            <StatCard
                label="Absen / Tanpa Ket."
                value={dashboardData.summary.absent}
                icon="fa-solid fa-user-xmark"
                color="red"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card
                title="Tren Kehadiran Staff (7 Hari Terakhir)"
                collapsible={false}
            >
                {#snippet children()}
                    <div class="h-64">
                        <canvas bind:this={chartCanvas}></canvas>
                    </div>
                {/snippet}
            </Card>

            <Card title="Pemberitahuan" class="flex flex-col h-full">
                <div
                    class="flex p-1 mb-3 space-x-1 bg-gray-100 rounded-lg dark:bg-gray-700"
                >
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'missing'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "missing")}
                    >
                        Belum Absen
                        {#if dashboardData.alerts?.missing?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'missing'
                                    ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.missing?.length}
                            </span>
                        {/if}
                    </button>
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'permit'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "permit")}
                    >
                        Izin
                        {#if dashboardData.alerts?.permits?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'permit'
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.permits?.length}
                            </span>
                        {/if}
                    </button>
                    <button
                        class="flex-1 px-1 py-1.5 text-xs font-medium rounded-md transition-colors flex items-center justify-center gap-2 {activeAlertTab ===
                        'absent'
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'}"
                        onclick={() => (activeAlertTab = "absent")}
                    >
                        Bolos
                        {#if dashboardData.alerts?.absents?.length > 0}
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none rounded-full {activeAlertTab ===
                                'absent'
                                    ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'}"
                            >
                                {dashboardData.alerts?.absents?.length}
                            </span>
                        {/if}
                    </button>
                </div>

                <div
                    class="overflow-y-auto flex-1 pr-2 space-y-3 max-h-75 custom-scrollbar"
                >
                    {#if activeAlertTab === "missing"}
                        {#if dashboardData.alerts?.missing?.length > 0}
                            {#each dashboardData.alerts.missing as item}
                                <div
                                    class="p-3 bg-gray-50 rounded-lg border-l-4 border-gray-400 dark:bg-gray-700/50"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="font-medium text-gray-600"
                                            >{item.shift}</span
                                        >
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p class="py-8 text-sm text-center text-gray-500">
                                Semua staff terjadwal sudah absen.
                            </p>
                        {/if}
                    {:else if activeAlertTab === "permit"}
                        {#if dashboardData.alerts?.permits?.length > 0}
                            {#each dashboardData.alerts.permits as item}
                                <div
                                    class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500 dark:bg-blue-900/20"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="text-blue-600"
                                            >{item.reason}</span
                                        >
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p class="py-8 text-sm text-center text-gray-500">
                                Tidak ada izin staff hari ini.
                            </p>
                        {/if}
                    {:else if activeAlertTab === "absent"}
                        {#if dashboardData.alerts?.absents?.length > 0}
                            {#each dashboardData.alerts.absents as item}
                                <div
                                    class="p-3 bg-red-50 rounded-lg border-l-4 border-red-500 dark:bg-red-900/20"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {item.name}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-gray-500"
                                    >
                                        <span>{item.position}</span>
                                        <span class="font-bold text-red-600"
                                            >ALPHA</span
                                        >
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p class="py-8 text-sm text-center text-gray-500">
                                Tidak ada data bolos/alpha staff.
                            </p>
                        {/if}
                    {/if}
                </div>
            </Card>
        </div>

        <Card title="Aktivitas Absensi Staff Terkini" bodyWithoutPadding={true}>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr
                            class="text-sm text-left text-gray-600 dark:text-gray-400"
                        >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Waktu</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Nama</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Jabatan</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Tipe</th
                            >
                            <th
                                class="px-4 py-3 font-semibold uppercase whitespace-nowrap border-b border-gray-200 dark:border-gray-700"
                                >Status</th
                            >
                        </tr>
                    </thead>
                    <tbody>
                        {#if dashboardData.recent_attendance.length > 0}
                            {#each dashboardData.recent_attendance as activity}
                                <tr
                                    class="border-b border-gray-200 transition-colors dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 last:border-b-0"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="font-mono text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {activity.time}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {activity.name}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {activity.position}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={activity.type === "Masuk"
                                                ? "success"
                                                : "warning"}
                                        >
                                            {#snippet children()}
                                                {activity.type}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getBadgeVariantFromColor(
                                                activity.status_color,
                                            )}
                                        >
                                            {#snippet children()}
                                                {activity.status}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="5"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Belum ada aktivitas absensi staff hari ini.
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </Card>
    {:else}
        <!-- Staff Dashboard -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Stat Cards -->
            <StatCard
                label="Hadir Bulan Ini"
                value={dashboardData.summary.present}
                unit="hari"
                icon="fa-solid fa-calendar-check"
                color="green"
            />
            <StatCard
                label="Terlambat"
                value={dashboardData.summary.late}
                unit="kali"
                icon="fa-solid fa-clock"
                color="orange"
            />
            <StatCard
                label="Izin / Cuti"
                value={dashboardData.summary.permit}
                unit="hari"
                icon="fa-solid fa-file-contract"
                color="blue"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Upcoming Schedule -->
            <Card title="Jadwal Mendatang">
                <div class="space-y-3">
                    {#if upcomingSchedules.length > 0}
                        {#each upcomingSchedules as schedule}
                            <div
                                class="flex items-center p-4 rounded-lg border border-gray-100 transition-colors dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <div
                                    class="py-2 w-12 text-center bg-blue-100 rounded-lg shrink-0 dark:bg-blue-900/30"
                                >
                                    <div
                                        class="text-xs font-bold text-blue-600 uppercase dark:text-blue-400"
                                    >
                                        {schedule.date.split(" ")[1]}
                                    </div>
                                    <div
                                        class="text-lg font-bold text-blue-800 dark:text-blue-200"
                                    >
                                        {schedule.date.split(" ")[0]}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {schedule.shift_name}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {schedule.time}
                                    </div>
                                </div>
                            </div>
                        {/each}
                    {:else}
                        <p class="text-sm text-gray-500">
                            Tidak ada jadwal mendatang.
                        </p>
                    {/if}
                </div>
            </Card>

            <!-- Recent Activity -->
            <Card title="Aktivitas Terakhir" bodyWithoutPadding={true}>
                <div class="overflow-hidden">
                    <table class="custom-table min-w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each dashboardData.recent_activity as activity}
                                <tr
                                    class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {activity.date}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="font-mono text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {activity.check_in}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div
                                            class="font-mono text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {activity.check_out}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant={getBadgeVariantFromColor(
                                                activity.status_color,
                                            )}
                                        >
                                            {#snippet children()}
                                                {activity.status}
                                            {/snippet}
                                        </Badge>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    {/if}
</div>

<style>
    /* Custom Scrollbar for Alert List */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #e5e7eb;
        border-radius: 20px;
    }
    :global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #374151;
    }
</style>
