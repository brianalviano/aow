<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\{OrdersExport, ProductsExport};
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\ReportRequest;
use App\Models\{CompanyProfile, DropPoint};
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\{RedirectResponse, Response as HttpResponse};
use Inertia\{Inertia, Response};
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
     *
     * @param  ReportRequest $request
     * @return Response
     */
    public function index(ReportRequest $request): Response
    {
        $filters = [
            'date_from'     => $request->input('date_from'),
            'date_to'       => $request->input('date_to'),
            'drop_point_id' => $request->input('drop_point_id'),
            'per_page'      => 15,
        ];

        $type = $request->input('type', 'orders');

        $reportData = $type === 'products'
            ? $this->reportService->getProductReport($filters)
            : $this->reportService->getSalesReport($filters);

        $dropPoints = $this->reportService->getDropPoints()
            ->map(fn(DropPoint $dp) => ['value' => $dp->id, 'label' => $dp->name])
            ->prepend(['value' => '', 'label' => 'Semua Drop Point'])
            ->values();

        return Inertia::render('Domains/Admin/Report/Index', [
            'type'        => $type,
            'filters'     => $filters,
            'report'      => $reportData,
            'drop_points' => $dropPoints,
        ]);
    }

    /**
     * Export the report as a downloadable PDF.
     *
     * @param  ReportRequest $request
     * @return HttpResponse
     */
    public function exportPdf(ReportRequest $request): HttpResponse
    {
        $filters = [
            'date_from'     => $request->input('date_from'),
            'date_to'       => $request->input('date_to'),
            'drop_point_id' => $request->input('drop_point_id'),
        ];

        $type     = $request->input('type', 'orders');
        $settings = CompanyProfile::query()->first();
        $dropPoint = $filters['drop_point_id'] ? DropPoint::find($filters['drop_point_id']) : null;

        if ($type === 'products') {
            $products = $this->reportService->getProductsForExport($filters);
            $summary  = [
                'total_sold'    => (int) $products->sum('total_sold'),
                'total_revenue' => (int) $products->sum('total_revenue'),
            ];

            $pdf = Pdf::loadView('exports.products-report', [
                'products'      => $products,
                'summary'       => $summary,
                'settings'      => $settings,
                'dateFrom'      => $filters['date_from'],
                'dateTo'        => $filters['date_to'],
                'dropPointName' => $dropPoint?->name,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan-produk-' . now()->format('Ymd-His') . '.pdf');
        }

        $orders = $this->reportService->getOrdersForExport($filters);
        $summary = [
            'total_orders'    => $orders->count(),
            'total_revenue'   => (int) $orders->where('order_status', 'delivered')->sum('total_amount'),
            'total_cancelled' => $orders->where('order_status', 'cancelled')->count(),
            'total_pending'   => $orders->whereIn('order_status', ['pending', 'confirmed', 'shipped'])->count(),
        ];

        $pdf = Pdf::loadView('exports.orders-report', [
            'orders'        => $orders,
            'summary'       => $summary,
            'settings'      => $settings,
            'dateFrom'      => $filters['date_from'],
            'dateTo'        => $filters['date_to'],
            'dropPointName' => $dropPoint?->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-pesanan-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * Export the report as a downloadable Excel (.xlsx) file.
     *
     * @param  ReportRequest $request
     * @return BinaryFileResponse
     */
    public function exportExcel(ReportRequest $request): BinaryFileResponse
    {
        $filters = [
            'date_from'     => $request->input('date_from'),
            'date_to'       => $request->input('date_to'),
            'drop_point_id' => $request->input('drop_point_id'),
        ];

        $type = $request->input('type', 'orders');

        if ($type === 'products') {
            $products = $this->reportService->getProductsForExport($filters);

            return Excel::download(
                new ProductsExport($products),
                'laporan-produk-' . now()->format('Ymd-His') . '.xlsx'
            );
        }

        $orders = $this->reportService->getOrdersForExport($filters);

        return Excel::download(
            new OrdersExport($orders),
            'laporan-pesanan-' . now()->format('Ymd-His') . '.xlsx'
        );
    }
}
