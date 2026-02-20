<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rows = [
                ['name' => 'Unit', 'code' => 'UNT', 'is_active' => true],
                ['name' => 'Kilogram', 'code' => 'KGM', 'is_active' => true],
                ['name' => 'Liter', 'code' => 'LTR', 'is_active' => true],
                ['name' => 'Pcs', 'code' => 'PCS', 'is_active' => true],
                ['name' => 'Roll', 'code' => 'ROL', 'is_active' => true],
                ['name' => 'Set', 'code' => 'SET', 'is_active' => true],
                ['name' => 'Karung', 'code' => 'KRG', 'is_active' => true],
            ];

            foreach ($rows as $data) {
                ProductUnit::query()->updateOrCreate(
                    ['code' => $data['code']],
                    $data
                );
            }
        });
    }
}
