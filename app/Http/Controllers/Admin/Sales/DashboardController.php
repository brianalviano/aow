<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\SalesDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(SalesDashboardService $dashboard)
    {
        $user = Auth::user()->load('role');
        $role = $user->role->name ?? 'Staff';

        $data = $dashboard->buildStatsFor($role, $user);

        return Inertia::render('Domains/Admin/Sales/Dashboard/Index', [
            'role' => $role,
            'dashboardData' => $data,
        ]);
    }
}
