<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductFactorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rows = [
                ['name' => 'Yanmar', 'code' => 'YMR', 'is_active' => true],
                ['name' => 'Kubota', 'code' => 'KBT', 'is_active' => true],
                ['name' => 'Shimizu', 'code' => 'SMZ', 'is_active' => true],
                ['name' => 'Dongfeng', 'code' => 'DFG', 'is_active' => true],
                ['name' => 'Quick', 'code' => 'QCK', 'is_active' => true],
                ['name' => 'Petrokimia Gresik', 'code' => 'PTK', 'is_active' => true],
                ['name' => 'Pupuk Kujang', 'code' => 'KUJ', 'is_active' => true],
            ];

            foreach ($rows as $data) {
                ProductFactory::query()->updateOrCreate(
                    ['code' => $data['code']],
                    $data
                );
            }
        });
    }
}
