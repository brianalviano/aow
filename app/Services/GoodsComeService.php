<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoodsCome;
use App\Enums\GoodsComeSourceType;
use Illuminate\Support\Facades\{DB, Log};
use App\Traits\RetryableTransactionsTrait;
use App\Services\StockService;

class GoodsComeService
{
    use RetryableTransactionsTrait;
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->stock = $stock;
    }

    public function receiveGoods(array $data): GoodsCome
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $warehouseId = (string) $data['warehouse_id'];
                    $productId = (string) $data['product_id'];
                    $divisionId = isset($data['product_division_id']) ? (string) $data['product_division_id'] : null;
                    $rackId = isset($data['product_rack_id']) ? (string) $data['product_rack_id'] : null;
                    $qty = (int) $data['quantity'];
                    $unitCost = (int) $data['unit_cost'];
                    $createdById = isset($data['created_by_id']) ? (string) $data['created_by_id'] : null;
                    $bucket = $this->stock->resolveBucketFromData($data);
                    $ownerUserId = $this->stock->resolveOwnerUserIdFromData($data);

                    $stockRow = $this->stock->getOrCreateStockByBucket($warehouseId, $productId, $divisionId, $rackId, $bucket, $ownerUserId);
                    $previousQty = (int) $stockRow->quantity;
                    $newQty = $previousQty + $qty;

                    $gc = new GoodsCome();
                    $gc->referencable_id = isset($data['referencable_id']) ? (string) $data['referencable_id'] : null;
                    $gc->referencable_type = isset($data['referencable_type']) ? (string) $data['referencable_type'] : null;
                    $gc->source_type = isset($data['source_type']) ? (string) $data['source_type'] : GoodsComeSourceType::Manual->value;
                    $gc->warehouse_id = $warehouseId;
                    $gc->product_division_id = $divisionId;
                    $gc->product_rack_id = $rackId;
                    $gc->product_id = $productId;
                    $gc->quantity = $qty;
                    $gc->quantity_return = isset($data['quantity_return']) ? (int) $data['quantity_return'] : 0;
                    $gc->unit_cost = $unitCost;
                    $gc->notes = isset($data['notes']) ? (string) $data['notes'] : null;
                    $gc->expired_date = isset($data['expired_date']) ? $data['expired_date'] : null;
                    $gc->previous_stock = $previousQty;
                    $gc->stock_after = $newQty;
                    $gc->batch_numbers = isset($data['batch_numbers']) ? (string) $data['batch_numbers'] : null;
                    $gc->barcode = isset($data['barcode']) ? (string) $data['barcode'] : null;
                    $gc->supplier_name = isset($data['supplier_name']) ? (string) $data['supplier_name'] : null;
                    $gc->sender_name = isset($data['sender_name']) ? (string) $data['sender_name'] : null;
                    $gc->vehicle_plate_number = isset($data['vehicle_plate_number']) ? (string) $data['vehicle_plate_number'] : null;
                    $gc->invoice_number = isset($data['invoice_number']) ? (string) $data['invoice_number'] : null;
                    $gc->purchase_date = isset($data['purchase_date']) ? $data['purchase_date'] : null;
                    $gc->created_by_id = $createdById;
                    $gc->save();

                    $this->stock->updateAverageCostInbound($warehouseId, $productId, $qty, $unitCost, $bucket);

                    $stockRow->quantity = $newQty;
                    $stockRow->save();

                    $this->stock->createStockCard(
                        $stockRow,
                        'IN',
                        $qty,
                        $newQty,
                        (string) $gc->id,
                        GoodsCome::class,
                        isset($data['notes']) ? (string) $data['notes'] : null,
                        $createdById,
                        $unitCost
                    );

                    return $gc;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('receive_goods_error', [
                    'user_id' => isset($data['created_by_id']) ? (string) $data['created_by_id'] : null,
                    'warehouse_id' => (string) $data['warehouse_id'],
                    'product_id' => (string) $data['product_id'],
                    'quantity' => (int) $data['quantity'],
                    'unit_cost' => (int) $data['unit_cost'],
                    'payload' => $data,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }
}
