<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Services\{DashboardService, ScheduleService};
use App\Enums\RoleName;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(ScheduleService $schedules, DashboardService $dashboard)
    {
        $user = Auth::user()->load('role');
        $role = $user->role->name ?? 'Staff';

        $data = $dashboard->buildStatsFor($role, $user);
        $todaySummary = $role === RoleName::ManagerHR->value
            ? null
            : $dashboard->buildTodaySummary($user, $schedules);
        $hasActiveCheckIn = $dashboard->hasActiveCheckIn((string) $user->id);

        return Inertia::render('Domains/Admin/HR/Dashboard/Index', [
            'role' => $role,
            'dashboardData' => $data,
            'today' => $todaySummary,
            'attendance_state' => [
                'has_active_check_in' => $hasActiveCheckIn,
            ],
        ]);
    }
}
