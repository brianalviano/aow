<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ProductCategory\ProductCategoryData;
use App\Models\ProductCategory;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for ProductCategory business logic.
 */
class ProductCategoryService
{
    use RetryableTransactionsTrait;

    /**
     * Get paginated product categories.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null)
    {
        return ProductCategory::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Store a newly created product category.
     *
     * @param ProductCategoryData $data
     * @return ProductCategory
     * @throws \Throwable
     */
    public function createProductCategory(ProductCategoryData $data): ProductCategory
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    return ProductCategory::create([
                        'name' => $data->name,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create product category', [
                    'error' => $e->getMessage(),
                    'data' => [
                        'name' => $data->name,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update the specified product category.
     *
     * @param ProductCategory $productCategory
     * @param ProductCategoryData $data
     * @return ProductCategory
     * @throws \Throwable
     */
    public function updateProductCategory(ProductCategory $productCategory, ProductCategoryData $data): ProductCategory
    {
        return $this->runWithRetry(function () use ($productCategory, $data) {
            try {
                return DB::transaction(function () use ($productCategory, $data) {
                    $productCategory->update([
                        'name' => $data->name,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ]);

                    return $productCategory->refresh();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update product category', [
                    'error' => $e->getMessage(),
                    'product_category_id' => $productCategory->id,
                    'data' => [
                        'name' => $data->name,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete the specified product category.
     *
     * @param ProductCategory $productCategory
     * @return bool|null
     * @throws \Throwable
     */
    public function deleteProductCategory(ProductCategory $productCategory): ?bool
    {
        return $this->runWithRetry(function () use ($productCategory) {
            try {
                return DB::transaction(function () use ($productCategory) {
                    return $productCategory->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete product category', [
                    'error' => $e->getMessage(),
                    'product_category_id' => $productCategory->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
