<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\{PurchaseReturn, Supplier, Warehouse, PurchaseOrder, Product, SupplierDeliveryOrder, GoodsCome, PurchaseOrderItem, Stock};
use App\Enums\PurchaseReturnStatus;
use App\Enums\{PurchaseReturnReason, PurchaseReturnResolution, SupplierDeliveryOrderStatus};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PurchaseReturn\StorePurchaseReturnRequest;
use App\Services\{PurchaseReturnService, SupplierDeliveryOrderService};
use App\Http\Requests\PurchaseReturn\CreateSupplierDeliveryOrderRequest;
use Throwable;
use App\Models\PurchaseReturnItem;

/**
 * Controller untuk mengelola Retur Pembelian (Purchase Returns).
 *
 * Menyediakan daftar retur pembelian dengan filter dasar.
 *
 * @author PJD
 */
class PurchaseReturnController extends Controller
{
    /**
     * Tampilkan daftar retur pembelian.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $status = (string) $request->string('status')->toString();
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $returnDateFrom = (string) $request->string('return_date_from')->toString();
        $returnDateTo = (string) $request->string('return_date_to')->toString();

        $query = PurchaseReturn::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhereHas('purchaseOrder', function ($qq) use ($q) {
                            $qq->where('number', 'ilike', "%{$q}%");
                        })
                        ->orWhereHas('supplier', function ($qq) use ($q) {
                            $qq->where('name', 'ilike', "%{$q}%");
                        });
                });
            })
            ->when($status !== '', function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->where('warehouse_id', $warehouseId);
            })
            ->when($returnDateFrom !== '', function ($builder) use ($returnDateFrom) {
                $builder->whereDate('return_date', '>=', $returnDateFrom);
            })
            ->when($returnDateTo !== '', function ($builder) use ($returnDateTo) {
                $builder->whereDate('return_date', '<=', $returnDateTo);
            })
            ->orderByDesc('return_date')
            ->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 10);
        $returns = $query->paginate($perPage)->appends([
            'q' => $q,
            'status' => $status,
            'warehouse_id' => $warehouseId,
            'return_date_from' => $returnDateFrom,
            'return_date_to' => $returnDateTo,
        ]);

        $items = array_map(function ($r) use ($request) {
            /** @var PurchaseReturn $r */
            return [
                'id' => (string) $r->id,
                'number' => (string) $r->number,
                'purchase_order' => [
                    'id' => (string) ($r->purchaseOrder?->id ?? ''),
                    'number' => (string) ($r->purchaseOrder?->number ?? ''),
                ],
                'supplier' => [
                    'id' => (string) ($r->supplier?->id ?? ''),
                    'name' => (string) ($r->supplier?->name ?? ''),
                ],
                'warehouse' => [
                    'id' => (string) ($r->warehouse?->id ?? ''),
                    'name' => (string) ($r->warehouse?->name ?? ''),
                ],
                'return_date' => $r->return_date ? (string) $r->return_date->format('Y-m-d') : null,
                'status' => $r->status instanceof PurchaseReturnStatus ? (string) $r->status->value : (string) $r->status,
                'status_label' => $r->status instanceof PurchaseReturnStatus
                    ? $r->status->label()
                    : PurchaseReturnStatus::from((string) $r->status)->label(),
                'credit_amount' => (int) ($r->credit_amount ?? 0),
                'refund_amount' => (int) ($r->refund_amount ?? 0),
            ];
        }, $returns->items());

        // Attach current receiving SDO id to each Purchase Return item (for "Penerimaan Barang" navigation)
        $prIds = array_map(fn(array $it): string => (string) $it['id'], $items);
        if (!empty($prIds)) {
            $sdos = SupplierDeliveryOrder::query()
                ->where('sourceable_type', PurchaseReturn::class)
                ->whereIn('sourceable_id', $prIds)
                ->orderBy('delivery_date')
                ->orderBy('created_at')
                ->get(['id', 'sourceable_id', 'status']);
            $currentSdoMap = [];
            foreach ($sdos as $sdo) {
                $prId = (string) $sdo->sourceable_id;
                $statusValue = $sdo->status instanceof SupplierDeliveryOrderStatus
                    ? (string) $sdo->status->value
                    : (string) $sdo->status;
                if (
                    $statusValue === SupplierDeliveryOrderStatus::InDelivery->value
                    && !array_key_exists($prId, $currentSdoMap)
                ) {
                    $currentSdoMap[$prId] = (string) $sdo->id;
                }
            }
            $items = array_map(function (array $it) use ($currentSdoMap): array {
                $prId = (string) $it['id'];
                $it['current_receiving_sdo_id'] = isset($currentSdoMap[$prId])
                    ? (string) $currentSdoMap[$prId]
                    : null;
                return $it;
            }, $items);
        }

        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);

        $baseQuery = PurchaseReturn::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhereHas('purchaseOrder', function ($qq) use ($q) {
                            $qq->where('number', 'ilike', "%{$q}%");
                        })
                        ->orWhereHas('supplier', function ($qq) use ($q) {
                            $qq->where('name', 'ilike', "%{$q}%");
                        });
                });
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->where('warehouse_id', $warehouseId);
            })
            ->when($returnDateFrom !== '', function ($builder) use ($returnDateFrom) {
                $builder->whereDate('return_date', '>=', $returnDateFrom);
            })
            ->when($returnDateTo !== '', function ($builder) use ($returnDateTo) {
                $builder->whereDate('return_date', '<=', $returnDateTo);
            });

        $countRows = (clone $baseQuery)
            ->select(['status', DB::raw('COUNT(*) AS aggregate')])
            ->groupBy('status')
            ->get()
            ->map(fn($r) => [
                'status' => $r->status instanceof PurchaseReturnStatus ? (string) $r->status->value : (string) $r->status,
                'count' => (int) $r->aggregate,
            ]);

        $statusCounters = [];
        foreach (PurchaseReturnStatus::cases() as $s) {
            $statusCounters[$s->value] = 0;
        }
        foreach ($countRows as $row) {
            $statusCounters[$row['status']] = $row['count'];
        }
        $statusCounters[''] = (int) (clone $baseQuery)->count();

        return Inertia::render('Domains/Admin/Purchasing/PurchaseReturns/Index', [
            'purchase_returns' => $items,
            'meta' => [
                'current_page' => $returns->currentPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
                'last_page' => $returns->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'warehouse_id' => $warehouseId,
                'return_date_from' => $returnDateFrom,
                'return_date_to' => $returnDateTo,
            ],
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseReturnStatus::cases()),
            'warehouses' => $warehouses->map(fn($w) => ['id' => (string) $w->id, 'name' => (string) $w->name]),
            'statusCounters' => $statusCounters,
        ]);
    }

    public function show(PurchaseReturn $purchaseReturn): Response
    {
        $return = PurchaseReturn::query()
            ->with([
                'supplier:id,name,phone,email,address',
                'warehouse:id,name,address,phone',
                'purchaseOrder:id,number,order_date,supplier_id,warehouse_id',
                'purchaseOrder.warehouse:id,name,address,phone',
            ])
            ->whereKey($purchaseReturn->getKey())
            ->firstOrFail();
        $items = PurchaseReturnItem::query()
            ->with(['product:id,name,sku'])
            ->where('purchase_return_id', $return->getKey())
            ->orderBy('created_at')
            ->get();
        $productIds = $items->pluck('product_id')->filter()->map(fn($id) => (string) $id)->values()->all();
        $stocksByProduct = [];
        if (!empty($productIds) && $return->warehouse_id) {
            $stockRows = Stock::query()
                ->where('warehouse_id', $return->warehouse_id)
                ->whereIn('product_id', $productIds)
                ->select(['product_id', DB::raw('SUM(quantity) as quantity')])
                ->groupBy(['product_id'])
                ->get();
            foreach ($stockRows as $row) {
                $stocksByProduct[(string) $row->product_id] = (int) $row->quantity;
            }
        }
        $reasonValue = $return->reason instanceof PurchaseReturnReason ? (string) $return->reason->value : (string) ($return->reason ?? '');
        $resolutionValue = (string) ($return->getRawOriginal('resolution') ?? '');
        $resolutionLabel = $resolutionValue && in_array($resolutionValue, PurchaseReturnResolution::values(), true)
            ? PurchaseReturnResolution::from($resolutionValue)->label()
            : ($resolutionValue ?: null);
        $statusValue = $return->status instanceof PurchaseReturnStatus ? (string) $return->status->value : (string) ($return->status ?? '');
        $data = [
            'id' => (string) $return->id,
            'number' => (string) ($return->number ?? ''),
            'supplier' => [
                'id' => (string) ($return->supplier?->id ?? ''),
                'name' => (string) ($return->supplier?->name ?? ''),
                'phone' => (string) ($return->supplier?->phone ?? ''),
                'email' => (string) ($return->supplier?->email ?? ''),
                'address' => (string) ($return->supplier?->address ?? ''),
            ],
            'warehouse' => [
                'id' => (string) ($return->warehouse?->id ?? ''),
                'name' => (string) ($return->warehouse?->name ?? ''),
                'address' => (string) ($return->warehouse?->address ?? ''),
                'phone' => (string) ($return->warehouse?->phone ?? ''),
            ],
            'purchase_order' => [
                'id' => (string) ($return->purchaseOrder?->id ?? ''),
                'number' => (string) ($return->purchaseOrder?->number ?? ''),
                'order_date' => $return->purchaseOrder?->order_date ? (string) $return->purchaseOrder->order_date->format('Y-m-d') : null,
                'supplier' => [
                    'id' => (string) ($return->purchaseOrder?->supplier_id ?? ''),
                    'name' => (string) ($return->purchaseOrder?->supplier?->name ?? ''),
                ],
                'warehouse' => [
                    'id' => (string) ($return->purchaseOrder?->warehouse_id ?? ''),
                    'name' => (string) ($return->purchaseOrder?->warehouse?->name ?? ''),
                    'address' => (string) ($return->purchaseOrder?->warehouse?->address ?? ''),
                    'phone' => (string) ($return->purchaseOrder?->warehouse?->phone ?? ''),
                ],
            ],
            'return_date' => $return->return_date ? (string) $return->return_date->format('Y-m-d') : null,
            'reason' => $reasonValue,
            'reason_label' => $reasonValue ? PurchaseReturnReason::from($reasonValue)->label() : null,
            'resolution' => $resolutionValue,
            'resolution_label' => $resolutionLabel,
            'status' => $statusValue,
            'status_label' => $statusValue ? PurchaseReturnStatus::from($statusValue)->label() : null,
            'credit_amount' => (int) ($return->credit_amount ?? 0),
            'refund_amount' => (int) ($return->refund_amount ?? 0),
            'notes' => (string) ($return->notes ?? ''),
            'items' => $items->map(function (PurchaseReturnItem $it) use ($stocksByProduct) {
                $pid = (string) ($it->product_id ?? '');
                return [
                    'product' => [
                        'id' => (string) ($it->product?->id ?? ''),
                        'name' => (string) ($it->product?->name ?? ''),
                        'sku' => (string) ($it->product?->sku ?? ''),
                    ],
                    'quantity' => (int) ($it->quantity ?? 0),
                    'price' => (int) ($it->price ?? 0),
                    'subtotal' => (int) ($it->subtotal ?? 0),
                    'notes' => (string) ($it->notes ?? ''),
                    'stock_quantity' => (int) ($stocksByProduct[$pid] ?? 0),
                ];
            })->values()->all(),
        ];
        return Inertia::render('Domains/Admin/Purchasing/PurchaseReturns/Show', [
            'purchase_return' => $data,
        ]);
    }

    public function print(PurchaseReturn $purchaseReturn): Response
    {
        $return = PurchaseReturn::query()
            ->with([
                'supplier:id,name,phone,email,address',
                'warehouse:id,name',
                'purchaseOrder:id,number,order_date,supplier_id,warehouse_id',
            ])
            ->whereKey($purchaseReturn->getKey())
            ->firstOrFail();
        $items = PurchaseReturnItem::query()
            ->with(['product:id,name,sku'])
            ->where('purchase_return_id', $return->getKey())
            ->orderBy('created_at')
            ->get();
        $reasonValue = $return->reason instanceof PurchaseReturnReason ? (string) $return->reason->value : (string) ($return->reason ?? '');
        $resolutionValue = (string) ($return->getRawOriginal('resolution') ?? '');
        $resolutionLabel = $resolutionValue && in_array($resolutionValue, PurchaseReturnResolution::values(), true)
            ? PurchaseReturnResolution::from($resolutionValue)->label()
            : ($resolutionValue ?: null);
        $statusValue = $return->status instanceof PurchaseReturnStatus ? (string) $return->status->value : (string) ($return->status ?? '');
        $data = [
            'id' => (string) $return->id,
            'number' => (string) ($return->number ?? ''),
            'supplier' => [
                'id' => (string) ($return->supplier?->id ?? ''),
                'name' => (string) ($return->supplier?->name ?? ''),
                'phone' => (string) ($return->supplier?->phone ?? ''),
                'email' => (string) ($return->supplier?->email ?? ''),
                'address' => (string) ($return->supplier?->address ?? ''),
            ],
            'warehouse' => [
                'id' => (string) ($return->warehouse?->id ?? ''),
                'name' => (string) ($return->warehouse?->name ?? ''),
            ],
            'purchase_order' => [
                'id' => (string) ($return->purchaseOrder?->id ?? ''),
                'number' => (string) ($return->purchaseOrder?->number ?? ''),
                'order_date' => $return->purchaseOrder?->order_date ? (string) $return->purchaseOrder->order_date->format('Y-m-d') : null,
                'supplier' => [
                    'id' => (string) ($return->purchaseOrder?->supplier_id ?? ''),
                    'name' => (string) ($return->purchaseOrder?->supplier?->name ?? ''),
                ],
                'warehouse' => [
                    'id' => (string) ($return->purchaseOrder?->warehouse_id ?? ''),
                    'name' => (string) ($return->purchaseOrder?->warehouse?->name ?? ''),
                ],
            ],
            'return_date' => $return->return_date ? (string) $return->return_date->format('Y-m-d') : null,
            'reason' => $reasonValue,
            'reason_label' => $reasonValue ? PurchaseReturnReason::from($reasonValue)->label() : null,
            'resolution' => $resolutionValue,
            'resolution_label' => $resolutionLabel,
            'status' => $statusValue,
            'status_label' => $statusValue ? PurchaseReturnStatus::from($statusValue)->label() : null,
            'credit_amount' => (int) ($return->credit_amount ?? 0),
            'refund_amount' => (int) ($return->refund_amount ?? 0),
            'notes' => (string) ($return->notes ?? ''),
            'items' => $items->map(function (PurchaseReturnItem $it) {
                return [
                    'product' => [
                        'id' => (string) ($it->product?->id ?? ''),
                        'name' => (string) ($it->product?->name ?? ''),
                        'sku' => (string) ($it->product?->sku ?? ''),
                    ],
                    'quantity' => (int) ($it->quantity ?? 0),
                    'price' => (int) ($it->price ?? 0),
                    'subtotal' => (int) ($it->subtotal ?? 0),
                    'notes' => (string) ($it->notes ?? ''),
                ];
            })->values()->all(),
        ];
        return Inertia::render('Domains/Admin/Purchasing/PurchaseReturns/Print', [
            'purchase_return' => $data,
        ]);
    }

    public function storeDelivery(CreateSupplierDeliveryOrderRequest $request, PurchaseReturn $purchaseReturn, SupplierDeliveryOrderService $service): RedirectResponse
    {
        try {
            $rawRes = (string) ($purchaseReturn->getRawOriginal('resolution') ?? '');
            $resolution = in_array($rawRes, PurchaseReturnResolution::values(), true)
                ? PurchaseReturnResolution::from($rawRes)
                : null;
            if ($resolution !== PurchaseReturnResolution::Replace) {
                throw new \InvalidArgumentException('Pengiriman hanya untuk retur dengan resolusi penggantian');
            }
            $items = array_map(function ($it) {
                return [
                    'product_id' => (string) $it['product_id'],
                    'quantity' => (int) $it['quantity'],
                    'notes' => isset($it['notes']) ? (string) $it['notes'] : null,
                ];
            }, (array) $request->input('items', []));
            $sdo = $service->createFromPurchaseReturn(
                $purchaseReturn,
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
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat pengiriman: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->back();
        }
    }

    public function create(): Response
    {
        $purchaseOrderId = (string) request()->string('purchase_order_id')->toString();
        $prefillSupplierId = (string) request()->string('supplier_id')->toString();
        $prefillWarehouseId = (string) request()->string('warehouse_id')->toString();
        $prefill = [
            'supplier_id' => '',
            'warehouse_id' => '',
            'purchase_order_id' => '',
        ];
        if ($purchaseOrderId !== '') {
            /** @var PurchaseOrder|null $po */
            $po = PurchaseOrder::query()->where('id', $purchaseOrderId)->first();
            if ($po) {
                $prefill['purchase_order_id'] = (string) $po->id;
                $prefill['supplier_id'] = (string) ($po->supplier_id ?? '');
                $prefill['warehouse_id'] = (string) ($po->warehouse_id ?? '');
            }
        } else {
            $prefill['supplier_id'] = $prefillSupplierId;
            $prefill['warehouse_id'] = $prefillWarehouseId;
        }

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'address']);
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name', 'address', 'phone']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,name', 'warehouse:id,name,address,phone'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'number', 'supplier_id', 'warehouse_id', 'order_date']);
        $poIds = $purchaseOrders->pluck('id')->map(fn($id) => (string) $id)->values()->all();
        $orderedRows = PurchaseOrderItem::query()
            ->whereIn('purchase_order_id', $poIds)
            ->select(['purchase_order_id', 'product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy(['purchase_order_id', 'product_id'])
            ->get();
        $receivedRows = GoodsCome::query()
            ->join('supplier_delivery_orders', 'supplier_delivery_orders.id', '=', 'goods_comes.referencable_id')
            ->where('goods_comes.referencable_type', SupplierDeliveryOrder::class)
            ->where('supplier_delivery_orders.sourceable_type', PurchaseOrder::class)
            ->whereIn('supplier_delivery_orders.sourceable_id', $poIds)
            ->select(['supplier_delivery_orders.sourceable_id as purchase_order_id', 'goods_comes.product_id', DB::raw('SUM(goods_comes.quantity) as qty')])
            ->groupBy(['supplier_delivery_orders.sourceable_id', 'goods_comes.product_id'])
            ->get();
        $orderedMap = [];
        foreach ($orderedRows as $row) {
            $poId = (string) $row->purchase_order_id;
            $pid = (string) $row->product_id;
            $orderedMap[$poId][$pid] = (int) $row->qty;
        }
        $receivedMap = [];
        foreach ($receivedRows as $row) {
            $poId = (string) $row->purchase_order_id;
            $pid = (string) $row->product_id;
            $qty = (int) $row->qty;
            if ($qty > 0) {
                $receivedMap[$poId][$pid] = $qty;
            }
        }
        $allowedProductsByPO = [];
        foreach ($poIds as $poId) {
            $ordered = array_keys($orderedMap[$poId] ?? []);
            $received = array_keys($receivedMap[$poId] ?? []);
            $allowed = array_values(array_intersect($ordered, $received));
            $allowedProductsByPO[$poId] = array_map(fn($pid) => (string) $pid, $allowed);
        }
        $priceRows = PurchaseOrderItem::query()
            ->whereIn('purchase_order_id', $poIds)
            ->orderByDesc('created_at')
            ->select(['purchase_order_id', 'product_id', 'price'])
            ->get();
        $poProductPricesByPO = [];
        foreach ($priceRows as $row) {
            $poId = (string) $row->purchase_order_id;
            $pid = (string) $row->product_id;
            $price = (int) $row->price;
            if (!isset($poProductPricesByPO[$poId])) {
                $poProductPricesByPO[$poId] = [];
            }
            if (!isset($poProductPricesByPO[$poId][$pid])) {
                $poProductPricesByPO[$poId][$pid] = $price;
            }
        }
        $stockRows = Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();

        return Inertia::render('Domains/Admin/Purchasing/PurchaseReturns/Form', [
            'prefill' => $prefill,
            'suppliers' => $suppliers->map(fn($s) => [
                'id' => (string) $s->id,
                'name' => (string) $s->name,
                'phone' => (string) ($s->phone ?? ''),
                'email' => (string) ($s->email ?? ''),
                'address' => (string) ($s->address ?? ''),
            ]),
            'warehouses' => $warehouses->map(fn($w) => ['id' => (string) $w->id, 'name' => (string) $w->name]),
            'products' => $products->map(fn($p) => ['id' => (string) $p->id, 'name' => (string) $p->name, 'sku' => (string) $p->sku]),
            'purchase_orders' => $purchaseOrders->map(fn($po) => [
                'id' => (string) $po->id,
                'number' => (string) ($po->number ?? ''),
                'order_date' => $po->order_date ? (string) $po->order_date->format('Y-m-d') : null,
                'supplier' => [
                    'id' => (string) ($po->supplier_id ?? ''),
                    'name' => (string) ($po->supplier?->name ?? ''),
                ],
                'warehouse' => [
                    'id' => (string) ($po->warehouse_id ?? ''),
                    'name' => (string) ($po->warehouse?->name ?? ''),
                    'address' => (string) ($po->warehouse?->address ?? ''),
                    'phone' => (string) ($po->warehouse?->phone ?? ''),
                ],
            ]),
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseReturnStatus::cases()),
            'reasonOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseReturnReason::cases()),
            'resolutionOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], PurchaseReturnResolution::cases()),
            'allowedProductsByPO' => $allowedProductsByPO,
            'poProductPricesByPO' => $poProductPricesByPO,
        ]);
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnService $service): RedirectResponse
    {
        try {
            $items = array_map(function ($it) {
                return new \App\DTOs\PurchaseReturn\PurchaseReturnItemData(
                    (string) $it['product_id'],
                    (int) $it['quantity'],
                    isset($it['price']) ? (int) $it['price'] : 0,
                    isset($it['notes']) ? (string) $it['notes'] : null,
                    null,
                    null
                );
            }, (array) $request->input('items', []));
            $data = new \App\DTOs\PurchaseReturn\PurchaseReturnData(
                $request->input('purchase_order_id') ? (string) $request->input('purchase_order_id') : null,
                (string) $request->input('supplier_id'),
                (string) $request->input('warehouse_id'),
                $request->input('number') ? (string) $request->input('number') : null,
                (string) $request->input('return_date'),
                (string) $request->input('reason'),
                (string) $request->input('resolution'),
                (string) $request->input('status'),
                $request->input('notes') ? (string) $request->input('notes') : null,
                (string) $request->user()->getAuthIdentifier(),
                $items
            );
            $service->create($data);
            Inertia::flash('toast', [
                'message' => 'Retur Pembelian berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('purchase-returns.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Retur Pembelian: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
