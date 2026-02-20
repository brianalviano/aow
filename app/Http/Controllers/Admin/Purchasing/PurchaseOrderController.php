<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\{StorePurchaseOrderRequest, UpdatePurchaseOrderRequest, RejectPurchaseOrderRequest, CreateSupplierDeliveryOrderRequest, ReceiveSupplierDeliveryOrderRequest};
use App\Http\Resources\PurchaseOrderResource;
use App\Models\{PurchaseOrder, Warehouse, Supplier, ValueAddedTax, IncomeTax, Product, ProductSupplier, SupplierDeliveryOrder, ProductPurchasePrice, Stock, GoodsCome, SupplierDeliveryOrderItem, PurchaseOrderItem};
use App\Services\{PurchaseOrderService, SupplierDeliveryOrderService};
use App\DTOs\PurchaseOrder\PurchaseOrderData;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use App\Enums\{PurchaseOrderStatus, SupplierDeliveryOrderStatus};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $status = (string) $request->string('status')->toString();
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $orderDateFrom = (string) $request->string('order_date_from')->toString();
        $orderDateTo = (string) $request->string('order_date_to')->toString();

        $query = PurchaseOrder::query()
            ->with(['warehouse', 'supplier'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhere('supplier_name', 'ilike', "%{$q}%")
                        ->orWhere('supplier_invoice_number', 'ilike', "%{$q}%");
                });
            })
            ->when($status !== '', function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->where('warehouse_id', $warehouseId);
            })
            ->when($orderDateFrom !== '', function ($builder) use ($orderDateFrom) {
                $builder->whereDate('order_date', '>=', $orderDateFrom);
            })
            ->when($orderDateTo !== '', function ($builder) use ($orderDateTo) {
                $builder->whereDate('order_date', '<=', $orderDateTo);
            })
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 10);
        $pos = $query->paginate($perPage)->appends([
            'q' => $q,
            'status' => $status,
            'warehouse_id' => $warehouseId,
            'order_date_from' => $orderDateFrom,
            'order_date_to' => $orderDateTo,
        ]);
        $items = array_map(
            fn($p) => PurchaseOrderResource::make($p)->toArray($request),
            $pos->items(),
        );

        // Attach delivery existence and current receiving SDO id to each PO item
        $poIds = array_map(fn($it) => (string) $it['id'], $items);
        $sdos = SupplierDeliveryOrder::query()
            ->where('sourceable_type', PurchaseOrder::class)
            ->whereIn('sourceable_id', $poIds)
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->get(['id', 'sourceable_id', 'status']);
        $hasDeliveryMap = [];
        $currentSdoMap = [];
        foreach ($sdos as $s) {
            $poId = (string) $s->sourceable_id;
            $hasDeliveryMap[$poId] = true;
            $statusValue = $s->status instanceof SupplierDeliveryOrderStatus ? (string) $s->status->value : (string) $s->status;
            if (
                $statusValue === SupplierDeliveryOrderStatus::InDelivery->value
                && !array_key_exists($poId, $currentSdoMap)
            ) {
                $currentSdoMap[$poId] = (string) $s->id;
            }
        }
        $items = array_map(function (array $it) use ($hasDeliveryMap, $currentSdoMap): array {
            $poId = (string) $it['id'];
            $it['has_delivery'] = (bool) ($hasDeliveryMap[$poId] ?? false);
            $it['current_receiving_sdo_id'] = isset($currentSdoMap[$poId]) ? (string) $currentSdoMap[$poId] : null;
            return $it;
        }, $items);

        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name', 'address', 'phone']);

        $baseQuery = PurchaseOrder::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhere('supplier_name', 'ilike', "%{$q}%")
                        ->orWhere('supplier_invoice_number', 'ilike', "%{$q}%");
                });
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->where('warehouse_id', $warehouseId);
            })
            ->when($orderDateFrom !== '', function ($builder) use ($orderDateFrom) {
                $builder->whereDate('order_date', '>=', $orderDateFrom);
            })
            ->when($orderDateTo !== '', function ($builder) use ($orderDateTo) {
                $builder->whereDate('order_date', '<=', $orderDateTo);
            });

        $countRows = (clone $baseQuery)
            ->select(['status', DB::raw('COUNT(*) AS aggregate')])
            ->groupBy('status')
            ->get()
            ->map(fn($r) => [
                'status' => $r->status instanceof PurchaseOrderStatus ? (string) $r->status->value : (string) $r->status,
                'count' => (int) $r->aggregate,
            ]);

        $statusCounters = [];
        foreach (PurchaseOrderStatus::cases() as $s) {
            $statusCounters[$s->value] = 0;
        }
        foreach ($countRows as $row) {
            $statusCounters[$row['status']] = $row['count'];
        }
        $statusCounters[''] = (int) (clone $baseQuery)->count();

        return Inertia::render('Domains/Admin/Purchasing/PurchaseOrders/Index', [
            'purchase_orders' => $items,
            'meta' => [
                'current_page' => $pos->currentPage(),
                'per_page' => $pos->perPage(),
                'total' => $pos->total(),
                'last_page' => $pos->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'warehouse_id' => $warehouseId,
                'order_date_from' => $orderDateFrom,
                'order_date_to' => $orderDateTo,
            ],
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseOrderStatus::cases()),
            'warehouses' => $warehouses->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
                'address' => (string) ($w->address ?? ''),
                'phone' => (string) ($w->phone ?? ''),
            ]),
            'statusCounters' => $statusCounters,
        ]);
    }

    public function create(): Response
    {
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'address']);
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name', 'address', 'phone']);
        $vats = ValueAddedTax::query()->orderBy('percentage')->get(['id', 'percentage']);
        $incomeTaxes = IncomeTax::query()->orderBy('percentage')->get(['id', 'percentage']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $productSupplierPrices = ProductSupplier::query()->get(['supplier_id', 'product_id', 'price']);
        $productPurchasePrices = ProductPurchasePrice::query()->get(['product_id', 'price']);
        $stockRows = Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();

        return Inertia::render('Domains/Admin/Purchasing/PurchaseOrders/Form', [
            'purchase_order' => null,
            'suppliers' => $suppliers->map(fn($s) => [
                'id' => (string) $s->id,
                'name' => (string) $s->name,
                'phone' => (string) ($s->phone ?? ''),
                'email' => (string) ($s->email ?? ''),
                'address' => (string) ($s->address ?? ''),
            ]),
            'warehouses' => $warehouses->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
                'address' => (string) ($w->address ?? ''),
                'phone' => (string) ($w->phone ?? ''),
            ]),
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseOrderStatus::cases()),
            'value_added_taxes' => $vats->map(fn($t) => ['id' => (string) $t->id, 'name' => 'PPN', 'percentage' => (string) $t->percentage]),
            'income_taxes' => $incomeTaxes->map(fn($t) => ['id' => (string) $t->id, 'name' => 'PPh', 'percentage' => (string) $t->percentage]),
            'products' => $products->map(fn($p) => ['id' => (string) $p->id, 'name' => (string) $p->name, 'sku' => (string) $p->sku]),
            'product_supplier_prices' => $productSupplierPrices->map(fn($ps) => [
                'supplier_id' => (string) $ps->supplier_id,
                'product_id' => (string) $ps->product_id,
                'price' => (int) $ps->price,
            ]),
            'product_purchase_prices' => $productPurchasePrices->map(fn($pp) => [
                'product_id' => (string) $pp->product_id,
                'price' => (int) $pp->price,
            ]),
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $po = $service->create(PurchaseOrderData::fromStoreRequest($request, (string) $request->user()->getAuthIdentifier()));
            Inertia::flash('toast', [
                'message' => 'Purchase Order berhasil dibuat: ' . $po->number,
                'type' => 'success',
            ]);
            return redirect()->route('purchase-orders.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Purchase Order: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['warehouse', 'supplier', 'items.product']);
        $sdos = SupplierDeliveryOrder::query()
            ->with(['items.product'])
            ->where('sourceable_type', PurchaseOrder::class)
            ->where('sourceable_id', $purchaseOrder->getKey())
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->get();
        $receivedRows = GoodsCome::query()
            ->whereIn('referencable_id', $sdos->pluck('id')->map(fn($id) => (string) $id)->toArray())
            ->where('referencable_type', SupplierDeliveryOrder::class)
            ->select(['referencable_id', 'product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy(['referencable_id', 'product_id'])
            ->get();
        $receivedQtyMap = [];
        foreach ($receivedRows as $r) {
            $sid = (string) $r->referencable_id;
            $pid = (string) $r->product_id;
            if (!array_key_exists($sid, $receivedQtyMap)) {
                $receivedQtyMap[$sid] = [];
            }
            $receivedQtyMap[$sid][$pid] = (int) $r->qty;
        }
        $receivings = [];
        foreach ($sdos as $s) {
            foreach ($s->items as $it) {
                $received = (int) ($receivedQtyMap[(string) $s->id][(string) $it->product_id] ?? 0);
                $receivings[] = [
                    'sdo_number' => (string) ($s->number ?? ''),
                    'sdo_delivery_date' => $s->delivery_date ? (string) $s->delivery_date->format('Y-m-d') : null,
                    'product_name' => (string) ($it->product->name ?? ''),
                    'sdo_quantity' => (int) $it->quantity,
                    'received_quantity' => $received,
                    'remaining_quantity' => max(0, (int) $it->quantity - $received),
                ];
            }
        }
        return Inertia::render('Domains/Admin/Purchasing/PurchaseOrders/Show', [
            'purchase_order' => PurchaseOrderResource::make($purchaseOrder)->toArray(request()),
            'supplier_delivery_orders' => $sdos->map(fn($s) => [
                'id' => (string) $s->id,
                'number' => (string) $s->number,
                'delivery_date' => $s->delivery_date ? (string) $s->delivery_date->format('Y-m-d') : null,
                'status' => $s->status instanceof SupplierDeliveryOrderStatus ? (string) $s->status->value : (string) $s->status,
                'status_label' => $s->status instanceof SupplierDeliveryOrderStatus
                    ? $s->status->label()
                    : SupplierDeliveryOrderStatus::from((string) $s->status)->label(),
                'items' => $s->items->map(fn($it) => [
                    'product' => ['name' => (string) ($it->product->name ?? '')],
                    'quantity' => (int) $it->quantity,
                    'notes' => (string) ($it->notes ?? ''),
                ]),
            ]),
            'receivings' => $receivings,
        ]);
    }

    public function print(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['warehouse', 'supplier', 'items.product']);
        return Inertia::render('Domains/Admin/Purchasing/PurchaseOrders/Print', [
            'purchase_order' => PurchaseOrderResource::make($purchaseOrder)->toArray(request()),
        ]);
    }

    public function storeDelivery(CreateSupplierDeliveryOrderRequest $request, PurchaseOrder $purchaseOrder, SupplierDeliveryOrderService $service): RedirectResponse
    {
        try {
            $items = array_map(function ($it) {
                return [
                    'product_id' => (string) $it['product_id'],
                    'quantity' => (int) $it['quantity'],
                    'notes' => isset($it['notes']) ? (string) $it['notes'] : null,
                ];
            }, (array) $request->input('items', []));
            $sdo = $service->createFromPurchaseOrder(
                $purchaseOrder,
                $items,
                (string) $request->input('delivery_date'),
                (string) $request->input('number'),
                $request->input('notes') ? (string) $request->input('notes') : null,
            );
            Inertia::flash('toast', [
                'message' => 'Pengiriman dibuat: ' . $sdo->number,
                'type' => 'success',
            ]);
            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat pengiriman: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function updateSupplierInvoice(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_invoice_number' => ['nullable', 'string', 'max:255', 'unique:purchase_orders,supplier_invoice_number,' . $purchaseOrder->getKey()],
            'supplier_invoice_date' => ['nullable', 'date'],
            'supplier_invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $updates = [];
        if ($request->exists('supplier_invoice_number')) {
            $num = trim((string) ($validated['supplier_invoice_number'] ?? ''));
            $updates['supplier_invoice_number'] = $num !== '' ? $num : null;
        }
        if ($request->exists('supplier_invoice_date')) {
            $date = trim((string) ($validated['supplier_invoice_date'] ?? ''));
            $updates['supplier_invoice_date'] = $date !== '' ? $date : null;
        }
        if ($request->hasFile('supplier_invoice_file')) {
            $path = $request->file('supplier_invoice_file')->store('supplier-invoices', 'public');
            $updates['supplier_invoice_file'] = Storage::url($path);
        }
        if (!empty($updates)) {
            $purchaseOrder->update($updates);
        }
        Inertia::flash('toast', [
            'message' => 'Data faktur supplier berhasil diperbarui',
            'type' => 'success',
        ]);
        return redirect()->route('purchase-orders.show', $purchaseOrder->getKey());
    }

    public function edit(PurchaseOrder $purchaseOrder): Response|RedirectResponse
    {
        $current = $purchaseOrder->status instanceof PurchaseOrderStatus
            ? $purchaseOrder->status
            : PurchaseOrderStatus::from((string) $purchaseOrder->status);
        if (!in_array($current, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::RejectedByHo, PurchaseOrderStatus::RejectedBySupplier], true)) {
            Inertia::flash('toast', [
                'message' => 'Edit Purchase Order hanya diperbolehkan untuk status Draft, Ditolak HO, atau Ditolak Pemasok',
                'type' => 'error',
            ]);
            return redirect()->route('purchase-orders.show', $purchaseOrder->getKey());
        }
        $purchaseOrder->load(['warehouse', 'supplier', 'items.product']);
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'address']);
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);
        $vats = ValueAddedTax::query()->orderBy('percentage')->get(['id', 'percentage']);
        $incomeTaxes = IncomeTax::query()->orderBy('percentage')->get(['id', 'percentage']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $productSupplierPrices = ProductSupplier::query()->get(['supplier_id', 'product_id', 'price']);
        $productPurchasePrices = ProductPurchasePrice::query()->get(['product_id', 'price']);
        $stockRows = Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();

        return Inertia::render('Domains/Admin/Purchasing/PurchaseOrders/Form', [
            'purchase_order' => PurchaseOrderResource::make($purchaseOrder)->toArray(request()),
            'suppliers' => $suppliers->map(fn($s) => [
                'id' => (string) $s->id,
                'name' => (string) $s->name,
                'phone' => (string) ($s->phone ?? ''),
                'email' => (string) ($s->email ?? ''),
                'address' => (string) ($s->address ?? ''),
            ]),
            'warehouses' => $warehouses->map(fn($w) => ['id' => (string) $w->id, 'name' => (string) $w->name]),
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseOrderStatus::cases()),
            'value_added_taxes' => $vats->map(fn($t) => ['id' => (string) $t->id, 'name' => 'PPN', 'percentage' => (string) $t->percentage]),
            'income_taxes' => $incomeTaxes->map(fn($t) => ['id' => (string) $t->id, 'name' => 'PPh', 'percentage' => (string) $t->percentage]),
            'products' => $products->map(fn($p) => ['id' => (string) $p->id, 'name' => (string) $p->name, 'sku' => (string) $p->sku]),
            'product_supplier_prices' => $productSupplierPrices->map(fn($ps) => [
                'supplier_id' => (string) $ps->supplier_id,
                'product_id' => (string) $ps->product_id,
                'price' => (int) $ps->price,
            ]),
            'product_purchase_prices' => $productPurchasePrices->map(fn($pp) => [
                'product_id' => (string) $pp->product_id,
                'price' => (int) $pp->price,
            ]),
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $current = $purchaseOrder->status instanceof PurchaseOrderStatus
                ? $purchaseOrder->status
                : PurchaseOrderStatus::from((string) $purchaseOrder->status);
            if (!in_array($current, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::RejectedByHo, PurchaseOrderStatus::RejectedBySupplier], true)) {
                Inertia::flash('toast', [
                    'message' => 'Tidak dapat mengedit Purchase Order pada status saat ini',
                    'type' => 'error',
                ]);
                return back();
            }
            $service->update($purchaseOrder, PurchaseOrderData::fromUpdateRequest($request, (string) $request->user()->getAuthIdentifier()));
            Inertia::flash('toast', [
                'message' => 'Purchase Order berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('purchase-orders.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Purchase Order: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $service->delete($purchaseOrder);
            Inertia::flash('toast', [
                'message' => 'Purchase Order berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('purchase-orders.index');
        } catch (Throwable $e) {
            $msg = $e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'SQLSTATE[23503]')
                ? 'Tidak dapat menghapus Purchase Order karena direferensikan oleh transaksi lain.'
                : $e->getMessage();
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Purchase Order: ' . $msg,
                'type' => 'error',
            ]);
            return redirect()->route('purchase-orders.index');
        }
    }

    public function advance(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $current = $purchaseOrder->status instanceof PurchaseOrderStatus ? $purchaseOrder->status : PurchaseOrderStatus::Draft;
            if ($current === PurchaseOrderStatus::PendingHoApproval) {
                $roleName = (string) (request()->user()?->role?->name ?? '');
                if (!in_array($roleName, ['Super Admin', 'Director'], true)) {
                    Inertia::flash('toast', [
                        'message' => 'Hanya role tertinggi yang diperbolehkan menyetujui HO',
                        'type' => 'error',
                    ]);
                    return back();
                }
            }
            $service->advanceStatus($purchaseOrder);
            $purchaseOrder->refresh();
            $statusLabel = $purchaseOrder->status instanceof PurchaseOrderStatus
                ? $purchaseOrder->status->label()
                : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label();
            Inertia::flash('toast', [
                'message' => 'Status Purchase Order diperbarui: ' . $statusLabel,
                'type' => 'success',
            ]);
            return back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui status: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function rejectHo(RejectPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $roleName = (string) ($request->user()?->role?->name ?? '');
            if (!in_array($roleName, ['Super Admin', 'Director'], true)) {
                Inertia::flash('toast', [
                    'message' => 'Hanya role tertinggi yang diperbolehkan menolak pada tahap HO',
                    'type' => 'error',
                ]);
                return back();
            }
            $service->rejectByHo($purchaseOrder, (string) $request->input('reason'), (string) $request->user()->getAuthIdentifier());
            Inertia::flash('toast', [
                'message' => 'Purchase Order ditolak oleh HO',
                'type' => 'success',
            ]);
            return back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menolak PO: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function rejectSupplier(RejectPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $service->rejectBySupplier($purchaseOrder, (string) $request->input('reason'));
            Inertia::flash('toast', [
                'message' => 'Purchase Order ditolak oleh pemasok',
                'type' => 'success',
            ]);
            return back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menolak PO: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
