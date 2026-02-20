<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Purchasing;

use App\Http\Controllers\Controller;
use App\Services\PurchasingDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(PurchasingDashboardService $dashboard)
    {
        $user = Auth::user()->load('role');
        $role = $user->role->name ?? 'Staff';

        $data = $dashboard->buildStatsFor($role, $user);

        return Inertia::render('Domains/Admin/Purchasing/Dashboard/Index', [
            'role' => $role,
            'dashboardData' => $data,
        ]);
    }
}

