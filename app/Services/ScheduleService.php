<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Schedule\ScheduleBatchData;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use App\Traits\RetryableTransactionsTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class ScheduleService
{
    use RetryableTransactionsTrait;

    /**
     * Assign rotation schedule for a user.
     *
     * @param string $userId The user ID.
     * @param string $startDate The start date.
     * @param string $endDate The end date.
     * @param string $shiftId The shift ID.
     * @return void
     */
    public function assignRotationSchedule(string $userId, string $startDate, string $endDate, string $shiftId): void
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            Schedule::updateOrCreate(
                ['user_id' => $userId, 'date' => $date->format('Y-m-d')],
                ['shift_id' => $shiftId],
            );
        }
    }

    /**
     * Assign fixed schedule for a user.
     *
     * @param int $userId The user ID.
     * @param string $startDate The start date.
     * @param string $endDate The end date.
     * @param int $shiftId The shift ID.
     * @param array $workingDays The working days.
     * @return void
     */
    public function assignFixedSchedule(
        int $userId,
        string $startDate,
        string $endDate,
        int $shiftId,
        array $workingDays = [1, 2, 3, 4, 5]
    ): void {
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            if (in_array($date->dayOfWeek, $workingDays, true)) {
                Schedule::updateOrCreate(
                    ['user_id' => $userId, 'date' => $date->format('Y-m-d')],
                    ['shift_id' => $shiftId],
                );
            } else {
                Schedule::where('user_id', $userId)
                    ->where('date', $date->format('Y-m-d'))
                    ->delete();
            }
        }
    }

    /**
     * Get today's schedule for a user.
     *
     * @param string $userId The user ID.
     * @return ?Schedule
     */
    public function getTodaySchedule(string $userId): ?Schedule
    {
        return Schedule::with('shift')
            ->where('user_id', $userId)
            ->where('date', Carbon::today()->format('Y-m-d'))
            ->first();
    }

    /**
     * Create a manual schedule for today using a flexible shift.
     *
     * @param string $userId
     * @return Schedule
     */
    public function createTodayFlexibleSchedule(string $userId): Schedule
    {
        return $this->runWithRetry(function () use ($userId) {
            return DB::transaction(function () use ($userId) {
                $shift = Shift::query()
                    ->where('is_off', false)
                    ->get(['id', 'name', 'start_time', 'end_time', 'is_overnight', 'is_off', 'color'])
                    ->first(function ($s) {
                        return \Illuminate\Support\Str::lower((string) $s->name) === 'flexible';
                    });
                if (!$shift) {
                    $shift = new Shift();
                    $shift->name = 'Flexible';
                    $shift->start_time = null;
                    $shift->end_time = null;
                    $shift->is_overnight = false;
                    $shift->is_off = false;
                    $shift->color = 'blue';
                    $shift->save();
                }
                $today = Carbon::today()->toDateString();
                $schedule = Schedule::query()
                    ->where('user_id', $userId)
                    ->where('date', $today)
                    ->first();
                if ($schedule) {
                    $schedule->shift_id = (string) $shift->id;
                    $schedule->is_manual = true;
                    $schedule->save();
                } else {
                    $schedule = new Schedule();
                    $schedule->user_id = $userId;
                    $schedule->shift_id = (string) $shift->id;
                    $schedule->date = $today;
                    $schedule->is_manual = true;
                    $schedule->save();
                }
                $schedule->load(['shift']);
                return $schedule;
            }, 3);
        }, 3);
    }

    /**
     * Build schedule matrix for a role.
     *
     * @param ?string $roleId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function buildMatrix(?string $roleId, string $startDate, string $endDate): array
    {
        $this->syncIndonesianHolidays($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $d) {
            $dates[] = $d->toDateString();
        }

        $usersQuery = User::query()
            ->with(['role'])
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->whereNot('roles.name', 'Super Admin')
            ->select(['users.id', 'users.name', 'users.role_id']);
        if ($roleId) {
            $usersQuery->where('users.role_id', $roleId);
        }
        $usersQuery
            ->orderByRaw('roles.name IS NULL')
            ->orderBy('roles.name')
            ->orderBy('users.name');
        $employees = $usersQuery->get(['id', 'name', 'role_id']);
        $employeeIds = $employees->pluck('id')->all();

        $this->materializeFromRules($employeeIds, $startDate, $endDate);

        $shifts = Shift::query()
            ->orderBy('name')
            ->get(['id', 'name', 'start_time', 'end_time', 'is_overnight', 'is_off']);

        $holidays = Holiday::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_compulsory', true)
            ->get(['name', 'date', 'is_compulsory'])
            ->map(fn($h) => [
                'date' => $h->date->toDateString(),
                'name' => (string) $h->name,
                'is_compulsory' => (bool) $h->is_compulsory,
            ])
            ->all();

        $schedules = Schedule::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $employeeIds)
            ->get(['user_id', 'date', 'shift_id']);

        $map = [];
        foreach ($schedules as $s) {
            $uid = (string) $s->user_id;
            $dt = $s->date instanceof Carbon ? $s->date->toDateString() : (string) $s->date;
            $map[$uid] ??= [];
            $map[$uid][$dt] = $s->shift_id ? (string) $s->shift_id : null;
        }

        $items = $employees->map(fn($u) => [
            'id' => (string) $u->id,
            'name' => (string) $u->name,
            'role' => $u->role ? [
                'id' => (string) $u->role->id,
                'name' => (string) $u->role->name,
            ] : null,
        ])->all();

        return [
            'employees' => $items,
            'dates' => $dates,
            'shifts' => $shifts->map(fn($s) => [
                'id' => (string) $s->id,
                'name' => (string) $s->name,
                'start_time' => $this->formatTime($s->start_time),
                'end_time' => $this->formatTime($s->end_time),
                'is_overnight' => (bool) $s->is_overnight,
                'is_off' => (bool) $s->is_off,
            ])->all(),
            'scheduleMap' => $map,
            'holidays' => $holidays,
        ];
    }

    /**
     * Save schedule batch.
     *
     * @param ScheduleBatchData $data The schedule batch data.
     * @return void
     */
    public function saveBatch(ScheduleBatchData $data): void
    {
        $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                foreach ($data->entries as $e) {
                    $userId = $e->userId;
                    $date = $e->date;
                    $shiftId = $e->shiftId;
                    if ($shiftId === null) {
                        Schedule::where('user_id', $userId)
                            ->where('date', $date)
                            ->delete();
                    } else {
                        $schedule = Schedule::updateOrCreate(
                            ['user_id' => $userId, 'date' => $date],
                            ['shift_id' => $shiftId],
                        );
                        if ($schedule) {
                            $schedule->is_manual = true;
                            $schedule->save();
                        }
                    }
                }
                return null;
            }, 5);
        }, 3);
    }

    /**
     * Sync Indonesian holidays.
     *
     * @param string $startDate The start date.
     * @param string $endDate The end date.
     * @return void
     */
    private function syncIndonesianHolidays(string $startDate, string $endDate): void
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $baseYears = range($start->year, $end->year);
        $currentYear = Carbon::today()->year;
        $years = array_values(array_unique(array_merge($baseYears, [$currentYear])));
        sort($years);
        $shouldLog = (bool) config('app.env') !== 'production';
        $nationalByDate = [];
        foreach ($years as $year) {
            try {
                $resp = Http::timeout(10)->get('https://api-harilibur.vercel.app/api', ['year' => $year]);
            } catch (ConnectionException $e) {
                if (app()->isLocal() && str_contains(strtolower($e->getMessage()), 'ssl certificate')) {
                    if ($shouldLog) {
                        Log::warning('holidays.sync.retry_insecure', ['year' => $year]);
                    }
                    try {
                        $resp = Http::timeout(10)->withOptions(['verify' => false])->get('https://api-harilibur.vercel.app/api', ['year' => $year]);
                    } catch (\Throwable $e2) {
                        if ($shouldLog) {
                            Log::warning('holidays.sync.error', ['year' => $year, 'error' => get_class($e2), 'message' => $e2->getMessage()]);
                        }
                        continue;
                    }
                } else {
                    if ($shouldLog) {
                        Log::warning('holidays.sync.error', ['year' => $year, 'error' => get_class($e), 'message' => $e->getMessage()]);
                    }
                    continue;
                }
            } catch (\Throwable $e) {
                if ($shouldLog) {
                    Log::warning('holidays.sync.error', ['year' => $year, 'error' => get_class($e), 'message' => $e->getMessage()]);
                }
                continue;
            }
            if (!$resp->ok()) {
                if ($shouldLog) {
                    Log::warning('holidays.sync.http_not_ok', ['year' => $year, 'status' => $resp->status()]);
                }
                continue;
            }
            $items = $resp->json();
            if (!is_array($items)) {
                if ($shouldLog) {
                    Log::warning('holidays.sync.invalid_json', ['year' => $year]);
                }
                continue;
            }
            $addedForYear = 0;
            foreach ($items as $it) {
                $dateStr = (string) ($it['holiday_date'] ?? $it['date'] ?? '');
                $name = (string) ($it['holiday_name'] ?? $it['event'] ?? '');
                $isNational = (bool) ($it['is_national_holiday'] ?? false);
                if ($dateStr === '' || $name === '' || !$isNational) {
                    continue;
                }
                $dt = Carbon::parse($dateStr);
                $key = $dt->toDateString();
                $nationalByDate[$key] = [
                    'name' => $name,
                    'date' => $key,
                    'is_compulsory' => true,
                ];
                $addedForYear++;
            }
        }
        if (empty($nationalByDate)) {
            return;
        }
        $dates = array_keys($nationalByDate);
        $existing = Holiday::query()
            ->whereIn('date', $dates)
            ->get(['date'])
            ->map(fn($h) => $h->date->toDateString())
            ->all();
        $toInsert = [];
        foreach ($dates as $d) {
            if (!in_array($d, $existing, true)) {
                $toInsert[] = $nationalByDate[$d];
            }
        }
        $toUpdate = array_intersect($dates, $existing);
        if (!empty($toInsert)) {
            $this->runWithRetry(function () use ($toInsert) {
                return DB::transaction(function () use ($toInsert) {
                    foreach ($toInsert as $row) {
                        \App\Models\Holiday::create($row);
                    }
                    return null;
                }, 3);
            }, 3);
        }
        if (!empty($toUpdate)) {
            $this->runWithRetry(function () use ($toUpdate, $nationalByDate) {
                return DB::transaction(function () use ($toUpdate, $nationalByDate) {
                    foreach ($toUpdate as $d) {
                        \App\Models\Holiday::query()
                            ->where('date', $d)
                            ->update([
                                'name' => $nationalByDate[$d]['name'],
                                'is_compulsory' => true,
                                'updated_at' => Carbon::now(),
                            ]);
                    }
                    return null;
                }, 3);
            }, 3);
        }
    }

    private function formatTime(?string $t): ?string
    {
        if ($t === null || $t === '') {
            return null;
        }
        return substr($t, 0, 5);
    }

    private function materializeFromRules(array $employeeIds, string $startDate, string $endDate): void
    {
        if (empty($employeeIds)) {
            return;
        }
        $dates = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $d) {
            $dates[] = $d->toDateString();
        }
        $holidayDates = Holiday::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_compulsory', true)
            ->get(['date'])
            ->map(fn($h) => $h->date->toDateString())
            ->all();
        $holidaySet = array_flip($holidayDates);
        $offShiftId = Shift::query()->where('is_off', true)->value('id');
        $attendedRows = Schedule::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $employeeIds)
            ->whereHas('attendance')
            ->get(['user_id', 'date'])
            ->map(fn($s) => [
                'user_id' => (string) $s->user_id,
                'date' => $s->date instanceof Carbon ? $s->date->toDateString() : (string) $s->date,
            ])
            ->all();
        $attendedSet = [];
        foreach ($attendedRows as $ar) {
            $attendedSet[$ar['user_id'] . '|' . $ar['date']] = true;
        }
        Schedule::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $employeeIds)
            ->where('is_manual', false)
            ->whereDoesntHave('attendance')
            ->delete();
        $existingRows = Schedule::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $employeeIds)
            ->get(['id', 'user_id', 'date', 'is_manual'])
            ->map(fn($s) => [
                'id' => (string) $s->id,
                'user_id' => (string) $s->user_id,
                'date' => $s->date instanceof Carbon ? $s->date->toDateString() : (string) $s->date,
                'is_manual' => (bool) ($s->is_manual ?? false),
            ])
            ->all();
        $existingMap = [];
        $manualSet = [];
        foreach ($existingRows as $row) {
            $key = $row['user_id'] . '|' . $row['date'];
            $existingMap[$key] = $row;
            if ($row['is_manual']) {
                $manualSet[$key] = true;
            }
            if (isset($attendedSet[$key])) {
                $manualSet[$key] = true;
            }
        }
        $userRules = \App\Models\ScheduleRule::query()
            ->whereIn('user_id', $employeeIds)
            ->where('is_active', true)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'user_id',
                'start_date',
                'end_date',
                'rotation_even_shift_id',
                'rotation_odd_shift_id',
                'rotation_off_day',
            ]);
        $ruleIds = $userRules->pluck('id')->all();
        $details = empty($ruleIds) ? collect() : \App\Models\ScheduleRuleDetail::query()
            ->whereIn('schedule_rule_id', $ruleIds)
            ->get(['schedule_rule_id', 'day_of_week', 'shift_id']);
        $ruleDetails = [];
        foreach ($details as $d) {
            $rid = (string) $d->schedule_rule_id;
            $ruleDetails[$rid] ??= [];
            $ruleDetails[$rid][(int) $d->day_of_week] = $d->shift_id ? (string) $d->shift_id : null;
        }
        $userRulesBySubject = [];
        foreach ($userRules as $r) {
            $uid = (string) $r->user_id;
            $userRulesBySubject[$uid] ??= [];
            $userRulesBySubject[$uid][] = [
                'id' => (string) $r->id,
                'start_date' => $r->start_date ? Carbon::parse($r->start_date)->toDateString() : null,
                'end_date' => $r->end_date ? Carbon::parse($r->end_date)->toDateString() : null,
                'rotation_even_shift_id' => $r->rotation_even_shift_id ? (string) $r->rotation_even_shift_id : null,
                'rotation_odd_shift_id' => $r->rotation_odd_shift_id ? (string) $r->rotation_odd_shift_id : null,
                'rotation_off_day' => $r->rotation_off_day !== null ? (int) $r->rotation_off_day : null,
            ];
        }
        $toInsert = [];
        $toUpdate = [];
        foreach ($employeeIds as $uidRaw) {
            $uid = (string) $uidRaw;
            foreach ($dates as $dt) {
                $key = $uid . '|' . $dt;
                if (isset($manualSet[$key])) {
                    continue;
                }
                $day = Carbon::parse($dt)->dayOfWeek;
                $dayMonFirst = ($day + 6) % 7;
                $shiftId = null;
                $uCandidates = $userRulesBySubject[$uid] ?? [];
                foreach ($uCandidates as $rule) {
                    $sd = $rule['start_date'];
                    $ed = $rule['end_date'];
                    $inRange = ($sd === null || $sd <= $dt) && ($ed === null || $dt <= $ed);
                    if ($inRange) {
                        $rid = $rule['id'];
                        $candidate = null;
                        $re = $rule['rotation_even_shift_id'] ?? null;
                        $ro = $rule['rotation_odd_shift_id'] ?? null;
                        $rd = $rule['rotation_off_day'] ?? null;
                        if ($re !== null || $ro !== null) {
                            if ($rd !== null && $dayMonFirst === (int) $rd) {
                                $candidate = null;
                            } else {
                                $week = Carbon::parse($dt)->weekOfYear;
                                $isEven = $week % 2 === 0;
                                if ($isEven && $re !== null) {
                                    $candidate = (string) $re;
                                } elseif (!$isEven && $ro !== null) {
                                    $candidate = (string) $ro;
                                } else {
                                    $candidate = $ruleDetails[$rid][$dayMonFirst] ?? null;
                                }
                            }
                        } else {
                            $candidate = $ruleDetails[$rid][$dayMonFirst] ?? null;
                        }
                        if ($candidate !== null) {
                            $shiftId = $candidate;
                            break;
                        }
                    }
                }
                if ($shiftId === null && $offShiftId) {
                    $shiftId = (string) $offShiftId;
                }
                if ($shiftId) {
                    if (isset($existingMap[$key])) {
                        $row = $existingMap[$key];
                        if (!$row['is_manual'] && !isset($attendedSet[$key])) {
                            $toUpdate[] = [
                                'id' => $row['id'],
                                'shift_id' => $shiftId,
                                'updated_at' => Carbon::now(),
                                'is_manual' => false,
                            ];
                        }
                    } else {
                        $toInsert[] = [
                            'user_id' => $uid,
                            'shift_id' => $shiftId,
                            'date' => $dt,
                            'is_manual' => false,
                        ];
                    }
                }
            }
        }
        if (!empty($toInsert) || !empty($toUpdate)) {
            $this->runWithRetry(function () use ($toInsert, $toUpdate) {
                return DB::transaction(function () use ($toInsert, $toUpdate) {
                    if (!empty($toUpdate)) {
                        foreach ($toUpdate as $u) {
                            Schedule::query()
                                ->where('id', $u['id'])
                                ->update([
                                    'shift_id' => $u['shift_id'],
                                    'is_manual' => $u['is_manual'],
                                    'updated_at' => $u['updated_at'],
                                ]);
                        }
                    }
                    if (!empty($toInsert)) {
                        foreach ($toInsert as $row) {
                            Schedule::create([
                                'user_id' => $row['user_id'],
                                'shift_id' => $row['shift_id'],
                                'date' => $row['date'],
                                'is_manual' => $row['is_manual'],
                            ]);
                        }
                    }
                    return null;
                }, 3);
            }, 3);
        }
    }
}
