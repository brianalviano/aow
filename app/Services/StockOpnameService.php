<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Stock, StockOpname, StockOpnameAssignment, StockOpnameItem, Warehouse, User, Product, AverageCost};
use App\Enums\{StockOpnameStatus, StockOpnameAssignmentStatus, StockOpnameItemStatus, RoleName};
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{DB, Log, Notification};
use Throwable;
use App\Http\Requests\StockOpname\{StoreStockOpnameRequest, SubmitStockOpnameCountRequest};
use App\Notifications\StockOpnameAssignedNotification;
use App\Enums\StockBucket;

class StockOpnameService
{
    use RetryableTransactionsTrait;

    public function createFromRequest(StoreStockOpnameRequest $request, string $createdById): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($request, $createdById) {
                return DB::transaction(function () use ($request, $createdById) {
                    $p = $request->validated();
                    $warehouseId = (string) $p['warehouse_id'];
                    $productIds = array_map(fn($id) => (string) $id, (array) $p['product_ids']);
                    $userIds = array_map(fn($id) => (string) $id, (array) $p['user_ids']);
                    $statusStr = isset($p['status']) && is_string($p['status']) ? (string) $p['status'] : null;
                    $statusEnum = $statusStr ? \App\Enums\StockOpnameStatus::from($statusStr) : \App\Enums\StockOpnameStatus::Draft;

                    $op = new StockOpname();
                    $op->warehouse_id = $warehouseId;
                    $op->number = $this->generateNumber();
                    $op->scheduled_date = (string) $p['scheduled_date'];
                    $op->notes = $p['notes'] ?? null;
                    $op->status = $statusEnum->value;
                    $op->save();

                    $snapshotMap = $this->buildSystemSnapshot($warehouseId, $productIds);

                    $allowedUserIds = User::query()
                        ->whereIn('id', $userIds)
                        ->whereHas('role', function ($q) {
                            $q->whereIn('name', RoleName::stockOpnameAssignable());
                        })
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                        ->all();

                    $assignments = [];
                    foreach ($allowedUserIds as $uid) {
                        $a = new StockOpnameAssignment();
                        $a->stock_opname_id = (string) $op->getKey();
                        $a->user_id = $uid;
                        $a->status = ($statusEnum === StockOpnameStatus::Scheduled)
                            ? StockOpnameAssignmentStatus::Assigned->value
                            : StockOpnameAssignmentStatus::Pending->value;
                        $a->save();
                        $assignments[] = $a;
                    }

                    foreach ($assignments as $a) {
                        $rows = [];
                        foreach ($productIds as $pid) {
                            $rows[] = [
                                'product_id' => $pid,
                                'system_quantity' => (int) ($snapshotMap[$pid] ?? 0),
                                'actual_quantity' => 0,
                                'difference' => 0,
                                'hpp' => 0,
                                'subtotal' => 0,
                                'status' => StockOpnameItemStatus::Pending->value,
                                'counted_at' => null,
                                'verified_at' => null,
                                'notes' => null,
                            ];
                        }
                        if (!empty($rows)) {
                            $a->stockOpname()->first()->items()->createMany(array_map(function ($r) use ($a) {
                                return [
                                    'stock_opname_id' => (string) $a->stock_opname_id,
                                    'stock_opname_assignment_id' => (string) $a->getKey(),
                                    ...$r,
                                ];
                            }, $rows));
                        }
                    }

                    if ($statusEnum === StockOpnameStatus::Scheduled) {
                        $this->notifyAssignedUsers($op, $assignments);
                    }

                    return $op;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'create',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $createdById,
            ]);
            throw $e;
        }
    }

    public function createFromData(array $p, string $createdById): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($p, $createdById) {
                return DB::transaction(function () use ($p) {
                    $warehouseId = (string) $p['warehouse_id'];
                    $productIds = array_map(fn($id) => (string) $id, (array) ($p['product_ids'] ?? []));
                    $userIds = array_map(fn($id) => (string) $id, (array) ($p['user_ids'] ?? []));
                    $statusStr = isset($p['status']) && is_string($p['status']) ? (string) $p['status'] : null;
                    $statusEnum = $statusStr ? StockOpnameStatus::from($statusStr) : StockOpnameStatus::Draft;

                    $op = new StockOpname();
                    $op->warehouse_id = $warehouseId;
                    $op->number = $this->generateNumber();
                    $op->scheduled_date = isset($p['scheduled_date']) ? (string) $p['scheduled_date'] : null;
                    $op->notes = $p['notes'] ?? null;
                    $op->status = $statusEnum->value;
                    $op->save();

                    $snapshotMap = $this->buildSystemSnapshot($warehouseId, $productIds);

                    $allowedUserIds = User::query()
                        ->whereIn('id', $userIds)
                        ->whereHas('role', function ($q) {
                            $q->whereIn('name', RoleName::stockOpnameAssignable());
                        })
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                        ->all();

                    $assignments = [];
                    foreach ($allowedUserIds as $uid) {
                        $a = new StockOpnameAssignment();
                        $a->stock_opname_id = (string) $op->getKey();
                        $a->user_id = $uid;
                        $a->status = ($statusEnum === StockOpnameStatus::Scheduled)
                            ? StockOpnameAssignmentStatus::Assigned->value
                            : StockOpnameAssignmentStatus::Pending->value;
                        $a->save();
                        $assignments[] = $a;
                    }

                    foreach ($assignments as $a) {
                        $rows = [];
                        foreach ($productIds as $pid) {
                            $rows[] = [
                                'product_id' => $pid,
                                'system_quantity' => (int) ($snapshotMap[$pid] ?? 0),
                                'actual_quantity' => 0,
                                'difference' => 0,
                                'hpp' => 0,
                                'subtotal' => 0,
                                'status' => StockOpnameItemStatus::Pending->value,
                                'counted_at' => null,
                                'verified_at' => null,
                                'notes' => null,
                            ];
                        }
                        if (!empty($rows)) {
                            $a->stockOpname()->first()->items()->createMany(array_map(function ($r) use ($a, $op) {
                                return [
                                    'stock_opname_id' => (string) $op->getKey(),
                                    'stock_opname_assignment_id' => (string) $a->getKey(),
                                    ...$r,
                                ];
                            }, $rows));
                        }
                    }

                    if ($statusEnum === StockOpnameStatus::Scheduled) {
                        $this->notifyAssignedUsers($op, $assignments);
                    }

                    return $op;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'create_from_data',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $createdById,
            ]);
            throw $e;
        }
    }

    public function submitCountsFromRequest(StockOpnameAssignment $assignment, SubmitStockOpnameCountRequest $request, string $performedById): StockOpnameAssignment
    {
        try {
            return $this->runWithRetry(function () use ($assignment, $request, $performedById) {
                return DB::transaction(function () use ($assignment, $request, $performedById) {
                    $p = $request->validated();
                    $items = (array) ($p['items'] ?? []);
                    $rowsByProduct = [];
                    foreach ($items as $it) {
                        $pid = (string) $it['product_id'];
                        $val = $it['actual_quantity'] ?? null;
                        $rowsByProduct[$pid] = ($val === '' || $val === null) ? null : (int) $val;
                    }

                    $opname = StockOpname::query()
                        ->where('id', (string) $assignment->stock_opname_id)
                        ->lockForUpdate()
                        ->first();
                    if ($opname) {
                        $current = $opname->status instanceof StockOpnameStatus
                            ? $opname->status
                            : StockOpnameStatus::from((string) $opname->status);
                        if (!in_array($current, [StockOpnameStatus::InProgress, StockOpnameStatus::Completed, StockOpnameStatus::Canceled], true)) {
                            $opname->status = StockOpnameStatus::InProgress->value;
                            $opname->save();
                        }
                    }

                    $query = StockOpnameItem::query()
                        ->where('stock_opname_id', (string) $assignment->stock_opname_id)
                        ->where('stock_opname_assignment_id', (string) $assignment->getKey());
                    $list = $query->get(['id', 'product_id']);
                    foreach ($list as $row) {
                        $pid = (string) $row->product_id;
                        if (!array_key_exists($pid, $rowsByProduct)) {
                            continue;
                        }
                        $val = $rowsByProduct[$pid];
                        if ($val === null) {
                            continue;
                        }
                        $actual = (int) $val;
                        StockOpnameItem::query()
                            ->where('id', (string) $row->id)
                            ->update([
                                'actual_quantity' => $actual,
                                'status' => StockOpnameItemStatus::Counted->value,
                                'counted_at' => now(),
                                'notes' => $p['notes_map'][$pid] ?? null,
                            ]);
                    }

                    $assignment->status = StockOpnameAssignmentStatus::Completed->value;
                    $assignment->save();

                    $totalAssignments = StockOpnameAssignment::query()
                        ->where('stock_opname_id', (string) $assignment->stock_opname_id)
                        ->count();
                    $completedAssignments = StockOpnameAssignment::query()
                        ->where('stock_opname_id', (string) $assignment->stock_opname_id)
                        ->where('status', StockOpnameAssignmentStatus::Completed->value)
                        ->count();
                    if ($totalAssignments > 0 && $completedAssignments === $totalAssignments) {
                        $opname = StockOpname::query()->where('id', (string) $assignment->stock_opname_id)->first();
                        if ($opname) {
                            $this->finalize($opname, app(StockService::class), (string) $performedById);
                        }
                    }
                    return $assignment;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'submit',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'assignment_id' => (string) $assignment->getKey(),
                'user_id' => $performedById,
            ]);
            throw $e;
        }
    }

    public function finalize(StockOpname $opname, StockService $stock, string $performedById): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($opname, $stock, $performedById) {
                return DB::transaction(function () use ($opname, $stock, $performedById) {
                    $rows = StockOpnameItem::query()
                        ->where('stock_opname_id', (string) $opname->getKey())
                        ->get(['id', 'product_id', 'system_quantity', 'actual_quantity', 'status']);

                    $systemQtyByProduct = [];
                    $actualTotalByProduct = [];
                    $productIds = [];
                    $countedProducts = [];
                    foreach ($rows as $r) {
                        $pid = (string) $r->product_id;
                        $productIds[] = $pid;
                        $systemQtyByProduct[$pid] = $systemQtyByProduct[$pid] ?? (int) $r->system_quantity;
                        $st = $r->status instanceof StockOpnameItemStatus ? $r->status : StockOpnameItemStatus::from((string) $r->status);
                        if (in_array($st, [StockOpnameItemStatus::Counted, StockOpnameItemStatus::Verified], true)) {
                            $actualTotalByProduct[$pid] = ($actualTotalByProduct[$pid] ?? 0) + (int) $r->actual_quantity;
                            $countedProducts[$pid] = true;
                        }
                    }
                    foreach ($systemQtyByProduct as $pid => $sysQty) {
                        if (!($countedProducts[$pid] ?? false)) {
                            $actualTotalByProduct[$pid] = (int) $sysQty;
                        }
                    }

                    $warehouseId = (string) $opname->warehouse_id;
                    $costMap = [];
                    if (!empty($productIds)) {
                        $costRows = AverageCost::query()
                            ->where('warehouse_id', $warehouseId)
                            ->whereIn('product_id', $productIds)
                            ->where('bucket', StockBucket::NonVat->value)
                            ->get(['product_id', 'cost']);
                        foreach ($costRows as $cr) {
                            $costMap[(string) $cr->product_id] = (int) $cr->cost;
                        }
                    }

                    $diffItems = [];
                    $grandTotal = 0;
                    foreach ($actualTotalByProduct as $pid => $actualSum) {
                        $diff = $actualSum - (int) ($systemQtyByProduct[$pid] ?? 0);
                        $hpp = (int) ($costMap[$pid] ?? 0);
                        $grandTotal += (int) (abs($diff) * $hpp);
                        if ($diff !== 0) {
                            $diffItems[] = [
                                'product_id' => (string) $pid,
                                'difference' => (int) $diff,
                                'notes' => null,
                            ];
                        }
                    }

                    foreach ($rows as $r) {
                        $pid = (string) $r->product_id;
                        $hpp = (int) ($costMap[$pid] ?? 0);
                        $subtotal = (int) ($hpp * (int) $r->actual_quantity);
                        StockOpnameItem::query()
                            ->where('id', (string) $r->id)
                            ->update([
                                'hpp' => $hpp,
                                'subtotal' => $subtotal,
                            ]);
                    }

                    $opname->grand_total = (int) $grandTotal;
                    $opname->save();

                    if (!empty($diffItems)) {
                        $this->applyStockOpname($opname, $diffItems, (string) $performedById, $stock);
                    } else {
                        $opname->status = StockOpnameStatus::Completed->value;
                        $opname->save();
                    }

                    StockOpnameItem::query()
                        ->where('stock_opname_id', (string) $opname->getKey())
                        ->update([
                            'verified_at' => now(),
                            'status' => StockOpnameItemStatus::Verified->value,
                        ]);

                    $this->unlockStockAfterOpname($opname);

                    return $opname->refresh();
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'finalize',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
                'performed_by_id' => (string) $performedById,
            ]);
            throw $e;
        }
    }

    public function completeWithoutChanges(StockOpname $opname, string $performedById): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($opname, $performedById) {
                return DB::transaction(function () use ($opname, $performedById) {
                    $row = StockOpname::query()
                        ->where('id', (string) $opname->getKey())
                        ->lockForUpdate()
                        ->first();
                    if (!$row) {
                        return $opname;
                    }
                    $current = $row->status instanceof StockOpnameStatus
                        ? $row->status
                        : StockOpnameStatus::from((string) $row->status);
                    if ($current !== StockOpnameStatus::Scheduled) {
                        return $row;
                    }
                    StockOpnameAssignment::query()
                        ->where('stock_opname_id', (string) $row->getKey())
                        ->update(['status' => StockOpnameAssignmentStatus::Completed->value]);
                    $svc = app(StockService::class);
                    return $this->finalize($row, $svc, (string) $performedById);
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'complete_without_changes',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
                'performed_by_id' => (string) $performedById,
            ]);
            throw $e;
        }
    }

    public function lockStockForOpname(StockOpname $opname): void
    {
        try {
            $this->runWithRetry(function () use ($opname) {
                return DB::transaction(function () use ($opname) {
                    Stock::query()
                        ->where('warehouse_id', (string) $opname->warehouse_id)
                        ->update(['locked_by_stock_opname_id' => (string) $opname->id]);
                    return null;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'lock',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
            ]);
            throw $e;
        }
    }

    public function applyStockOpname(StockOpname $opname, array $items, string $performedById, StockService $stockService): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($opname, $items, $performedById, $stockService) {
                return DB::transaction(function () use ($opname, $items, $performedById, $stockService) {
                    foreach ($items as $item) {
                        $oi = $item instanceof StockOpnameItem ? $item : $this->hydrateOpnameItem($opname, $item);
                        $productId = (string) $oi->product_id;
                        $difference = (int) $oi->difference;

                        $stockRow = $stockService->getOrCreateStock((string) $opname->warehouse_id, $productId, null, null);
                        $prevQty = (int) $stockRow->quantity;
                        $newQty = $prevQty + $difference;
                        $stockRow->quantity = $newQty;
                        $stockRow->save();

                        $type = $difference >= 0 ? 'OPN_IN' : 'OPN_OUT';
                        $stockService->createStockCard(
                            $stockRow,
                            $type,
                            abs($difference),
                            $newQty,
                            (string) $opname->id,
                            StockOpname::class,
                            $oi->notes,
                            (string) $performedById
                        );
                    }

                    $opname->status = StockOpnameStatus::Completed->value;
                    $opname->save();

                    return $opname;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'apply',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
                'performed_by_id' => (string) $performedById,
            ]);
            throw $e;
        }
    }

    public function unlockStockAfterOpname(StockOpname $opname): void
    {
        try {
            $this->runWithRetry(function () use ($opname) {
                return DB::transaction(function () use ($opname) {
                    Stock::query()
                        ->where('warehouse_id', (string) $opname->warehouse_id)
                        ->where('locked_by_stock_opname_id', (string) $opname->id)
                        ->update(['locked_by_stock_opname_id' => null]);
                    return null;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'unlock',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
            ]);
            throw $e;
        }
    }

    public function startOnAssignmentEntry(StockOpname $opname, StockOpnameAssignment $assignment, string $userId): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($opname, $assignment) {
                return DB::transaction(function () use ($opname) {
                    $row = StockOpname::query()
                        ->where('id', (string) $opname->getKey())
                        ->lockForUpdate()
                        ->first();
                    if ($row) {
                        $current = $row->status instanceof StockOpnameStatus
                            ? $row->status
                            : StockOpnameStatus::from((string) $row->status);
                        if (!in_array($current, [StockOpnameStatus::InProgress, StockOpnameStatus::Completed, StockOpnameStatus::Canceled], true)) {
                            $row->status = StockOpnameStatus::InProgress->value;
                            $row->save();
                        }
                        return $row->refresh();
                    }
                    return $opname;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'start_assignment',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
                'assignment_id' => (string) $assignment->getKey(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    public function updateFromRequest(StockOpname $opname, StoreStockOpnameRequest $request, string $updatedById): StockOpname
    {
        try {
            return $this->runWithRetry(function () use ($opname, $request) {
                return DB::transaction(function () use ($opname, $request) {
                    $p = $request->validated();
                    $warehouseId = (string) $p['warehouse_id'];
                    $productIds = array_map(fn($id) => (string) $id, (array) $p['product_ids']);
                    $userIds = array_map(fn($id) => (string) $id, (array) $p['user_ids']);
                    $statusStr = isset($p['status']) && is_string($p['status']) ? (string) $p['status'] : null;
                    $statusEnum = $statusStr ? StockOpnameStatus::from($statusStr) : StockOpnameStatus::Draft;

                    $opname->warehouse_id = $warehouseId;
                    $opname->scheduled_date = (string) $p['scheduled_date'];
                    $opname->notes = $p['notes'] ?? null;
                    $opname->status = $statusEnum->value;
                    $opname->save();

                    $snapshotMap = $this->buildSystemSnapshot($warehouseId, $productIds);

                    $allowedUserIds = User::query()
                        ->whereIn('id', $userIds)
                        ->whereHas('role', function ($q) {
                            $q->whereIn('name', RoleName::stockOpnameAssignable());
                        })
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                        ->all();

                    $existingAssignments = StockOpnameAssignment::query()
                        ->where('stock_opname_id', (string) $opname->getKey())
                        ->get();

                    $toDelete = $existingAssignments->filter(fn($a) => !in_array((string) $a->user_id, $allowedUserIds, true));
                    foreach ($toDelete as $del) {
                        StockOpnameItem::query()->where('stock_opname_assignment_id', (string) $del->getKey())->delete();
                        $del->delete();
                    }

                    $assignments = [];
                    foreach ($allowedUserIds as $uid) {
                        $found = $existingAssignments->firstWhere('user_id', $uid);
                        if ($found) {
                            $found->status = ($statusEnum === StockOpnameStatus::Scheduled)
                                ? StockOpnameAssignmentStatus::Assigned->value
                                : StockOpnameAssignmentStatus::Pending->value;
                            $found->save();
                            $assignments[] = $found;
                        } else {
                            $a = new StockOpnameAssignment();
                            $a->stock_opname_id = (string) $opname->getKey();
                            $a->user_id = $uid;
                            $a->status = ($statusEnum === StockOpnameStatus::Scheduled)
                                ? StockOpnameAssignmentStatus::Assigned->value
                                : StockOpnameAssignmentStatus::Pending->value;
                            $a->save();
                            $assignments[] = $a;
                        }
                    }

                    foreach ($assignments as $a) {
                        StockOpnameItem::query()
                            ->where('stock_opname_id', (string) $opname->getKey())
                            ->where('stock_opname_assignment_id', (string) $a->getKey())
                            ->delete();

                        $rows = [];
                        foreach ($productIds as $pid) {
                            $rows[] = [
                                'stock_opname_id' => (string) $opname->getKey(),
                                'stock_opname_assignment_id' => (string) $a->getKey(),
                                'product_id' => $pid,
                                'system_quantity' => (int) ($snapshotMap[$pid] ?? 0),
                                'actual_quantity' => 0,
                                'difference' => 0,
                                'hpp' => 0,
                                'subtotal' => 0,
                                'status' => StockOpnameItemStatus::Pending->value,
                                'counted_at' => null,
                                'verified_at' => null,
                                'notes' => null,
                            ];
                        }
                        if (!empty($rows)) {
                            $a->stockOpname()->first()->items()->createMany($rows);
                        }
                    }

                    if ($statusEnum === StockOpnameStatus::Scheduled) {
                        $this->notifyAssignedUsers($opname, $assignments);
                    }

                    return $opname->refresh();
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_opname_service_error', [
                'action' => 'update',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_opname_id' => (string) $opname->getKey(),
                'updated_by_id' => $updatedById,
            ]);
            throw $e;
        }
    }

    private function hydrateOpnameItem(StockOpname $opname, array $data): StockOpnameItem
    {
        $oi = new StockOpnameItem();
        $oi->stock_opname_id = (string) $opname->id;
        $oi->product_id = (string) $data['product_id'];
        $oi->difference = (int) $data['difference'];
        $oi->notes = isset($data['notes']) ? (string) $data['notes'] : null;
        return $oi;
    }

    private function buildSystemSnapshot(string $warehouseId, array $productIds): array
    {
        $map = [];
        if (empty($productIds)) {
            return $map;
        }
        $rows = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->select(['product_id', DB::raw('SUM(quantity) as qty')])
            ->groupBy('product_id')
            ->get();
        foreach ($rows as $r) {
            $map[(string) $r->product_id] = (int) $r->qty;
        }
        return $map;
    }

    private function generateNumber(): string
    {
        $monthYear = now()->format('mY');
        $prefix = 'STO/' . $monthYear . '/';
        $last = StockOpname::query()
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

    private function notifyAssignedUsers(StockOpname $opname, array $assignments): void
    {
        $userIds = array_values(array_unique(array_map(fn($a) => (string) $a->user_id, $assignments)));
        if (empty($userIds)) {
            return;
        }
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email']);
        if ($users->isEmpty()) {
            return;
        }
        Notification::send($users, new StockOpnameAssignedNotification($opname));
    }
}
