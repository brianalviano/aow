<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PurchaseReturn\{PurchaseReturnData, PurchaseReturnItemData};
use App\Enums\{PurchaseReturnStatus, PurchaseReturnResolution, StockBucket, GoodsComeSourceType, SupplierDeliveryOrderStatus};
use App\Models\{PurchaseReturn, PurchaseReturnItem, AverageCost, SupplierDeliveryOrder, PurchaseOrder, GoodsCome};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\RetryableTransactionsTrait;
use App\Services\{StockService, GoodsComeService};

class PurchaseReturnService
{
    use RetryableTransactionsTrait;

    public function create(PurchaseReturnData $data): PurchaseReturn
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $return = new PurchaseReturn();
                    $return->purchase_order_id = $data->purchaseOrderId;
                    $return->supplier_id = $data->supplierId;
                    $return->warehouse_id = $data->warehouseId;
                    $return->number = $data->number ?: $this->generateNumber();
                    $return->return_date = $data->returnDate;
                    $return->reason = $data->reason;
                    $return->resolution = $data->resolution;
                    $return->status = $data->status ?: PurchaseReturnStatus::Draft->value;
                    $return->notes = $data->notes;
                    $return->created_by_id = $data->createdById;
                    $return->credit_amount = 0;
                    $return->refund_amount = 0;
                    $return->save();

                    $goodsComeIdByProduct = [];
                    $poPriceByProduct = [];
                    if (!empty($data->purchaseOrderId)) {
                        $sdoIds = SupplierDeliveryOrder::query()
                            ->where('sourceable_type', \App\Models\PurchaseOrder::class)
                            ->where('sourceable_id', $data->purchaseOrderId)
                            ->pluck('id')
                            ->map(fn($id) => (string) $id)
                            ->all();
                        if (!empty($sdoIds)) {
                            $goodsComeRows = \App\Models\GoodsCome::query()
                                ->where('referencable_type', SupplierDeliveryOrder::class)
                                ->whereIn('referencable_id', $sdoIds)
                                ->orderByDesc('created_at')
                                ->get(['id', 'product_id']);
                            foreach ($goodsComeRows as $r) {
                                $pid = (string) $r->product_id;
                                if (!isset($goodsComeIdByProduct[$pid])) {
                                    $goodsComeIdByProduct[$pid] = (string) $r->id;
                                }
                            }
                        }
                        $priceRows = \App\Models\PurchaseOrderItem::query()
                            ->where('purchase_order_id', $data->purchaseOrderId)
                            ->orderByDesc('created_at')
                            ->get(['product_id', 'price']);
                        foreach ($priceRows as $pr) {
                            $pid = (string) $pr->product_id;
                            if (!isset($poPriceByProduct[$pid])) {
                                $poPriceByProduct[$pid] = (int) $pr->price;
                            }
                        }
                    }

                    $total = 0;
                    foreach ($data->items as $it) {
                        $poPrice = $poPriceByProduct[$it->productId] ?? 0;
                        $price = $poPrice > 0 ? $poPrice : $this->resolvePrice($data->warehouseId, $it);
                        $subtotal = $price * $it->quantity;
                        $payload = [
                            'purchase_return_id' => $return->getKey(),
                            'product_id' => $it->productId,
                            'product_division_id' => $it->productDivisionId,
                            'product_rack_id' => $it->productRackId,
                            'quantity' => $it->quantity,
                            'price' => $price,
                            'subtotal' => $subtotal,
                            'expired_date' => null,
                            'batch_numbers' => null,
                            'notes' => $it->notes,
                        ];
                        if (!empty($data->purchaseOrderId)) {
                            $gcId = $goodsComeIdByProduct[$it->productId] ?? null;
                            if ($gcId === null) {
                                throw new \InvalidArgumentException('GoodsCome not found for product on selected PO: ' . $it->productId);
                            }
                            $payload['goods_come_id'] = $gcId;
                        } else {
                            throw new \InvalidArgumentException('Purchase Order is required to create Purchase Return items.');
                        }
                        $return->items()->create($payload);
                        $total += $subtotal;
                    }

                    if ($data->resolution === PurchaseReturnResolution::CreditNote->value) {
                        $return->credit_amount = $total;
                        $return->refund_amount = 0;
                    } elseif ($data->resolution === PurchaseReturnResolution::Discount->value) {
                        $return->refund_amount = $total;
                        $return->credit_amount = 0;
                    } else {
                        $return->credit_amount = 0;
                        $return->refund_amount = 0;
                    }
                    $return->save();

                    // SDO tidak dibuat otomatis saat create; dibuat saat transisi ke InDelivery melalui modal.

                    if ($data->status === PurchaseReturnStatus::Completed->value) {
                        $this->ensureStockAvailability($data->warehouseId, $data->items);
                        $this->issueStockForPurchaseReturn($return, array_map(function (PurchaseReturnItemData $it) {
                            return [
                                'product_id' => $it->productId,
                                'quantity' => $it->quantity,
                                'notes' => $it->notes,
                            ];
                        }, $data->items));
                    }

                    return $return;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('purchase_return_create_error', [
                    'purchase_order_id' => (string) ($data->purchaseOrderId ?? ''),
                    'supplier_id' => (string) ($data->supplierId ?? ''),
                    'warehouse_id' => (string) ($data->warehouseId ?? ''),
                    'created_by_id' => (string) ($data->createdById ?? ''),
                    'resolution' => (string) ($data->resolution ?? ''),
                    'reason' => (string) ($data->reason ?? ''),
                    'status' => (string) ($data->status ?? ''),
                    'items' => array_map(function (PurchaseReturnItemData $it) {
                        return [
                            'product_id' => (string) $it->productId,
                            'quantity' => (int) $it->quantity,
                            'notes' => $it->notes,
                        ];
                    }, $data->items),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function advanceStatus(
        PurchaseReturn $purchaseReturn,
        ?string $deliveryDate = null,
        ?string $number = null,
        ?string $notes = null
    ): PurchaseReturn {
        return $this->runWithRetry(function () use ($purchaseReturn, $deliveryDate, $number, $notes) {
            try {
                return DB::transaction(function () use ($purchaseReturn, $deliveryDate, $number, $notes) {
                    $current =
                        $purchaseReturn->status instanceof PurchaseReturnStatus
                        ? $purchaseReturn->status
                        : PurchaseReturnStatus::from((string) $purchaseReturn->status);
                    if ($current !== PurchaseReturnStatus::Draft) {
                        throw new \InvalidArgumentException('Status saat ini tidak dapat dilanjutkan');
                    }
                    $resolution =
                        $purchaseReturn->resolution instanceof PurchaseReturnResolution
                        ? $purchaseReturn->resolution
                        : ($purchaseReturn->resolution
                            ? PurchaseReturnResolution::from((string) $purchaseReturn->resolution)
                            : null);
                    if ($resolution !== PurchaseReturnResolution::Replace) {
                        throw new \InvalidArgumentException('Transisi ke InDelivery hanya untuk resolusi penggantian');
                    }
                    app(\App\Services\SupplierDeliveryOrderService::class)->createFromPurchaseReturn(
                        $purchaseReturn,
                        [],
                        $deliveryDate ?: now()->toDateString(),
                        $number,
                        $notes,
                    );
                    $purchaseReturn->refresh();
                    return $purchaseReturn;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('purchase_return_advance_error', [
                    'purchase_return_id' => (string) $purchaseReturn->getKey(),
                    'delivery_date' => (string) ($deliveryDate ?? ''),
                    'number' => (string) ($number ?? ''),
                    'notes' => (string) ($notes ?? ''),
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function issueStockForPurchaseReturn(PurchaseReturn $return, array $items): PurchaseReturn
    {
        return $this->runWithRetry(function () use ($return, $items) {
            try {
                return DB::transaction(function () use ($return, $items) {
                    $warehouseId = (string) $return->warehouse_id;
                    foreach ($items as $item) {
                        $productId = (string) $item['product_id'];
                        $qty = (int) $item['quantity'];
                        $notes = isset($item['notes']) ? (string) $item['notes'] : null;
                        app(StockService::class)->deductStockWithPriority(
                            $warehouseId,
                            $productId,
                            $qty,
                            (string) $return->id,
                            PurchaseReturn::class,
                            $notes,
                            $return->created_by_id,
                            'PRC_OUT'
                        );
                    }
                    $return->status = $return->status ?: PurchaseReturnStatus::Completed->value;
                    $return->save();
                    return $return;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('issue_stock_for_purchase_return_error', [
                    'purchase_return_id' => (string) $return->id,
                    'items' => $items,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function receiveReplacementForPurchaseReturn(
        PurchaseReturn $return,
        array $items,
        ?string $receivedById = null,
        ?string $senderName = null,
        ?string $vehiclePlateNumber = null,
        ?SupplierDeliveryOrder $sdo = null
    ): PurchaseReturn {
        return $this->runWithRetry(function () use ($return, $items, $receivedById, $senderName, $vehiclePlateNumber, $sdo) {
            try {
                return DB::transaction(function () use ($return, $items, $receivedById, $senderName, $vehiclePlateNumber, $sdo) {
                    $warehouseId = (string) $return->warehouse_id;
                    $po = $return->purchase_order_id
                        ? PurchaseOrder::query()->where('id', (string) $return->purchase_order_id)->first()
                        : null;
                    $isVat = $po ? (bool) $po->is_value_added_tax_enabled : false;
                    $supplierName = $po && $po->supplier ? (string) ($po->supplier->name ?? '') : null;
                    $orderDate = $po && $po->order_date ? (string) $po->order_date->format('Y-m-d') : null;

                    $priceMap = PurchaseReturnItem::query()
                        ->where('purchase_return_id', (string) $return->id)
                        ->get(['product_id', 'price'])
                        ->keyBy('product_id')
                        ->map(fn($row) => (int) ($row->price ?? 0))
                        ->toArray();

                    foreach ($items as $it) {
                        $productId = (string) $it['product_id'];
                        $qty = (int) $it['quantity'];
                        if ($qty <= 0) {
                            throw new \InvalidArgumentException('Quantity must be positive');
                        }
                        $unitCost = isset($it['unit_cost']) ? (int) $it['unit_cost'] : (int) ($priceMap[$productId] ?? 0);

                        app(GoodsComeService::class)->receiveGoods([
                            'referencable_id' => (string) $return->id,
                            'referencable_type' => PurchaseReturn::class,
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
                            'invoice_number' => $po ? ($po->supplier_invoice_number ?? null) : null,
                            'purchase_date' => $orderDate,
                            'created_by_id' => $receivedById,
                            'is_value_added_tax_enabled' => $isVat,
                        ]);
                    }

                    if ($sdo) {
                        if (
                            (string) $sdo->sourceable_type === PurchaseReturn::class
                            && (string) $sdo->sourceable_id === (string) $return->id
                        ) {
                            $sdo->status = SupplierDeliveryOrderStatus::Completed->value;
                            $sdo->save();
                        }
                    } else {
                        SupplierDeliveryOrder::query()
                            ->where('sourceable_type', PurchaseReturn::class)
                            ->where('sourceable_id', (string) $return->id)
                            ->where('status', SupplierDeliveryOrderStatus::InDelivery->value)
                            ->update(['status' => SupplierDeliveryOrderStatus::Completed->value]);
                    }

                    $returnItems = PurchaseReturnItem::query()
                        ->where('purchase_return_id', (string) $return->id)
                        ->get(['product_id', 'quantity']);
                    $returnQtyByProduct = [];
                    foreach ($returnItems as $row) {
                        $pid = (string) $row->product_id;
                        $returnQtyByProduct[$pid] = ($returnQtyByProduct[$pid] ?? 0) + (int) $row->quantity;
                    }
                    $receivedRows = GoodsCome::query()
                        ->where('referencable_type', PurchaseReturn::class)
                        ->where('referencable_id', (string) $return->id)
                        ->get(['product_id', 'quantity']);
                    $receivedQtyByProduct = [];
                    foreach ($receivedRows as $row) {
                        $pid = (string) $row->product_id;
                        $receivedQtyByProduct[$pid] = ($receivedQtyByProduct[$pid] ?? 0) + (int) $row->quantity;
                    }

                    $allReceived = !empty($returnQtyByProduct);
                    $anyReceived = false;
                    foreach ($returnQtyByProduct as $pid => $retQty) {
                        $received = (int) ($receivedQtyByProduct[$pid] ?? 0);
                        if ($received < (int) $retQty) {
                            $allReceived = false;
                        }
                        if ($received > 0) {
                            $anyReceived = true;
                        }
                    }
                    $newStatus = $return->status;
                    if ($allReceived) {
                        $newStatus = PurchaseReturnStatus::Completed->value;
                    } elseif ($anyReceived) {
                        $newStatus = PurchaseReturnStatus::PartiallyDelivered->value;
                    } else {
                        $newStatus = PurchaseReturnStatus::InDelivery->value;
                    }
                    if ($return->status !== $newStatus) {
                        $return->status = $newStatus;
                        $return->save();
                    }

                    return $return;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('receiveReplacementForPurchaseReturn failed', [
                    'purchase_return_id' => (string) $return->id,
                    'received_by_id' => (string) ($receivedById ?? ''),
                    'sender_name' => (string) ($senderName ?? ''),
                    'vehicle_plate_number' => (string) ($vehiclePlateNumber ?? ''),
                    'items' => $items,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    private function generateNumber(): string
    {
        $prefix = 'RT/' . now()->format('Ym') . '/';
        $last = PurchaseReturn::query()
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

    /**
     * @param array<int, PurchaseReturnItemData> $items
     */
    private function ensureStockAvailability(string $warehouseId, array $items): void
    {
        $required = [];
        foreach ($items as $it) {
            $pid = $it->productId;
            $required[$pid] = ($required[$pid] ?? 0) + $it->quantity;
        }
        $productIds = array_keys($required);
        if (empty($productIds)) {
            return;
        }
        $rows = \App\Models\Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'quantity']);
        $available = [];
        foreach ($rows as $row) {
            $pid = (string) $row->product_id;
            $available[$pid] = ($available[$pid] ?? 0) + (int) $row->quantity;
        }
        foreach ($required as $pid => $qty) {
            $av = $available[$pid] ?? 0;
            if ($qty > $av) {
                throw new \InvalidArgumentException('Insufficient stock for product: ' . $pid);
            }
        }
    }

    private function resolvePrice(string $warehouseId, PurchaseReturnItemData $it): int
    {
        if ($it->price > 0) {
            return $it->price;
        }
        $avg = AverageCost::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $it->productId)
            ->where('bucket', StockBucket::NonVat->value)
            ->first();
        return $avg ? (int) $avg->cost : 0;
    }
}
