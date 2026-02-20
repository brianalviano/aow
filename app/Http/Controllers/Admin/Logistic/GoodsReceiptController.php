<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ReceiveSupplierDeliveryOrderRequest;
use App\Models\{PurchaseOrder, SupplierDeliveryOrder, GoodsCome, PurchaseReturn, PurchaseReturnItem};
use App\Enums\{PurchaseOrderStatus, SupplierDeliveryOrderStatus, PurchaseReturnResolution, PurchaseReturnStatus, PurchaseReturnReason};
use App\Services\{StockService, SupplierDeliveryOrderService};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{DB, Storage};
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Goods Receipt Controller.
 *
 * Mengelola penerimaan barang (Goods Receipt) dari Supplier Delivery Order (SDO)
 * sebagai bagian dari operasi Logistik. Halaman daftar menampilkan SDO berstatus
 * InDelivery, dan form penerimaan memanfaatkan komponen ReceivingForm yang sama.
 *
 * @author PJD
 */
class GoodsReceiptController extends Controller
{
    /**
     * Tampilkan daftar SDO yang siap diterima (status InDelivery).
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $q = trim((string) $request->string('q')->toString());
        $docType = trim((string) $request->string('doc_type')->toString());
        $status = trim((string) $request->string('status')->toString());
        $allowedStatuses = [
            SupplierDeliveryOrderStatus::InDelivery->value,
            SupplierDeliveryOrderStatus::Completed->value,
        ];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = '';
        }
        $query = SupplierDeliveryOrder::query()
            ->with(['sourceable', 'supplier'])
            ->whereIn('status', [
                SupplierDeliveryOrderStatus::InDelivery->value,
                SupplierDeliveryOrderStatus::Completed->value,
            ])
            ->when($docType === 'po', function ($q2) {
                $q2->where('sourceable_type', PurchaseOrder::class);
            })
            ->when($docType === 'pr', function ($q2) {
                $q2->where('sourceable_type', PurchaseReturn::class);
            })
            ->when($status !== '', function ($q2) use ($status) {
                $q2->where('status', $status);
            })
            ->orderByRaw(
                'CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END',
                [SupplierDeliveryOrderStatus::InDelivery->value, SupplierDeliveryOrderStatus::Completed->value]
            )
            ->orderByRaw(
                'CASE WHEN status = ? THEN COALESCE(ABS((delivery_date::date - CURRENT_DATE)), 999999) ELSE 999999 END',
                [SupplierDeliveryOrderStatus::InDelivery->value]
            )
            ->orderByRaw('delivery_date IS NULL')
            ->orderBy('delivery_date')
            ->orderBy('created_at');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('number', 'like', '%' . $q . '%');
            });
        }

        $perPage = (int) $request->integer('per_page', 10);
        $sdos = $query
            ->select(['id', 'number', 'delivery_date', 'status', 'supplier_id', 'sourceable_id', 'sourceable_type'])
            ->paginate($perPage)
            ->appends([
                'q' => $q,
                'doc_type' => $docType,
                'status' => $status,
            ]);

        $inDeliveryCountsByType = SupplierDeliveryOrder::query()
            ->where('status', SupplierDeliveryOrderStatus::InDelivery->value)
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where('number', 'like', '%' . $q . '%');
            })
            ->select(['sourceable_type', DB::raw('COUNT(*) as cnt')])
            ->groupBy('sourceable_type')
            ->get()
            ->keyBy('sourceable_type');
        $inDeliveryCountPo = (int) ($inDeliveryCountsByType[PurchaseOrder::class]->cnt ?? 0);
        $inDeliveryCountPr = (int) ($inDeliveryCountsByType[PurchaseReturn::class]->cnt ?? 0);
        $inDeliveryCountTotal = $inDeliveryCountPo + $inDeliveryCountPr;

        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Index', [
            'filters' => ['q' => $q, 'doc_type' => $docType, 'status' => $status],
            'deliveries' => array_map(function (SupplierDeliveryOrder $s) {
                $po = null;
                $docTypeRow = $s->sourceable_type === PurchaseOrder::class ? 'po' : 'pr';
                if ($s->sourceable_type === PurchaseOrder::class) {
                    $po = $s->sourceable;
                } elseif ($s->sourceable_type === PurchaseReturn::class) {
                    $po = $s->sourceable?->purchaseOrder;
                }
                $statusValue = $s->status instanceof SupplierDeliveryOrderStatus ? (string) $s->status->value : (string) $s->status;
                $statusLabel = $s->status instanceof SupplierDeliveryOrderStatus
                    ? $s->status->label()
                    : SupplierDeliveryOrderStatus::from($statusValue)->label();
                return [
                    'id' => (string) $s->getKey(),
                    'number' => (string) ($s->number ?? ''),
                    'delivery_date' => $s->delivery_date ? (string) $s->delivery_date->format('Y-m-d') : null,
                    'status' => $statusValue,
                    'status_label' => $statusLabel,
                    'doc_type' => $docTypeRow,
                    'source_id' => (string) $s->sourceable_id,
                    'purchase_order' => [
                        'id' => (string) ($po?->id ?? ''),
                        'number' => (string) ($po?->number ?? ''),
                        'order_date' => $po && $po->order_date ? (string) $po->order_date->format('Y-m-d') : null,
                        'supplier' => ['name' => (string) ($s->supplier?->name ?? '')],
                        'warehouse' => [
                            'name' => (string) ($po?->warehouse?->name ?? ($s->sourceable_type === PurchaseReturn::class ? (string) ($s->sourceable?->warehouse?->name ?? '') : '')),
                            'address' => (string) ($po?->warehouse?->address ?? ''),
                            'phone' => (string) ($po?->warehouse?->phone ?? ''),
                        ],
                        'status' => $po
                            ? ($po->status instanceof PurchaseOrderStatus ? (string) $po->status->value : (string) $po->status)
                            : null,
                        'status_label' => $po
                            ? ($po->status instanceof PurchaseOrderStatus
                                ? $po->status->label()
                                : PurchaseOrderStatus::from((string) $po->status)->label())
                            : null,
                    ],
                ];
            }, $sdos->items()),
            'meta' => [
                'current_page' => $sdos->currentPage(),
                'per_page' => $sdos->perPage(),
                'total' => $sdos->total(),
                'last_page' => $sdos->lastPage(),
            ],
            'counts' => [
                'in_delivery' => [
                    'po' => $inDeliveryCountPo,
                    'pr' => $inDeliveryCountPr,
                    'total' => $inDeliveryCountTotal,
                ],
            ],
        ]);
    }

    /**
     * Tampilkan form penerimaan berdasarkan satu ID Supplier Delivery Order (SDO).
     *
     * @param Request $request
     * @param SupplierDeliveryOrder $supplierDeliveryOrder
     * @return Response
     */
    public function createBySdo(Request $request, SupplierDeliveryOrder $supplierDeliveryOrder): Response
    {
        if ($supplierDeliveryOrder->sourceable_type === PurchaseOrder::class) {
            $supplierDeliveryOrder->load(['items.product']);
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = PurchaseOrder::query()
                ->with(['supplier', 'warehouse'])
                ->findOrFail((string) $supplierDeliveryOrder->sourceable_id);
            $receivedQtyByProduct = GoodsCome::query()
                ->where('referencable_id', (string) $supplierDeliveryOrder->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->select(['product_id', DB::raw('SUM(quantity) as qty')])
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id')
                ->map(fn($row) => (int) $row->qty)
                ->toArray();
            $items = $supplierDeliveryOrder->items->map(fn($it) => [
                'product_id' => (string) $it->product_id,
                'product_name' => (string) ($it->product?->name ?? ''),
                'sku' => (string) ($it->product?->sku ?? ''),
                'sdo_quantity' => (int) $it->quantity,
                'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
                'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
            ]);
            return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/ReceivingForm', [
                'purchase_order' => [
                    'id' => (string) $purchaseOrder->id,
                    'number' => (string) $purchaseOrder->number,
                    'supplier' => ['name' => (string) ($purchaseOrder->supplier?->name ?? '')],
                    'warehouse' => ['name' => (string) ($purchaseOrder->warehouse?->name ?? '')],
                    'status' => $purchaseOrder->status instanceof PurchaseOrderStatus ? (string) $purchaseOrder->status->value : (string) $purchaseOrder->status,
                    'status_label' => $purchaseOrder->status instanceof PurchaseOrderStatus
                        ? $purchaseOrder->status->label()
                        : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label(),
                ],
                'supplier_delivery_order' => [
                    'id' => (string) $supplierDeliveryOrder->id,
                    'number' => (string) ($supplierDeliveryOrder->number ?? ''),
                    'delivery_date' => $supplierDeliveryOrder->delivery_date ? (string) $supplierDeliveryOrder->delivery_date->format('Y-m-d') : null,
                ],
                'items' => $items,
                'reason_options' => array_map(fn(PurchaseReturnReason $r) => [
                    'value' => (string) $r->value,
                    'label' => (string) $r->label(),
                ], PurchaseReturnReason::cases()),
                'resolution_options' => array_map(fn(PurchaseReturnResolution $r) => [
                    'value' => (string) $r->value,
                    'label' => (string) $r->label(),
                ], PurchaseReturnResolution::cases()),
            ]);
        }
        /** @var PurchaseReturn $purchaseReturn */
        $purchaseReturn = PurchaseReturn::query()
            ->with(['supplier', 'warehouse', 'items.product', 'purchaseOrder'])
            ->findOrFail((string) $supplierDeliveryOrder->sourceable_id);
        $receivedQtyByProduct = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (int) $row->qty)
            ->toArray();
        $items = $purchaseReturn->items->map(fn(PurchaseReturnItem $it) => [
            'product_id' => (string) $it->product_id,
            'product_name' => (string) ($it->product?->name ?? ''),
            'sku' => (string) ($it->product?->sku ?? ''),
            'return_quantity' => (int) $it->quantity,
            'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
            'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
        ]);
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/ReceivingForm', [
            'purchase_return' => [
                'id' => (string) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ''),
                'return_date' => $purchaseReturn->return_date ? (string) $purchaseReturn->return_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($purchaseReturn->supplier?->name ?? '')],
                'warehouse' => ['name' => (string) ($purchaseReturn->warehouse?->name ?? '')],
                'purchase_order' => [
                    'id' => (string) ($purchaseReturn->purchaseOrder?->id ?? ''),
                    'number' => (string) ($purchaseReturn->purchaseOrder?->number ?? ''),
                ],
                'status' => $purchaseReturn->status instanceof PurchaseReturnStatus ? (string) $purchaseReturn->status->value : (string) $purchaseReturn->status,
                'status_label' => $purchaseReturn->status instanceof PurchaseReturnStatus
                    ? $purchaseReturn->status->label()
                    : PurchaseReturnStatus::from((string) $purchaseReturn->status)->label(),
                'resolution' => (string) ($purchaseReturn->getRawOriginal('resolution') ?? ''),
            ],
            'supplier_delivery_order' => [
                'id' => (string) $supplierDeliveryOrder->id,
                'number' => (string) ($supplierDeliveryOrder->number ?? ''),
                'delivery_date' => $supplierDeliveryOrder->delivery_date ? (string) $supplierDeliveryOrder->delivery_date->format('Y-m-d') : null,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Tampilkan detail penerimaan berdasarkan satu ID SDO.
     *
     * @param Request $request
     * @param SupplierDeliveryOrder $supplierDeliveryOrder
     * @return Response
     */
    public function showBySdo(Request $request, SupplierDeliveryOrder $supplierDeliveryOrder): Response
    {
        if ($supplierDeliveryOrder->sourceable_type === PurchaseOrder::class) {
            $supplierDeliveryOrder->load(['items.product']);
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = PurchaseOrder::query()
                ->with(['supplier', 'warehouse'])
                ->findOrFail((string) $supplierDeliveryOrder->sourceable_id);
            $receivedQtyByProduct = GoodsCome::query()
                ->where('referencable_id', (string) $supplierDeliveryOrder->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->select(['product_id', DB::raw('SUM(quantity) as qty')])
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id')
                ->map(fn($row) => (int) $row->qty)
                ->toArray();
            $items = $supplierDeliveryOrder->items->map(fn($it) => [
                'product_id' => (string) $it->product_id,
                'product_name' => (string) ($it->product?->name ?? ''),
                'sku' => (string) ($it->product?->sku ?? ''),
                'sdo_quantity' => (int) $it->quantity,
                'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
                'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
            ]);
            $receiving = null;
            $receivingRow = GoodsCome::query()
                ->where('referencable_id', (string) $supplierDeliveryOrder->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->latest('created_at')
                ->first();
            if ($receivingRow) {
                $receiving = [
                    'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                    'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
                ];
            }
            return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Show', [
                'purchase_order' => [
                    'id' => (string) $purchaseOrder->id,
                    'number' => (string) $purchaseOrder->number,
                    'supplier' => ['name' => (string) ($purchaseOrder->supplier?->name ?? '')],
                    'warehouse' => ['name' => (string) ($purchaseOrder->warehouse?->name ?? '')],
                    'status' => $purchaseOrder->status instanceof PurchaseOrderStatus ? (string) $purchaseOrder->status->value : (string) $purchaseOrder->status,
                    'status_label' => $purchaseOrder->status instanceof PurchaseOrderStatus
                        ? $purchaseOrder->status->label()
                        : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label(),
                    'supplier_invoice_number' => (string) ($purchaseOrder->supplier_invoice_number ?? ''),
                    'supplier_invoice_date' => $purchaseOrder->supplier_invoice_date ? (string) $purchaseOrder->supplier_invoice_date->format('Y-m-d') : null,
                    'supplier_invoice_file' => (string) ($purchaseOrder->supplier_invoice_file ?? ''),
                ],
                'supplier_delivery_order' => [
                    'id' => (string) $supplierDeliveryOrder->id,
                    'number' => (string) ($supplierDeliveryOrder->number ?? ''),
                    'delivery_date' => $supplierDeliveryOrder->delivery_date ? (string) $supplierDeliveryOrder->delivery_date->format('Y-m-d') : null,
                ],
                'receiving' => $receiving,
                'items' => $items,
            ]);
        }
        /** @var PurchaseReturn $purchaseReturn */
        $purchaseReturn = PurchaseReturn::query()
            ->with(['supplier', 'warehouse', 'items.product', 'purchaseOrder'])
            ->findOrFail((string) $supplierDeliveryOrder->sourceable_id);
        $receivedQtyByProduct = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (int) $row->qty)
            ->toArray();
        $items = $purchaseReturn->items->map(fn(PurchaseReturnItem $it) => [
            'product_id' => (string) $it->product_id,
            'product_name' => (string) ($it->product?->name ?? ''),
            'sku' => (string) ($it->product?->sku ?? ''),
            'return_quantity' => (int) $it->quantity,
            'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
            'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
        ]);
        $receiving = null;
        $receivingRow = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->latest('created_at')
            ->first();
        if ($receivingRow) {
            $receiving = [
                'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
            ];
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Show', [
            'purchase_return' => [
                'id' => (string) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ''),
                'return_date' => $purchaseReturn->return_date ? (string) $purchaseReturn->return_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($purchaseReturn->supplier?->name ?? '')],
                'warehouse' => ['name' => (string) ($purchaseReturn->warehouse?->name ?? '')],
                'purchase_order' => [
                    'id' => (string) ($purchaseReturn->purchaseOrder?->id ?? ''),
                    'number' => (string) ($purchaseReturn->purchaseOrder?->number ?? ''),
                ],
                'status' => $purchaseReturn->status instanceof PurchaseReturnStatus ? (string) $purchaseReturn->status->value : (string) $purchaseReturn->status,
                'status_label' => $purchaseReturn->status instanceof PurchaseReturnStatus
                    ? $purchaseReturn->status->label()
                    : PurchaseReturnStatus::from((string) $purchaseReturn->status)->label(),
                'resolution' => (string) ($purchaseReturn->getRawOriginal('resolution') ?? ''),
            ],
            'supplier_delivery_order' => [
                'id' => (string) $supplierDeliveryOrder->id,
                'number' => (string) ($supplierDeliveryOrder->number ?? ''),
                'delivery_date' => $supplierDeliveryOrder->delivery_date ? (string) $supplierDeliveryOrder->delivery_date->format('Y-m-d') : null,
            ],
            'receiving' => $receiving,
            'items' => $items,
        ]);
    }

    /**
     * Cetak tanda terima berdasarkan satu ID SDO.
     *
     * @param SupplierDeliveryOrder $supplierDeliveryOrder
     * @return Response
     */
    public function printBySdo(SupplierDeliveryOrder $supplierDeliveryOrder): Response
    {
        if ($supplierDeliveryOrder->sourceable_type === PurchaseOrder::class) {
            $supplierDeliveryOrder->load(['items.product']);
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = PurchaseOrder::query()
                ->with(['supplier', 'warehouse'])
                ->findOrFail((string) $supplierDeliveryOrder->sourceable_id);
            $receivedQtyByProduct = GoodsCome::query()
                ->where('referencable_id', (string) $supplierDeliveryOrder->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->select(['product_id', DB::raw('SUM(quantity) as qty')])
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id')
                ->map(fn($row) => (int) $row->qty)
                ->toArray();
            $items = $supplierDeliveryOrder->items->map(fn($it) => [
                'product_id' => (string) $it->product_id,
                'product_name' => (string) ($it->product?->name ?? ''),
                'sku' => (string) ($it->product?->sku ?? ''),
                'sdo_quantity' => (int) $it->quantity,
                'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
                'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
            ]);
            $receiving = null;
            $receivingRow = GoodsCome::query()
                ->where('referencable_id', (string) $supplierDeliveryOrder->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->latest('created_at')
                ->first();
            if ($receivingRow) {
                $receiving = [
                    'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                    'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
                ];
            }
            return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Print', [
                'purchase_order' => [
                    'id' => (string) $purchaseOrder->id,
                    'number' => (string) $purchaseOrder->number,
                    'supplier' => ['name' => (string) ($purchaseOrder->supplier?->name ?? '')],
                    'warehouse' => [
                        'name' => (string) ($purchaseOrder->warehouse?->name ?? ''),
                        'address' => (string) ($purchaseOrder->warehouse?->address ?? ''),
                        'phone' => (string) ($purchaseOrder->warehouse?->phone ?? ''),
                    ],
                    'status' => $purchaseOrder->status instanceof PurchaseOrderStatus ? (string) $purchaseOrder->status->value : (string) $purchaseOrder->status,
                    'status_label' => $purchaseOrder->status instanceof PurchaseOrderStatus
                        ? $purchaseOrder->status->label()
                        : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label(),
                    'supplier_invoice_number' => (string) ($purchaseOrder->supplier_invoice_number ?? ''),
                    'supplier_invoice_date' => $purchaseOrder->supplier_invoice_date ? (string) $purchaseOrder->supplier_invoice_date->format('Y-m-d') : null,
                    'supplier_invoice_file' => (string) ($purchaseOrder->supplier_invoice_file ?? ''),
                ],
                'supplier_delivery_order' => [
                    'id' => (string) $supplierDeliveryOrder->id,
                    'number' => (string) ($supplierDeliveryOrder->number ?? ''),
                    'delivery_date' => $supplierDeliveryOrder->delivery_date ? (string) $supplierDeliveryOrder->delivery_date->format('Y-m-d') : null,
                ],
                'receiving' => $receiving,
                'items' => $items,
            ]);
        }
        /** @var PurchaseReturn $purchaseReturn */
        $purchaseReturn = PurchaseReturn::query()
            ->with(['supplier', 'warehouse', 'items.product', 'purchaseOrder'])
            ->findOrFail((string) $supplierDeliveryOrder->sourceable_id);
        $receivedQtyByProduct = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (int) $row->qty)
            ->toArray();
        $items = $purchaseReturn->items->map(fn(PurchaseReturnItem $it) => [
            'product_id' => (string) $it->product_id,
            'product_name' => (string) ($it->product?->name ?? ''),
            'sku' => (string) ($it->product?->sku ?? ''),
            'return_quantity' => (int) $it->quantity,
            'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
            'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
        ]);
        $receiving = null;
        $receivingRow = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->latest('created_at')
            ->first();
        if ($receivingRow) {
            $receiving = [
                'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
            ];
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Print', [
            'purchase_return' => [
                'id' => (string) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ''),
                'return_date' => $purchaseReturn->return_date ? (string) $purchaseReturn->return_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($purchaseReturn->supplier?->name ?? '')],
                'warehouse' => [
                    'name' => (string) ($purchaseReturn->warehouse?->name ?? ''),
                    'address' => (string) ($purchaseReturn->warehouse?->address ?? ''),
                    'phone' => (string) ($purchaseReturn->warehouse?->phone ?? ''),
                ],
                'purchase_order' => [
                    'id' => (string) ($purchaseReturn->purchaseOrder?->id ?? ''),
                    'number' => (string) ($purchaseReturn->purchaseOrder?->number ?? ''),
                ],
                'status' => $purchaseReturn->status instanceof PurchaseReturnStatus ? (string) $purchaseReturn->status->value : (string) $purchaseReturn->status,
                'status_label' => $purchaseReturn->status instanceof PurchaseReturnStatus
                    ? $purchaseReturn->status->label()
                    : PurchaseReturnStatus::from((string) $purchaseReturn->status)->label(),
                'resolution' => (string) ($purchaseReturn->getRawOriginal('resolution') ?? ''),
            ],
            'supplier_delivery_order' => [
                'id' => (string) $supplierDeliveryOrder->id,
                'number' => (string) ($supplierDeliveryOrder->number ?? ''),
                'delivery_date' => $supplierDeliveryOrder->delivery_date ? (string) $supplierDeliveryOrder->delivery_date->format('Y-m-d') : null,
            ],
            'receiving' => $receiving,
            'items' => $items,
        ]);
    }

    /**
     * Tampilkan form penerimaan untuk PO tertentu, dengan SDO yang dipilih.
     *
     * @param Request $request
     * @param PurchaseOrder $purchaseOrder
     * @return Response
     */
    public function create(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'warehouse']);
        $sdos = SupplierDeliveryOrder::query()
            ->with(['items.product'])
            ->where('sourceable_type', PurchaseOrder::class)
            ->where('sourceable_id', $purchaseOrder->getKey())
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->get();
        $selectedId = (string) ($request->string('sdo_id')->toString() ?: $request->string('SJ_id')->toString());
        $selectedSdo = $sdos->firstWhere('id', $selectedId);
        $items = [];
        if ($selectedSdo) {
            $receivedQtyByProduct = GoodsCome::query()
                ->where('referencable_id', (string) $selectedSdo->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->select(['product_id', DB::raw('SUM(quantity) as qty')])
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id')
                ->map(fn($row) => (int) $row->qty)
                ->toArray();
            $items = $selectedSdo->items->map(fn($it) => [
                'product_id' => (string) $it->product_id,
                'product_name' => (string) ($it->product->name ?? ''),
                'sku' => (string) ($it->product->sku ?? ''),
                'sdo_quantity' => (int) $it->quantity,
                'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
                'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
            ]);
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/ReceivingForm', [
            'purchase_order' => [
                'id' => (string) $purchaseOrder->id,
                'number' => (string) $purchaseOrder->number,
                'supplier' => [
                    'name' => (string) ($purchaseOrder->supplier->name ?? ''),
                ],
                'warehouse' => [
                    'name' => (string) ($purchaseOrder->warehouse->name ?? ''),
                ],
                'status' => $purchaseOrder->status instanceof PurchaseOrderStatus ? (string) $purchaseOrder->status->value : (string) $purchaseOrder->status,
                'status_label' => $purchaseOrder->status instanceof PurchaseOrderStatus
                    ? $purchaseOrder->status->label()
                    : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label(),
            ],
            'supplier_delivery_order' => $selectedSdo ? [
                'id' => (string) $selectedSdo->id,
                'number' => (string) ($selectedSdo->number ?? ''),
                'delivery_date' => $selectedSdo->delivery_date ? (string) $selectedSdo->delivery_date->format('Y-m-d') : null,
            ] : null,
            'items' => $items,
            'reason_options' => array_map(fn(PurchaseReturnReason $r) => [
                'value' => (string) $r->value,
                'label' => (string) $r->label(),
            ], PurchaseReturnReason::cases()),
            'resolution_options' => array_map(fn(PurchaseReturnResolution $r) => [
                'value' => (string) $r->value,
                'label' => (string) $r->label(),
            ], PurchaseReturnResolution::cases()),
        ]);
    }

    /**
     * Tampilkan detail penerimaan barang (read-only) untuk PO tertentu.
     *
     * @param Request $request
     * @param PurchaseOrder $purchaseOrder
     * @return Response
     */
    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'warehouse']);
        $sdos = SupplierDeliveryOrder::query()
            ->with(['items.product'])
            ->where('sourceable_type', PurchaseOrder::class)
            ->where('sourceable_id', $purchaseOrder->getKey())
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->get();
        $selectedId = (string) ($request->string('sdo_id')->toString() ?: $request->string('SJ_id')->toString());
        $selectedSdo = $sdos->firstWhere('id', $selectedId);
        $items = [];
        if ($selectedSdo) {
            $receivedQtyByProduct = GoodsCome::query()
                ->where('referencable_id', (string) $selectedSdo->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->select(['product_id', DB::raw('SUM(quantity) as qty')])
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id')
                ->map(fn($row) => (int) $row->qty)
                ->toArray();
            $items = $selectedSdo->items->map(fn($it) => [
                'product_id' => (string) $it->product_id,
                'product_name' => (string) ($it->product->name ?? ''),
                'sku' => (string) ($it->product->sku ?? ''),
                'sdo_quantity' => (int) $it->quantity,
                'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
                'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
            ]);
        }
        $receiving = null;
        if ($selectedSdo) {
            $receivingRow = GoodsCome::query()
                ->where('referencable_id', (string) $selectedSdo->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->latest('created_at')
                ->first();
            if ($receivingRow) {
                $receiving = [
                    'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                    'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
                ];
            }
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Show', [
            'purchase_order' => [
                'id' => (string) $purchaseOrder->id,
                'number' => (string) $purchaseOrder->number,
                'supplier' => ['name' => (string) ($purchaseOrder->supplier->name ?? '')],
                'warehouse' => ['name' => (string) ($purchaseOrder->warehouse->name ?? '')],
                'status' => $purchaseOrder->status instanceof PurchaseOrderStatus ? (string) $purchaseOrder->status->value : (string) $purchaseOrder->status,
                'status_label' => $purchaseOrder->status instanceof PurchaseOrderStatus
                    ? $purchaseOrder->status->label()
                    : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label(),
                'supplier_invoice_number' => (string) ($purchaseOrder->supplier_invoice_number ?? ''),
                'supplier_invoice_date' => $purchaseOrder->supplier_invoice_date ? (string) $purchaseOrder->supplier_invoice_date->format('Y-m-d') : null,
                'supplier_invoice_file' => (string) ($purchaseOrder->supplier_invoice_file ?? ''),
            ],
            'supplier_delivery_order' => $selectedSdo ? [
                'id' => (string) $selectedSdo->id,
                'number' => (string) ($selectedSdo->number ?? ''),
                'delivery_date' => $selectedSdo->delivery_date ? (string) $selectedSdo->delivery_date->format('Y-m-d') : null,
            ] : null,
            'receiving' => $receiving,
            'items' => $items,
        ]);
    }

    public function print(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'warehouse']);
        $sdos = SupplierDeliveryOrder::query()
            ->with(['items.product'])
            ->where('sourceable_type', PurchaseOrder::class)
            ->where('sourceable_id', $purchaseOrder->getKey())
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->get();
        $selectedId = (string) ($request->string('sdo_id')->toString() ?: $request->string('SJ_id')->toString());
        $selectedSdo = $sdos->firstWhere('id', $selectedId);
        $items = [];
        if ($selectedSdo) {
            $receivedQtyByProduct = GoodsCome::query()
                ->where('referencable_id', (string) $selectedSdo->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->select(['product_id', DB::raw('SUM(quantity) as qty')])
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id')
                ->map(fn($row) => (int) $row->qty)
                ->toArray();
            $items = $selectedSdo->items->map(fn($it) => [
                'product_id' => (string) $it->product_id,
                'product_name' => (string) ($it->product->name ?? ''),
                'sku' => (string) ($it->product->sku ?? ''),
                'sdo_quantity' => (int) $it->quantity,
                'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
                'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
            ]);
        }
        $receiving = null;
        if ($selectedSdo) {
            $receivingRow = GoodsCome::query()
                ->where('referencable_id', (string) $selectedSdo->id)
                ->where('referencable_type', SupplierDeliveryOrder::class)
                ->latest('created_at')
                ->first();
            if ($receivingRow) {
                $receiving = [
                    'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                    'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
                ];
            }
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Print', [
            'purchase_order' => [
                'id' => (string) $purchaseOrder->id,
                'number' => (string) $purchaseOrder->number,
                'supplier' => ['name' => (string) ($purchaseOrder->supplier->name ?? '')],
                'warehouse' => [
                    'name' => (string) ($purchaseOrder->warehouse->name ?? ''),
                    'address' => (string) ($purchaseOrder->warehouse->address ?? ''),
                    'phone' => (string) ($purchaseOrder->warehouse->phone ?? ''),
                ],
                'status' => $purchaseOrder->status instanceof PurchaseOrderStatus ? (string) $purchaseOrder->status->value : (string) $purchaseOrder->status,
                'status_label' => $purchaseOrder->status instanceof PurchaseOrderStatus
                    ? $purchaseOrder->status->label()
                    : PurchaseOrderStatus::from((string) $purchaseOrder->status)->label(),
                'supplier_invoice_number' => (string) ($purchaseOrder->supplier_invoice_number ?? ''),
                'supplier_invoice_date' => $purchaseOrder->supplier_invoice_date ? (string) $purchaseOrder->supplier_invoice_date->format('Y-m-d') : null,
                'supplier_invoice_file' => (string) ($purchaseOrder->supplier_invoice_file ?? ''),
            ],
            'supplier_delivery_order' => $selectedSdo ? [
                'id' => (string) $selectedSdo->id,
                'number' => (string) ($selectedSdo->number ?? ''),
                'delivery_date' => $selectedSdo->delivery_date ? (string) $selectedSdo->delivery_date->format('Y-m-d') : null,
            ] : null,
            'receiving' => $receiving,
            'items' => $items,
        ]);
    }

    /**
     * Proses penerimaan barang berdasarkan SDO terpilih.
     *
     * @param ReceiveSupplierDeliveryOrderRequest $request
     * @param PurchaseOrder $purchaseOrder
     * @param StockService $stock
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function store(ReceiveSupplierDeliveryOrderRequest $request, PurchaseOrder $purchaseOrder, SupplierDeliveryOrderService $deliveryOrders): RedirectResponse
    {
        try {
            $sdoId = (string) $request->input('sdo_id');
            $sdo = SupplierDeliveryOrder::query()->where('id', $sdoId)->first();
            if (
                !$sdo
                || $sdo->sourceable_type !== PurchaseOrder::class
                || (string) $sdo->sourceable_id !== (string) $purchaseOrder->id
            ) {
                throw new \InvalidArgumentException('Supplier Delivery Order tidak valid untuk PO ini');
            }
            $items = array_map(function ($it) {
                return [
                    'product_id' => (string) $it['product_id'],
                    'quantity' => (int) $it['quantity'],
                    'notes' => isset($it['notes']) ? (string) $it['notes'] : null,
                ];
            }, (array) $request->input('items', []));
            $exceptions = array_map(function ($ex) {
                return [
                    'product_id' => (string) $ex['product_id'],
                    'quantity' => (int) $ex['quantity'],
                    'reason' => (string) $ex['reason'],
                    'resolution' => (string) $ex['resolution'],
                    'notes' => isset($ex['notes']) ? (string) $ex['notes'] : null,
                ];
            }, (array) $request->input('exceptions', []));
            $deliveryOrders->receiveSupplierDeliveryOrder(
                $sdo,
                $items,
                (string) $request->user()->getAuthIdentifier(),
                (string) $request->input('sender_name'),
                (string) $request->input('vehicle_plate_number'),
                $exceptions
            );
            $updates = [];
            if ($request->filled('supplier_invoice_number')) {
                $updates['supplier_invoice_number'] = (string) $request->input('supplier_invoice_number');
            }
            if ($request->filled('supplier_invoice_date')) {
                $updates['supplier_invoice_date'] = (string) $request->input('supplier_invoice_date');
            }
            if ($request->hasFile('supplier_invoice_file')) {
                $path = $request->file('supplier_invoice_file')->store('supplier-invoices', 'public');
                $updates['supplier_invoice_file'] = Storage::url($path);
            }
            if (!empty($updates)) {
                $purchaseOrder->update($updates);
            }
            Inertia::flash('toast', [
                'message' => 'Goods Receipt berhasil untuk SJ: ' . $sdo->number,
                'type' => 'success',
            ]);
            return redirect()->route('goods-receipts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memproses Goods Receipt: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function createReturn(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load(['supplier', 'warehouse', 'items.product', 'purchaseOrder']);
        $receivedQtyByProduct = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (int) $row->qty)
            ->toArray();
        $items = $purchaseReturn->items->map(fn(PurchaseReturnItem $it) => [
            'product_id' => (string) $it->product_id,
            'product_name' => (string) ($it->product?->name ?? ''),
            'sku' => (string) ($it->product?->sku ?? ''),
            'return_quantity' => (int) $it->quantity,
            'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
            'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
        ]);
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/ReceivingForm', [
            'purchase_return' => [
                'id' => (string) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ''),
                'return_date' => $purchaseReturn->return_date ? (string) $purchaseReturn->return_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($purchaseReturn->supplier?->name ?? '')],
                'warehouse' => ['name' => (string) ($purchaseReturn->warehouse?->name ?? '')],
                'purchase_order' => [
                    'id' => (string) ($purchaseReturn->purchaseOrder?->id ?? ''),
                    'number' => (string) ($purchaseReturn->purchaseOrder?->number ?? ''),
                ],
                'status' => $purchaseReturn->status instanceof PurchaseReturnStatus ? (string) $purchaseReturn->status->value : (string) $purchaseReturn->status,
                'status_label' => $purchaseReturn->status instanceof PurchaseReturnStatus
                    ? $purchaseReturn->status->label()
                    : PurchaseReturnStatus::from((string) $purchaseReturn->status)->label(),
                'resolution' => $purchaseReturn->resolution instanceof PurchaseReturnResolution ? (string) $purchaseReturn->resolution->value : (string) $purchaseReturn->resolution,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Tampilkan detail penerimaan penggantian untuk Retur Pembelian (read-only).
     *
     * @param Request $request
     * @param PurchaseReturn $purchaseReturn
     * @return Response
     */
    public function showReturn(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load(['supplier', 'warehouse', 'items.product', 'purchaseOrder']);
        $receivedQtyByProduct = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (int) $row->qty)
            ->toArray();
        $items = $purchaseReturn->items->map(fn(PurchaseReturnItem $it) => [
            'product_id' => (string) $it->product_id,
            'product_name' => (string) ($it->product?->name ?? ''),
            'sku' => (string) ($it->product?->sku ?? ''),
            'return_quantity' => (int) $it->quantity,
            'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
            'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
        ]);
        $receiving = null;
        $receivingRow = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->latest('created_at')
            ->first();
        if ($receivingRow) {
            $receiving = [
                'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
            ];
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Show', [
            'purchase_return' => [
                'id' => (string) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ''),
                'return_date' => $purchaseReturn->return_date ? (string) $purchaseReturn->return_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($purchaseReturn->supplier?->name ?? '')],
                'warehouse' => ['name' => (string) ($purchaseReturn->warehouse?->name ?? '')],
                'purchase_order' => [
                    'id' => (string) ($purchaseReturn->purchaseOrder?->id ?? ''),
                    'number' => (string) ($purchaseReturn->purchaseOrder?->number ?? ''),
                ],
                'status' => $purchaseReturn->status instanceof PurchaseReturnStatus ? (string) $purchaseReturn->status->value : (string) $purchaseReturn->status,
                'status_label' => $purchaseReturn->status instanceof PurchaseReturnStatus
                    ? $purchaseReturn->status->label()
                    : PurchaseReturnStatus::from((string) $purchaseReturn->status)->label(),
                'resolution' => $purchaseReturn->resolution instanceof PurchaseReturnResolution ? (string) $purchaseReturn->resolution->value : (string) $purchaseReturn->resolution,
            ],
            'receiving' => $receiving,
            'items' => $items,
        ]);
    }

    public function printReturn(PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load(['supplier', 'warehouse', 'items.product', 'purchaseOrder']);
        $receivedQtyByProduct = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (int) $row->qty)
            ->toArray();
        $items = $purchaseReturn->items->map(fn(PurchaseReturnItem $it) => [
            'product_id' => (string) $it->product_id,
            'product_name' => (string) ($it->product?->name ?? ''),
            'sku' => (string) ($it->product?->sku ?? ''),
            'return_quantity' => (int) $it->quantity,
            'received_quantity' => (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0),
            'remaining_quantity' => max(0, (int) $it->quantity - (int) ($receivedQtyByProduct[(string) $it->product_id] ?? 0)),
        ]);
        $receiving = null;
        $receivingRow = GoodsCome::query()
            ->where('referencable_id', (string) $purchaseReturn->id)
            ->where('referencable_type', PurchaseReturn::class)
            ->latest('created_at')
            ->first();
        if ($receivingRow) {
            $receiving = [
                'sender_name' => (string) ($receivingRow->sender_name ?? ''),
                'vehicle_plate_number' => (string) ($receivingRow->vehicle_plate_number ?? ''),
            ];
        }
        return Inertia::render('Domains/Admin/Logistic/GoodsReceipts/Print', [
            'purchase_return' => [
                'id' => (string) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ''),
                'return_date' => $purchaseReturn->return_date ? (string) $purchaseReturn->return_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($purchaseReturn->supplier?->name ?? '')],
                'warehouse' => [
                    'name' => (string) ($purchaseReturn->warehouse?->name ?? ''),
                    'address' => (string) ($purchaseReturn->warehouse?->address ?? ''),
                    'phone' => (string) ($purchaseReturn->warehouse?->phone ?? ''),
                ],
                'purchase_order' => [
                    'id' => (string) ($purchaseReturn->purchaseOrder?->id ?? ''),
                    'number' => (string) ($purchaseReturn->purchaseOrder?->number ?? ''),
                ],
                'status' => $purchaseReturn->status instanceof PurchaseReturnStatus ? (string) $purchaseReturn->status->value : (string) $purchaseReturn->status,
                'status_label' => $purchaseReturn->status instanceof PurchaseReturnStatus
                    ? $purchaseReturn->status->label()
                    : PurchaseReturnStatus::from((string) $purchaseReturn->status)->label(),
                'resolution' => $purchaseReturn->resolution instanceof PurchaseReturnResolution ? (string) $purchaseReturn->resolution->value : (string) $purchaseReturn->resolution,
            ],
            'receiving' => $receiving,
            'items' => $items,
        ]);
    }

    public function storeReturn(\App\Http\Requests\PurchaseReturn\ReceivePurchaseReturnReplacementRequest $request, PurchaseReturn $purchaseReturn, \App\Services\PurchaseReturnService $service): RedirectResponse
    {
        try {
            $sdoId = (string) $request->input('sdo_id');
            $sdo = null;
            if ($sdoId !== '') {
                $sdo = SupplierDeliveryOrder::query()->where('id', $sdoId)->first();
                if (
                    !$sdo
                    || $sdo->sourceable_type !== PurchaseReturn::class
                    || (string) $sdo->sourceable_id !== (string) $purchaseReturn->id
                ) {
                    throw new \InvalidArgumentException('Supplier Delivery Order tidak valid untuk Retur ini');
                }
            }
            $items = array_map(function ($it) {
                return [
                    'product_id' => (string) $it['product_id'],
                    'quantity' => (int) $it['quantity'],
                    'notes' => isset($it['notes']) ? (string) $it['notes'] : null,
                ];
            }, (array) $request->input('items', []));
            $service->receiveReplacementForPurchaseReturn(
                $purchaseReturn,
                $items,
                (string) $request->user()->getAuthIdentifier(),
                (string) $request->input('sender_name'),
                (string) $request->input('vehicle_plate_number'),
                $sdo
            );
            Inertia::flash('toast', [
                'message' => 'Goods Receipt penggantian berhasil untuk Retur: ' . ($purchaseReturn->number ?? ''),
                'type' => 'success',
            ]);
            return redirect()->route('goods-receipts.index');
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memproses Goods Receipt penggantian: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
