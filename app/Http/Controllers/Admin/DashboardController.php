<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order, Customer, OrderItem};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * @return Response
     */
    public function index(): Response
    {
        $now = Carbon::now();
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();

        // 1. Summary Statistics
        $stats = [
            'total_revenue' => (int) Order::where('payment_status', 'paid')->sum('total_amount'),
            'total_orders' => Order::count(),
            'total_customers' => Customer::count(),
            'today_orders' => Order::whereDate('created_at', $now->toDateString())->count(),
        ];

        // 2. Sales Trend (Last 6 Months)
        $driver = DB::getDriverName();
        $dateField = $driver === 'pgsql'
            ? 'TO_CHAR(created_at, \'YYYY-MM\')'
            : 'DATE_FORMAT(created_at, "%Y-%m")';

        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->select(
                DB::raw("$dateField as month"),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('revenue', 'month');

        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i)->format('Y-m');
            $chartData[] = [
                'label' => $now->copy()->subMonths($i)->format('M'),
                'value' => (int) ($monthlyRevenue[$month] ?? 0),
            ];
        }

        // 3. Recent Orders
        $recentOrders = Order::with('customer:id,name,email')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer' => [
                    'name' => $order->customer?->name ?? 'Guest',
                    'email' => $order->customer?->email ?? '',
                ],
                'delivery_date' => $order->delivery_date?->toDateString(),
                'delivery_time' => $order->delivery_time?->format('H:i'),
                'total_amount' => $order->total_amount,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        // 4. Top Products (by quantity)
        $topProducts = OrderItem::with('product:id,name,image')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->product_id,
                'name' => $item->product?->name ?? 'Unknown',
                'total_sold' => (int) $item->total_sold,
                'image' => $item->product?->image,
            ]);

        return Inertia::render('Domains/Admin/Dashboard/Index', [
            'stats' => $stats,
            'chartData' => $chartData,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
        ]);
    }
}
