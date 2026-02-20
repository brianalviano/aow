<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use App\Services\LogisticDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(LogisticDashboardService $dashboard)
    {
        $user = Auth::user()->load('role');
        $role = $user->role->name ?? 'Staff';

        $data = $dashboard->buildStatsFor($role, $user);

        return Inertia::render('Domains/Admin/Logistic/Dashboard/Index', [
            'role' => $role,
            'dashboardData' => $data,
        ]);
    }
}

