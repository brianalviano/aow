<?php

declare(strict_types=1);

namespace App\Imports;

use App\Services\StockService;
use App\Models\{Warehouse, Product};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockOutImport implements ToCollection, WithHeadingRow
{
    private StockService $service;
    private ?string $userId;

    private function getRowValue(array $row, array $keys): string
    {
        foreach ($keys as $k) {
            if (isset($row[$k])) {
                return (string) $row[$k];
            }
        }
        return '';
    }

    public function __construct(StockService $service, ?string $userId = null)
    {
        $this->service = $service;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $warehouseNames = [];
        $warehouseCodes = [];
        $productTokens = [];
        foreach ($rows as $row) {
            $wName = $this->getRowValue($row, ['warehouse_name', 'warehouse']);
            $wCode = $this->getRowValue($row, ['warehouse_code']);
            $pVal = $this->getRowValue($row, ['product', 'product_name_sku', 'product_name', 'product_sku']);
            if ($wName !== '') $warehouseNames[] = strtoupper($wName);
            if ($wCode !== '') $warehouseCodes[] = strtoupper($wCode);
            if ($pVal !== '') $productTokens[] = strtoupper($pVal);
        }
        $warehouseNames = array_values(array_unique($warehouseNames));
        $warehouseCodes = array_values(array_unique($warehouseCodes));
        $productTokens = array_values(array_unique($productTokens));

        $warehouses = Warehouse::query()
            ->where(function ($q) use ($warehouseNames, $warehouseCodes) {
                if (!empty($warehouseNames)) {
                    $q->whereIn(DB::raw('UPPER(name)'), $warehouseNames);
                }
                if (!empty($warehouseCodes)) {
                    $q->orWhereIn(DB::raw('UPPER(code)'), $warehouseCodes);
                }
            })
            ->get(['id', 'name', 'code']);
        $warehousesByName = $warehouses->keyBy(function ($w) {
            return strtoupper((string) $w->name);
        });
        $warehousesByCode = $warehouses->keyBy(function ($w) {
            return strtoupper((string) $w->code);
        });
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

        foreach ($rows as $row) {
            $wNameRaw = $this->getRowValue($row, ['warehouse_name', 'warehouse']);
            $wCodeRaw = $this->getRowValue($row, ['warehouse_code']);
            $pRaw = $this->getRowValue($row, ['product', 'product_name_sku', 'product_name', 'product_sku']);
            $wName = $wNameRaw !== '' ? strtoupper($wNameRaw) : '';
            $wCode = $wCodeRaw !== '' ? strtoupper($wCodeRaw) : '';
            $pToken = $pRaw !== '' ? strtoupper($pRaw) : '';
            $warehouseId = '';
            if ($wName && $warehousesByName->has($wName)) {
                $warehouseId = (string) $warehousesByName->get($wName)->id;
            } elseif ($wCode && $warehousesByCode->has($wCode)) {
                $warehouseId = (string) $warehousesByCode->get($wCode)->id;
            }
            $productId = '';
            if ($pToken) {
                if ($productsBySku->has($pToken)) {
                    $productId = (string) $productsBySku->get($pToken)->id;
                } elseif ($productsByName->has($pToken)) {
                    $productId = (string) $productsByName->get($pToken)->id;
                }
            }
            $qty = isset($row['quantity']) ? (int) $row['quantity'] : 0;
            $notes = isset($row['notes']) ? (string) $row['notes'] : null;

            if ($warehouseId && $productId && $qty > 0) {
                $this->service->issueGoods([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'notes' => $notes,
                    'created_by_id' => $this->userId,
                ]);
            }
        }
    }
}
