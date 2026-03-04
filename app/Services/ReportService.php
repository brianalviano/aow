<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{DropPoint, Order, OrderItem};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Log};
use Throwable;

/**
 * Generates report data for the admin reports page.
 *
 * Provides:
 * - getSalesReport()   → paginated order list + summary stats
 * - getProductReport() → product sales aggregation between date range
 * - getDropPoints()    → all drop points for filter options
 *
 * All queries respect date_from, date_to, and drop_point_id filters.
 * Revenue is only counted from 'delivered' orders.
 */
class ReportService
{
    /**
     * Retrieve a paginated list of orders and summary statistics.
     *
     * @param  array{date_from?: string|null, date_to?: string|null, drop_point_id?: string|null, per_page?: int} $filters
     * @return array{summary: array, orders: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     * @throws Throwable
     */
    public function getSalesReport(array $filters): array
    {
        try {
            $query = Order::query()
                ->with(['customer:id,name,email', 'dropPoint:id,name'])
                ->when($filters['date_from'] ?? null, fn(Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                ->when($filters['date_to'] ?? null, fn(Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))
                ->when($filters['drop_point_id'] ?? null, fn(Builder $q, string $id) => $q->where('drop_point_id', $id))
                ->orderByDesc('created_at');

            $summary = $this->buildSalesSummary(clone $query);

            $orders = $query->paginate($filters['per_page'] ?? 15)
                ->appends(array_filter([
                    'date_from'     => $filters['date_from'] ?? null,
                    'date_to'       => $filters['date_to'] ?? null,
                    'drop_point_id' => $filters['drop_point_id'] ?? null,
                    'type'          => 'orders',
                ]));

            return compact('summary', 'orders');
        } catch (Throwable $e) {
            Log::error('ReportService::getSalesReport - Gagal mengambil laporan penjualan', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve aggregated product sales between the given date range.
     *
     * @param  array{date_from?: string|null, date_to?: string|null, drop_point_id?: string|null, per_page?: int} $filters
     * @return array{summary: array, products: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     * @throws Throwable
     */
    public function getProductReport(array $filters): array
    {
        try {
            $query = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
                ->whereNull('orders.deleted_at')
                ->where('orders.order_status', 'delivered')
                ->when($filters['date_from'] ?? null, fn($q, string $d) => $q->whereDate('orders.created_at', '>=', $d))
                ->when($filters['date_to'] ?? null, fn($q, string $d) => $q->whereDate('orders.created_at', '<=', $d))
                ->when($filters['drop_point_id'] ?? null, fn($q, string $id) => $q->where('orders.drop_point_id', $id))
                ->selectRaw('
                    order_items.product_id,
                    products.name AS product_name,
                    product_categories.name AS category_name,
                    SUM(order_items.quantity)   AS total_sold,
                    SUM(order_items.subtotal)   AS total_revenue
                ')
                ->groupBy('order_items.product_id', 'products.name', 'product_categories.name')
                ->orderByDesc('total_sold');

            $productTotals = (clone $query)->get();
            $totalSold     = (int) $productTotals->sum('total_sold');
            $totalRevenue  = (int) $productTotals->sum('total_revenue');

            $products = $query->paginate($filters['per_page'] ?? 15)
                ->appends(array_filter([
                    'date_from'     => $filters['date_from'] ?? null,
                    'date_to'       => $filters['date_to'] ?? null,
                    'drop_point_id' => $filters['drop_point_id'] ?? null,
                    'type'          => 'products',
                ]));

            $summary = [
                'total_sold'    => $totalSold,
                'total_revenue' => $totalRevenue,
            ];

            return compact('summary', 'products');
        } catch (Throwable $e) {
            Log::error('ReportService::getProductReport - Gagal mengambil laporan produk', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve all orders (no pagination) for PDF/Excel export.
     *
     * @param  array{date_from?: string|null, date_to?: string|null, drop_point_id?: string|null} $filters
     * @return Collection<int, Order>
     * @throws Throwable
     */
    public function getOrdersForExport(array $filters): Collection
    {
        try {
            return Order::query()
                ->with(['customer:id,name,email,phone', 'dropPoint:id,name'])
                ->when($filters['date_from'] ?? null, fn(Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                ->when($filters['date_to'] ?? null, fn(Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))
                ->when($filters['drop_point_id'] ?? null, fn(Builder $q, string $id) => $q->where('drop_point_id', $id))
                ->orderByDesc('created_at')
                ->get();
        } catch (Throwable $e) {
            Log::error('ReportService::getOrdersForExport - Gagal mengambil data pesanan untuk ekspor', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve product aggregation for export (no pagination).
     *
     * @param  array{date_from?: string|null, date_to?: string|null, drop_point_id?: string|null} $filters
     * @return Collection<int, object>
     * @throws Throwable
     */
    public function getProductsForExport(array $filters): Collection
    {
        try {
            return OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
                ->whereNull('orders.deleted_at')
                ->where('orders.order_status', 'delivered')
                ->when($filters['date_from'] ?? null, fn($q, string $d) => $q->whereDate('orders.created_at', '>=', $d))
                ->when($filters['date_to'] ?? null, fn($q, string $d) => $q->whereDate('orders.created_at', '<=', $d))
                ->when($filters['drop_point_id'] ?? null, fn($q, string $id) => $q->where('orders.drop_point_id', $id))
                ->selectRaw('
                    order_items.product_id,
                    products.name AS product_name,
                    product_categories.name AS category_name,
                    SUM(order_items.quantity) AS total_sold,
                    SUM(order_items.subtotal) AS total_revenue
                ')
                ->groupBy('order_items.product_id', 'products.name', 'product_categories.name')
                ->orderByDesc('total_sold')
                ->get();
        } catch (Throwable $e) {
            Log::error('ReportService::getProductsForExport - Gagal mengambil data produk untuk ekspor', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve all drop points for the filter dropdown.
     *
     * @return Collection<int, DropPoint>
     */
    public function getDropPoints(): Collection
    {
        return DropPoint::query()->select(['id', 'name'])->orderBy('name')->get();
    }

    /**
     * Build sales summary aggregate from an existing query builder instance.
     *
     * @param  Builder $query
     * @return array{total_orders: int, total_revenue: int, total_cancelled: int, total_pending: int}
     */
    private function buildSalesSummary(Builder $query): array
    {
        $all = (clone $query)->get();

        return [
            'total_orders'    => $all->count(),
            'total_revenue'   => (int) $all->where('order_status', 'delivered')->sum('total_amount'),
            'total_cancelled' => $all->where('order_status', 'cancelled')->count(),
            'total_pending'   => $all->whereIn('order_status', ['pending', 'confirmed', 'shipped'])->count(),
        ];
    }
}
