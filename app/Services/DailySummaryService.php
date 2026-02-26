<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{DailySummary, DropPoint, Order, ProductSummary};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log};
use Throwable;

/**
 * Service responsible for generating and persisting daily order summaries.
 *
 * Provides two aggregation jobs:
 * - generateForDate()        → DailySummary per drop point per day
 * - generateProductSummaryForDate() → ProductSummary per product per day
 *
 * Both are idempotent (upsert) and intended to run nightly via scheduled Artisan command.
 */
class DailySummaryService
{
    /**
     * Generate and persist daily summaries for all drop points for the given date.
     *
     * Queries are scoped to orders whose `created_at` date matches $date. Each drop point
     * gets its own summary row (upserted to avoid duplicates on re-run).
     *
     * @param  Carbon $date The date to generate summaries for.
     * @return int          Number of drop points processed.
     * @throws Throwable
     */
    public function generateForDate(Carbon $date): int
    {
        try {
            return DB::transaction(function () use ($date) {
                $dateStr    = $date->toDateString();
                $dropPoints = DropPoint::all();
                $processed  = 0;

                foreach ($dropPoints as $dropPoint) {
                    $base = Order::query()
                        ->where('drop_point_id', $dropPoint->id)
                        ->whereDate('created_at', $dateStr);

                    $totalOrders = (clone $base)->count();

                    // Completed orders contribute to revenue
                    $revenueBase      = (clone $base)->where('order_status', 'delivered');
                    $totalRevenue     = (int) $revenueBase->sum('total_amount');
                    $totalDeliveryFee = (int) $revenueBase->sum('delivery_fee');
                    $totalAdminFee    = (int) $revenueBase->sum('admin_fee');
                    $totalDiscount    = (int) $revenueBase->sum('discount_amount');

                    $totalItems = (int) DB::table('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->where('orders.drop_point_id', $dropPoint->id)
                        ->where('orders.order_status', 'delivered')
                        ->whereDate('orders.created_at', $dateStr)
                        ->sum('order_items.quantity');

                    $totalPending = (clone $base)
                        ->whereIn('order_status', ['pending', 'confirmed', 'shipped'])
                        ->count();

                    $totalCancelled = (clone $base)
                        ->where('order_status', 'cancelled')
                        ->count();

                    DailySummary::updateOrCreate(
                        ['drop_point_id' => $dropPoint->id, 'date' => $dateStr],
                        [
                            'total_orders'       => $totalOrders,
                            'total_items'        => $totalItems,
                            'total_revenue'      => $totalRevenue,
                            'total_pending'      => $totalPending,
                            'total_cancelled'    => $totalCancelled,
                            'total_delivery_fee' => $totalDeliveryFee,
                            'total_admin_fee'    => $totalAdminFee,
                            'total_discount'     => $totalDiscount,
                        ]
                    );

                    $processed++;
                }

                return $processed;
            });
        } catch (Throwable $e) {
            Log::error('DailySummaryService - Gagal membuat ringkasan harian', [
                'date'  => $date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate and persist product sales summaries for the given date.
     *
     * Aggregates total_sold (quantity) and total_revenue (subtotal) per product
     * from order_items belonging to delivered orders on $date. Only products that
     * actually had sales on that day will have a row created.
     *
     * @param  Carbon $date The date to generate product summaries for.
     * @return int          Number of distinct products processed.
     * @throws Throwable
     */
    public function generateProductSummaryForDate(Carbon $date): int
    {
        try {
            return DB::transaction(function () use ($date) {
                $dateStr = $date->toDateString();

                $rows = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.order_status', 'delivered')
                    ->whereDate('orders.created_at', $dateStr)
                    ->whereNull('orders.deleted_at')
                    ->selectRaw('order_items.product_id, SUM(order_items.quantity) as total_sold, SUM(order_items.subtotal) as total_revenue')
                    ->groupBy('order_items.product_id')
                    ->get();

                foreach ($rows as $row) {
                    ProductSummary::updateOrCreate(
                        ['product_id' => $row->product_id, 'date' => $dateStr],
                        [
                            'total_sold'    => (int) $row->total_sold,
                            'total_revenue' => (int) $row->total_revenue,
                        ]
                    );
                }

                return $rows->count();
            });
        } catch (Throwable $e) {
            Log::error('DailySummaryService - Gagal membuat ringkasan produk harian', [
                'date'  => $date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
