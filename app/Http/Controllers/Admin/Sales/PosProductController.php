<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\ProductPriceService;
use Illuminate\Http\Request;
use Inertia\{Inertia, Response};
use App\Models\{Product, SellingPriceLevel, ProductSellingPrice, Stock, Warehouse};
use Illuminate\Support\Facades\DB;

class PosProductController extends Controller
{
    /**
     * Tampilkan daftar produk POS (list-only) dengan harga per level dan stok.
     *
     * Pencarian berdasarkan nama atau SKU. Tidak ada halaman detail.
     *
     * @param Request $request
     * @param ProductPriceService $priceService
     * @return Response
     */
    public function index(Request $request, ProductPriceService $priceService): Response
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'sku']);

        $productIds = $products->pluck('id')->map(fn($id) => (string) $id)->all();

        $centralWarehouseIds = Warehouse::query()
            ->where('is_central', true)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $stockMap = [];
        if (!empty($productIds) && !empty($centralWarehouseIds)) {
            $stockRows = Stock::query()
                ->whereIn('product_id', $productIds)
                ->whereIn('warehouse_id', $centralWarehouseIds)
                ->select(['product_id', DB::raw('SUM(quantity) as quantity')])
                ->groupBy(['product_id'])
                ->get();
            foreach ($stockRows as $row) {
                $stockMap[(string) $row->product_id] = (int) $row->quantity;
            }
        }

        $sellingMainMap = $priceService->getSellingPriceMainMap($productIds);
        $sellingPriceMap = $priceService->getSellingPriceMap($productIds);

        $levels = SellingPriceLevel::query()
            ->orderBy('name')
            ->get(['id', 'name', 'percent_adjust'])
            ->map(fn($l) => [
                'id' => (string) $l->id,
                'name' => (string) $l->name,
                'percent_adjust' => $l->percent_adjust !== null ? (float) $l->percent_adjust : null,
            ])
            ->all();

        $items = $products->map(function ($p) use ($stockMap) {
            $pid = (string) $p->id;
            return [
                'id' => $pid,
                'name' => (string) $p->name,
                'sku' => $p->sku !== null ? (string) $p->sku : null,
                'stock_quantity' => (int) ($stockMap[$pid] ?? 0),
            ];
        })->all();

        return Inertia::render('Domains/Admin/Sales/POSProducts/Index', [
            'products' => $items,
            'levels' => $levels,
            'sellingPriceMainMap' => $sellingMainMap,
            'sellingPriceMap' => $sellingPriceMap,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }
}
