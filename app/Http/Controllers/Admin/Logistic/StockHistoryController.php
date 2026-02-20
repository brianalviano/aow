<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Exports\StockHistoryExport;
use App\Exports\StockInImportTemplateExport;
use App\Exports\StockOutImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockHistoryResource;
use App\Models\StockCard;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\GoodsComeService;
use App\Http\Requests\Stock\StoreStockInRequest;
use App\Http\Requests\Stock\StoreStockOutRequest;
use App\Http\Requests\Stock\ImportStockInRequest;
use App\Http\Requests\Stock\ImportStockOutRequest;
use App\Imports\StockInImport;
use App\Imports\StockOutImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\ProductPriceService;
use App\Services\StockService;

class StockHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $dateFrom = (string) $request->string('date_from')->toString();
        $dateTo = (string) $request->string('date_to')->toString();
        $type = strtolower((string) $request->string('type')->toString());
        $productId = (string) $request->string('product_id')->toString();
        $bucket = strtolower((string) $request->string('bucket')->toString());
        $warehouseId = (string) $request->string('warehouse_id')->toString();

        $query = StockCard::query()
            ->with(['stock.product', 'stock.warehouse', 'user'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('type', 'ilike', "%{$q}%")
                        ->orWhere('notes', 'ilike', "%{$q}%")
                        ->orWhereHas('stock.product', function ($p) use ($q) {
                            $p->where('name', 'ilike', "%{$q}%")
                                ->orWhere('sku', 'ilike', "%{$q}%");
                        })
                        ->orWhereHas('stock.warehouse', function ($wh) use ($q) {
                            $wh->where('name', 'ilike', "%{$q}%")
                                ->orWhere('code', 'ilike', "%{$q}%");
                        });
                });
            })
            ->when($type !== '' && in_array($type, ['in', 'out'], true), function ($builder) use ($type) {
                $builder->where('type', $type);
            })
            ->when($productId !== '', function ($builder) use ($productId) {
                $builder->whereHas('stock', function ($s) use ($productId) {
                    $s->where('product_id', $productId);
                });
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->whereHas('stock', function ($s) use ($warehouseId) {
                    $s->where('warehouse_id', $warehouseId);
                });
            })
            ->when($bucket !== '' && in_array($bucket, ['vat', 'non_vat'], true), function ($builder) use ($bucket) {
                $builder->whereHas('stock', function ($s) use ($bucket) {
                    if ($bucket === 'vat') {
                        $s->where('bucket', 'vat');
                    } else {
                        $s->where(function ($q) {
                            $q->whereNull('bucket')->orWhere('bucket', 'non_vat');
                        });
                    }
                });
            })
            ->when($dateFrom !== '', function ($builder) use ($dateFrom) {
                $builder->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($builder) use ($dateTo) {
                $builder->whereDate('created_at', '<=', $dateTo);
            })
            ->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 10);
        $cards = $query->paginate($perPage)->appends([
            'q' => $q,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => $type,
            'product_id' => $productId,
            'bucket' => $bucket,
            'warehouse_id' => $warehouseId,
        ]);

        $items = array_map(
            fn($c) => StockHistoryResource::make($c)->toArray($request),
            $cards->items(),
        );

        $products = Product::query()
            ->select(['id', 'name', 'sku'])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => (string) $p->getKey(),
                'name' => (string) $p->name,
                'sku' => $p->sku ? (string) $p->sku : null,
            ])
            ->toArray();
        $warehouses = Warehouse::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($w) => [
                'id' => (string) $w->getKey(),
                'name' => (string) $w->name,
            ])
            ->toArray();

        return Inertia::render('Domains/Admin/Logistic/StockHistory/Index', [
            'stockCards' => $items,
            'meta' => [
                'current_page' => $cards->currentPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
                'last_page' => $cards->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'type' => $type,
                'product_id' => $productId,
                'bucket' => $bucket,
                'warehouse_id' => $warehouseId,
            ],
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = (string) $request->string('q')->toString();
        $dateFrom = (string) $request->string('date_from')->toString();
        $dateTo = (string) $request->string('date_to')->toString();
        $type = strtolower((string) $request->string('type')->toString());
        $productId = (string) $request->string('product_id')->toString();
        $bucket = strtolower((string) $request->string('bucket')->toString());
        $warehouseId = (string) $request->string('warehouse_id')->toString();

        return Excel::download(new StockHistoryExport($q, $dateFrom, $dateTo, $type, $productId, $bucket, $warehouseId), 'stock_history.xlsx');
    }

    public function productPrice(Request $request, ProductPriceService $priceService): JsonResponse
    {
        $productId = (string) $request->string('product_id')->toString();
        $price = $productId !== '' ? $priceService->getPurchasePrice($productId) : 0;
        return response()->json(['price' => $price]);
    }

    public function storeIn(StoreStockInRequest $request, GoodsComeService $service)
    {
        $p = $request->validated();
        $service->receiveGoods([
            'warehouse_id' => (string) $p['warehouse_id'],
            'product_id' => (string) $p['product_id'],
            'quantity' => (int) $p['quantity'],
            'unit_cost' => (int) $p['unit_cost'],
            'notes' => $p['notes'] ?? null,
            'created_by_id' => (string) $request->user()?->id,
            'is_vat' => isset($p['is_vat']) ? (bool) $p['is_vat'] : false,
        ]);
        Inertia::flash('toast', [
            'message' => 'Stok masuk berhasil dicatat',
            'type' => 'success',
        ]);
        return redirect()->route('stock-histories.index');
    }

    public function storeOut(StoreStockOutRequest $request, GoodsComeService $service)
    {
        $p = $request->validated();
        $service->issueGoods([
            'warehouse_id' => (string) $p['warehouse_id'],
            'product_id' => (string) $p['product_id'],
            'quantity' => (int) $p['quantity'],
            'notes' => $p['notes'] ?? null,
            'created_by_id' => (string) $request->user()?->id,
        ]);
        Inertia::flash('toast', [
            'message' => 'Stok keluar berhasil dicatat',
            'type' => 'success',
        ]);
        return redirect()->route('stock-histories.index');
    }

    public function importIn(ImportStockInRequest $request, GoodsComeService $service)
    {
        $file = $request->file('file');
        Excel::import(new StockInImport($service, (string) $request->user()?->id), $file);
        Inertia::flash('toast', [
            'message' => 'Import stok masuk berhasil diproses',
            'type' => 'success',
        ]);
        return redirect()->route('stock-histories.index');
    }

    public function importOut(ImportStockOutRequest $request, StockService $service)
    {
        $file = $request->file('file');
        Excel::import(new StockOutImport($service, (string) $request->user()?->id), $file);
        Inertia::flash('toast', [
            'message' => 'Import stok keluar berhasil diproses',
            'type' => 'success',
        ]);
        return redirect()->route('stock-histories.index');
    }

    public function importInTemplate(): BinaryFileResponse
    {
        return Excel::download(new StockInImportTemplateExport(), 'stock_in_import_template.xlsx');
    }

    public function importOutTemplate(): BinaryFileResponse
    {
        return Excel::download(new StockOutImportTemplateExport(), 'stock_out_import_template.xlsx');
    }
}
