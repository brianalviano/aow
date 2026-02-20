<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rows = [
                ['name' => 'Pertanian', 'code' => 'AGR', 'is_active' => true],
                ['name' => 'Mesin & Perkakas', 'code' => 'MAC', 'is_active' => true],
            ];

            foreach ($rows as $data) {
                ProductCategory::query()->updateOrCreate(
                    ['code' => $data['code']],
                    $data
                );
            }
        });
    }
}
