<?php

declare(strict_types=1);

namespace App\Imports;

use App\Services\StockDocumentService;
use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket, StockDocumentStatus};
use App\DTOs\StockDocument\{StockDocumentData, StockDocumentItemData};
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Impor Dokumen Stok dari file Excel dengan 4 kolom:
 * - Product Name / SKU
 * - quantity
 * - unit_cost (opsional untuk OUT)
 * - notes
 *
 * Gudang dipilih dari UI dan diteruskan via request (warehouse_id).
 */
class StockDocumentsImport implements ToCollection, WithHeadingRow
{
    private StockDocumentService $service;
    private ?string $userId;
    private StockDocumentType $type;
    private StockDocumentReason $reason;
    private ?StockBucket $bucket;
    private ?string $notes;
    private string $warehouseId;

    private function getRowValue(array $row, array $keys): string
    {
        foreach ($keys as $k) {
            if (isset($row[$k])) {
                return (string) $row[$k];
            }
        }
        return '';
    }

    public function __construct(
        StockDocumentService $service,
        ?string $userId = null,
        string $type = 'in',
        string $reason = '',
        ?string $bucket = null,
        ?string $notes = null,
        string $warehouseId = ''
    ) {
        $this->service = $service;
        $this->userId = $userId;
        $this->type = StockDocumentType::from(strtolower($type));
        $this->reason = StockDocumentReason::from($reason);
        $this->bucket = $bucket ? StockBucket::from(strtolower($bucket)) : null;
        $this->notes = $notes ?: null;
        $this->warehouseId = $warehouseId;
    }

    public function collection(Collection $rows)
    {
        $productTokens = [];
        foreach ($rows as $row) {
            $rowArr = $row instanceof Collection ? $row->toArray() : (array) $row;
            $pVal = $this->getRowValue($rowArr, ['product', 'product_name_sku', 'product_name', 'product_sku']);
            if ($pVal !== '') $productTokens[] = strtoupper($pVal);
        }
        $productTokens = array_values(array_unique($productTokens));

        $products = Product::query()
            ->where(function ($q) use ($productTokens) {
                if (!empty($productTokens)) {
                    $q->whereIn(DB::raw('UPPER(name)'), $productTokens)
                        ->orWhereIn(DB::raw('UPPER(sku)'), $productTokens);
                }
            })
            ->get(['id', 'name', 'sku']);
        $productsBySku = $products->filter(function ($p) {
            return !empty($p->sku);
        })->keyBy(function ($p) {
            return strtoupper((string) $p->sku);
        });
        $productsByName = $products->keyBy(function ($p) {
            return strtoupper((string) $p->name);
        });

        $items = [];

        foreach ($rows as $row) {
            $rowArr = $row instanceof Collection ? $row->toArray() : (array) $row;
            $pRaw = $this->getRowValue($rowArr, ['product', 'product_name_sku', 'product_name', 'product_sku']);
            $pToken = $pRaw !== '' ? strtoupper($pRaw) : '';
            $productId = '';
            if ($pToken) {
                if ($productsBySku->has($pToken)) {
                    $productId = (string) $productsBySku->get($pToken)->id;
                } elseif ($productsByName->has($pToken)) {
                    $productId = (string) $productsByName->get($pToken)->id;
                }
            }
            $qty = isset($rowArr['quantity']) ? (int) $rowArr['quantity'] : 0;
            $notes = isset($rowArr['notes']) ? (string) $rowArr['notes'] : null;
            $unitCostRaw = isset($rowArr['unit_cost']) ? (string) $rowArr['unit_cost'] : (isset($rowArr['unit_price']) ? (string) $rowArr['unit_price'] : '');
            $unitPrice = $unitCostRaw !== '' ? (int) $unitCostRaw : null;

            if ($productId && $qty > 0) {
                $items[] = new StockDocumentItemData(
                    productId: (string) $productId,
                    quantity: (int) $qty,
                    unitPrice: $this->type === StockDocumentType::In ? $unitPrice : null,
                    notes: $notes ?: null,
                    productDivisionId: null,
                    productRackId: null,
                    ownerUserId: null
                );
            }
        }

        if (count($items) > 0 && $this->warehouseId !== '') {
            $dto = new StockDocumentData(
                warehouseId: (string) $this->warehouseId,
                type: $this->type,
                reason: $this->reason,
                userId: (string) ($this->userId ?? ''),
                documentDate: now()->toDateString(),
                number: null,
                sourceableType: null,
                sourceableId: null,
                bucket: $this->bucket ?? StockBucket::NonVat,
                notes: $this->notes,
                status: StockDocumentStatus::Draft
            );
            $this->service->createManualStockDocument($dto, $items);
        }
    }
}
