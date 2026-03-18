<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\ProductCategory\ProductCategoryData;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProductCategoryService $productCategoryService): void
    {
        ProductCategory::truncate();

        $categories = [
            [
                'name' => 'Makanan Utama',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Cemilan',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Minuman',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Makanan Penutup',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            try {
                $dto = new ProductCategoryData(
                    name: $category['name'],
                    isActive: $category['is_active'],
                    sortOrder: $category['sort_order'],
                );

                $productCategoryService->createProductCategory($dto);
            } catch (\Throwable $e) {
                Log::error('Failed to seed product category: ' . $category['name'], [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
