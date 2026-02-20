<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\{Product, Stock, Warehouse, User};
use Illuminate\Support\Facades\DB;
use App\Enums\{RoleName, StockBucket};

/**
 * Halaman laporan "Stok Produk".
 *
 * Menampilkan daftar produk beserta agregasi stok PPN/Non PPN dan breakdown per gudang.
 * Mendukung pencarian, filter bucket (PPN/Non PPN/Semua), filter gudang, dan paginasi.
 *
 * @author PJD
 */
class ProductStockController extends Controller
{
    /**
     * Tampilkan daftar stok produk teragregasi.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $bucket = strtolower((string) $request->string('bucket')->toString());
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $marketingId = (string) $request->string('marketing_id')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        $query = Product::query()
            ->where('is_active', true)
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');

        $products = $query->paginate($perPage)->appends([
            'q' => $q,
            'bucket' => $bucket,
            'warehouse_id' => $warehouseId,
            'per_page' => $perPage,
        ]);

        $items = collect($products->items());
        $productIds = $items->pluck('id')->map(fn($id) => (string) $id)->all();

        $warehouses = Warehouse::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($w) => [
                'id' => (string) $w->getKey(),
                'name' => (string) $w->name,
            ])
            ->toArray();

        $marketings = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => (string) $u->getKey(),
                'name' => (string) $u->name,
            ])
            ->toArray();

        $totalVat = [];
        $totalNonVat = [];
        $warehouseBreakdown = [];
        $marketingBreakdown = [];

        if (!empty($productIds)) {
            // Ambil semua stok per gudang & bucket
            $stockRows = Stock::query()
                ->whereIn('product_id', $productIds)
                ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                    $builder->where('warehouse_id', $warehouseId);
                })
                ->when($marketingId !== '', function ($builder) use ($marketingId) {
                    $builder->where('owner_user_id', $marketingId);
                })
                ->select([
                    'warehouse_id',
                    'product_id',
                    'owner_user_id',
                    DB::raw("CASE WHEN bucket = 'vat' THEN 'vat' ELSE 'non_vat' END AS bucket"),
                    DB::raw('SUM(quantity) as quantity'),
                ])
                ->groupBy([
                    'warehouse_id',
                    'product_id',
                    'owner_user_id',
                    DB::raw("CASE WHEN bucket = 'vat' THEN 'vat' ELSE 'non_vat' END"),
                ])
                ->get();

            foreach ($stockRows as $row) {
                $pid = (string) $row->product_id;
                $wid = (string) $row->warehouse_id;
                $oid = $row->owner_user_id ? (string) $row->owner_user_id : '';
                $b = $row->bucket instanceof StockBucket ? (string) $row->bucket->value : ((string) ($row->bucket ?? '')); // 'vat' atau 'non_vat'
                $qty = (int) $row->quantity;

                if ($b === 'vat') {
                    $totalVat[$pid] = ($totalVat[$pid] ?? 0) + $qty;
                } else {
                    $totalNonVat[$pid] = ($totalNonVat[$pid] ?? 0) + $qty;
                }

                if (!isset($warehouseBreakdown[$pid])) {
                    $warehouseBreakdown[$pid] = [];
                }
                if (!isset($warehouseBreakdown[$pid][$wid])) {
                    $warehouseBreakdown[$pid][$wid] = [
                        'vat_quantity' => 0,
                        'non_vat_quantity' => 0,
                    ];
                }
                if ($b === 'vat') {
                    $warehouseBreakdown[$pid][$wid]['vat_quantity'] += $qty;
                } else {
                    $warehouseBreakdown[$pid][$wid]['non_vat_quantity'] += $qty;
                }

                if (!isset($marketingBreakdown[$pid])) {
                    $marketingBreakdown[$pid] = [];
                }
                $mkKey = $oid !== '' ? $oid : null;
                if ($mkKey === null) {
                    continue;
                }
                if (!isset($marketingBreakdown[$pid][$mkKey])) {
                    $marketingBreakdown[$pid][$mkKey] = [
                        'vat_quantity' => 0,
                        'non_vat_quantity' => 0,
                    ];
                }
                if ($b === 'vat') {
                    $marketingBreakdown[$pid][$mkKey]['vat_quantity'] += $qty;
                } else {
                    $marketingBreakdown[$pid][$mkKey]['non_vat_quantity'] += $qty;
                }
            }
        }

        $warehouseMap = [];
        foreach ($warehouses as $w) {
            $warehouseMap[$w['id']] = $w['name'];
        }
        $marketingMap = [];
        foreach ($marketings as $m) {
            $marketingMap[$m['id']] = $m['name'];
        }

        $itemsOut = $items->map(function ($p) use ($totalVat, $totalNonVat, $warehouseBreakdown, $warehouseMap, $marketingBreakdown, $marketingMap) {
            $pid = (string) $p->id;
            $break = [];
            foreach (($warehouseBreakdown[$pid] ?? []) as $wid => $vals) {
                $break[] = [
                    'id' => $wid,
                    'name' => $warehouseMap[$wid] ?? '',
                    'vat_quantity' => (int) ($vals['vat_quantity'] ?? 0),
                    'non_vat_quantity' => (int) ($vals['non_vat_quantity'] ?? 0),
                ];
            }
            $mBreak = [];
            foreach ($marketingMap as $mid => $mname) {
                $vals = $marketingBreakdown[$pid][$mid] ?? ['vat_quantity' => 0, 'non_vat_quantity' => 0];
                $mBreak[] = [
                    'id' => $mid,
                    'name' => $mname,
                    'vat_quantity' => (int) ($vals['vat_quantity'] ?? 0),
                    'non_vat_quantity' => (int) ($vals['non_vat_quantity'] ?? 0),
                ];
            }
            return [
                'id' => $pid,
                'name' => (string) $p->name,
                'sku' => $p->sku ? (string) $p->sku : null,
                'vat_quantity_total' => (int) ($totalVat[$pid] ?? 0),
                'non_vat_quantity_total' => (int) ($totalNonVat[$pid] ?? 0),
                'warehouses' => $break,
                'marketings' => $mBreak,
            ];
        })->values()->all();

        return Inertia::render('Domains/Admin/Logistic/ProductStocks/Index', [
            'products' => $itemsOut,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'bucket' => $bucket,
                'warehouse_id' => $warehouseId,
                'marketing_id' => $marketingId,
                'per_page' => $perPage,
            ],
            'warehouses' => $warehouses,
            'marketings' => $marketings,
        ]);
    }
}
