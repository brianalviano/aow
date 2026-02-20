<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Product\ProductData;
use App\Enums\{ProductType, ProductVariantType};
use App\Models\{Product, ProductCategory, ProductSubCategory, ProductUnit, ProductFactory, ProductSubFactory, ProductCondition};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\{RetryableTransactionsTrait, FileHelperTrait};

class ProductService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    public function create(ProductData $data): Product
    {
        return $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $p = new Product();
                $p->name = $data->name;
                $p->description = $data->description;
                $p->sku = $data->sku;
                $p->weight = $data->weight;
                $p->is_active = $data->isActive ?? true;
                $p->product_category_id = $data->productCategoryId;
                $p->product_sub_category_id = $data->productSubCategoryId;
                $p->product_unit_id = $data->productUnitId;
                $p->product_factory_id = $data->productFactoryId;
                $p->product_sub_factory_id = $data->productSubFactoryId;
                $p->product_condition_id = $data->productConditionId;
                $p->product_type = $this->toProductType($data->productType);
                $p->product_variant_type = $this->toProductVariantType($data->productVariantType);
                $p->parent_product_id = $data->parentProductId;
                $p->min_stock = $data->minStock ?? 0;
                $p->max_stock = $data->maxStock ?? 0;
                if ($data->image) {
                    $stored = $this->handleFileUpload($data->image, null, 'product_images');
                    $p->image = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                }
                $p->save();
                return $p;
            }, 5);
        }, 3);
    }

    public function update(Product $product, ProductData $data): Product
    {
        return $this->runWithRetry(function () use ($product, $data) {
            return DB::transaction(function () use ($product, $data) {
                $product->name = $data->name;
                $product->description = $data->description;
                $product->sku = $data->sku;
                $product->weight = $data->weight;
                $product->is_active = $data->isActive ?? $product->is_active;
                $product->product_category_id = $data->productCategoryId;
                $product->product_sub_category_id = $data->productSubCategoryId;
                $product->product_unit_id = $data->productUnitId;
                $product->product_factory_id = $data->productFactoryId;
                $product->product_sub_factory_id = $data->productSubFactoryId;
                $product->product_condition_id = $data->productConditionId;
                $product->product_type = $this->toProductType($data->productType) ?? $product->product_type;
                $product->product_variant_type = $this->toProductVariantType($data->productVariantType) ?? $product->product_variant_type;
                $product->parent_product_id = $data->parentProductId;
                $product->min_stock = $data->minStock ?? $product->min_stock;
                $product->max_stock = $data->maxStock ?? $product->max_stock;
                if ($data->image) {
                    $existingPublicPath = $product->image ? '/storage/' . ltrim((string) $product->image, '/') : null;
                    $stored = $this->handleFileUpload($data->image, $existingPublicPath, 'product_images');
                    $product->image = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                    $product->save();
                } else {
                    $product->save();
                }
                return $product;
            }, 5);
        }, 3);
    }

    public function delete(Product $product): void
    {
        $this->runWithRetry(function () use ($product) {
            return DB::transaction(function () use ($product) {
                $old = $product->image;
                $product->delete();
                if ($old) {
                    $this->deleteFile('/storage/' . ltrim((string) $old, '/'));
                }
                return null;
            }, 5);
        }, 3);
    }

    public function importBatch(array $rows): array
    {
        return $this->runWithRetry(function () use ($rows) {
            return DB::transaction(function () use ($rows) {
                $now = now();
                $validRows = [];
                foreach ($rows as $r) {
                    $name = isset($r['name']) ? trim((string) $r['name']) : '';
                    $sku = isset($r['sku']) ? trim(strtoupper((string) $r['sku'])) : '';
                    $description = isset($r['description']) ? trim((string) $r['description']) : '';
                    if ($name === '' || $sku === '') {
                        continue;
                    }
                    $validRows[] = [
                        'name' => $name,
                        'sku' => $sku,
                        'description' => $description,
                        'weight' => isset($r['weight']) ? (float) $r['weight'] : null,
                        'is_active' => isset($r['is_active']) ? (bool) $r['is_active'] : true,
                        'product_type' => isset($r['product_type']) ? (string) Str::lower((string) $r['product_type']) : null,
                        'product_variant_type' => isset($r['product_variant_type']) ? (string) Str::lower((string) $r['product_variant_type']) : null,
                        'min_stock' => isset($r['min_stock']) ? (int) $r['min_stock'] : 0,
                        'max_stock' => isset($r['max_stock']) ? (int) $r['max_stock'] : 0,
                        'category_name' => isset($r['category_name']) ? (string) $r['category_name'] : null,
                        'sub_category_name' => isset($r['sub_category_name']) ? (string) $r['sub_category_name'] : null,
                        'unit_code' => isset($r['unit_code']) ? (string) $r['unit_code'] : null,
                        'factory_name' => isset($r['factory_name']) ? (string) $r['factory_name'] : null,
                        'sub_factory_name' => isset($r['sub_factory_name']) ? (string) $r['sub_factory_name'] : null,
                        'condition_name' => isset($r['condition_name']) ? (string) $r['condition_name'] : null,
                        'parent_sku' => isset($r['parent_sku']) ? (string) $r['parent_sku'] : null,
                        'image_url' => isset($r['image_url']) ? (string) $r['image_url'] : null,
                    ];
                }

                if (count($validRows) === 0) {
                    return ['created' => 0, 'updated' => 0, 'failed' => count($rows)];
                }

                $skus = array_map(fn($x) => strtoupper($x['sku']), $validRows);
                $existing = Product::query()
                    ->whereIn('sku', $skus)
                    ->get(['id', 'sku'])
                    ->keyBy(function ($p) {
                        return Str::upper((string) $p->sku);
                    });

                $categoryNames = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['category_name'] ? Str::lower(trim((string) $x['category_name'])) : null;
                }, $validRows))));
                $categories = ProductCategory::query()
                    ->get(['id', 'name'])
                    ->keyBy(function ($c) {
                        return Str::lower((string) $c->name);
                    });

                $subCategoryNames = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['sub_category_name'] ? Str::lower(trim((string) $x['sub_category_name'])) : null;
                }, $validRows))));
                $subCategories = ProductSubCategory::query()
                    ->get(['id', 'name'])
                    ->keyBy(function ($sc) {
                        return Str::lower((string) $sc->name);
                    });

                $unitCodes = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['unit_code'] ? Str::lower(trim((string) $x['unit_code'])) : null;
                }, $validRows))));
                $units = ProductUnit::query()
                    ->get(['id', 'code'])
                    ->keyBy(function ($u) {
                        return Str::lower((string) $u->code);
                    });

                $factoryNames = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['factory_name'] ? Str::lower(trim((string) $x['factory_name'])) : null;
                }, $validRows))));
                $factories = ProductFactory::query()
                    ->get(['id', 'name'])
                    ->keyBy(function ($f) {
                        return Str::lower((string) $f->name);
                    });

                $subFactoryNames = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['sub_factory_name'] ? Str::lower(trim((string) $x['sub_factory_name'])) : null;
                }, $validRows))));
                $subFactories = ProductSubFactory::query()
                    ->get(['id', 'name'])
                    ->keyBy(function ($sf) {
                        return Str::lower((string) $sf->name);
                    });

                $conditionNames = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['condition_name'] ? Str::lower(trim((string) $x['condition_name'])) : null;
                }, $validRows))));
                $conditions = ProductCondition::query()
                    ->get(['id', 'name'])
                    ->keyBy(function ($c) {
                        return Str::lower((string) $c->name);
                    });

                $parentSkus = array_values(array_unique(array_filter(array_map(function ($x) {
                    return $x['parent_sku'] ? Str::upper(trim((string) $x['parent_sku'])) : null;
                }, $validRows))));
                $parents = Product::query()
                    ->whereIn('sku', $parentSkus)
                    ->get(['id', 'sku'])
                    ->keyBy(function ($p) {
                        return Str::upper((string) $p->sku);
                    });

                foreach ($validRows as $r) {
                    $categoryId = null;
                    if (!empty($r['category_name'])) {
                        $key = Str::lower(trim((string) $r['category_name']));
                        if ($categories->has($key)) {
                            $categoryId = (string) $categories->get($key)->id;
                        }
                    }
                    $subCategoryId = null;
                    if (!empty($r['sub_category_name'])) {
                        $key = Str::lower(trim((string) $r['sub_category_name']));
                        if ($subCategories->has($key)) {
                            $subCategoryId = (string) $subCategories->get($key)->id;
                        }
                    }
                    $unitId = null;
                    if (!empty($r['unit_code'])) {
                        $key = Str::lower(trim((string) $r['unit_code']));
                        if ($units->has($key)) {
                            $unitId = (string) $units->get($key)->id;
                        }
                    }
                    $factoryId = null;
                    if (!empty($r['factory_name'])) {
                        $key = Str::lower(trim((string) $r['factory_name']));
                        if ($factories->has($key)) {
                            $factoryId = (string) $factories->get($key)->id;
                        }
                    }
                    $subFactoryId = null;
                    if (!empty($r['sub_factory_name'])) {
                        $key = Str::lower(trim((string) $r['sub_factory_name']));
                        if ($subFactories->has($key)) {
                            $subFactoryId = (string) $subFactories->get($key)->id;
                        }
                    }
                    $conditionId = null;
                    if (!empty($r['condition_name'])) {
                        $key = Str::lower(trim((string) $r['condition_name']));
                        if ($conditions->has($key)) {
                            $conditionId = (string) $conditions->get($key)->id;
                        }
                    }
                    $parentId = null;
                    if (!empty($r['parent_sku'])) {
                        $key = Str::upper(trim((string) $r['parent_sku']));
                        if ($parents->has($key)) {
                            $parentId = (string) $parents->get($key)->id;
                        }
                    }

                    $image = $r['image_url'] ?? null;
                    $ptype = !empty($r['product_type']) ? ProductType::tryFrom(Str::lower(trim((string) $r['product_type']))) : null;
                    $vtype = !empty($r['product_variant_type']) ? ProductVariantType::tryFrom(Str::lower(trim((string) $r['product_variant_type']))) : null;
                    Product::query()->updateOrCreate(
                        ['sku' => Str::upper($r['sku'])],
                        [
                            'name' => $r['name'],
                            'description' => $r['description'],
                            'image' => $image,
                            'weight' => $r['weight'],
                            'is_active' => $r['is_active'],
                            'product_category_id' => $categoryId,
                            'product_sub_category_id' => $subCategoryId,
                            'product_unit_id' => $unitId,
                            'product_factory_id' => $factoryId,
                            'product_sub_factory_id' => $subFactoryId,
                            'product_condition_id' => $conditionId,
                            'product_type' => $ptype?->value,
                            'product_variant_type' => $vtype?->value,
                            'parent_product_id' => $parentId,
                            'min_stock' => $r['min_stock'],
                            'max_stock' => $r['max_stock'],
                        ],
                    );
                }

                $created = 0;
                $updated = 0;
                foreach ($validRows as $r) {
                    if ($existing->has(Str::upper($r['sku']))) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
                $failed = count($rows) - count($validRows);

                return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
            }, 5);
        }, 3);
    }

    private function toProductType(?string $value): ?ProductType
    {
        if ($value === null || $value === '') {
            return null;
        }
        return ProductType::tryFrom(Str::lower(trim($value)));
    }

    private function toProductVariantType(?string $value): ?ProductVariantType
    {
        if ($value === null || $value === '') {
            return null;
        }
        return ProductVariantType::tryFrom(Str::lower(trim($value)));
    }
}
