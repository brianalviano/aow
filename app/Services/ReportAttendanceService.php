<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\{Attendance, Schedule, Setting, User, Holiday};
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportAttendanceService
{
    public function generate(string $startParam, string $endParam): array
    {
        if ($startParam === '' || $endParam === '') {
            $today = Carbon::today();
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $startOfWeek->copy()->addDays(6);
            $startParam = $startOfWeek->toDateString();
            $endParam = $endOfWeek->toDateString();
        }

        $startDate = Carbon::parse($startParam)->startOfDay();
        $endDate = Carbon::parse($endParam)->endOfDay();

        $employees = User::query()
            ->with(['role'])
            ->whereNotNull('role_id')
            ->orderBy('name')
            ->get()
            ->map(fn(User $u) => [
                'id' => (string) $u->id,
                'name' => (string) $u->name,
                'role' => $u->role ? ['name' => $u->role->name] : null,
                'avatar' => null,
            ]);

        $rawSchedules = Schedule::query()
            ->with(['shift'])
            ->whereBetween('date', [$startParam, $endParam])
            ->get()
            ->groupBy('user_id');

        $indexedSchedules = [];
        foreach ($rawSchedules as $userId => $items) {
            foreach ($items as $s) {
                $dateKey = $s->date->toDateString();
                $indexedSchedules[(string) $userId][$dateKey] = $s;
            }
        }

        $rawAttendances = Attendance::query()
            ->with(['schedule'])
            ->whereBetween('check_in_at', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id');

        $indexedAttendancesBySchedule = [];
        $indexedAttendancesByDate = [];
        foreach ($rawAttendances as $userId => $items) {
            foreach ($items as $a) {
                if ($a->schedule_id) {
                    $indexedAttendancesBySchedule[(string) $userId][(string) $a->schedule_id] = $a;
                }
                if ($a->check_in_at) {
                    $dateKey = $a->check_in_at->toDateString();
                    $indexedAttendancesByDate[(string) $userId][$dateKey] = $a;
                }
            }
        }

        $dates = [];
        $curr = Carbon::parse($startParam);
        $end = Carbon::parse($endParam);
        while ($curr->lte($end)) {
            $dates[] = $curr->toDateString();
            $curr->addDay();
        }

        $holidayDates = Holiday::query()
            ->whereBetween('date', [$startParam, $endParam])
            ->where('is_compulsory', true)
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->all();
        $holidaySet = array_flip($holidayDates);

        $report = $employees->map(function (array $employee) use ($dates, $indexedSchedules, $indexedAttendancesBySchedule, $indexedAttendancesByDate, $holidaySet) {
            $empId = $employee['id'];
            $row = [];
            foreach ($dates as $date) {
                $schedule = $indexedSchedules[$empId][$date] ?? null;

                $attendance = null;
                if ($schedule) {
                    $attendance = $indexedAttendancesBySchedule[$empId][$schedule->id] ?? null;
                }
                if (!$attendance) {
                    $attendance = $indexedAttendancesByDate[$empId][$date] ?? null;
                }

                $status = 'off';
                $label = '-';
                $color = 'gray';

                if ($schedule) {
                    if ($schedule->shift && (bool) $schedule->shift->is_off) {
                        $status = 'libur';
                        $label = 'Libur';
                        $color = 'gray';
                    } else {
                        if ($attendance) {
                            if ($attendance->status === AttendanceStatus::Late) {
                                $status = 'telat';
                                $lateMinutes = (int) ($attendance->late_duration ?? 0);
                                $label = $lateMinutes > 0 ? ('Telat ' . $lateMinutes . 'm') : 'Telat';
                                $color = 'orange';
                            } elseif ($attendance->status === AttendanceStatus::Permit) {
                                $status = 'izin';
                                $label = 'Izin';
                                $color = 'blue';
                            } elseif ($attendance->status === AttendanceStatus::Absent) {
                                $status = 'alpha';
                                $label = 'Alpha';
                                $color = 'red';
                            } else {
                                $status = 'hadir';
                                $label = 'Hadir';
                                $color = 'green';
                            }
                        } else {
                            if (Carbon::parse($date)->endOfDay()->isPast()) {
                                $status = 'alpha';
                                $label = 'Alpha';
                                $color = 'red';
                            } else {
                                $status = 'pending';
                                $label = '-';
                                $color = 'gray';
                            }
                        }
                    }
                } else {
                    if (isset($holidaySet[$date])) {
                        $status = 'libur';
                        $label = 'Libur';
                        $color = 'gray';
                    } else {
                        $status = 'off';
                        $label = '-';
                        $color = 'gray';
                    }
                }

                $row[$date] = [
                    'status' => $status,
                    'label' => $label,
                    'color' => $color,
                    'check_in' => $attendance?->check_in_at?->format('H:i'),
                    'check_out' => $attendance?->check_out_at?->format('H:i'),
                    'is_night' => $schedule?->shift?->is_overnight ?? false,
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'check_in_at' => $attendance->check_in_at?->format('d M Y H:i'),
                        'check_out_at' => $attendance->check_out_at?->format('d M Y H:i'),
                        'check_in_photo_url' => $attendance->check_in_photo ? Storage::url($attendance->check_in_photo) : null,
                        'check_out_photo_url' => $attendance->check_out_photo ? Storage::url($attendance->check_out_photo) : null,
                        'check_in_notes' => $attendance->check_in_notes,
                        'check_out_notes' => $attendance->check_out_notes,
                        'check_in_lat' => $attendance->check_in_lat,
                        'check_in_long' => $attendance->check_in_long,
                        'check_out_lat' => $attendance->check_out_lat,
                        'check_out_long' => $attendance->check_out_long,
                        'status' => $attendance->status,
                        'late_duration' => $attendance->late_duration,
                    ] : null,
                    'schedule' => $schedule ? [
                        'id' => $schedule->id,
                        'shift_name' => $schedule->shift?->name,
                        'start_time' => $schedule->shift?->start_time,
                        'end_time' => $schedule->shift?->end_time,
                    ] : null,
                ];
            }

            $summary = [
                'hadir' => 0,
                'telat' => 0,
                'izin' => 0,
                'alpha' => 0,
                'libur' => 0,
            ];
            $workdays = 0;
            foreach ($row as $date => $cell) {
                $st = (string) ($cell['status'] ?? '');
                if ($st === 'off' || $st === 'pending') {
                    continue;
                }
                if (in_array($st, ['hadir', 'telat', 'izin', 'alpha', 'libur'], true)) {
                    $summary[$st]++;
                }
                if ($st !== 'libur') {
                    $workdays++;
                }
            }

            return [
                'employee' => $employee,
                'days' => $row,
                'summary' => [
                    'counts' => $summary,
                    'workdays' => $workdays,
                ],
            ];
        });

        $gf = (array) config('tomtom.geofence', []);
        $s = Setting::query()->first();
        $geofence = [
            'center_lat' => $s ? (float) $s->latitude : (float) ($gf['center_lat'] ?? 0),
            'center_long' => $s ? (float) $s->longitude : (float) ($gf['center_long'] ?? 0),
            'radius_m' => (int) ($gf['radius_m'] ?? 100),
        ];

        return [
            'report' => $report->values()->all(),
            'dates' => $dates,
            'filters' => [
                'start_date' => $startParam,
                'end_date' => $endParam,
            ],
            'geofence' => $geofence,
            'holidays' => $holidayDates,
        ];
    }
}
