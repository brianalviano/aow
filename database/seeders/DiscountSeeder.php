<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Discount, DiscountItem, Product, ProductCategory};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Schema};

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('discounts') || !Schema::hasTable('discount_items')) {
            return;
        }

        DB::transaction(function (): void {
            $startMonth = now()->startOfMonth();
            $endMonth = now()->endOfMonth();

            $rows = [
                [
                    'name' => 'Promo Gajian 10%',
                    'type' => 'basic',
                    'scope' => 'global',
                    'value_type' => 'percentage',
                    'value' => '10.00',
                    'start_at' => $startMonth,
                    'end_at' => $endMonth,
                    'is_active' => true,
                ],
                [
                    'name' => 'Diskon Item Rp 15.000',
                    'type' => 'basic',
                    'scope' => 'specific',
                    'value_type' => 'nominal',
                    'value' => '15000.00',
                    'start_at' => now()->subDays(3),
                    'end_at' => now()->addDays(27),
                    'is_active' => true,
                ],
                [
                    'name' => 'BOGO Beli 2 Gratis 1',
                    'type' => 'bogo',
                    'scope' => 'specific',
                    'value_type' => null,
                    'value' => '0.00',
                    'start_at' => now()->addDays(1),
                    'end_at' => now()->addDays(31),
                    'is_active' => true,
                ],
                [
                    'name' => 'Diskon Weekend Rp 20.000',
                    'type' => 'basic',
                    'scope' => 'global',
                    'value_type' => 'nominal',
                    'value' => '20000.00',
                    'start_at' => now()->next('Saturday'),
                    'end_at' => now()->next('Sunday')->endOfDay(),
                    'is_active' => true,
                ],
                [
                    'name' => 'Member Special 5%',
                    'type' => 'basic',
                    'scope' => 'specific',
                    'value_type' => 'percentage',
                    'value' => '5.00',
                    'start_at' => now()->subDays(10),
                    'end_at' => now()->addDays(20),
                    'is_active' => false,
                ],
            ];

            $discounts = [];
            foreach ($rows as $data) {
                $discounts[$data['name']] = Discount::query()->updateOrCreate(
                    ['name' => $data['name']],
                    $data
                );
            }

            $products = Product::query()
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit(6)
                ->get(['id']);

            $mac = ProductCategory::query()->where('code', 'MAC')->first();
            $agr = ProductCategory::query()->where('code', 'AGR')->first();
            $category = $mac ?? $agr ?? ProductCategory::query()->first();

            if (isset($discounts['Diskon Item Rp 15.000']) && $products->isNotEmpty()) {
                $target = $products->take(3);
                foreach ($target as $p) {
                    DiscountItem::query()->updateOrCreate(
                        [
                            'discount_id' => (string) $discounts['Diskon Item Rp 15.000']->id,
                            'itemable_type' => Product::class,
                            'itemable_id' => (string) $p->id,
                        ],
                        [
                            'min_qty_buy' => 1,
                            'free_product_id' => null,
                            'free_qty_get' => 0,
                            'custom_value' => null,
                        ]
                    );
                }
            }

            if (isset($discounts['BOGO Beli 2 Gratis 1']) && $products->isNotEmpty()) {
                $bogo = $products->slice(3, 2);
                if ($bogo->isEmpty()) {
                    $bogo = $products->take(2);
                }
                foreach ($bogo as $p) {
                    DiscountItem::query()->updateOrCreate(
                        [
                            'discount_id' => (string) $discounts['BOGO Beli 2 Gratis 1']->id,
                            'itemable_type' => Product::class,
                            'itemable_id' => (string) $p->id,
                        ],
                        [
                            'min_qty_buy' => 2,
                            'free_product_id' => (string) $p->id,
                            'free_qty_get' => 1,
                            'custom_value' => null,
                        ]
                    );
                }
            }

            if (isset($discounts['Member Special 5%']) && $category) {
                DiscountItem::query()->updateOrCreate(
                    [
                        'discount_id' => (string) $discounts['Member Special 5%']->id,
                        'itemable_type' => ProductCategory::class,
                        'itemable_id' => (string) $category->id,
                    ],
                    [
                        'min_qty_buy' => 1,
                        'free_product_id' => null,
                        'free_qty_get' => 0,
                        'custom_value' => null,
                    ]
                );
            }
        });
    }
}
