<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Report\ReportFilterData;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\DropPoint;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles admin report views and exports.
 *
 * Supports two report types (controlled by ?type= query param):
 *   - orders   → paginated order list with summary stats
 *   - products → product sales aggregation sorted by qty sold
 *
 * Export actions (exportPdf, exportExcel) trigger file downloads using the same filters.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * Display the reports overview page with filtered data.
     */
    public function index(ReportFilterData $dto): Response
    {
        $filters = [
            'date_from' => $dto->dateFrom,
            'date_to' => $dto->dateTo,
            'drop_point_id' => $dto->dropPointId,
            'per_page' => 15,
        ];

        $type = $dto->type ?? 'orders';

        $reportData = $type === 'products'
            ? $this->reportService->getProductReport($filters)
            : $this->reportService->getSalesReport($filters);

        $dropPoints = $this->reportService->getDropPoints()
            ->map(fn (DropPoint $dp) => ['value' => $dp->id, 'label' => $dp->name])
            ->prepend(['value' => '', 'label' => 'Semua Drop Point'])
            ->values();

        return Inertia::render('Domains/Admin/Report/Index', [
            'type' => $type,
            'filters' => $filters,
            'report' => $reportData,
            'drop_points' => $dropPoints,
        ]);
    }

    /**
     * Export the report as a downloadable PDF.
     */
    public function exportPdf(ReportFilterData $dto): HttpResponse
    {
        $filters = [
            'date_from' => $dto->dateFrom,
            'date_to' => $dto->dateTo,
            'drop_point_id' => $dto->dropPointId,
        ];

        $type = $dto->type ?? 'orders';
        $settings = CompanyProfile::query()->first();
        $dropPoint = $filters['drop_point_id'] ? DropPoint::find($filters['drop_point_id']) : null;

        if ($type === 'products') {
            $products = $this->reportService->getProductsForExport($filters);
            $summary = [
                'total_sold' => (int) $products->sum('total_sold'),
                'total_revenue' => (int) $products->sum('total_revenue'),
                'total_cost' => (int) $products->sum('total_cost'),
                'total_profit' => (int) $products->sum('total_profit'),
            ];

            $pdf = Pdf::loadView('exports.products-report', [
                'products' => $products,
                'summary' => $summary,
                'settings' => $settings,
                'dateFrom' => $filters['date_from'],
                'dateTo' => $filters['date_to'],
                'dropPointName' => $dropPoint?->name,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan-produk-'.now()->format('Ymd-His').'.pdf');
        }

        $orders = $this->reportService->getOrdersForExport($filters);
        $deliveredOrders = $orders->filter(fn (Order $o) => ($o->order_status->value ?? (string) $o->order_status) === 'delivered');
        $deliveredOrderIds = $deliveredOrders->pluck('id');
        $totalCost = (int) OrderItem::whereIn('order_id', $deliveredOrderIds)
            ->selectRaw('SUM(quantity * COALESCE(cost_price, 0)) as total_cost')
            ->value('total_cost');
        $totalRevenue = (int) $deliveredOrders->sum('total_amount');
        $summary = [
            'total_orders' => $orders->count(),
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalRevenue - $totalCost,
            'total_cancelled' => $orders->filter(fn (Order $o) => ($o->order_status->value ?? (string) $o->order_status) === 'cancelled')->count(),
            'total_pending' => $orders->filter(fn (Order $o) => in_array($o->order_status->value ?? (string) $o->order_status, ['pending', 'confirmed', 'cooking', 'on_delivery', 'arrived']))->count(),
        ];

        $pdf = Pdf::loadView('exports.orders-report', [
            'orders' => $orders,
            'summary' => $summary,
            'settings' => $settings,
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
            'dropPointName' => $dropPoint?->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-pesanan-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Export the report as a downloadable Excel (.xlsx) file.
     */
    public function exportExcel(ReportFilterData $dto): BinaryFileResponse
    {
        $filters = [
            'date_from' => $dto->dateFrom,
            'date_to' => $dto->dateTo,
            'drop_point_id' => $dto->dropPointId,
        ];

        $type = $dto->type ?? 'orders';

        if ($type === 'products') {
            $products = $this->reportService->getProductsForExport($filters);

            return Excel::download(
                new ProductsExport($products),
                'laporan-produk-'.now()->format('Ymd-His').'.xlsx'
            );
        }

        $orders = $this->reportService->getOrdersForExport($filters);

        return Excel::download(
            new OrdersExport($orders),
            'laporan-pesanan-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}
