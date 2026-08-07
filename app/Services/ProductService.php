<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Product\ProductData;
use App\Models\Chef;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\FileHelperTrait;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for Product business logic.
 */
class ProductService
{
    use FileHelperTrait, RetryableTransactionsTrait;

    /**
     * Get paginated products.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $categoryId = null)
    {
        return Product::query()
            ->with(['productCategory', 'manipulation'])
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
                        'cost_price' => $data->costPrice,
                        'image' => $imagePath,
                        'stock_limit' => $data->stockLimit,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ]);

                    foreach ($data->options as $optionData) {
                        $option = $product->productOptions()->create([
                            'name' => $optionData->name,
                            'is_required' => $optionData->isRequired,
                            'is_multiple' => $optionData->isMultiple,
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

                    $product->manipulation()->create([
                        'fake_sales_count' => $data->fakeSalesCount,
                        'fake_testimonials_count' => $data->fakeTestimonialsCount,
                        'is_active' => $data->isManipulationActive,
                    ]);

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
                        'cost_price' => $data->costPrice,
                        'image' => $imagePath,
                        'stock_limit' => $data->stockLimit,
                        'is_active' => $data->isActive,
                        'sort_order' => $data->sortOrder,
                    ]);

                    // Smart sync options
                    $existingOptions = $product->productOptions()->with('items')->get()->keyBy('id');
                    $keepOptionIds = [];

                    foreach ($data->options as $optionData) {
                        $optionId = $optionData->id;
                        $option = null;

                        if ($optionId && $existingOptions->has($optionId)) {
                            $option = $existingOptions->get($optionId);
                            $option->update([
                                'name' => $optionData->name,
                                'is_required' => $optionData->isRequired,
                                'is_multiple' => $optionData->isMultiple,
                                'sort_order' => $optionData->sortOrder,
                            ]);
                        } else {
                            $option = $product->productOptions()->create([
                                'name' => $optionData->name,
                                'is_required' => $optionData->isRequired,
                                'is_multiple' => $optionData->isMultiple,
                                'sort_order' => $optionData->sortOrder,
                            ]);
                        }

                        $keepOptionIds[] = $option->id;

                        // Reconcile items for this option
                        $existingItems = $option->items->keyBy('id');
                        $keepItemIds = [];

                        foreach ($optionData->items as $itemData) {
                            $itemId = $itemData->id;
                            $item = null;

                            if ($itemId && $existingItems->has($itemId)) {
                                $item = $existingItems->get($itemId);
                                $item->update([
                                    'name' => $itemData->name,
                                    'extra_price' => $itemData->extraPrice,
                                    'sort_order' => $itemData->sortOrder,
                                ]);
                            } else {
                                $item = $option->items()->create([
                                    'name' => $itemData->name,
                                    'extra_price' => $itemData->extraPrice,
                                    'sort_order' => $itemData->sortOrder,
                                ]);
                            }

                            $keepItemIds[] = $item->id;
                        }

                        // Delete removed items
                        foreach ($existingItems as $existingItemId => $existingItem) {
                            if (! in_array($existingItemId, $keepItemIds)) {
                                try {
                                    $existingItem->delete();
                                } catch (QueryException $e) {
                                    if ($e->getCode() === '23503') {
                                        throw new \RuntimeException(
                                            "Gagal menghapus item opsi '{$existingItem->name}' karena sudah digunakan dalam riwayat pesanan."
                                        );
                                    }
                                    throw $e;
                                }
                            }
                        }
                    }

                    // Delete removed options
                    foreach ($existingOptions as $existingOptionId => $existingOption) {
                        if (! in_array($existingOptionId, $keepOptionIds)) {
                            try {
                                $existingOption->items()->delete();
                                $existingOption->delete();
                            } catch (QueryException $e) {
                                if ($e->getCode() === '23503') {
                                    throw new \RuntimeException(
                                        "Gagal menghapus opsi '{$existingOption->name}' karena sudah digunakan dalam riwayat pesanan."
                                    );
                                }
                                throw $e;
                            }
                        }
                    }

                    $product->manipulation()->updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'fake_sales_count' => $data->fakeSalesCount,
                            'fake_testimonials_count' => $data->fakeTestimonialsCount,
                            'is_active' => $data->isManipulationActive,
                        ]
                    );

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

    /**
     * Get detailed transaction report for a specific product.
     */
    public function getProductTransactions(Product $product, array $filters = []): array
    {
        $query = OrderItem::query()
            ->with([
                'order:id,number,order_status,payment_status,delivery_date,created_at,customer_id,drop_point_id',
                'order.customer:id,name,email',
                'order.dropPoint:id,name',
                'chef:id,name,business_name',
                'options.productOption',
                'options.productOptionItem',
            ])
            ->where('product_id', $product->id)
            ->whereHas('order', function ($q) use ($filters) {
                $q->whereNull('deleted_at');
                if (! empty($filters['date_from'])) {
                    $q->whereDate('created_at', '>=', $filters['date_from']);
                }
                if (! empty($filters['date_to'])) {
                    $q->whereDate('created_at', '<=', $filters['date_to']);
                }
                if (! empty($filters['drop_point_id'])) {
                    $q->where('drop_point_id', $filters['drop_point_id']);
                }
                if (! empty($filters['status'])) {
                    $q->where('order_status', $filters['status']);
                }
            })
            ->when($filters['chef_id'] ?? null, function ($q, $chefId) {
                $q->where('chef_id', $chefId);
            });

        // Calculate summary
        $allItems = (clone $query)->get();
        $totalOrders = $allItems->pluck('order_id')->unique()->count();
        $totalQuantity = (int) $allItems->sum('quantity');
        $totalRevenue = (int) $allItems->sum('subtotal');
        $avgQtyPerOrder = $totalOrders > 0 ? round($totalQuantity / $totalOrders, 1) : 0;

        // Chef breakdown with percentage
        $chefBreakdown = $allItems->groupBy(fn ($item) => $item->chef_id ?? 'no_chef')
            ->map(function ($items, $chefId) use ($totalRevenue) {
                $chef = $items->first()?->chef;
                $chefRevenue = (int) $items->sum('subtotal');
                $percentage = $totalRevenue > 0 ? round(($chefRevenue / $totalRevenue) * 100, 1) : 0;

                return [
                    'chef_id' => $chefId === 'no_chef' ? null : $chefId,
                    'chef_name' => $chef?->name ?? 'Belum Diassign',
                    'business_name' => $chef?->business_name,
                    'total_orders' => $items->pluck('order_id')->unique()->count(),
                    'total_quantity' => (int) $items->sum('quantity'),
                    'total_revenue' => $chefRevenue,
                    'percentage' => $percentage,
                ];
            })
            ->values()
            ->sortByDesc('total_quantity')
            ->values();

        // Variant / Ukuran Kemasan breakdown with percentage
        $variantBreakdownMap = [];
        foreach ($allItems as $item) {
            $optionsParts = [];
            foreach ($item->options as $opt) {
                if ($opt->productOption && $opt->productOptionItem) {
                    $optionsParts[] = $opt->productOption->name.': '.$opt->productOptionItem->name;
                }
            }
            sort($optionsParts);
            $variantLabel = ! empty($optionsParts) ? implode(', ', $optionsParts) : 'Kemasan Reguler / Standar';

            if (! isset($variantBreakdownMap[$variantLabel])) {
                $variantBreakdownMap[$variantLabel] = [
                    'variant_name' => $variantLabel,
                    'total_orders' => 0,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                ];
            }

            $variantBreakdownMap[$variantLabel]['total_orders'] += 1;
            $variantBreakdownMap[$variantLabel]['total_quantity'] += $item->quantity;
            $variantBreakdownMap[$variantLabel]['total_revenue'] += $item->subtotal;
        }

        $variantBreakdown = array_map(function ($vb) use ($totalQuantity) {
            $vb['percentage'] = $totalQuantity > 0 ? round(($vb['total_quantity'] / $totalQuantity) * 100, 1) : 0;

            return $vb;
        }, array_values($variantBreakdownMap));

        usort($variantBreakdown, fn ($a, $b) => $b['total_quantity'] <=> $a['total_quantity']);

        $paginatedItems = $query->orderByDesc('created_at')->paginate($filters['per_page'] ?? 15);

        return [
            'summary' => [
                'total_orders' => $totalOrders,
                'total_quantity' => $totalQuantity,
                'total_revenue' => $totalRevenue,
                'avg_qty_per_order' => $avgQtyPerOrder,
            ],
            'chef_breakdown' => $chefBreakdown,
            'variant_breakdown' => $variantBreakdown,
            'items' => $paginatedItems,
        ];
    }
}
