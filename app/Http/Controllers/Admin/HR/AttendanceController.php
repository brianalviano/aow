<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HR;

use App\DTOs\Attendance\CheckInData;
use App\DTOs\Attendance\CheckOutData;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\{Attendance, Setting};
use App\Http\Requests\Attendance\StoreCheckInRequest;
use App\Http\Requests\Attendance\StoreCheckOutRequest;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use App\Enums\RoleName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Tampilkan halaman absensi beserta ringkasan dan riwayat.
     *
     * @param Request $request
     * @param ScheduleService $schedules
     * @return Response
     */
    public function index(Request $request, ScheduleService $schedules): Response
    {
        $userId = (string) auth()->id();
        $schedule = $schedules->getTodaySchedule($userId);
        if ($schedule) {
            $schedule->load(['attendance']);
        }

        $activeOpen = Attendance::query()
            ->with(['schedule.shift'])
            ->where('user_id', $userId)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest()
            ->first();
        $hasActiveCheckIn = $activeOpen !== null;

        $gf = (array) config('tomtom.geofence', []);
        $s = Setting::query()->first();
        $cfg = [
            'center_lat' => $s ? (float) $s->latitude : (float) ($gf['center_lat'] ?? 0),
            'center_long' => $s ? (float) $s->longitude : (float) ($gf['center_long'] ?? 0),
            'radius_m' => (int) ($gf['radius_m'] ?? 100),
            'tomtom_key' => (string) config('tomtom.api_key', ''),
            'tomtom_sdk_base' => (string) config('tomtom.sdk_cdn_base', 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0'),
        ];

        $q = (string) $request->string('q')->toString();
        $perPage = (int) $request->integer('per_page', 10);
        $historiesQ = Attendance::query()
            ->with(['schedule.shift'])
            ->where('user_id', $userId)
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('status', 'ilike', "%{$q}%")
                        ->orWhere('check_in_notes', 'ilike', "%{$q}%")
                        ->orWhere('check_out_notes', 'ilike', "%{$q}%")
                        ->orWhereHas('schedule', function ($sq) use ($q) {
                            $sq->where('date', 'ilike', "%{$q}%")
                                ->orWhereHas('shift', function ($sh) use ($q) {
                                    $sh->where('name', 'ilike', "%{$q}%");
                                });
                        });
                });
            })
            ->orderByDesc('check_in_at');
        $histories = $historiesQ->paginate($perPage)->appends(['q' => $q]);
        $items = array_map(
            fn($a) => AttendanceResource::make($a)->toArray($request),
            $histories->items(),
        );

        $todaySummary = $schedule ? [
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
            'attendance' => $schedule->attendance ? AttendanceResource::make($schedule->attendance)->toArray($request) : null,
        ] : null;

        $activeSummary = $activeOpen ? array_merge(
            AttendanceResource::make($activeOpen)->toArray($request),
            [
                'is_cross_day' => $activeOpen->schedule?->date instanceof Carbon
                    ? !$activeOpen->schedule->date->isToday()
                    : false,
            ],
        ) : null;

        return Inertia::render('Domains/Admin/HR/Attendance/Index', [
            'schedule' => $schedule ? [
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
            ] : null,
            'attendance_state' => [
                'has_active_check_in' => $hasActiveCheckIn,
            ],
            'geofence' => $cfg,
            'today' => $todaySummary,
            'active' => $activeSummary,
            'attendances' => $items,
            'meta' => [
                'current_page' => $histories->currentPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'last_page' => $histories->lastPage(),
            ],
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function showCheckIn(ScheduleService $schedules): Response|RedirectResponse
    {
        $userId = (string) auth()->id();
        $schedule = $schedules->getTodaySchedule($userId);
        $roleName = auth()->user()?->role?->name ?? null;
        $isFlexible = $roleName !== null && (function ($r) {
            $t = trim((string) $r);
            return $t === RoleName::ManagerHR->value;
        })($roleName);
        if ($schedule) {
            $schedule->load(['attendance']);
        }
        $activeOpen = Attendance::query()
            ->with(['schedule.shift'])
            ->where('user_id', $userId)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest()
            ->first();
        $hasActiveCheckIn = $activeOpen !== null;

        if (!$isFlexible && ($schedule === null || ($schedule->shift?->is_off ?? false))) {
            Inertia::flash('toast', [
                'message' => 'Tidak ada jadwal aktif untuk absen',
                'type' => 'error',
            ]);
            return redirect()->route('absents.index');
        }
        if ($hasActiveCheckIn && (string) $activeOpen->schedule_id !== (string) $schedule->id) {
            Inertia::flash('toast', [
                'message' => 'Selesaikan checkout shift sebelumnya sebelum absen masuk',
                'type' => 'warning',
            ]);
            return redirect()->route('absents.index');
        }
        $gf = (array) config('tomtom.geofence', []);
        $s = Setting::query()->first();
        $cfg = [
            'center_lat' => $s ? (float) $s->latitude : (float) ($gf['center_lat'] ?? 0),
            'center_long' => $s ? (float) $s->longitude : (float) ($gf['center_long'] ?? 0),
            'radius_m' => (int) ($gf['radius_m'] ?? 100),
            'tomtom_key' => (string) config('tomtom.api_key', ''),
            'tomtom_sdk_base' => (string) config('tomtom.sdk_cdn_base', 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0'),
        ];
        return Inertia::render('Domains/Admin/HR/Attendance/Form', [
            'schedule' => $schedule ? [
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
            ] : null,
            'attendance_state' => [
                'has_active_check_in' => $hasActiveCheckIn,
            ],
            'geofence' => $cfg,
            'mode' => 'checkin',
        ]);
    }

    public function showCheckOut(): Response|RedirectResponse
    {
        $userId = (string) auth()->id();
        $roleName = auth()->user()?->role?->name ?? null;
        $isFlexible = $roleName !== null && (function ($r) {
            $t = trim((string) $r);
            return $t === RoleName::ManagerHR->value;
        })($roleName);

        $activeOpen = Attendance::query()
            ->with(['schedule.shift'])
            ->where('user_id', $userId)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest()
            ->first();
        $hasActiveCheckIn = $activeOpen !== null;

        if (!$hasActiveCheckIn) {
            Inertia::flash('toast', [
                'message' => 'Tidak ada absensi aktif untuk checkout',
                'type' => 'warning',
            ]);
            return redirect()->route('absents.index');
        }

        $schedule = $activeOpen->schedule;
        if ($schedule) {
            $schedule->setRelation('attendance', $activeOpen);
        }

        if ($schedule && $schedule->shift && $schedule->shift->end_time && !$isFlexible) {
            $end = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . (string) $schedule->shift->end_time);
            if ((bool) $schedule->shift->is_overnight) {
                $end->addDay();
            }
            $allowedStart = (clone $end)->subMinutes(15);
            if (Carbon::now()->lessThan($allowedStart)) {
                Inertia::flash('toast', [
                    'message' => 'Checkout hanya dapat dimulai 15 menit sebelum jam pulang',
                    'type' => 'warning',
                ]);
                return redirect()->route('absents.index');
            }
        }

        $gf = (array) config('tomtom.geofence', []);
        $s = Setting::query()->first();
        $cfg = [
            'center_lat' => $s ? (float) $s->latitude : (float) ($gf['center_lat'] ?? 0),
            'center_long' => $s ? (float) $s->longitude : (float) ($gf['center_long'] ?? 0),
            'radius_m' => (int) ($gf['radius_m'] ?? 100),
            'tomtom_key' => (string) config('tomtom.api_key', ''),
            'tomtom_sdk_base' => (string) config('tomtom.sdk_cdn_base', 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0'),
        ];
        return Inertia::render('Domains/Admin/HR/Attendance/Form', [
            'schedule' => $schedule ? [
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
            ] : null,
            'attendance_state' => [
                'has_active_check_in' => $hasActiveCheckIn,
            ],
            'geofence' => $cfg,
            'mode' => 'checkout',
        ]);
    }

    public function checkIn(
        StoreCheckInRequest $request,
        ScheduleService $schedules,
        AttendanceService $service
    ): RedirectResponse {
        $userId = (string) auth()->id();
        $roleName = auth()->user()?->role?->name ?? null;
        $isFlexible = $roleName !== null && (function ($r) {
            $t = trim((string) $r);
            return $t === RoleName::ManagerHR->value;
        })($roleName);
        $schedule = $schedules->getTodaySchedule($userId);
        if (!$isFlexible && ($schedule === null || $schedule->shift?->is_off)) {
            Inertia::flash('toast', [
                'message' => 'Tidak ada jadwal aktif untuk absen masuk',
                'type' => 'error',
            ]);
            return redirect()->route('absents.index');
        }
        if ($isFlexible && ($schedule === null || $schedule->shift?->is_off)) {
            $schedule = $schedules->createTodayFlexibleSchedule($userId);
        }

        try {
            $service->checkIn($schedule, CheckInData::fromRequest($request));
            Inertia::flash('toast', [
                'message' => 'Absensi masuk berhasil',
                'type' => 'success',
            ]);
            return redirect()->route('absents.index');
        } catch (Throwable $e) {
            Log::error('attendance.checkin_failed', [
                'user_id' => $userId,
                'schedule_id' => $schedule ? (string) $schedule->id : null,
                'action' => 'checkin',
                'outcome' => 'error',
                'message' => $e->getMessage(),
            ]);
            Inertia::flash('toast', [
                'message' => 'Gagal melakukan absensi masuk: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
        return redirect()->back();
    }

    public function checkOut(
        StoreCheckOutRequest $request,
        AttendanceService $service
    ): RedirectResponse {
        $userId = (string) auth()->id();
        try {
            $ok = $service->checkOut($userId, CheckOutData::fromRequest($request));
            if ($ok) {
                Inertia::flash('toast', [
                    'message' => 'Absensi keluar berhasil',
                    'type' => 'success',
                ]);
                return redirect()->route('absents.index');
            } else {
                Inertia::flash('toast', [
                    'message' => 'Tidak ada absensi aktif untuk checkout',
                    'type' => 'warning',
                ]);
            }
        } catch (Throwable $e) {
            Log::error('attendance.checkout_failed', [
                'user_id' => $userId,
                'action' => 'checkout',
                'outcome' => 'error',
                'message' => $e->getMessage(),
            ]);
            Inertia::flash('toast', [
                'message' => 'Gagal melakukan absensi keluar: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
        return redirect()->back();
    }
}
