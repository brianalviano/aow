<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Product\ProductData;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProductService $productService): void
    {
        // Define some sample products
        $products = [
            'Food' => [
                [
                    'name' => 'Nasi Goreng',
                    'description' => 'Nasi goreng spesial dengan telur dan ayam.',
                    'price' => 25000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Mie Goreng',
                    'description' => 'Mie goreng jawa yang lezat.',
                    'price' => 20000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 2,
                ],
            ],
            'Beverage' => [
                [
                    'name' => 'Es Teh Manis',
                    'description' => 'Es teh manis segar.',
                    'price' => 5000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Kopi Susu',
                    'description' => 'Kopi susu gula aren.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 2,
                ],
            ],
            'Snack' => [
                [
                    'name' => 'Kentang Goreng',
                    'description' => 'Kentang goreng renyah dengan saus.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            ],
        ];

        foreach ($products as $categoryName => $categoryProducts) {
            $category = ProductCategory::where('name', $categoryName)->first();

            if (!$category) {
                Log::warning("ProductCategory '{$categoryName}' not found. Skipping its products.");
                continue;
            }

            foreach ($categoryProducts as $product) {
                try {
                    $dto = new ProductData(
                        productCategoryId: $category->id,
                        name: $product['name'],
                        description: $product['description'],
                        price: $product['price'],
                        image: null, // Note: image handled manually if needed
                        stockLimit: $product['stock_limit'],
                        isActive: $product['is_active'],
                        sortOrder: $product['sort_order'],
                    );

                    $productService->createProduct($dto);
                } catch (\Throwable $e) {
                    Log::error('Failed to seed product: ' . $product['name'], [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}
