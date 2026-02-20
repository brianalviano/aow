<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleRule\StoreScheduleRuleRequest;
use App\Http\Requests\ScheduleRule\UpdateScheduleRuleRequest;
use App\Models\{ScheduleRule, ScheduleRuleDetail, User, Position, Shift};
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class ScheduleRuleController extends Controller
{
    public function index(): Response
    {
        $q = (string) request()->query('q', '');

        $baseQuery = ScheduleRule::query()
            ->join('users as u', 'u.id', '=', 'schedule_rules.user_id')
            ->orderBy('u.name')
            ->select([
                'schedule_rules.id',
                'schedule_rules.user_id',
                'schedule_rules.start_date',
                'schedule_rules.end_date',
                'schedule_rules.is_active',
                'u.name as user_name',
                'u.email as user_email',
                'schedule_rules.rotation_even_shift_id',
                'schedule_rules.rotation_odd_shift_id',
                'schedule_rules.rotation_off_day',
            ]);

        if ($q !== '') {
            $baseQuery->where('u.name', 'like', '%' . $q . '%');
        }

        $rules = $baseQuery->get();

        $detailsGrouped = ScheduleRuleDetail::query()
            ->whereIn('schedule_rule_id', collect($rules)->pluck('id')->all())
            ->get(['schedule_rule_id', 'day_of_week', 'shift_id'])
            ->groupBy('schedule_rule_id');

        $shifts = Shift::query()->get(['id', 'name', 'start_time', 'end_time', 'is_overnight', 'is_off', 'color']);
        $shiftMap = $shifts->keyBy('id')->map(fn($s) => [
            'id' => (string) $s->id,
            'name' => (string) $s->name,
            'start_time' => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
            'end_time' => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
            'is_overnight' => (bool) $s->is_overnight,
            'is_off' => (bool) $s->is_off,
            'color' => $s->color ? (string) $s->color : null,
        ])->all();

        $items = collect($rules)->map(function ($r) use ($detailsGrouped, $shiftMap) {
            $userName = (string) ($r->user_name ?? 'Unknown');
            $details = ($detailsGrouped[$r->id] ?? collect())->map(function ($d) use ($shiftMap) {
                $sh = $d->shift_id ? ($shiftMap[$d->shift_id] ?? null) : null;
                return [
                    'day_of_week' => (int) $d->day_of_week,
                    'shift' => $sh,
                ];
            })->values()->all();
            $userEmail = (string) ($r->user_email ?? 'Unknown');
            return [
                'id' => (string) $r->id,
                'user_id' => (string) $r->user_id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'start_date' => $r->start_date?->toDateString(),
                'end_date' => $r->end_date?->toDateString(),
                'is_active' => (bool) $r->is_active,
                'details' => $details,
                'rotation_even_shift' => $r->rotation_even_shift_id ? ($shiftMap[$r->rotation_even_shift_id] ?? null) : null,
                'rotation_odd_shift' => $r->rotation_odd_shift_id ? ($shiftMap[$r->rotation_odd_shift_id] ?? null) : null,
                'rotation_off_day' => $r->rotation_off_day !== null ? (int) $r->rotation_off_day : null,
            ];
        })->values()->all();

        return Inertia::render('Domains/Admin/HR/ScheduleRules/Index', [
            'rules' => $items,
            'filters' => ['q' => $q],
            'shifts' => array_values($shiftMap),
        ]);
    }

    public function create(): Response
    {
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn($u) => [
            'id' => (string) $u->id,
            'name' => (string) $u->name,
        ])->all();
        $shifts = Shift::query()->orderBy('name')->get(['id', 'name', 'start_time', 'end_time', 'is_off'])->map(fn($s) => [
            'id' => (string) $s->id,
            'name' => (string) $s->name,
            'start_time' => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
            'end_time' => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
            'is_off' => (bool) $s->is_off,
        ])->all();
        $usedUserIds = ScheduleRule::query()->pluck('user_id')->all();
        return Inertia::render('Domains/Admin/HR/ScheduleRules/Form', [
            'users' => $users,
            'shifts' => $shifts,
            'rule' => null,
            'used_user_ids' => $usedUserIds,
        ]);
    }

    public function store(StoreScheduleRuleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data) {
            $rule = new ScheduleRule([
                'user_id' => $data['user_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'rotation_even_shift_id' => $data['rotation_even_shift_id'] ?? null,
                'rotation_odd_shift_id' => $data['rotation_odd_shift_id'] ?? null,
                'rotation_off_day' => $data['rotation_off_day'] ?? null,
            ]);
            $rule->save();
            if (isset($data['details']) && is_array($data['details'])) {
                $payload = array_map(function ($d) {
                    return [
                        'day_of_week' => (int) $d['day_of_week'],
                        'shift_id' => $d['shift_id'] ?? null,
                    ];
                }, $data['details']);
                if (!empty($payload)) {
                    $rule->details()->createMany($payload);
                }
            }
        });
        Inertia::flash('toast', [
            'message' => 'Aturan jadwal berhasil dibuat',
            'type' => 'success',
        ]);
        return redirect()->route('schedule-rules.index');
    }

    public function edit(ScheduleRule $scheduleRule): Response
    {
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn($u) => [
            'id' => (string) $u->id,
            'name' => (string) $u->name,
        ])->all();
        $shifts = Shift::query()->orderBy('name')->get(['id', 'name', 'start_time', 'end_time', 'is_off'])->map(fn($s) => [
            'id' => (string) $s->id,
            'name' => (string) $s->name,
            'start_time' => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
            'end_time' => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
            'is_off' => (bool) $s->is_off,
        ])->all();
        $usedUserIds = ScheduleRule::query()->pluck('user_id')->all();
        $usedUserIds = array_values(array_filter($usedUserIds, fn($id) => (string) $id !== (string) $scheduleRule->user_id));
        $details = ScheduleRuleDetail::query()
            ->where('schedule_rule_id', $scheduleRule->id)
            ->orderBy('day_of_week')
            ->get(['day_of_week', 'shift_id'])
            ->map(fn($d) => [
                'day_of_week' => (int) $d->day_of_week,
                'shift_id' => $d->shift_id ? (string) $d->shift_id : null,
            ])->all();
        return Inertia::render('Domains/Admin/HR/ScheduleRules/Form', [
            'rule' => [
                'id' => (string) $scheduleRule->id,
                'user_id' => (string) $scheduleRule->user_id,
                'start_date' => $scheduleRule->start_date?->toDateString(),
                'end_date' => $scheduleRule->end_date?->toDateString(),
                'is_active' => (bool) $scheduleRule->is_active,
                'details' => $details,
                'rotation_even_shift_id' => $scheduleRule->rotation_even_shift_id ? (string) $scheduleRule->rotation_even_shift_id : null,
                'rotation_odd_shift_id' => $scheduleRule->rotation_odd_shift_id ? (string) $scheduleRule->rotation_odd_shift_id : null,
                'rotation_off_day' => $scheduleRule->rotation_off_day !== null ? (int) $scheduleRule->rotation_off_day : null,
            ],
            'users' => $users,
            'shifts' => $shifts,
            'used_user_ids' => $usedUserIds,
        ]);
    }

    public function update(UpdateScheduleRuleRequest $request, ScheduleRule $scheduleRule): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $scheduleRule) {
            $scheduleRule->update([
                'user_id' => $data['user_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'rotation_even_shift_id' => $data['rotation_even_shift_id'] ?? null,
                'rotation_odd_shift_id' => $data['rotation_odd_shift_id'] ?? null,
                'rotation_off_day' => $data['rotation_off_day'] ?? null,
            ]);
            if (isset($data['details']) && is_array($data['details'])) {
                ScheduleRuleDetail::query()
                    ->where('schedule_rule_id', $scheduleRule->id)
                    ->delete();
                $payload = array_map(function ($d) {
                    return [
                        'day_of_week' => (int) $d['day_of_week'],
                        'shift_id' => $d['shift_id'] ?? null,
                    ];
                }, $data['details']);
                if (!empty($payload)) {
                    $scheduleRule->details()->createMany($payload);
                }
            }
            $scheduleRule->refresh();
        });
        Inertia::flash('toast', [
            'message' => 'Aturan jadwal berhasil diperbarui',
            'type' => 'success',
        ]);
        return redirect()->route('schedule-rules.index');
    }

    public function destroy(ScheduleRule $scheduleRule): RedirectResponse
    {
        DB::transaction(function () use ($scheduleRule) {
            ScheduleRuleDetail::query()
                ->where('schedule_rule_id', $scheduleRule->id)
                ->delete();
            $scheduleRule->delete();
        });
        Inertia::flash('toast', [
            'message' => 'Aturan jadwal berhasil dihapus',
            'type' => 'success',
        ]);
        return redirect()->route('schedule-rules.index');
    }
}
