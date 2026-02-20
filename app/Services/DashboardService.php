<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\{AttendanceStatus, LeaveRequestStatus};
use App\Http\Resources\AttendanceResource;
use App\Models\{Attendance, Schedule, User, LeaveRequest};
use Illuminate\Support\Carbon;

class DashboardService
{
    public function buildStatsFor(string $role, User $user): array
    {
        if (in_array($role, ['Super Admin', 'Director'], true)) {
            return $this->getAdminStats();
        }
        if (str_starts_with($role, 'Manager ')) {
            return $this->getManagerStats();
        }
        return $this->getStaffStats($user);
    }

    public function buildTodaySummary(User $user, ScheduleService $schedules): ?array
    {
        $schedule = $schedules->getTodaySchedule((string) $user->id);
        if ($schedule) {
            $schedule->load(['attendance', 'shift']);
        }
        if (!$schedule) {
            return null;
        }
        return [
            'schedule' => [
                'id' => (string) $schedule->id,
                'date' => $schedule->date->toDateString(),
                'shift' => $schedule->shift ? [
                    'id' => (string) $schedule->shift->id,
                    'name' => (string) $schedule->shift->name,
                    'start_time' => $schedule->shift->start_time,
                    'end_time' => $schedule->shift->end_time,
                    'is_overnight' => (bool) $schedule->shift->is_overnight,
                    'is_off' => (bool) $schedule->shift->is_off,
                ] : null,
            ],
            'attendance' => $schedule->attendance ? AttendanceResource::make($schedule->attendance)->toArray(request()) : null,
        ];
    }

    public function hasActiveCheckIn(string $userId): bool
    {
        $activeOpen = Attendance::query()
            ->where('user_id', $userId)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest()
            ->first();
        return $activeOpen !== null;
    }

    private function getManagerStats(): array
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $totalStaff = User::whereHas('role', function ($q) {
            $q->where('name', 'ilike', 'Staff%');
        })->count();

        $presentToday = Attendance::whereDate('check_in_at', $today)
            ->where('status', AttendanceStatus::Present)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->count();

        $lateToday = Attendance::whereDate('check_in_at', $today)
            ->where('status', AttendanceStatus::Late)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->count();

        $absentToday = Attendance::whereDate('created_at', $today)
            ->where('status', AttendanceStatus::Absent)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->count();

        $dates = collect(range(6, 0))->map(fn($days) => $today->copy()->subDays($days));
        $trendLabels = $dates->map(fn($date) => $date->format('d M'));
        $trendData = $dates->map(function ($date) {
            return Attendance::whereDate('check_in_at', $date)
                ->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])
                ->whereHas('user.role', function ($q) {
                    $q->where('name', 'ilike', 'Staff%');
                })
                ->count();
        });

        $recentLate = Attendance::with(['user.role'])
            ->whereDate('check_in_at', $today)
            ->where('status', AttendanceStatus::Late)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->latest('check_in_at')
            ->take(5)
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
                'time' => $att->check_in_at->format('H:i'),
                'late_duration' => $att->late_duration . ' min',
            ]);

        $recentAttendance = Attendance::with(['user.role'])
            ->whereDate('updated_at', $today)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
                'time' => $att->check_out_at ? $att->check_out_at->format('H:i') : ($att->check_in_at?->format('H:i') ?? '-'),
                'type' => $att->check_out_at ? 'Keluar' : 'Masuk',
                'status' => $att->status->label(),
                'status_color' => $att->status->color(),
            ]);

        $permits = Attendance::with(['user.role'])
            ->whereDate('created_at', $today)
            ->where('status', AttendanceStatus::Permit)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
                'reason' => 'Izin',
            ]);

        $absents = Attendance::with(['user.role'])
            ->whereDate('created_at', $today)
            ->where('status', AttendanceStatus::Absent)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
            ]);

        $missing = Schedule::with(['user.role', 'shift'])
            ->whereDate('date', $today)
            ->whereHas('user.role', function ($q) {
                $q->where('name', 'ilike', 'Staff%');
            })
            ->whereDoesntHave('attendance')
            ->get()
            ->filter(function ($schedule) use ($now) {
                if (!$schedule->shift->start_time) {
                    return false;
                }
                $startTime = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $schedule->shift->start_time);
                return $now->greaterThan($startTime);
            })
            ->map(fn($sch) => [
                'name' => $sch->user->name,
                'role' => $sch->user->role->name,
                'shift' => $sch->shift->name . ' (' . $sch->shift->start_time . ')',
            ])
            ->values();

        return [
            'summary' => [
                'total_staff' => $totalStaff,
                'present' => $presentToday,
                'late' => $lateToday,
                'absent' => $absentToday,
            ],
            'charts' => [
                'trend' => [
                    'labels' => $trendLabels,
                    'data' => $trendData,
                ]
            ],
            'recent_late' => $recentLate,
            'recent_attendance' => $recentAttendance,
            'alerts' => [
                'permits' => $permits,
                'absents' => $absents,
                'missing' => $missing,
                'pending_leaves' => LeaveRequest::with(['user.role'])
                    ->where('status', LeaveRequestStatus::Pending)
                    ->whereHas('user.role', function ($q) {
                        $q->where('name', 'ilike', 'Staff%');
                    })
                    ->latest()
                    ->get()
                    ->map(fn($lr) => [
                        'id' => $lr->id,
                        'name' => $lr->user->name,
                        'role' => $lr->user->role->name,
                        'type' => $lr->type->label(),
                        'reason' => $lr->reason,
                        'date' => Carbon::parse($lr->start_date)->format('d/m'),
                        'date_full' => Carbon::parse($lr->start_date)->isSameDay($lr->end_date)
                            ? Carbon::parse($lr->start_date)->translatedFormat('d M Y')
                            : Carbon::parse($lr->start_date)->translatedFormat('d M Y') . ' - ' . Carbon::parse($lr->end_date)->translatedFormat('d M Y'),
                    ]),
            ],
        ];
    }

    private function getAdminStats(): array
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $totalEmployees = User::count();
        $presentToday = Attendance::whereDate('check_in_at', $today)
            ->where('status', AttendanceStatus::Present)
            ->count();
        $lateToday = Attendance::whereDate('check_in_at', $today)
            ->where('status', AttendanceStatus::Late)
            ->count();
        $absentToday = Attendance::whereDate('created_at', $today)
            ->where('status', AttendanceStatus::Absent)
            ->count();

        $dates = collect(range(6, 0))->map(fn($days) => $today->copy()->subDays($days));
        $trendLabels = $dates->map(fn($date) => $date->format('d M'));
        $trendData = $dates->map(function ($date) {
            return Attendance::whereDate('check_in_at', $date)
                ->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])
                ->count();
        });

        $recentLate = Attendance::with(['user.role'])
            ->whereDate('check_in_at', $today)
            ->where('status', AttendanceStatus::Late)
            ->latest('check_in_at')
            ->take(5)
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
                'time' => $att->check_in_at->format('H:i'),
                'late_duration' => $att->late_duration . ' min',
            ]);

        $recentAttendance = Attendance::with(['user.role'])
            ->whereDate('updated_at', $today)
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
                'time' => $att->check_out_at ? $att->check_out_at->format('H:i') : ($att->check_in_at?->format('H:i') ?? '-'),
                'type' => $att->check_out_at ? 'Keluar' : 'Masuk',
                'status' => $att->status->label(),
                'status_color' => $att->status->color(),
            ]);

        $permits = Attendance::with(['user.role'])
            ->whereDate('created_at', $today)
            ->where('status', AttendanceStatus::Permit)
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
                'reason' => 'Izin',
            ]);

        $absents = Attendance::with(['user.role'])
            ->whereDate('created_at', $today)
            ->where('status', AttendanceStatus::Absent)
            ->get()
            ->map(fn($att) => [
                'name' => $att->user->name,
                'role' => $att->user->role->name,
            ]);

        $missing = Schedule::with(['user.role', 'shift'])
            ->whereDate('date', $today)
            ->whereDoesntHave('attendance')
            ->get()
            ->filter(function ($schedule) use ($now) {
                if (!$schedule->shift->start_time) {
                    return false;
                }
                $startTime = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $schedule->shift->start_time);
                return $now->greaterThan($startTime);
            })
            ->map(fn($sch) => [
                'name' => $sch->user->name,
                'role' => $sch->user->role->name,
                'shift' => $sch->shift->name . ' (' . $sch->shift->start_time . ')',
            ])
            ->values();

        return [
            'summary' => [
                'total_employees' => $totalEmployees,
                'present' => $presentToday,
                'late' => $lateToday,
                'absent' => $absentToday,
            ],
            'charts' => [
                'trend' => [
                    'labels' => $trendLabels,
                    'data' => $trendData,
                ]
            ],
            'recent_late' => $recentLate,
            'recent_attendance' => $recentAttendance,
            'alerts' => [
                'permits' => $permits,
                'absents' => $absents,
                'missing' => $missing,
                'pending_leaves' => LeaveRequest::with(['user.role'])
                    ->where('status', LeaveRequestStatus::Pending)
                    ->latest()
                    ->get()
                    ->map(fn($lr) => [
                        'id' => $lr->id,
                        'name' => $lr->user->name,
                        'role' => $lr->user->role->name,
                        'type' => $lr->type->label(),
                        'reason' => $lr->reason,
                        'date' => Carbon::parse($lr->start_date)->format('d/m'),
                        'date_full' => Carbon::parse($lr->start_date)->isSameDay($lr->end_date)
                            ? Carbon::parse($lr->start_date)->translatedFormat('d M Y')
                            : Carbon::parse($lr->start_date)->translatedFormat('d M Y') . ' - ' . Carbon::parse($lr->end_date)->translatedFormat('d M Y'),
                    ]),
            ],
        ];
    }

    private function getStaffStats(User $user): array
    {
        $currentMonth = Carbon::now()->month;
        $today = Carbon::today();

        $myAttendance = Attendance::where('user_id', $user->id)
            ->whereMonth('check_in_at', $currentMonth)
            ->get();

        $presentCount = $myAttendance->where('status', AttendanceStatus::Present)->count();
        $lateCount = $myAttendance->where('status', AttendanceStatus::Late)->count();
        $permitCount = $myAttendance->where('status', AttendanceStatus::Permit)->count();

        $upcomingSchedules = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->take(5)
            ->get()
            ->map(fn($sch) => [
                'date' => $sch->date->format('d M Y'),
                'shift_name' => $sch->shift->name,
                'time' => $sch->shift->start_time . ' - ' . $sch->shift->end_time,
            ]);

        $recentActivity = Attendance::where('user_id', $user->id)
            ->latest('check_in_at')
            ->take(5)
            ->get()
            ->map(fn($att) => [
                'date' => $att->check_in_at ? $att->check_in_at->format('d M Y') : '-',
                'check_in' => $att->check_in_at ? $att->check_in_at->format('H:i') : '-',
                'check_out' => $att->check_out_at ? $att->check_out_at->format('H:i') : '-',
                'status' => $att->status->label(),
                'status_color' => $att->status->color(),
            ]);

        return [
            'summary' => [
                'present' => $presentCount,
                'late' => $lateCount,
                'permit' => $permitCount,
            ],
            'upcoming_schedules' => $upcomingSchedules,
            'recent_activity' => $recentActivity,
        ];
    }
}
