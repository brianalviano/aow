<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Product\ProductData;
use App\Models\Product;
use App\Traits\FileHelperTrait;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for Product business logic.
 */
class ProductService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    /**
     * Get paginated products.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $categoryId = null)
    {
        return Product::query()
            ->with(['productCategory'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('product_category_id', $categoryId);
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Store a newly created product.
     *
     * @param ProductData $data
     * @return Product
     * @throws \Throwable
     */
    public function createProduct(ProductData $data): Product
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $imagePath = $this->handleFileInput($data->image, null, 'products');

                    $product = Product::create([
                        'product_category_id' => $data->productCategoryId,
                        'name' => $data->name,
                        'description' => $data->description,
                        'price' => $data->price,
                        'image' => $imagePath,
                        'stock_limit' => $data->stockLimit,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ]);

                    foreach ($data->options as $optionData) {
                        $option = $product->productOptions()->create([
                            'name' => $optionData->name,
                            'is_required' => $optionData->isRequired,
                            'sort_order' => $optionData->sortOrder,
                        ]);

                        foreach ($optionData->items as $itemData) {
                            $option->items()->create([
                                'name' => $itemData->name,
                                'extra_price' => $itemData->extraPrice,
                                'sort_order' => $itemData->sortOrder,
                            ]);
                        }
                    }

                    return $product;
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create product', [
                    'error' => $e->getMessage(),
                    'data' => [
                        'product_category_id' => $data->productCategoryId,
                        'name' => $data->name,
                        'description' => $data->description,
                        'price' => $data->price,
                        'stock_limit' => $data->stockLimit,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                        'options_count' => count($data->options),
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update the specified product.
     *
     * @param Product $product
     * @param ProductData $data
     * @return Product
     * @throws \Throwable
     */
    public function updateProduct(Product $product, ProductData $data): Product
    {
        return $this->runWithRetry(function () use ($product, $data) {
            try {
                return DB::transaction(function () use ($product, $data) {
                    $imagePath = $this->handleFileInput($data->image, $product->image, 'products');

                    $product->update([
                        'product_category_id' => $data->productCategoryId,
                        'name' => $data->name,
                        'description' => $data->description,
                        'price' => $data->price,
                        'image' => $imagePath,
                        'stock_limit' => $data->stockLimit,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ]);

                    // Clean sync by deleting existing options and recreating them
                    $product->productOptions()->delete();

                    foreach ($data->options as $optionData) {
                        $option = $product->productOptions()->create([
                            'name' => $optionData->name,
                            'is_required' => $optionData->isRequired,
                            'sort_order' => $optionData->sortOrder,
                        ]);

                        foreach ($optionData->items as $itemData) {
                            $option->items()->create([
                                'name' => $itemData->name,
                                'extra_price' => $itemData->extraPrice,
                                'sort_order' => $itemData->sortOrder,
                            ]);
                        }
                    }

                    return $product->refresh();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update product', [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'data' => [
                        'product_category_id' => $data->productCategoryId,
                        'name' => $data->name,
                        'description' => $data->description,
                        'price' => $data->price,
                        'stock_limit' => $data->stockLimit,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                        'options_count' => count($data->options),
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete the specified product.
     *
     * @param Product $product
     * @return bool|null
     * @throws \Throwable
     */
    public function deleteProduct(Product $product): ?bool
    {
        return $this->runWithRetry(function () use ($product) {
            try {
                return DB::transaction(function () use ($product) {
                    if ($product->image) {
                        $this->deleteFile($product->image);
                    }
                    return $product->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete product', [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
