<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSubCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = ProductCategory::query()->get()->keyBy('code');

            $rows = [
                ['category_code' => 'AGR', 'name' => 'Benih Padi', 'code' => 'BPR', 'is_active' => true],
                ['category_code' => 'AGR', 'name' => 'Benih Jagung', 'code' => 'BJG', 'is_active' => true],
                ['category_code' => 'AGR', 'name' => 'Pupuk Urea', 'code' => 'URE', 'is_active' => true],
                ['category_code' => 'AGR', 'name' => 'Pupuk NPK', 'code' => 'NPK', 'is_active' => true],
                ['category_code' => 'AGR', 'name' => 'Pestisida', 'code' => 'PES', 'is_active' => true],
                ['category_code' => 'AGR', 'name' => 'Alat Irigasi', 'code' => 'IRR', 'is_active' => true],
                ['category_code' => 'MAC', 'name' => 'Mesin Diesel', 'code' => 'DSL', 'is_active' => true],
                ['category_code' => 'MAC', 'name' => 'Pompa Air', 'code' => 'PMP', 'is_active' => true],
                ['category_code' => 'MAC', 'name' => 'Genset', 'code' => 'GEN', 'is_active' => true],
                ['category_code' => 'MAC', 'name' => 'Sprayer', 'code' => 'SPR', 'is_active' => true],
                ['category_code' => 'MAC', 'name' => 'Sparepart Traktor', 'code' => 'SPT', 'is_active' => true],
            ];

            foreach ($rows as $r) {
                $category = $categories->get($r['category_code']);
                if (!$category) {
                    continue;
                }
                ProductSubCategory::query()->updateOrCreate(
                    ['code' => $r['code']],
                    [
                        'product_category_id' => (string) $category->id,
                        'name' => $r['name'],
                        'code' => $r['code'],
                        'is_active' => $r['is_active'],
                    ]
                );
            }
        });
    }
}
