<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\DTOs\Schedule\ScheduleBatchData;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ScheduleController extends Controller
{
    public function index(Request $request, ScheduleService $service): Response
    {
        $startParam = (string) $request->string('start_date')->toString();
        $endParam = (string) $request->string('end_date')->toString();
        $roleId = $request->has('role_id') ? (string) $request->string('role_id')->toString() : null;

        if ($startParam === '' || $endParam === '') {
            $today = Carbon::today();
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $startOfWeek->copy()->addDays(6);
            $startParam = $startOfWeek->toDateString();
            $endParam = $endOfWeek->toDateString();
        }

        $matrix = $service->buildMatrix($roleId, $startParam, $endParam);
        $roles = \App\Models\Role::query()->orderBy('name')->get(['id', 'name'])->map(fn($r) => [
            'id' => (string) $r->id,
            'name' => (string) $r->name,
        ])->all();

        return Inertia::render('Domains/Admin/HR/Schedule/Index', [
            'employees' => $matrix['employees'],
            'dates' => $matrix['dates'],
            'shifts' => $matrix['shifts'],
            'scheduleMap' => $matrix['scheduleMap'],
            'holidays' => $matrix['holidays'],
            'roles' => $roles,
            'filters' => [
                'role_id' => $roleId,
                'start_date' => $startParam,
                'end_date' => $endParam,
            ],
        ]);
    }

    public function store(StoreScheduleRequest $request, ScheduleService $service): RedirectResponse
    {
        $dto = ScheduleBatchData::fromRequest($request);
        try {
            $service->saveBatch($dto);
            Inertia::flash('toast', [
                'message' => 'Jadwal berhasil disimpan',
                'type' => 'success',
            ]);
            return redirect()->route('schedules.index', [
                'role_id' => $dto->roleId ?? null,
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
