<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{
    Stock,
    StockCard,
    AverageCost,
    AverageCostHistory,
    StockAdjustment,
    StockAdjustmentItem,
    StockTransfer,
    StockTransferItem,
    User
};
use Illuminate\Support\Facades\DB;
use App\Traits\RetryableTransactionsTrait;
use App\Enums\{
    StockCardType,
    StockAdjustmentType,
    StockAdjustmentStatus,
    StockTransferStatus,
    AverageCostTransactionType,
    RoleName,
    StockBucket
};
use Illuminate\Support\Facades\Log;

class StockService
{
    use RetryableTransactionsTrait;

    public function applyStockAdjustment(StockAdjustment $adjustment, array $items, ?User $approvedBy = null, ?User $supervisor = null, ?bool $viaPin = null): StockAdjustment
    {
        return $this->runWithRetry(function () use ($adjustment, $items, $approvedBy, $supervisor, $viaPin) {
            return DB::transaction(function () use ($adjustment, $items, $approvedBy, $supervisor, $viaPin) {
                $warehouseId = (string) $adjustment->warehouse_id;
                foreach ($items as $item) {
                    $si = $item instanceof StockAdjustmentItem ? $item : $this->hydrateStockAdjustmentItem($adjustment, $item);
                    $productId = (string) $si->product_id;
                    $qty = (int) $si->quantity;
                    $unitCost = (int) $si->unit_cost;
                    $type = (string) $si->adjustment_type;

                    $stockRow = $this->getOrCreateStock($warehouseId, $productId, null, null);
                    $prevQty = (int) $stockRow->quantity;
                    if ($type === StockAdjustmentType::Increase->value) {
                        $newQty = $prevQty + $qty;
                        $this->updateAverageCostInbound($warehouseId, $productId, $qty, $unitCost, null, AverageCostTransactionType::Adjustment->value);
                        $stockRow->quantity = $newQty;
                        $stockRow->save();
                        $this->createStockCard($stockRow, 'ADJ_IN', $qty, $newQty, (string) $adjustment->id, StockAdjustment::class, $adjustment->notes, $adjustment->approved_by_id);
                    } else {
                        $newQty = $prevQty - $qty;
                        $stockRow->quantity = $newQty;
                        $stockRow->save();
                        $this->createStockCard($stockRow, 'ADJ_OUT', $qty, $newQty, (string) $adjustment->id, StockAdjustment::class, $adjustment->notes, $adjustment->approved_by_id);
                    }
                }

                if ($approvedBy) {
                    $adjustment->approved_by_id = (string) $approvedBy->id;
                }
                if ($supervisor) {
                    $adjustment->supervisor_approval_id = (string) $supervisor->id;
                }
                if ($viaPin !== null) {
                    $adjustment->approved_via_pin = (bool) $viaPin;
                }
                $adjustment->status = $adjustment->status ?: StockAdjustmentStatus::Approved->value;
                $adjustment->save();

                return $adjustment;
            }, 5);
        }, 3);
    }

    public function transfer(StockTransfer $transfer, array $items, ?string $performedById = null): StockTransfer
    {
        return $this->runWithRetry(function () use ($transfer, $items, $performedById) {
            return DB::transaction(function () use ($transfer, $items, $performedById) {
                $fromId = (string) $transfer->from_warehouse_id;
                $toId = (string) $transfer->to_warehouse_id;
                $toOwnerHeader = $transfer->to_owner_user_id ? (string) $transfer->to_owner_user_id : null;

                foreach ($items as $item) {
                    $ti = $item instanceof StockTransferItem ? $item : $this->hydrateTransferItem($transfer, $item);
                    $productId = (string) $ti->product_id;
                    $qty = (int) $ti->quantity;
                    $fromOwnerId = is_array($item) && isset($item['from_owner_user_id']) ? ((string) $item['from_owner_user_id'] ?: null) : null;
                    $toOwnerId = is_array($item) && isset($item['to_owner_user_id']) ? ((string) $item['to_owner_user_id'] ?: null) : null;
                    if ($toOwnerId === null) {
                        $toOwnerId = $toOwnerHeader;
                    }

                    $remaining = $qty;

                    $fromNonVat = $this->getStockRowByBucket($fromId, $productId, null, null, StockBucket::NonVat, $fromOwnerId) ?? $this->getStockRowByNullBucket($fromId, $productId, null, null, $fromOwnerId);
                    if ($fromNonVat) {
                        $move = min((int) $fromNonVat->quantity, $remaining);
                        if ($move > 0) {
                            $fromNonVat->quantity = (int) $fromNonVat->quantity - $move;
                            $fromNonVat->save();
                            $this->createStockCard($fromNonVat, 'TRF_OUT', $move, (int) $fromNonVat->quantity, (string) $transfer->id, StockTransfer::class, $transfer->notes, $performedById);

                            $toNonVat = $this->getOrCreateStockByBucket($toId, $productId, null, null, StockBucket::NonVat, $toOwnerId);
                            $toPrev = (int) $toNonVat->quantity;
                            $toNonVat->quantity = $toPrev + $move;
                            $toNonVat->save();
                            $this->createStockCard($toNonVat, 'TRF_IN', $move, (int) $toNonVat->quantity, (string) $transfer->id, StockTransfer::class, $transfer->notes, $performedById);

                            $sourceAvgNonVat = AverageCost::query()
                                ->where('warehouse_id', $fromId)
                                ->where('product_id', $productId)
                                ->where('bucket', StockBucket::NonVat->value)
                                ->lockForUpdate()
                                ->first();
                            if ($sourceAvgNonVat) {
                                $this->updateAverageCostInbound($toId, $productId, $move, (int) $sourceAvgNonVat->cost, StockBucket::NonVat, AverageCostTransactionType::Adjustment->value);
                            }
                            $remaining -= $move;
                        }
                    }

                    if ($remaining > 0) {
                        $fromVat = $this->getStockRowByBucket($fromId, $productId, null, null, StockBucket::Vat, $fromOwnerId);
                        if ($fromVat) {
                            $move = min((int) $fromVat->quantity, $remaining);
                            if ($move > 0) {
                                $fromVat->quantity = (int) $fromVat->quantity - $move;
                                $fromVat->save();
                                $this->createStockCard($fromVat, 'TRF_OUT', $move, (int) $fromVat->quantity, (string) $transfer->id, StockTransfer::class, $transfer->notes, $performedById);

                                $toVat = $this->getOrCreateStockByBucket($toId, $productId, null, null, StockBucket::Vat, $toOwnerId);
                                $toPrevV = (int) $toVat->quantity;
                                $toVat->quantity = $toPrevV + $move;
                                $toVat->save();
                                $this->createStockCard($toVat, 'TRF_IN', $move, (int) $toVat->quantity, (string) $transfer->id, StockTransfer::class, $transfer->notes, $performedById);

                                $sourceAvgVat = AverageCost::query()
                                    ->where('warehouse_id', $fromId)
                                    ->where('product_id', $productId)
                                    ->where('bucket', StockBucket::Vat->value)
                                    ->lockForUpdate()
                                    ->first();
                                if ($sourceAvgVat) {
                                    $this->updateAverageCostInbound($toId, $productId, $move, (int) $sourceAvgVat->cost, StockBucket::Vat, AverageCostTransactionType::Adjustment->value);
                                }
                                $remaining -= $move;
                            }
                        }
                    }
                }

                $transfer->status = $transfer->status ?: StockTransferStatus::Received->value;
                $transfer->save();

                return $transfer;
            }, 5);
        }, 3);
    }

    public function getOrCreateStock(string $warehouseId, string $productId, ?string $divisionId, ?string $rackId): Stock
    {
        $row = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($divisionId) {
                if ($divisionId === null) {
                    $q->whereNull('product_division_id');
                } else {
                    $q->where('product_division_id', $divisionId);
                }
            })
            ->where(function ($q) use ($rackId) {
                if ($rackId === null) {
                    $q->whereNull('product_rack_id');
                } else {
                    $q->where('product_rack_id', $rackId);
                }
            })
            ->lockForUpdate()
            ->first();

        if ($row) {
            return $row;
        }

        $s = new Stock();
        $s->warehouse_id = $warehouseId;
        $s->product_id = $productId;
        $s->product_division_id = $divisionId;
        $s->product_rack_id = $rackId;
        $s->quantity = 0;
        $s->save();
        return $s;
    }

    private function getAverageCost(string $warehouseId, string $productId): ?AverageCost
    {
        return AverageCost::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
    }

    public function getStockRowByBucket(string $warehouseId, string $productId, ?string $divisionId, ?string $rackId, StockBucket|string $bucket, ?string $ownerUserId = null): ?Stock
    {
        $bucketVal = $bucket instanceof StockBucket ? $bucket->value : (string) $bucket;
        return Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('bucket', $bucketVal)
            ->where(function ($q) use ($ownerUserId) {
                if ($ownerUserId === null) {
                    $q->whereNull('owner_user_id');
                } else {
                    $q->where('owner_user_id', $ownerUserId);
                }
            })
            ->where(function ($q) use ($divisionId) {
                if ($divisionId === null) {
                    $q->whereNull('product_division_id');
                } else {
                    $q->where('product_division_id', $divisionId);
                }
            })
            ->where(function ($q) use ($rackId) {
                if ($rackId === null) {
                    $q->whereNull('product_rack_id');
                } else {
                    $q->where('product_rack_id', $rackId);
                }
            })
            ->lockForUpdate()
            ->first();
    }

    public function getStockRowByNullBucket(string $warehouseId, string $productId, ?string $divisionId, ?string $rackId, ?string $ownerUserId = null): ?Stock
    {
        return Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNull('bucket')
            ->where(function ($q) use ($ownerUserId) {
                if ($ownerUserId === null) {
                    $q->whereNull('owner_user_id');
                } else {
                    $q->where('owner_user_id', $ownerUserId);
                }
            })
            ->where(function ($q) use ($divisionId) {
                if ($divisionId === null) {
                    $q->whereNull('product_division_id');
                } else {
                    $q->where('product_division_id', $divisionId);
                }
            })
            ->where(function ($q) use ($rackId) {
                if ($rackId === null) {
                    $q->whereNull('product_rack_id');
                } else {
                    $q->where('product_rack_id', $rackId);
                }
            })
            ->lockForUpdate()
            ->first();
    }

    public function updateAverageCostInbound(string $warehouseId, string $productId, int $qtyIn, int $unitCost, StockBucket|string|null $bucket = null, ?string $historyType = null): AverageCost
    {
        $useBucketEnum = $bucket instanceof StockBucket ? $bucket : StockBucket::from(($bucket ?? StockBucket::NonVat->value));
        $useBucket = $useBucketEnum->value;
        $avg = AverageCost::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('bucket', $useBucket)
            ->lockForUpdate()
            ->first();
        $oldCost = $avg ? (int) $avg->cost : 0;
        $oldQty = (int) Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('bucket', $useBucket)
            ->sum('quantity');

        $den = $oldQty + $qtyIn;
        $num = ($oldCost * $oldQty) + ($unitCost * $qtyIn);
        $newCost = $den > 0 ? intdiv($num, $den) : $unitCost;

        if (!$avg) {
            $avg = new AverageCost();
            $avg->warehouse_id = $warehouseId;
            $avg->product_id = $productId;
            $avg->bucket = $useBucketEnum;
        }
        $avg->cost = $newCost;
        $avg->bucket = $useBucketEnum;
        $avg->last_updated_at = now();
        $avg->save();

        $h = new AverageCostHistory();
        $h->average_cost_id = (string) $avg->id;
        $h->cost = $newCost;
        $h->quantity_affected = $qtyIn;
        $h->transaction_type = $historyType ?? AverageCostTransactionType::Purchase->value;
        $h->bucket = $useBucketEnum;
        $h->save();

        return $avg;
    }

    public function createStockCard(Stock $stock, string $type, int $quantity, int $balanceAfter, ?string $referencableId, ?string $referencableType, ?string $notes, ?string $userId, ?int $unitPrice = null): StockCard
    {
        $sc = new StockCard();
        $sc->stock_id = (string) $stock->id;
        $sc->type = $this->isInboundType($type) ? StockCardType::In->value : StockCardType::Out->value;
        $sc->quantity = $quantity;
        $sc->unit_price = (int) ($unitPrice ?? 0);
        $sc->subtotal = (int) ($sc->unit_price * $quantity);
        $sc->balance_before = $this->isInboundType($type) ? ($balanceAfter - $quantity) : ($balanceAfter + $quantity);
        $sc->balance_after = $balanceAfter;
        $sc->referencable_id = $referencableId;
        $sc->referencable_type = $referencableType;
        $sc->notes = $notes;
        $sc->user_id = $userId;
        $bucketEnum = $stock->bucket instanceof StockBucket ? $stock->bucket : StockBucket::NonVat;
        $avg = AverageCost::query()
            ->where('warehouse_id', (string) $stock->warehouse_id)
            ->where('product_id', (string) $stock->product_id)
            ->where('bucket', $bucketEnum->value)
            ->lockForUpdate()
            ->first();
        $sc->last_hpp = $avg ? (int) $avg->cost : 0;
        $sc->save();
        return $sc;
    }

    public function issueGoods(array $data): void
    {
        $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $warehouseId = (string) $data['warehouse_id'];
                    $productId = (string) $data['product_id'];
                    $qty = (int) $data['quantity'];
                    $notes = isset($data['notes']) ? (string) $data['notes'] : null;
                    $userId = isset($data['created_by_id']) ? (string) $data['created_by_id'] : null;
                    $ownerUserId = $this->resolveOwnerUserIdFromData($data);

                    $this->deductStockWithPriority($warehouseId, $productId, $qty, null, null, $notes, $userId, 'OUT', 0, $ownerUserId);
                    return null;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('issue_goods_error', [
                    'user_id' => isset($data['created_by_id']) ? (string) $data['created_by_id'] : null,
                    'warehouse_id' => (string) $data['warehouse_id'],
                    'product_id' => (string) $data['product_id'],
                    'quantity' => (int) $data['quantity'],
                    'payload' => $data,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function getTotalStockQuantity(string $warehouseId, string $productId, ?string $divisionId = null, ?string $rackId = null): int
    {
        $q = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId);
        if ($divisionId === null) {
            $q->whereNull('product_division_id');
        } else {
            $q->where('product_division_id', $divisionId);
        }
        if ($rackId === null) {
            $q->whereNull('product_rack_id');
        } else {
            $q->where('product_rack_id', $rackId);
        }
        return (int) $q->sum('quantity');
    }

    public function resolveBucketFromData(array $data): StockBucket
    {
        $flag = false;
        if (isset($data['is_vat'])) {
            $flag = (bool) $data['is_vat'];
        } elseif (isset($data['is_value_added_tax_enabled'])) {
            $flag = (bool) $data['is_value_added_tax_enabled'];
        } elseif (isset($data['value_added_tax_id'])) {
            $flag = $data['value_added_tax_id'] !== null;
        }
        return $flag ? StockBucket::Vat : StockBucket::NonVat;
    }

    public function deductStockWithPriority(string $warehouseId, string $productId, int $qty, ?string $referencableId, ?string $referencableType, ?string $notes, ?string $userId, string $cardType, int $unitPrice = 0, ?string $ownerUserId = null): void
    {
        $remaining = $qty;

        if ($ownerUserId !== null) {
            $nonVatOwner = $this->getStockRowByBucket($warehouseId, $productId, null, null, StockBucket::NonVat, $ownerUserId) ?? $this->getStockRowByNullBucket($warehouseId, $productId, null, null, $ownerUserId);
            if ($nonVatOwner && $remaining > 0) {
                $take = min((int) $nonVatOwner->quantity, $remaining);
                $nonVatOwner->quantity = (int) $nonVatOwner->quantity - $take;
                $nonVatOwner->save();
                $this->createStockCard($nonVatOwner, $cardType, $take, (int) $nonVatOwner->quantity, $referencableId, $referencableType, $notes, $userId, $unitPrice);
                $remaining -= $take;
            }
        } else {
            $nonVat = $this->getStockRowByBucket($warehouseId, $productId, null, null, StockBucket::NonVat) ?? $this->getStockRowByNullBucket($warehouseId, $productId, null, null);
            if ($nonVat && $remaining > 0) {
                $take = min((int) $nonVat->quantity, $remaining);
                $nonVat->quantity = (int) $nonVat->quantity - $take;
                $nonVat->save();
                $this->createStockCard($nonVat, $cardType, $take, (int) $nonVat->quantity, $referencableId, $referencableType, $notes, $userId, $unitPrice);
                $remaining -= $take;
            }
        }

        if ($remaining > 0) {
            if ($ownerUserId !== null) {
                $vatOwner = $this->getStockRowByBucket($warehouseId, $productId, null, null, StockBucket::Vat, $ownerUserId);
                if ($vatOwner) {
                    $take = min((int) $vatOwner->quantity, $remaining);
                    $vatOwner->quantity = (int) $vatOwner->quantity - $take;
                    $vatOwner->save();
                    $this->createStockCard($vatOwner, $cardType, $take, (int) $vatOwner->quantity, $referencableId, $referencableType, $notes, $userId, $unitPrice);
                    $remaining -= $take;
                }
            } else {
                $vat = $this->getStockRowByBucket($warehouseId, $productId, null, null, StockBucket::Vat);
                if ($vat) {
                    $take = min((int) $vat->quantity, $remaining);
                    $vat->quantity = (int) $vat->quantity - $take;
                    $vat->save();
                    $this->createStockCard($vat, $cardType, $take, (int) $vat->quantity, $referencableId, $referencableType, $notes, $userId, $unitPrice);
                    $remaining -= $take;
                }
            }
        }

        if ($remaining > 0 && $ownerUserId !== null) {
            $nonVat = $this->getStockRowByBucket($warehouseId, $productId, null, null, StockBucket::NonVat) ?? $this->getStockRowByNullBucket($warehouseId, $productId, null, null);
            if ($nonVat) {
                $take = min((int) $nonVat->quantity, $remaining);
                $nonVat->quantity = (int) $nonVat->quantity - $take;
                $nonVat->save();
                $this->createStockCard($nonVat, $cardType, $take, (int) $nonVat->quantity, $referencableId, $referencableType, $notes, $userId, $unitPrice);
                $remaining -= $take;
            }
        }

        if ($remaining > 0 && $ownerUserId !== null) {
            $vat = $this->getStockRowByBucket($warehouseId, $productId, null, null, StockBucket::Vat);
            if ($vat) {
                $take = min((int) $vat->quantity, $remaining);
                $vat->quantity = (int) $vat->quantity - $take;
                $vat->save();
                $this->createStockCard($vat, $cardType, $take, (int) $vat->quantity, $referencableId, $referencableType, $notes, $userId, $unitPrice);
            }
        }
    }

    public function getOrCreateStockByBucket(string $warehouseId, string $productId, ?string $divisionId, ?string $rackId, StockBucket|string $bucket, ?string $ownerUserId = null): Stock
    {
        $row = $this->getStockRowByBucket($warehouseId, $productId, $divisionId, $rackId, $bucket, $ownerUserId);
        if ($row) {
            return $row;
        }
        $s = new Stock();
        $s->warehouse_id = $warehouseId;
        $s->product_id = $productId;
        $s->product_division_id = $divisionId;
        $s->product_rack_id = $rackId;
        $s->bucket = $bucket instanceof StockBucket ? $bucket : StockBucket::from((string) $bucket);
        $s->owner_user_id = $ownerUserId;
        $s->quantity = 0;
        $s->save();
        return $s;
    }

    public function resolveOwnerUserIdFromData(array $data): ?string
    {
        if (!isset($data['created_by_id']) || $data['created_by_id'] === null) {
            return null;
        }
        $uid = (string) $data['created_by_id'];
        $user = User::query()->where('id', $uid)->first(['id', 'role_id']);
        if (!$user || !$user->role_id) {
            return null;
        }
        $role = \App\Models\Role::query()->where('id', (string) $user->role_id)->first(['name']);
        if (!$role) {
            return null;
        }
        if ((string) $role->name === RoleName::Marketing->value) {
            return $uid;
        }
        return null;
    }

    private function isInboundType(string $type): bool
    {
        return in_array($type, ['IN', 'TRF_IN', 'ADJ_IN', 'OPN_IN', 'SLS_IN'], true);
    }

    private function hydrateStockAdjustmentItem(StockAdjustment $adj, array $data): StockAdjustmentItem
    {
        $si = new StockAdjustmentItem();
        $si->stock_adjustment_id = (string) $adj->id;
        $si->product_id = (string) $data['product_id'];
        $si->adjustment_type = (string) $data['adjustment_type'];
        $si->quantity = (int) $data['quantity'];
        $si->unit_cost = isset($data['unit_cost']) ? (int) $data['unit_cost'] : 0;
        return $si;
    }

    private function hydrateTransferItem(StockTransfer $transfer, array $data): StockTransferItem
    {
        $ti = new StockTransferItem();
        $ti->stock_transfer_id = (string) $transfer->id;
        $ti->product_id = (string) $data['product_id'];
        $ti->quantity = (int) $data['quantity'];
        return $ti;
    }
}
