<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SelfScheduleController extends Controller
{
    public function index(Request $request, ScheduleService $service): Response
    {
        $startParam = (string) $request->string('start_date')->toString();
        $endParam = (string) $request->string('end_date')->toString();

        if ($startParam === '' || $endParam === '') {
            $today = Carbon::today();
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $startOfWeek->copy()->addDays(6);
            $startParam = $startOfWeek->toDateString();
            $endParam = $endOfWeek->toDateString();
        }

        $userId = (string) auth()->id();
        $matrix = $service->buildMatrix(null, $startParam, $endParam);

        $employees = array_values(array_filter($matrix['employees'], fn($e) => $e['id'] === $userId));
        $scheduleMap = isset($matrix['scheduleMap'][$userId]) ? [$userId => $matrix['scheduleMap'][$userId]] : [];

        return Inertia::render('Domains/Admin/HR/Schedule/Self', [
            'employees' => $employees,
            'dates' => $matrix['dates'],
            'shifts' => $matrix['shifts'],
            'scheduleMap' => $scheduleMap,
            'holidays' => $matrix['holidays'],
            'filters' => [
                'start_date' => $startParam,
                'end_date' => $endParam,
            ],
        ]);
    }
}
