<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\StockTransfer\{StockTransferData, StockTransferItemData};
use App\Models\{StockTransfer, StockTransferItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\RetryableTransactionsTrait;
use App\Enums\{StockTransferStatus};
use Throwable;

/**
 * Service untuk pengelolaan mutasi stok antar gudang.
 *
 * Aturan bisnis:
 * - Edit & hapus hanya diperbolehkan pada status Draft.
 * - Advance status: Draft → InTransit → Received.
 * - Pada transisi ke Received, pergerakan stok dieksekusi via StockService::transfer.
 *
 * @author PJD
 */
class StockTransferService
{
    use RetryableTransactionsTrait;

    public function create(StockTransferData $data): StockTransfer
    {
        try {
            return $this->runWithRetry(function () use ($data) {
                return DB::transaction(function () use ($data) {
                    $tr = new StockTransfer();
                    $this->fillHead($tr, $data);
                    $tr->number = $this->generateNumber($tr);
                    $tr->status = $data->status ?: StockTransferStatus::Draft->value;
                    $tr->save();

                    $this->syncItems($tr, $data->items);
                    $tr->save();

                    return $tr;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_transfer_service_error', [
                'action' => 'create',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'from_warehouse_id' => $data->fromWarehouseId,
                'to_warehouse_id' => $data->toWarehouseId,
                'user_id' => $data->createdById,
            ]);
            throw $e;
        }
    }

    public function update(StockTransfer $tr, StockTransferData $data): StockTransfer
    {
        try {
            return $this->runWithRetry(function () use ($tr, $data) {
                return DB::transaction(function () use ($tr, $data) {
                    $current = $tr->status instanceof StockTransferStatus
                        ? $tr->status
                        : StockTransferStatus::from((string) $tr->status);
                    if (!in_array($current, [StockTransferStatus::Draft], true)) {
                        throw new \InvalidArgumentException('Edit hanya diperbolehkan pada status Draft');
                    }
                    $this->fillHead($tr, $data);
                    $tr->status = $data->status ?: $tr->status;
                    $tr->save();

                    $this->syncItems($tr, $data->items);
                    $tr->save();

                    return $tr;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_transfer_service_error', [
                'action' => 'update',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_transfer_id' => (string) $tr->getKey(),
                'user_id' => $data->createdById,
            ]);
            throw $e;
        }
    }

    public function delete(StockTransfer $tr): void
    {
        try {
            $this->runWithRetry(function () use ($tr) {
                return DB::transaction(function () use ($tr) {
                    $current = $tr->status instanceof StockTransferStatus
                        ? $tr->status
                        : StockTransferStatus::from((string) $tr->status);
                    if ($current !== StockTransferStatus::Draft) {
                        throw new \InvalidArgumentException('Hapus hanya diperbolehkan pada status Draft');
                    }
                    StockTransferItem::query()->where('stock_transfer_id', (string) $tr->getKey())->delete();
                    $tr->delete();
                    return null;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_transfer_service_error', [
                'action' => 'delete',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_transfer_id' => (string) $tr->getKey(),
            ]);
            throw $e;
        }
    }

    public function advanceStatus(StockTransfer $tr, string $performedById, StockService $stock): StockTransfer
    {
        try {
            return $this->runWithRetry(function () use ($tr, $performedById, $stock) {
                return DB::transaction(function () use ($tr, $performedById, $stock) {
                    $current = $tr->status instanceof StockTransferStatus
                        ? $tr->status
                        : StockTransferStatus::from((string) $tr->status);
                    $next = $this->computeNextStatus($current);
                    if ($next === null) {
                        throw new \InvalidArgumentException('Status saat ini tidak dapat dilanjutkan');
                    }
                    if ($next === StockTransferStatus::Received) {
                        $tr->loadMissing(['items']);
                        $stock->transfer($tr, $tr->items->all(), $performedById);
                        $tr->status = $next->value;
                        $tr->save();
                        return $tr->refresh();
                    }
                    $tr->status = $next->value;
                    $tr->save();
                    return $tr;
                }, 5);
            }, 3);
        } catch (Throwable $e) {
            Log::error('stock_transfer_service_error', [
                'action' => 'advance',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stock_transfer_id' => (string) $tr->getKey(),
                'user_id' => $performedById,
            ]);
            throw $e;
        }
    }

    public function computeNextStatus(StockTransferStatus $current): ?StockTransferStatus
    {
        return match ($current) {
            StockTransferStatus::Draft => StockTransferStatus::InTransit,
            StockTransferStatus::InTransit => StockTransferStatus::Received,
            StockTransferStatus::Received => null,
            StockTransferStatus::Canceled => null,
        };
    }

    private function fillHead(StockTransfer $tr, StockTransferData $data): void
    {
        $tr->from_warehouse_id = $data->fromWarehouseId;
        $tr->to_warehouse_id = $data->toWarehouseId;
        $tr->to_owner_user_id = $data->toOwnerUserId;
        $tr->transfer_date = $data->transferDate;
        $tr->notes = $data->notes;
    }

    /**
     * @param array<StockTransferItemData> $items
     */
    private function syncItems(StockTransfer $tr, array $items): void
    {
        StockTransferItem::query()->where('stock_transfer_id', (string) $tr->getKey())->delete();
        $rows = [];
        foreach ($items as $it) {
            $rows[] = [
                'product_id' => (string) $it->productId,
                'quantity' => (int) $it->quantity,
            ];
        }
        if (!empty($rows)) {
            $tr->items()->createMany($rows);
        }
    }

    private function generateNumber(StockTransfer $tr): string
    {
        $monthYear = $tr->transfer_date ? $tr->transfer_date->format('mY') : now()->format('mY');
        $prefix = 'TRF/' . $monthYear . '/';
        $last = StockTransfer::query()
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
