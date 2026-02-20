<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Models\{StockTransfer, Warehouse, Product, User, Stock};
use App\Http\Requests\StockTransfer\{StoreStockTransferRequest, UpdateStockTransferRequest};
use App\Http\Resources\StockTransferResource;
use App\Services\{StockTransferService, StockService};
use App\DTOs\StockTransfer\StockTransferData;
use App\Enums\{StockTransferStatus, RoleName};
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Controller untuk CRUD Mutasi Stok antar gudang.
 *
 * @author PJD
 */
class StockTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $status = (string) $request->string('status')->toString();
        $fromWarehouseId = (string) $request->string('from_warehouse_id')->toString();
        $toWarehouseId = (string) $request->string('to_warehouse_id')->toString();
        $dateFrom = (string) $request->string('transfer_date_from')->toString();
        $dateTo = (string) $request->string('transfer_date_to')->toString();

        $query = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhere('notes', 'ilike', "%{$q}%");
                });
            })
            ->when($status !== '', function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->when($fromWarehouseId !== '', function ($builder) use ($fromWarehouseId) {
                $builder->where('from_warehouse_id', $fromWarehouseId);
            })
            ->when($toWarehouseId !== '', function ($builder) use ($toWarehouseId) {
                $builder->where('to_warehouse_id', $toWarehouseId);
            })
            ->when($dateFrom !== '', function ($builder) use ($dateFrom) {
                $builder->whereDate('transfer_date', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($builder) use ($dateTo) {
                $builder->whereDate('transfer_date', '<=', $dateTo);
            })
            ->orderByDesc('transfer_date')
            ->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 10);
        $trs = $query->paginate($perPage)->appends([
            'q' => $q,
            'status' => $status,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'transfer_date_from' => $dateFrom,
            'transfer_date_to' => $dateTo,
        ]);
        $items = array_map(
            fn($t) => StockTransferResource::make($t)->toArray($request),
            $trs->items(),
        );

        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);
        $statusCounters = [];
        foreach (StockTransferStatus::cases() as $s) {
            $statusCounters[$s->value] = 0;
        }
        $countRows = StockTransfer::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhere('notes', 'ilike', "%{$q}%");
                });
            })
            ->when($fromWarehouseId !== '', function ($builder) use ($fromWarehouseId) {
                $builder->where('from_warehouse_id', $fromWarehouseId);
            })
            ->when($toWarehouseId !== '', function ($builder) use ($toWarehouseId) {
                $builder->where('to_warehouse_id', $toWarehouseId);
            })
            ->when($dateFrom !== '', function ($builder) use ($dateFrom) {
                $builder->whereDate('transfer_date', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($builder) use ($dateTo) {
                $builder->whereDate('transfer_date', '<=', $dateTo);
            })
            ->select(['status', DB::raw('COUNT(*) AS aggregate')])
            ->groupBy('status')
            ->get()
            ->map(fn($r) => [
                'status' => $r->status instanceof StockTransferStatus ? (string) $r->status->value : (string) $r->status,
                'count' => (int) $r->aggregate,
            ]);
        foreach ($countRows as $row) {
            $statusCounters[$row['status']] = $row['count'];
        }
        $statusCounters[''] = (int) (clone $query)->count();

        return Inertia::render('Domains/Admin/Logistic/StockTransfers/Index', [
            'stock_transfers' => $items,
            'meta' => [
                'current_page' => $trs->currentPage(),
                'per_page' => $trs->perPage(),
                'total' => $trs->total(),
                'last_page' => $trs->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'transfer_date_from' => $dateFrom,
                'transfer_date_to' => $dateTo,
            ],
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], StockTransferStatus::cases()),
            'warehouses' => $warehouses->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
            ]),
            'statusCounters' => $statusCounters,
        ]);
    }

    public function create(): Response
    {
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $marketings = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
        $productStocks = $this->buildProductStocks($products->pluck('id')->map(fn($id) => (string) $id)->all());
        return Inertia::render('Domains/Admin/Logistic/StockTransfers/Form', [
            'stock_transfer' => null,
            'warehouses' => $warehouses->map(fn($w) => ['id' => (string) $w->id, 'name' => (string) $w->name]),
            'products' => $products->map(fn($p) => ['id' => (string) $p->id, 'name' => (string) $p->name, 'sku' => (string) ($p->sku ?? '')]),
            'marketings' => $marketings->map(fn($u) => ['id' => (string) $u->id, 'name' => (string) $u->name]),
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], StockTransferStatus::cases()),
            'default_transfer_date' => now()->toDateString(),
            'product_stocks' => $productStocks,
        ]);
    }

    public function store(StoreStockTransferRequest $request, StockTransferService $service): RedirectResponse
    {
        try {
            $tr = $service->create(StockTransferData::fromStoreRequest($request, (string) $request->user()->getAuthIdentifier()));
            Inertia::flash('toast', [
                'message' => 'Mutasi Stok dibuat: ' . ($tr->number ?? ''),
                'type' => 'success',
            ]);
            return redirect()->route('stock-transfers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Mutasi Stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(StockTransfer $stockTransfer): Response
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'toOwnerUser', 'items.product']);
        return Inertia::render('Domains/Admin/Logistic/StockTransfers/Show', [
            'stock_transfer' => StockTransferResource::make($stockTransfer)->toArray(request()),
        ]);
    }

    public function edit(StockTransfer $stockTransfer): Response
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'toOwnerUser', 'items.product']);
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $marketings = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
        $productStocks = $this->buildProductStocks($products->pluck('id')->map(fn($id) => (string) $id)->all());
        return Inertia::render('Domains/Admin/Logistic/StockTransfers/Form', [
            'stock_transfer' => StockTransferResource::make($stockTransfer)->toArray(request()),
            'warehouses' => $warehouses->map(fn($w) => ['id' => (string) $w->id, 'name' => (string) $w->name]),
            'products' => $products->map(fn($p) => ['id' => (string) $p->id, 'name' => (string) $p->name, 'sku' => (string) ($p->sku ?? '')]),
            'marketings' => $marketings->map(fn($u) => ['id' => (string) $u->id, 'name' => (string) $u->name]),
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], StockTransferStatus::cases()),
            'default_transfer_date' => now()->toDateString(),
            'product_stocks' => $productStocks,
        ]);
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer, StockTransferService $service): RedirectResponse
    {
        try {
            $tr = $service->update($stockTransfer, StockTransferData::fromUpdateRequest($request, (string) $request->user()->getAuthIdentifier()));
            Inertia::flash('toast', [
                'message' => 'Mutasi Stok diperbarui: ' . ($tr->number ?? ''),
                'type' => 'success',
            ]);
            return redirect()->route('stock-transfers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Mutasi Stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(StockTransfer $stockTransfer, StockTransferService $service): RedirectResponse
    {
        try {
            $service->delete($stockTransfer);
            Inertia::flash('toast', [
                'message' => 'Mutasi Stok dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('stock-transfers.index');
        } catch (Throwable $e) {
            $msg = $e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'SQLSTATE[23503]')
                ? 'Tidak dapat menghapus Mutasi Stok karena direferensikan oleh transaksi lain.'
                : $e->getMessage();
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Mutasi Stok: ' . $msg,
                'type' => 'error',
            ]);
            return redirect()->route('stock-transfers.index');
        }
    }

    public function advance(StockTransfer $stockTransfer, StockTransferService $service, StockService $stock): RedirectResponse
    {
        try {
            $service->advanceStatus($stockTransfer, (string) request()->user()->getAuthIdentifier(), $stock);
            Inertia::flash('toast', [
                'message' => 'Status Mutasi Stok diperbarui',
                'type' => 'success',
            ]);
            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui status Mutasi Stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function print(StockTransfer $stockTransfer): Response
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'toOwnerUser', 'items.product']);
        return Inertia::render('Domains/Admin/Logistic/StockTransfers/Print', [
            'stock_transfer' => StockTransferResource::make($stockTransfer)->toArray(request()),
        ]);
    }

    private function buildProductStocks(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $rows = Stock::query()
            ->whereIn('product_id', $productIds)
            ->select([
                'product_id',
                'warehouse_id',
                'owner_user_id',
                DB::raw('SUM(quantity) AS qty'),
            ])
            ->groupBy(['product_id', 'warehouse_id', 'owner_user_id'])
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $pid = (string) $row->product_id;
            $wid = $row->warehouse_id ? (string) $row->warehouse_id : null;
            $oid = $row->owner_user_id ? (string) $row->owner_user_id : null;
            $qty = (int) $row->qty;
            if (!isset($out[$pid])) {
                $out[$pid] = ['warehouses' => [], 'marketings' => []];
            }
            if ($wid) {
                $out[$pid]['warehouses'][$wid] = ($out[$pid]['warehouses'][$wid] ?? 0) + $qty;
            }
            if ($oid) {
                $out[$pid]['marketings'][$oid] = ($out[$pid]['marketings'][$oid] ?? 0) + $qty;
            }
        }
        return $out;
    }
}
