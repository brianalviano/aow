<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\{SupplierDeliveryOrderStatus, PurchaseOrderStatus, PurchaseReturnStatus, GoodsComeSourceType, PurchaseReturnResolution, PurchaseReturnReason};
use App\Models\{SupplierDeliveryOrder, SupplierDeliveryOrderItem, PurchaseOrder, PurchaseReturn, GoodsCome, GoodsReceiptAccrual, ProductBatch};
use Illuminate\Support\Facades\{DB, Log};
use App\Traits\RetryableTransactionsTrait;
use App\Services\{GoodsComeService, PurchaseReturnService};
use App\DTOs\PurchaseReturn\{PurchaseReturnData, PurchaseReturnItemData};

class SupplierDeliveryOrderService
{
    use RetryableTransactionsTrait;

    public function createFromPurchaseOrder(
        PurchaseOrder $po,
        array $items = [],
        ?string $deliveryDate = null,
        ?string $number = null,
        ?string $notes = null
    ): SupplierDeliveryOrder {
        return $this->runWithRetry(function () use ($po, $items, $deliveryDate, $number, $notes) {
            try {
                return DB::transaction(function () use ($po, $items, $deliveryDate, $number, $notes) {
                    $poItems = \App\Models\PurchaseOrderItem::query()
                        ->where('purchase_order_id', $po->getKey())
                        ->get(['product_id', 'quantity']);
                    $poQtyByProduct = [];
                    foreach ($poItems as $pi) {
                        $pid = (string) $pi->product_id;
                        $poQtyByProduct[$pid] = ($poQtyByProduct[$pid] ?? 0) + (int) $pi->quantity;
                    }

                    $sdoItems = SupplierDeliveryOrderItem::query()
                        ->whereHas('supplierDeliveryOrder', function ($q) use ($po) {
                            $q->where('sourceable_type', PurchaseOrder::class)
                                ->where('sourceable_id', $po->getKey());
                        })
                        ->get(['product_id', 'quantity']);
                    $deliveredQtyByProduct = [];
                    foreach ($sdoItems as $di) {
                        $pid = (string) $di->product_id;
                        $deliveredQtyByProduct[$pid] = ($deliveredQtyByProduct[$pid] ?? 0) + (int) $di->quantity;
                    }

                    if (empty($items)) {
                        $autoItems = [];
                        foreach ($poQtyByProduct as $pid => $poQty) {
                            $remaining = max(0, (int) $poQty - (int) ($deliveredQtyByProduct[$pid] ?? 0));
                            if ($remaining > 0) {
                                $autoItems[] = [
                                    'product_id' => (string) $pid,
                                    'quantity' => (int) $remaining,
                                    'notes' => null,
                                ];
                            }
                        }
                        if (empty($autoItems)) {
                            throw new \InvalidArgumentException('Tidak ada sisa item untuk dikirim');
                        }
                        $items = $autoItems;
                    }

                    foreach ($items as $it) {
                        $pid = (string) $it['product_id'];
                        $qty = (int) $it['quantity'];
                        $poQty = (int) ($poQtyByProduct[$pid] ?? 0);
                        $delivered = (int) ($deliveredQtyByProduct[$pid] ?? 0);
                        if ($qty <= 0) {
                            throw new \InvalidArgumentException('Quantity harus lebih dari 0');
                        }
                        if ($qty + $delivered > $poQty) {
                            throw new \InvalidArgumentException('Quantity melebihi sisa PO untuk produk: ' . $pid);
                        }
                    }

                    $sdo = new SupplierDeliveryOrder();
                    $sdo->supplier_id = $po->supplier_id;
                    $sdo->sourceable_id = $po->getKey();
                    $sdo->sourceable_type = PurchaseOrder::class;
                    $sdo->delivery_date = $deliveryDate ?: now()->toDateString();
                    $sdo->number = $number ?: $this->generateNumber();
                    $sdo->status = SupplierDeliveryOrderStatus::InDelivery->value;
                    $sdo->notes = $notes;
                    $sdo->save();

                    $rows = [];
                    foreach ($items as $it) {
                        $rows[] = [
                            'product_id' => (string) $it['product_id'],
                            'quantity' => (int) $it['quantity'],
                            'notes' => $it['notes'] ?? null,
                        ];
                    }
                    $sdo->items()->createMany($rows);

                    if ($po->status !== PurchaseOrderStatus::InDelivery->value) {
                        $po->status = PurchaseOrderStatus::InDelivery->value;
                        $po->save();
                    }

                    return $sdo;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('sdo_create_po_error', [
                    'purchase_order_id' => (string) $po->getKey(),
                    'number' => (string) ($number ?? ''),
                    'delivery_date' => (string) ($deliveryDate ?? ''),
                    'notes' => (string) ($notes ?? ''),
                    'payload_items' => $items,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function createFromPurchaseReturn(
        PurchaseReturn $pr,
        array $items = [],
        ?string $deliveryDate = null,
        ?string $number = null,
        ?string $notes = null
    ): SupplierDeliveryOrder {
        return $this->runWithRetry(function () use ($pr, $items, $deliveryDate, $number, $notes) {
            try {
                return DB::transaction(function () use ($pr, $items, $deliveryDate, $number, $notes) {
                    $prItems = \App\Models\PurchaseReturnItem::query()
                        ->where('purchase_return_id', $pr->getKey())
                        ->get(['product_id', 'quantity']);
                    $prQtyByProduct = [];
                    foreach ($prItems as $pi) {
                        $pid = (string) $pi->product_id;
                        $prQtyByProduct[$pid] = ($prQtyByProduct[$pid] ?? 0) + (int) $pi->quantity;
                    }

                    $sdoItems = SupplierDeliveryOrderItem::query()
                        ->whereHas('supplierDeliveryOrder', function ($q) use ($pr) {
                            $q->where('sourceable_type', PurchaseReturn::class)
                                ->where('sourceable_id', $pr->getKey());
                        })
                        ->get(['product_id', 'quantity']);
                    $deliveredQtyByProduct = [];
                    foreach ($sdoItems as $di) {
                        $pid = (string) $di->product_id;
                        $deliveredQtyByProduct[$pid] = ($deliveredQtyByProduct[$pid] ?? 0) + (int) $di->quantity;
                    }

                    if (empty($items)) {
                        $autoItems = [];
                        foreach ($prQtyByProduct as $pid => $prQty) {
                            $remaining = max(0, (int) $prQty - (int) ($deliveredQtyByProduct[$pid] ?? 0));
                            if ($remaining > 0) {
                                $autoItems[] = [
                                    'product_id' => (string) $pid,
                                    'quantity' => (int) $remaining,
                                    'notes' => null,
                                ];
                            }
                        }
                        if (empty($autoItems)) {
                            throw new \InvalidArgumentException('Tidak ada sisa item penggantian untuk dikirim');
                        }
                        $items = $autoItems;
                    }

                    foreach ($items as $it) {
                        $pid = (string) $it['product_id'];
                        $qty = (int) $it['quantity'];
                        $prQty = (int) ($prQtyByProduct[$pid] ?? 0);
                        $delivered = (int) ($deliveredQtyByProduct[$pid] ?? 0);
                        if ($qty <= 0) {
                            throw new \InvalidArgumentException('Quantity harus lebih dari 0');
                        }
                        if ($qty + $delivered > $prQty) {
                            throw new \InvalidArgumentException('Quantity melebihi sisa Retur untuk produk: ' . $pid);
                        }
                    }

                    $sdo = new SupplierDeliveryOrder();
                    $sdo->supplier_id = $pr->supplier_id;
                    $sdo->sourceable_id = $pr->getKey();
                    $sdo->sourceable_type = PurchaseReturn::class;
                    $sdo->delivery_date = $deliveryDate ?: now()->toDateString();
                    $sdo->number = $number ?: $this->generateNumber();
                    $sdo->status = SupplierDeliveryOrderStatus::InDelivery->value;
                    $sdo->notes = $notes;
                    $sdo->save();

                    $rows = [];
                    foreach ($items as $it) {
                        $rows[] = [
                            'product_id' => (string) $it['product_id'],
                            'quantity' => (int) $it['quantity'],
                            'notes' => $it['notes'] ?? null,
                        ];
                    }
                    $sdo->items()->createMany($rows);

                    if ($pr->status !== PurchaseReturnStatus::InDelivery->value) {
                        $pr->status = PurchaseReturnStatus::InDelivery->value;
                        $pr->save();
                    }

                    return $sdo;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('sdo_create_pr_error', [
                    'purchase_return_id' => (string) $pr->getKey(),
                    'number' => (string) ($number ?? ''),
                    'delivery_date' => (string) ($deliveryDate ?? ''),
                    'notes' => (string) ($notes ?? ''),
                    'payload_items' => $items,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function receiveSupplierDeliveryOrder(
        SupplierDeliveryOrder $sdo,
        array $items,
        ?string $receivedById = null,
        ?string $senderName = null,
        ?string $vehiclePlateNumber = null,
        ?array $exceptions = null
    ): SupplierDeliveryOrder {
        return $this->runWithRetry(function () use ($sdo, $items, $receivedById, $senderName, $vehiclePlateNumber, $exceptions) {
            try {
                return DB::transaction(function () use ($sdo, $items, $receivedById, $senderName, $vehiclePlateNumber, $exceptions) {
                    $po = null;
                    if ($sdo->sourceable_type === PurchaseOrder::class) {
                        $po = PurchaseOrder::query()->where('id', (string) $sdo->sourceable_id)->lockForUpdate()->first();
                    }
                    if (!$po) {
                        throw new \InvalidArgumentException('Related PurchaseOrder not found');
                    }

                    $warehouseId = (string) $po->warehouse_id;
                    $supplierName = $po->supplier_name ?? null;
                    $isVat = (bool) $po->is_value_added_tax_enabled;
                    $orderDate = $po->order_date ? (string) $po->order_date->format('Y-m-d') : null;

                    $poItemPrices = \App\Models\PurchaseOrderItem::query()
                        ->where('purchase_order_id', (string) $po->id)
                        ->get(['product_id', 'price'])
                        ->keyBy('product_id')
                        ->map(fn($row) => (int) $row->price)
                        ->toArray();

                    $period = now()->format('Ym');

                    foreach ($items as $it) {
                        $productId = (string) $it['product_id'];
                        $qty = (int) $it['quantity'];
                        if ($qty <= 0) {
                            throw new \InvalidArgumentException('Quantity must be positive');
                        }
                        $unitCost = isset($it['unit_cost']) ? (int) $it['unit_cost'] : (int) ($poItemPrices[$productId] ?? 0);

                        $gc = app(GoodsComeService::class)->receiveGoods([
                            'referencable_id' => (string) $sdo->id,
                            'referencable_type' => SupplierDeliveryOrder::class,
                            'source_type' => GoodsComeSourceType::PurchaseOrder->value,
                            'warehouse_id' => $warehouseId,
                            'product_division_id' => $it['product_division_id'] ?? null,
                            'product_rack_id' => $it['product_rack_id'] ?? null,
                            'product_id' => $productId,
                            'quantity' => $qty,
                            'unit_cost' => $unitCost,
                            'notes' => $it['notes'] ?? null,
                            'expired_date' => null,
                            'batch_numbers' => null,
                            'barcode' => null,
                            'supplier_name' => $supplierName,
                            'sender_name' => $senderName,
                            'vehicle_plate_number' => $vehiclePlateNumber,
                            'invoice_number' => $po->supplier_invoice_number ?? null,
                            'purchase_date' => $orderDate,
                            'created_by_id' => $receivedById,
                            'is_value_added_tax_enabled' => $isVat,
                        ]);

                        GoodsReceiptAccrual::create([
                            'goods_come_id' => (string) $gc->id,
                            'supplier_id' => $po->supplier_id,
                            'amount' => (int) ($qty * $unitCost),
                            'period' => $period,
                            'is_settled' => false,
                            'settled_at' => null,
                        ]);

                        if (isset($it['batches']) && is_array($it['batches'])) {
                            foreach ($it['batches'] as $b) {
                                ProductBatch::create([
                                    'goods_come_id' => (string) $gc->id,
                                    'product_id' => $productId,
                                    'batch_number' => (string) $b['batch_number'],
                                    'quantity' => (int) $b['quantity'],
                                    'expired_date' => $b['expired_date'] ?? null,
                                    'manufacturing_date' => $b['manufacturing_date'] ?? null,
                                ]);
                            }
                        }
                    }

                    $sdo->status = SupplierDeliveryOrderStatus::Completed->value;
                    $sdo->save();

                    $sdoItems = SupplierDeliveryOrderItem::query()
                        ->where('supplier_delivery_order_id', (string) $sdo->id)
                        ->get(['product_id', 'quantity']);
                    $sdoQtyByProduct = [];
                    foreach ($sdoItems as $row) {
                        $pid = (string) $row->product_id;
                        $sdoQtyByProduct[$pid] = ($sdoQtyByProduct[$pid] ?? 0) + (int) $row->quantity;
                    }
                    $receivedRows = GoodsCome::query()
                        ->where('referencable_id', (string) $sdo->id)
                        ->where('referencable_type', SupplierDeliveryOrder::class)
                        ->get(['product_id', 'quantity']);
                    $receivedQtyForSdo = [];
                    foreach ($receivedRows as $row) {
                        $pid = (string) $row->product_id;
                        $receivedQtyForSdo[$pid] = ($receivedQtyForSdo[$pid] ?? 0) + (int) $row->quantity;
                    }

                    $shortageItems = [];
                    foreach ($sdoQtyByProduct as $pid => $planned) {
                        $received = (int) ($receivedQtyForSdo[$pid] ?? 0);
                        $diff = $received - (int) $planned;
                        if ($diff < 0) {
                            $shortageItems[] = new PurchaseReturnItemData((string) $pid, (int) abs($diff), (int) ($poItemPrices[$pid] ?? 0), null, null, null);
                        }
                    }
                    if (is_array($exceptions) && !empty($exceptions)) {
                        $grouped = [];
                        foreach ($exceptions as $ex) {
                            $key = (string) ($ex['reason'] ?? '') . '|' . (string) ($ex['resolution'] ?? '');
                            if (!isset($grouped[$key])) {
                                $grouped[$key] = ['reason' => (string) $ex['reason'], 'resolution' => (string) $ex['resolution'], 'items' => []];
                            }
                            $pid = (string) $ex['product_id'];
                            $qty = (int) $ex['quantity'];
                            if ($qty <= 0) {
                                continue;
                            }
                            $price = (int) ($poItemPrices[$pid] ?? 0);
                            $grouped[$key]['items'][] = new PurchaseReturnItemData($pid, $qty, $price, null, null, $ex['notes'] ?? null);
                        }
                        $exceptionProductIds = [];
                        foreach ($grouped as $g) {
                            foreach ($g['items'] as $it) {
                                $exceptionProductIds[(string) $it->productId] = true;
                            }
                        }
                        if (!empty($exceptionProductIds)) {
                            $sdoIdsForPo = SupplierDeliveryOrder::query()
                                ->where('sourceable_type', PurchaseOrder::class)
                                ->where('sourceable_id', $po->getKey())
                                ->pluck('id')
                                ->map(fn($id) => (string) $id)
                                ->all();
                            $existingGcProducts = !empty($sdoIdsForPo)
                                ? GoodsCome::query()
                                ->where('referencable_type', SupplierDeliveryOrder::class)
                                ->whereIn('referencable_id', $sdoIdsForPo)
                                ->pluck('product_id')
                                ->map(fn($pid) => (string) $pid)
                                ->all()
                                : [];
                            foreach (array_keys($exceptionProductIds) as $pid) {
                                if (!in_array($pid, $existingGcProducts, true)) {
                                    $gcPlaceholder = new GoodsCome();
                                    $gcPlaceholder->referencable_id = (string) $sdo->id;
                                    $gcPlaceholder->referencable_type = SupplierDeliveryOrder::class;
                                    $gcPlaceholder->source_type = GoodsComeSourceType::PurchaseOrder->value;
                                    $gcPlaceholder->warehouse_id = (string) $po->warehouse_id;
                                    $gcPlaceholder->product_id = (string) $pid;
                                    $gcPlaceholder->quantity = 0;
                                    $gcPlaceholder->quantity_return = 0;
                                    $gcPlaceholder->unit_cost = 0;
                                    $gcPlaceholder->notes = 'Placeholder for purchase return due to receiving exceptions';
                                    $gcPlaceholder->expired_date = null;
                                    $gcPlaceholder->previous_stock = 0;
                                    $gcPlaceholder->stock_after = 0;
                                    $gcPlaceholder->batch_numbers = null;
                                    $gcPlaceholder->barcode = null;
                                    $gcPlaceholder->supplier_name = $supplierName;
                                    $gcPlaceholder->sender_name = $senderName;
                                    $gcPlaceholder->vehicle_plate_number = $vehiclePlateNumber;
                                    $gcPlaceholder->invoice_number = $po->supplier_invoice_number ?? null;
                                    $gcPlaceholder->purchase_date = $orderDate;
                                    $gcPlaceholder->created_by_id = $receivedById;
                                    $gcPlaceholder->save();
                                }
                            }
                        }
                        foreach ($grouped as $g) {
                            if (empty($g['items'])) {
                                continue;
                            }
                            $statusForReturn = in_array(
                                (string) $g['resolution'],
                                [PurchaseReturnResolution::Discount->value, PurchaseReturnResolution::CreditNote->value],
                                true
                            )
                                ? PurchaseReturnStatus::Completed->value
                                : PurchaseReturnStatus::Draft->value;
                            $data = new PurchaseReturnData(
                                (string) $po->getKey(),
                                (string) $po->supplier_id,
                                (string) $po->warehouse_id,
                                null,
                                now()->toDateString(),
                                (string) $g['reason'],
                                (string) $g['resolution'],
                                $statusForReturn,
                                'Auto generated from receiving exceptions for SDO ' . (string) ($sdo->number ?? ''),
                                (string) ($receivedById ?? ''),
                                $g['items']
                            );
                            app(PurchaseReturnService::class)->create($data);
                        }
                    } else {
                        if (!empty($shortageItems)) {
                            $shortageProductIds = [];
                            foreach ($shortageItems as $it) {
                                $shortageProductIds[(string) $it->productId] = true;
                            }
                            if (!empty($shortageProductIds)) {
                                $sdoIdsForPo = SupplierDeliveryOrder::query()
                                    ->where('sourceable_type', PurchaseOrder::class)
                                    ->where('sourceable_id', $po->getKey())
                                    ->pluck('id')
                                    ->map(fn($id) => (string) $id)
                                    ->all();
                                $existingGcProducts = !empty($sdoIdsForPo)
                                    ? GoodsCome::query()
                                    ->where('referencable_type', SupplierDeliveryOrder::class)
                                    ->whereIn('referencable_id', $sdoIdsForPo)
                                    ->pluck('product_id')
                                    ->map(fn($pid) => (string) $pid)
                                    ->all()
                                    : [];
                                foreach (array_keys($shortageProductIds) as $pid) {
                                    if (!in_array($pid, $existingGcProducts, true)) {
                                        $gcPlaceholder = new GoodsCome();
                                        $gcPlaceholder->referencable_id = (string) $sdo->id;
                                        $gcPlaceholder->referencable_type = SupplierDeliveryOrder::class;
                                        $gcPlaceholder->source_type = GoodsComeSourceType::PurchaseOrder->value;
                                        $gcPlaceholder->warehouse_id = (string) $po->warehouse_id;
                                        $gcPlaceholder->product_id = (string) $pid;
                                        $gcPlaceholder->quantity = 0;
                                        $gcPlaceholder->quantity_return = 0;
                                        $gcPlaceholder->unit_cost = 0;
                                        $gcPlaceholder->notes = 'Placeholder for purchase return due to receiving shortage';
                                        $gcPlaceholder->expired_date = null;
                                        $gcPlaceholder->previous_stock = 0;
                                        $gcPlaceholder->stock_after = 0;
                                        $gcPlaceholder->batch_numbers = null;
                                        $gcPlaceholder->barcode = null;
                                        $gcPlaceholder->supplier_name = $supplierName;
                                        $gcPlaceholder->sender_name = $senderName;
                                        $gcPlaceholder->vehicle_plate_number = $vehiclePlateNumber;
                                        $gcPlaceholder->invoice_number = $po->supplier_invoice_number ?? null;
                                        $gcPlaceholder->purchase_date = $orderDate;
                                        $gcPlaceholder->created_by_id = $receivedById;
                                        $gcPlaceholder->save();
                                    }
                                }
                            }
                            $data = new PurchaseReturnData(
                                (string) $po->getKey(),
                                (string) $po->supplier_id,
                                (string) $po->warehouse_id,
                                null,
                                now()->toDateString(),
                                PurchaseReturnReason::Others->value,
                                PurchaseReturnResolution::CreditNote->value,
                                PurchaseReturnStatus::Completed->value,
                                'Auto generated: Kekurangan penerimaan terhadap SDO ' . (string) ($sdo->number ?? ''),
                                (string) ($receivedById ?? ''),
                                $shortageItems
                            );
                            app(PurchaseReturnService::class)->create($data);
                        }
                    }

                    $poItems = \App\Models\PurchaseOrderItem::query()
                        ->where('purchase_order_id', $po->getKey())
                        ->get(['product_id', 'quantity']);
                    $poQtyByProduct = [];
                    foreach ($poItems as $row) {
                        $pid = (string) $row->product_id;
                        $poQtyByProduct[$pid] = ($poQtyByProduct[$pid] ?? 0) + (int) $row->quantity;
                    }

                    $sdoIds = SupplierDeliveryOrder::query()
                        ->where('sourceable_type', PurchaseOrder::class)
                        ->where('sourceable_id', $po->getKey())
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                        ->all();
                    $receivedRows2 = !empty($sdoIds)
                        ? GoodsCome::query()
                        ->where('referencable_type', SupplierDeliveryOrder::class)
                        ->whereIn('referencable_id', $sdoIds)
                        ->get(['product_id', 'quantity'])
                        : collect();
                    $receivedQtyByProduct = [];
                    foreach ($receivedRows2 as $row) {
                        $pid = (string) $row->product_id;
                        $receivedQtyByProduct[$pid] = ($receivedQtyByProduct[$pid] ?? 0) + (int) $row->quantity;
                    }

                    $allReceived = !empty($poQtyByProduct);
                    $anyReceived = false;
                    foreach ($poQtyByProduct as $pid => $ordered) {
                        $received = (int) ($receivedQtyByProduct[$pid] ?? 0);
                        if ($received < (int) $ordered) {
                            $allReceived = false;
                        }
                        if ($received > 0) {
                            $anyReceived = true;
                        }
                    }

                    $newStatus = $po->status;
                    if ($allReceived) {
                        $newStatus = PurchaseOrderStatus::Completed->value;
                    } elseif ($anyReceived) {
                        $newStatus = PurchaseOrderStatus::PartiallyDelivered->value;
                    } else {
                        $newStatus = PurchaseOrderStatus::InDelivery->value;
                    }
                    if ($po->status !== $newStatus) {
                        $po->status = $newStatus;
                        $po->save();
                    }

                    return $sdo;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('sdo_receive_po_error', [
                    'sdo_id' => (string) $sdo->id,
                    'received_by_id' => (string) ($receivedById ?? ''),
                    'sender_name' => (string) ($senderName ?? ''),
                    'vehicle_plate_number' => (string) ($vehiclePlateNumber ?? ''),
                    'items' => $items,
                    'exceptions' => $exceptions,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    private function generateNumber(): string
    {
        $prefix = 'SJ/' . now()->format('Ym') . '/';
        $last = SupplierDeliveryOrder::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
