<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ProductPrice\ProductPriceBatchData;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{Product, SellingPriceLevel, Supplier, ProductPurchasePrice, ProductSellingPrice, ProductSupplier};
use Carbon\Carbon;


/**
 * Layanan untuk membangun matriks harga dan menyimpan perubahan harga.
 */
class ProductPriceService
{
    use RetryableTransactionsTrait;

    /**
     * Ambil harga beli utama untuk sebuah produk.
     *
     * @param string $productId
     * @return int
     */
    public function getPurchasePrice(string $productId): int
    {
        $price = ProductPurchasePrice::query()
            ->where('product_id', $productId)
            ->value('price');
        return $price ? (int) $price : 0;
    }

    /**
     * Ambil peta harga jual utama (tanpa level) untuk daftar produk.
     *
     * @param array $productIds
     * @return array<string,int>
     */
    public function getSellingPriceMainMap(array $productIds): array
    {
        try {
            if (empty($productIds)) {
                return [];
            }
            $rows = ProductSellingPrice::query()
                ->whereIn('product_id', $productIds)
                ->whereNull('selling_price_level_id')
                ->get(['product_id', 'price']);
            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row->product_id] = (int) $row->price;
            }
            return $map;
        } catch (\Throwable $e) {
            Log::error('ProductPriceService.getSellingPriceMainMap failed', [
                'error' => $e->getMessage(),
                'product_ids' => $productIds,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Ambil peta harga jual per level untuk daftar produk.
     *
     * @param array $productIds
     * @return array<string,array<string,int>>
     */
    public function getSellingPriceMap(array $productIds): array
    {
        try {
            if (empty($productIds)) {
                return [];
            }
            $rows = ProductSellingPrice::query()
                ->whereIn('product_id', $productIds)
                ->whereNotNull('selling_price_level_id')
                ->get(['product_id', 'selling_price_level_id', 'price']);
            $map = [];
            foreach ($rows as $row) {
                $pid = (string) $row->product_id;
                $lid = (string) $row->selling_price_level_id;
                if (!isset($map[$pid])) {
                    $map[$pid] = [];
                }
                $map[$pid][$lid] = (int) $row->price;
            }
            return $map;
        } catch (\Throwable $e) {
            Log::error('ProductPriceService.getSellingPriceMap failed', [
                'error' => $e->getMessage(),
                'product_ids' => $productIds,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Bangun matriks harga untuk daftar produk.
     *
     * @param string $q Pencarian produk (nama/sku).
     * @param int $perPage Jumlah produk per halaman.
     * @return array
     */
    public function buildMatrix(string $q, int $perPage = 15): array
    {
        $productsQuery = Product::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');
        $paginator = $productsQuery->paginate($perPage)->withQueryString();
        $products = collect($paginator->items());

        $levels = SellingPriceLevel::query()
            ->orderBy('name')
            ->get(['id', 'name', 'percent_adjust']);

        $productIds = $products->pluck('id')->map(fn($id) => (string) $id)->all();

        $purchaseRows = ProductPurchasePrice::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'price']);
        $purchaseMap = [];
        foreach ($purchaseRows as $row) {
            $purchaseMap[(string) $row->product_id] = (int) $row->price;
        }

        $sellingRows = ProductSellingPrice::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'selling_price_level_id', 'price']);
        $sellingMap = [];
        $sellingMainMap = [];
        foreach ($sellingRows as $row) {
            $pid = (string) $row->product_id;
            $lid = $row->selling_price_level_id;
            if ($lid === null) {
                $sellingMainMap[$pid] = (int) $row->price;
                continue;
            }
            $lidStr = (string) $lid;
            $sellingMap[$pid] ??= [];
            $sellingMap[$pid][$lidStr] = (int) $row->price;
        }

        $supplierRows = ProductSupplier::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'supplier_id', 'price']);
        $supplierPriceMap = [];
        $supplierIds = [];
        foreach ($supplierRows as $row) {
            $pid = (string) $row->product_id;
            $sid = (string) $row->supplier_id;
            $supplierPriceMap[$pid] ??= [];
            $supplierPriceMap[$pid][$sid] = (int) $row->price;
            $supplierIds[] = $sid;
        }
        $supplierIds = array_values(array_unique($supplierIds));
        $suppliersCol = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'percent_adjust']);

        return [
            'products' => $products->map(fn($p) => [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
            ])->all(),
            'levels' => $levels->map(fn($l) => [
                'id' => (string) $l->id,
                'name' => (string) $l->name,
                'percent_adjust' => $l->percent_adjust !== null ? (float) $l->percent_adjust : null,
            ])->all(),
            'purchasePriceMap' => $purchaseMap,
            'sellingPriceMap' => $sellingMap,
            'sellingPriceMainMap' => $sellingMainMap,
            'suppliers' => $suppliersCol->map(fn($s) => [
                'id' => (string) $s->id,
                'name' => (string) $s->name,
                'percent_adjust' => $s->percent_adjust !== null ? (float) $s->percent_adjust : null,
            ])->all(),
            'supplierPriceMap' => $supplierPriceMap,
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'last_page' => (int) $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Simpan perubahan harga dalam batch.
     *
     * @param ProductPriceBatchData $data
     * @return void
     */
    public function saveBatch(ProductPriceBatchData $data): void
    {
        $now = Carbon::now();
        $purchaseUpserts = [];
        $purchaseDeletes = [];
        $sellingUpserts = [];
        $sellingDeletes = [];
        $supplierUpserts = [];
        $supplierDeletes = [];
        $changedSellingMainProductIds = [];
        $changedPurchaseProductIds = [];

        foreach ($data->entries as $e) {
            if ($e->type === 'purchase') {
                if ($e->price === null) {
                    $purchaseDeletes[] = $e->productId;
                } else {
                    $purchaseUpserts[] = [
                        'product_id' => $e->productId,
                        'price' => $e->price,
                    ];
                    $changedPurchaseProductIds[] = (string) $e->productId;
                }
            } elseif ($e->type === 'selling') {
                if ($e->levelId === null) {
                    if ($e->price === null) {
                        $sellingDeletes[] = [$e->productId, null];
                    } else {
                        $sellingUpserts[] = [
                            'product_id' => $e->productId,
                            'selling_price_level_id' => null,
                            'price' => $e->price,
                        ];
                        $changedSellingMainProductIds[] = (string) $e->productId;
                    }
                } else {
                    if ($e->price === null) {
                        $sellingDeletes[] = [$e->productId, $e->levelId];
                    } else {
                        $sellingUpserts[] = [
                            'product_id' => $e->productId,
                            'selling_price_level_id' => $e->levelId,
                            'price' => $e->price,
                        ];
                    }
                }
            } elseif ($e->type === 'supplier') {
                if ($e->supplierId === null) {
                    continue;
                }
                if ($e->price === null) {
                    $supplierDeletes[] = [$e->productId, $e->supplierId];
                } else {
                    $supplierUpserts[] = [
                        'product_id' => $e->productId,
                        'supplier_id' => $e->supplierId,
                        'price' => $e->price,
                    ];
                }
            }
        }
        $changedSellingMainProductIds = array_values(array_unique($changedSellingMainProductIds));
        $changedPurchaseProductIds = array_values(array_unique($changedPurchaseProductIds));

        $this->runWithRetry(function () use ($purchaseUpserts, $purchaseDeletes, $sellingUpserts, $sellingDeletes, $supplierUpserts, $supplierDeletes, $changedSellingMainProductIds, $changedPurchaseProductIds, $now) {
            return DB::transaction(function () use ($purchaseUpserts, $purchaseDeletes, $sellingUpserts, $sellingDeletes, $supplierUpserts, $supplierDeletes, $changedSellingMainProductIds, $changedPurchaseProductIds, $now) {
                if (!empty($purchaseUpserts) || !empty($purchaseDeletes)) {
                    $purchaseUpsertIds = !empty($purchaseUpserts)
                        ? array_values(array_unique(array_map(fn($r) => (string) $r['product_id'], $purchaseUpserts)))
                        : [];
                    $purchaseDeleteIds = !empty($purchaseDeletes) ? array_values(array_unique($purchaseDeletes)) : [];
                    $allPurchaseIds = array_values(array_unique(array_merge($purchaseUpsertIds, $purchaseDeleteIds)));
                    if (!empty($allPurchaseIds)) {
                        ProductPurchasePrice::query()
                            ->whereIn('product_id', $allPurchaseIds)
                            ->delete();
                    }
                    if (!empty($purchaseUpserts)) {
                        foreach ($purchaseUpserts as $r) {
                            ProductPurchasePrice::create([
                                'product_id' => $r['product_id'],
                                'price' => $r['price'],
                            ]);
                        }
                    }
                }

                if (!empty($sellingUpserts) || !empty($sellingDeletes)) {
                    $sellingPairsForDelete = [];
                    if (!empty($sellingUpserts)) {
                        foreach ($sellingUpserts as $r) {
                            $sellingPairsForDelete[] = [
                                (string) $r['product_id'],
                                $r['selling_price_level_id'] === null ? null : (string) $r['selling_price_level_id'],
                            ];
                        }
                    }
                    if (!empty($sellingDeletes)) {
                        foreach ($sellingDeletes as [$pid, $lid]) {
                            $sellingPairsForDelete[] = [
                                (string) $pid,
                                $lid === null ? null : (string) $lid,
                            ];
                        }
                    }
                    if (!empty($sellingPairsForDelete)) {
                        ProductSellingPrice::query()
                            ->where(function ($q) use ($sellingPairsForDelete) {
                                foreach ($sellingPairsForDelete as [$pid, $lid]) {
                                    $q->orWhere(function ($w) use ($pid, $lid) {
                                        $w->where('product_id', $pid);
                                        if ($lid === null) {
                                            $w->whereNull('selling_price_level_id');
                                        } else {
                                            $w->where('selling_price_level_id', $lid);
                                        }
                                    });
                                }
                            })
                            ->delete();
                    }
                    if (!empty($sellingUpserts)) {
                        foreach ($sellingUpserts as $r) {
                            ProductSellingPrice::create([
                                'product_id' => $r['product_id'],
                                'selling_price_level_id' => $r['selling_price_level_id'],
                                'price' => $r['price'],
                            ]);
                        }
                    }
                }

                if (!empty($supplierUpserts) || !empty($supplierDeletes)) {
                    $supplierPairsForDelete = [];
                    if (!empty($supplierUpserts)) {
                        foreach ($supplierUpserts as $r) {
                            $supplierPairsForDelete[] = [(string) $r['product_id'], (string) $r['supplier_id']];
                        }
                    }
                    if (!empty($supplierDeletes)) {
                        foreach ($supplierDeletes as [$pid, $sid]) {
                            $supplierPairsForDelete[] = [(string) $pid, (string) $sid];
                        }
                    }
                    if (!empty($supplierPairsForDelete)) {
                        ProductSupplier::query()
                            ->where(function ($q) use ($supplierPairsForDelete) {
                                foreach ($supplierPairsForDelete as [$pid, $sid]) {
                                    $q->orWhere(function ($w) use ($pid, $sid) {
                                        $w->where('product_id', $pid)
                                            ->where('supplier_id', $sid);
                                    });
                                }
                            })
                            ->delete();
                    }
                    if (!empty($supplierUpserts)) {
                        foreach ($supplierUpserts as $r) {
                            ProductSupplier::create([
                                'product_id' => $r['product_id'],
                                'supplier_id' => $r['supplier_id'],
                                'price' => $r['price'],
                            ]);
                        }
                    }
                }
                if (!empty($changedSellingMainProductIds)) {
                    $mainPrices = ProductSellingPrice::query()
                        ->whereIn('product_id', $changedSellingMainProductIds)
                        ->whereNull('selling_price_level_id')
                        ->get(['product_id', 'price'])
                        ->keyBy(fn($row) => (string) $row->product_id);
                    $levels = SellingPriceLevel::query()
                        ->whereNotNull('percent_adjust')
                        ->get(['id', 'percent_adjust']);
                    $existingDerived = ProductSellingPrice::query()
                        ->whereIn('product_id', $changedSellingMainProductIds)
                        ->whereNotNull('selling_price_level_id')
                        ->get(['id', 'product_id', 'selling_price_level_id'])
                        ->keyBy(function ($row) {
                            return (string) $row->product_id . '|' . (string) $row->selling_price_level_id;
                        });
                    $toInsert = [];
                    $toUpdate = [];
                    foreach ($changedSellingMainProductIds as $pid) {
                        $pidStr = (string) $pid;
                        $base = $mainPrices->get($pidStr);
                        if (!$base) {
                            continue;
                        }
                        $basePrice = (int) $base->price;
                        foreach ($levels as $lvl) {
                            $lvlId = (string) $lvl->id;
                            $pct = $lvl->percent_adjust !== null ? (float) $lvl->percent_adjust : null;
                            if ($pct === null) {
                                continue;
                            }
                            $newPrice = (int) round($basePrice * (1 + $pct / 100.0));
                            $key = $pidStr . '|' . $lvlId;
                            if ($existingDerived->has($key)) {
                                $toUpdate[] = [
                                    'id' => (string) $existingDerived->get($key)->id,
                                    'price' => $newPrice,
                                    'updated_at' => $now,
                                ];
                            } else {
                                $toInsert[] = [
                                    'product_id' => $pidStr,
                                    'selling_price_level_id' => $lvlId,
                                    'price' => $newPrice,
                                ];
                            }
                        }
                    }
                    if (!empty($toInsert)) {
                        foreach ($toInsert as $r) {
                            ProductSellingPrice::create([
                                'product_id' => $r['product_id'],
                                'selling_price_level_id' => $r['selling_price_level_id'],
                                'price' => $r['price'],
                            ]);
                        }
                    }
                    if (!empty($toUpdate)) {
                        foreach ($toUpdate as $u) {
                            ProductSellingPrice::query()
                                ->where('id', $u['id'])
                                ->update(['price' => $u['price'], 'updated_at' => $u['updated_at']]);
                        }
                    }
                }
                if (!empty($changedPurchaseProductIds)) {
                    $mainPurchases = ProductPurchasePrice::query()
                        ->whereIn('product_id', $changedPurchaseProductIds)
                        ->get(['product_id', 'price'])
                        ->keyBy(fn($row) => (string) $row->product_id);
                    $suppliers = Supplier::query()
                        ->whereNotNull('percent_adjust')
                        ->get(['id', 'percent_adjust']);
                    $existingSup = ProductSupplier::query()
                        ->whereIn('product_id', $changedPurchaseProductIds)
                        ->get(['id', 'product_id', 'supplier_id'])
                        ->keyBy(function ($row) {
                            return (string) $row->product_id . '|' . (string) $row->supplier_id;
                        });
                    $toInsert2 = [];
                    $toUpdate2 = [];
                    foreach ($changedPurchaseProductIds as $pid) {
                        $pidStr = (string) $pid;
                        $base = $mainPurchases->get($pidStr);
                        if (!$base) {
                            continue;
                        }
                        $basePrice = (int) $base->price;
                        foreach ($suppliers as $s) {
                            $sid = (string) $s->id;
                            $pct = $s->percent_adjust !== null ? (float) $s->percent_adjust : null;
                            if ($pct === null) {
                                continue;
                            }
                            $newPrice = (int) round($basePrice * (1 + $pct / 100.0));
                            $key = $pidStr . '|' . $sid;
                            if ($existingSup->has($key)) {
                                $toUpdate2[] = [
                                    'id' => (string) $existingSup->get($key)->id,
                                    'price' => $newPrice,
                                    'updated_at' => $now,
                                ];
                            } else {
                                $toInsert2[] = [
                                    'product_id' => $pidStr,
                                    'supplier_id' => $sid,
                                    'price' => $newPrice,
                                ];
                            }
                        }
                    }
                    if (!empty($toInsert2)) {
                        foreach ($toInsert2 as $r) {
                            ProductSupplier::create([
                                'product_id' => $r['product_id'],
                                'supplier_id' => $r['supplier_id'],
                                'price' => $r['price'],
                            ]);
                        }
                    }
                    if (!empty($toUpdate2)) {
                        foreach ($toUpdate2 as $u) {
                            ProductSupplier::query()
                                ->where('id', $u['id'])
                                ->update(['price' => $u['price'], 'updated_at' => $u['updated_at']]);
                        }
                    }
                }
                return null;
            }, 3);
        }, 3);
    }

    public function deleteLevel(string $levelId): void
    {
        $this->runWithRetry(function () use ($levelId) {
            return DB::transaction(function () use ($levelId) {
                ProductSellingPrice::query()
                    ->where('selling_price_level_id', $levelId)
                    ->delete();
                SellingPriceLevel::query()
                    ->where('id', $levelId)
                    ->delete();
                return null;
            }, 3);
        }, 3);
    }

    public function adjustSellingLevelPrices(string $levelId, float $percent): void
    {
        $delta = $percent / 100.0;
        $now = Carbon::now();
        $this->runWithRetry(function () use ($levelId, $delta, $now, $percent) {
            return DB::transaction(function () use ($levelId, $delta, $now, $percent) {
                $rows = ProductSellingPrice::query()
                    ->where('selling_price_level_id', $levelId)
                    ->get(['id', 'price']);
                foreach ($rows as $r) {
                    $newPrice = (int) round(((int) $r->price) * (1 + $delta));
                    ProductSellingPrice::query()
                        ->where('id', (string) $r->id)
                        ->update(['price' => $newPrice, 'updated_at' => $now]);
                }
                SellingPriceLevel::query()
                    ->where('id', $levelId)
                    ->update(['percent_adjust' => $percent, 'updated_at' => $now]);
                return null;
            }, 3);
        }, 3);
    }

    public function adjustSupplierPrices(string $supplierId, float $percent): void
    {
        $delta = $percent / 100.0;
        $now = Carbon::now();
        $this->runWithRetry(function () use ($supplierId, $delta, $now, $percent) {
            return DB::transaction(function () use ($supplierId, $delta, $now, $percent) {
                $rows = ProductSupplier::query()
                    ->where('supplier_id', $supplierId)
                    ->get(['id', 'price']);
                foreach ($rows as $r) {
                    $newPrice = (int) round(((int) $r->price) * (1 + $delta));
                    ProductSupplier::query()
                        ->where('id', (string) $r->id)
                        ->update(['price' => $newPrice, 'updated_at' => $now]);
                }
                Supplier::query()
                    ->where('id', $supplierId)
                    ->update(['percent_adjust' => $percent, 'updated_at' => $now]);
                return null;
            }, 3);
        }, 3);
    }

    public function adjustSellingLevelPricesFromMain(string $levelId, float $percent): void
    {
        $delta = $percent / 100.0;
        $now = Carbon::now();
        $this->runWithRetry(function () use ($levelId, $delta, $now, $percent) {
            return DB::transaction(function () use ($levelId, $delta, $now, $percent) {
                $mainPrices = ProductSellingPrice::query()
                    ->whereNull('selling_price_level_id')
                    ->get(['product_id', 'price'])
                    ->keyBy(fn($row) => (string) $row->product_id);
                $existingDerived = ProductSellingPrice::query()
                    ->where('selling_price_level_id', $levelId)
                    ->get(['id', 'product_id']);
                $existingMap = [];
                foreach ($existingDerived as $row) {
                    $existingMap[(string) $row->product_id] = (string) $row->id;
                }
                $toInsert = [];
                foreach ($mainPrices as $pid => $row) {
                    $newPrice = (int) round(((int) $row->price) * (1 + $delta));
                    if (isset($existingMap[$pid])) {
                        ProductSellingPrice::query()
                            ->where('id', $existingMap[$pid])
                            ->update(['price' => $newPrice, 'updated_at' => $now]);
                    } else {
                        $toInsert[] = [
                            'product_id' => (string) $pid,
                            'selling_price_level_id' => (string) $levelId,
                            'price' => $newPrice,
                        ];
                    }
                }
                if (!empty($toInsert)) {
                    foreach ($toInsert as $r) {
                        ProductSellingPrice::create([
                            'product_id' => $r['product_id'],
                            'selling_price_level_id' => $r['selling_price_level_id'],
                            'price' => $r['price'],
                        ]);
                    }
                }
                SellingPriceLevel::query()
                    ->where('id', $levelId)
                    ->update(['percent_adjust' => $percent, 'updated_at' => $now]);
                return null;
            }, 3);
        }, 3);
    }

    public function adjustSupplierPricesFromMain(string $supplierId, float $percent): void
    {
        $delta = $percent / 100.0;
        $now = Carbon::now();
        $this->runWithRetry(function () use ($supplierId, $delta, $now, $percent) {
            return DB::transaction(function () use ($supplierId, $delta, $now, $percent) {
                $mainPurchases = ProductPurchasePrice::query()
                    ->get(['product_id', 'price'])
                    ->keyBy(fn($row) => (string) $row->product_id);
                $existing = ProductSupplier::query()
                    ->where('supplier_id', $supplierId)
                    ->get(['id', 'product_id']);
                $existingMap = [];
                foreach ($existing as $row) {
                    $existingMap[(string) $row->product_id] = (string) $row->id;
                }
                $toInsert = [];
                foreach ($mainPurchases as $pid => $row) {
                    $newPrice = (int) round(((int) $row->price) * (1 + $delta));
                    if (isset($existingMap[$pid])) {
                        ProductSupplier::query()
                            ->where('id', $existingMap[$pid])
                            ->update(['price' => $newPrice, 'updated_at' => $now]);
                    } else {
                        $toInsert[] = [
                            'product_id' => (string) $pid,
                            'supplier_id' => (string) $supplierId,
                            'price' => $newPrice,
                        ];
                    }
                }
                if (!empty($toInsert)) {
                    foreach ($toInsert as $r) {
                        ProductSupplier::create([
                            'product_id' => $r['product_id'],
                            'supplier_id' => $r['supplier_id'],
                            'price' => $r['price'],
                        ]);
                    }
                }
                Supplier::query()
                    ->where('id', $supplierId)
                    ->update(['percent_adjust' => $percent, 'updated_at' => $now]);
                return null;
            }, 3);
        }, 3);
    }
}
