<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use App\Models\ChefTransfer;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

/**
 * Controller for managing chef report and income transfers.
 */
class ReportController extends Controller
{
    /**
     * Display a report of income and transfers for the chef.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request): Response
    {
        $chef = Auth::guard('chef')->user();

        $dateRange = $request->input('date_range', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Query transfers
        $transfersQuery = ChefTransfer::query()->where('chef_id', $chef->id);

        if ($dateRange === 'custom' && $startDate && $endDate) {
            $transfersQuery->whereBetween('transferred_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } elseif ($dateRange === '30_days') {
            $transfersQuery->where('transferred_at', '>=', Carbon::now()->subDays(30));
        } elseif ($dateRange === '90_days') {
            $transfersQuery->where('transferred_at', '>=', Carbon::now()->subDays(90));
        }

        $transfers = $transfersQuery->latest('transferred_at')->get();

        $totalTransferredAmount = $transfers->sum('amount');

        // Query sales / pendapatan
        $productIds = $chef->products()->pluck('products.id');

        $ordersQuery = OrderItem::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('order', function ($q) use ($dateRange, $startDate, $endDate) {
                $q->where('payment_status', 'paid')
                    ->where('order_status', '!=', 'cancelled');

                if ($dateRange === 'custom' && $startDate && $endDate) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ]);
                } elseif ($dateRange === '30_days') {
                    $q->where('created_at', '>=', Carbon::now()->subDays(30));
                } elseif ($dateRange === '90_days') {
                    $q->where('created_at', '>=', Carbon::now()->subDays(90));
                }
            });

        $totalSales = (int) $ordersQuery->sum('subtotal');

        $feePercentage = (float) $chef->fee_percentage;
        $totalFeeAmount = (int) round($totalSales * $feePercentage / 100);

        $netSales = $totalSales - $totalFeeAmount;
        $outstandingBalance = $netSales - $totalTransferredAmount;

        return Inertia::render('Domains/Chef/Report/Index', [
            'transfers' => $transfers,
            'summary' => [
                'total_income' => $totalSales,
                'fee_amount' => $totalFeeAmount,
                'net_income' => $netSales,
                'withdrawn' => $totalTransferredAmount,
                'unwithdrawn' => $outstandingBalance,
            ],
            'filters' => [
                'date_range' => $dateRange,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }
}
