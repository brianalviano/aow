<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\StockDocument\StockDocumentData;
use App\Enums\{StockDocumentType, StockBucket, StockDocumentStatus};
use App\Models\{StockDocument, StockDocumentItem};
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{DB, Log};

/**
 * Layanan domain untuk pengelolaan Dokumen Stok (IN/OUT).
 *
 * Tanggung jawab:
 * - Pembuatan dokumen stok manual beserta itemnya
 * - Transisi status dokumen (Draft → Pending HO → Completed, atau penolakan HO)
 * - Eksekusi pergerakan stok saat status menjadi Completed
 *
 * Service ini mengandalkan StockService untuk operasi stok dasar
 * seperti create stock card, average cost, dan deduct/inbound.
 *
 * @author PJD
 */
final class StockDocumentService
{
    use RetryableTransactionsTrait;

    public function __construct(
        private readonly StockService $stockService,
    ) {}

    /**
     * Buat dokumen stok manual beserta itemnya.
     *
     * @param StockDocumentData $dto Payload header dokumen stok.
     * @param array $items Daftar item dokumen (StockDocumentItemData).
     * @return StockDocument Dokumen stok yang berhasil dibuat.
     *
     * @throws \Throwable
     */
    public function createManualStockDocument(StockDocumentData $dto, array $items): StockDocument
    {
        return $this->runWithRetry(function () use ($dto, $items) {
            try {
                return DB::transaction(function () use ($dto, $items) {
                    $warehouseId = (string) $dto->warehouseId;
                    $bucketEnum = $dto->bucket ?? StockBucket::NonVat;
                    $number = $dto->number ?? $this->generateStockDocumentNumber($dto->type, $dto->documentDate);
                    $statusEnum = $dto->status ?? StockDocumentStatus::Completed;

                    $doc = new StockDocument();
                    $doc->warehouse_id = $warehouseId;
                    $doc->number = $number;
                    $doc->document_date = $dto->documentDate ?? now()->toDateString();
                    $doc->type = $dto->type->value;
                    $doc->reason = $dto->reason->value;
                    $doc->status = $statusEnum->value;
                    $doc->bucket = $bucketEnum;
                    $doc->notes = $dto->notes;
                    $doc->user_id = $dto->userId;
                    if ($dto->sourceableId && $dto->sourceableType) {
                        $doc->sourceable_id = (string) $dto->sourceableId;
                        $doc->sourceable_type = (string) $dto->sourceableType;
                    }
                    $doc->save();

                    foreach ($items as $item) {
                        if (!$item instanceof \App\DTOs\StockDocument\StockDocumentItemData) {
                            throw new \InvalidArgumentException('Items harus berupa StockDocumentItemData');
                        }
                        $row = new StockDocumentItem();
                        $row->stock_document_id = (string) $doc->id;
                        $row->product_id = (string) $item->productId;
                        $row->product_division_id = $item->productDivisionId;
                        $row->product_rack_id = $item->productRackId;
                        $row->owner_user_id = $item->ownerUserId;
                        $row->quantity = (int) $item->quantity;
                        $row->unit_price = (int) ($item->unitPrice ?? 0);
                        $row->subtotal = (int) ($row->unit_price * $row->quantity);
                        $row->bucket = $bucketEnum;
                        $row->notes = $item->notes;
                        $row->save();

                        if ($statusEnum === StockDocumentStatus::Completed) {
                            if ($dto->type === StockDocumentType::In) {
                                $ownerId = $item->ownerUserId ?? $this->stockService->resolveOwnerUserIdFromData(['created_by_id' => $dto->userId]);
                                $stockRow = $this->stockService->getOrCreateStockByBucket(
                                    $warehouseId,
                                    (string) $item->productId,
                                    $item->productDivisionId,
                                    $item->productRackId,
                                    $bucketEnum,
                                    $ownerId
                                );
                                $prevQty = (int) $stockRow->quantity;
                                $newQty = $prevQty + (int) $item->quantity;
                                $this->stockService->updateAverageCostInbound(
                                    $warehouseId,
                                    (string) $item->productId,
                                    (int) $item->quantity,
                                    (int) ($item->unitPrice ?? 0),
                                    $bucketEnum
                                );
                                $stockRow->quantity = $newQty;
                                $stockRow->save();
                                $this->stockService->createStockCard(
                                    $stockRow,
                                    'IN',
                                    (int) $item->quantity,
                                    $newQty,
                                    (string) $row->id,
                                    StockDocumentItem::class,
                                    $item->notes,
                                    $dto->userId,
                                    (int) ($item->unitPrice ?? 0)
                                );
                            } else {
                                $ownerId = $item->ownerUserId ?? $this->stockService->resolveOwnerUserIdFromData(['created_by_id' => $dto->userId]);
                                $this->stockService->deductStockWithPriority(
                                    $warehouseId,
                                    (string) $item->productId,
                                    (int) $item->quantity,
                                    (string) $row->id,
                                    StockDocumentItem::class,
                                    $item->notes,
                                    $dto->userId,
                                    'OUT',
                                    (int) ($item->unitPrice ?? 0),
                                    $ownerId
                                );
                            }
                        }
                    }

                    return $doc->refresh();
                }, 5);
            } catch (\Throwable $e) {
                Log::error('createManualStockDocument failed', [
                    'warehouse_id' => (string) $dto->warehouseId,
                    'user_id' => (string) $dto->userId,
                    'type' => $dto->type->value,
                    'reason' => $dto->reason->value,
                    'items_count' => count($items),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Hitung status berikutnya dari dokumen stok.
     *
     * @param StockDocumentStatus $current Status saat ini.
     * @return StockDocumentStatus|null Status berikutnya atau null bila tidak tersedia.
     */
    public function computeNextStockDocumentStatus(StockDocumentStatus $current): ?StockDocumentStatus
    {
        return match ($current) {
            StockDocumentStatus::Draft => StockDocumentStatus::PendingHoApproval,
            StockDocumentStatus::PendingHoApproval => StockDocumentStatus::Completed,
            StockDocumentStatus::RejectedByHo,
            StockDocumentStatus::Completed,
            StockDocumentStatus::Canceled => null,
        };
    }

    /**
     * Lanjutkan status dokumen stok; mengeksekusi pergerakan stok saat Completed.
     *
     * @param StockDocument $doc Dokumen stok yang akan di-advance.
     * @param string $performedById ID user yang melakukan aksi.
     * @return StockDocument Dokumen setelah status diperbarui.
     *
     * @throws \Throwable
     */
    public function advanceStockDocumentStatus(StockDocument $doc, string $performedById): StockDocument
    {
        try {
            return $this->runWithRetry(function () use ($doc, $performedById) {
                return DB::transaction(function () use ($doc, $performedById) {
                    $current = $doc->status instanceof StockDocumentStatus
                        ? $doc->status
                        : StockDocumentStatus::from((string) $doc->status);
                    $next = $this->computeNextStockDocumentStatus($current);
                    if ($next === null) {
                        throw new \InvalidArgumentException('Status saat ini tidak dapat dilanjutkan');
                    }

                    if ($next === StockDocumentStatus::Completed) {
                        $doc->loadMissing(['items']);
                        $warehouseId = (string) $doc->warehouse_id;
                        $bucketEnum = $doc->bucket instanceof StockBucket ? $doc->bucket : StockBucket::NonVat;
                        foreach ($doc->items as $item) {
                            $productId = (string) $item->product_id;
                            $qty = (int) $item->quantity;
                            $unitPrice = (int) ($item->unit_price ?? 0);
                            $notes = $item->notes ? (string) $item->notes : null;
                            $ownerId = $item->owner_user_id
                                ? (string) $item->owner_user_id
                                : $this->stockService->resolveOwnerUserIdFromData(['created_by_id' => $performedById]);
                            if (($doc->type instanceof StockDocumentType ? $doc->type : StockDocumentType::from((string) $doc->type)) === StockDocumentType::In) {
                                $stockRow = $this->stockService->getOrCreateStockByBucket(
                                    $warehouseId,
                                    $productId,
                                    $item->product_division_id ? (string) $item->product_division_id : null,
                                    $item->product_rack_id ? (string) $item->product_rack_id : null,
                                    $bucketEnum,
                                    $ownerId
                                );
                                $prevQty = (int) $stockRow->quantity;
                                $newQty = $prevQty + $qty;
                                $this->stockService->updateAverageCostInbound($warehouseId, $productId, $qty, $unitPrice, $bucketEnum);
                                $stockRow->quantity = $newQty;
                                $stockRow->save();
                                $this->stockService->createStockCard($stockRow, 'IN', $qty, $newQty, (string) $item->id, StockDocumentItem::class, $notes, $performedById, $unitPrice);
                            } else {
                                $this->stockService->deductStockWithPriority(
                                    $warehouseId,
                                    $productId,
                                    $qty,
                                    (string) $item->id,
                                    StockDocumentItem::class,
                                    $notes,
                                    $performedById,
                                    'OUT',
                                    $unitPrice,
                                    $ownerId
                                );
                            }
                        }
                    }

                    $doc->status = $next->value;
                    $doc->save();
                    return $doc->refresh();
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('advanceStockDocumentStatus failed', [
                'stock_document_id' => (string) $doc->getKey(),
                'performed_by_id' => (string) $performedById,
                'status' => (string) ($doc->status instanceof StockDocumentStatus ? $doc->status->value : (string) $doc->status),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Tolak dokumen stok pada tahap HO.
     *
     * @param StockDocument $doc Dokumen stok.
     * @param string $reason Alasan penolakan.
     * @param string $performedById ID user yang melakukan aksi.
     * @return StockDocument Dokumen setelah ditolak.
     *
     * @throws \Throwable
     */
    public function rejectStockDocumentByHo(StockDocument $doc, string $reason, string $performedById): StockDocument
    {
        try {
            return $this->runWithRetry(function () use ($doc, $reason, $performedById) {
                return DB::transaction(function () use ($doc, $reason, $performedById) {
                    $current = $doc->status instanceof StockDocumentStatus
                        ? $doc->status
                        : StockDocumentStatus::from((string) $doc->status);
                    if ($current !== StockDocumentStatus::PendingHoApproval) {
                        throw new \InvalidArgumentException('Penolakan hanya diperbolehkan pada status Menunggu Persetujuan HO');
                    }
                    $existingNotes = $doc->notes ? (string) $doc->notes : '';
                    $append = 'Ditolak HO: ' . $reason;
                    $doc->notes = trim($existingNotes !== '' ? ($existingNotes . ' | ' . $append) : $append);
                    $doc->status = StockDocumentStatus::RejectedByHo->value;
                    $doc->save();
                    return $doc->refresh();
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('rejectStockDocumentByHo failed', [
                'stock_document_id' => (string) $doc->getKey(),
                'performed_by_id' => (string) $performedById,
                'reason' => (string) $reason,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate nomor dokumen stok.
     *
     * @param StockDocumentType $type Jenis dokumen (IN/OUT).
     * @param string|null $documentDate Tanggal dokumen (Y-m-d).
     * @return string Nomor dokumen yang dihasilkan.
     */
    private function generateStockDocumentNumber(StockDocumentType $type, ?string $documentDate): string
    {
        $monthYear = $documentDate ? \Illuminate\Support\Carbon::parse($documentDate)->format('mY') : now()->format('mY');
        $prefix = ($type === StockDocumentType::In ? 'SM' : 'SK') . '/' . $monthYear . '/';
        $last = StockDocument::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
