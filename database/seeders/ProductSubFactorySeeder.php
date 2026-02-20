<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductFactory;
use App\Models\ProductSubFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSubFactorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $factories = ProductFactory::query()->get()->keyBy('code');

            $rows = [
                ['factory_code' => 'YMR', 'name' => 'Yanmar L48', 'code' => 'L48', 'is_active' => true],
                ['factory_code' => 'YMR', 'name' => 'Yanmar L70', 'code' => 'L70', 'is_active' => true],
                ['factory_code' => 'KBT', 'name' => 'Kubota D1105', 'code' => 'D11', 'is_active' => true],
                ['factory_code' => 'SMZ', 'name' => 'Shimizu PS 128 BIT', 'code' => '128', 'is_active' => true],
                ['factory_code' => 'QCK', 'name' => 'Quick G100', 'code' => 'G10', 'is_active' => true],
                ['factory_code' => 'KUJ', 'name' => 'Urea 46%', 'code' => 'U46', 'is_active' => true],
                ['factory_code' => 'PTK', 'name' => 'NPK 16-16-16', 'code' => 'N16', 'is_active' => true],
            ];

            foreach ($rows as $r) {
                $factory = $factories->get($r['factory_code']);
                if (!$factory) {
                    continue;
                }
                ProductSubFactory::query()->updateOrCreate(
                    ['code' => $r['code']],
                    [
                        'product_factory_id' => (string) $factory->id,
                        'name' => $r['name'],
                        'code' => $r['code'],
                        'is_active' => $r['is_active'],
                    ]
                );
            }
        });
    }
}
