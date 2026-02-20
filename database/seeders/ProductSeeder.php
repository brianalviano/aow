<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Product, ProductCategory, ProductSubCategory, ProductUnit, ProductFactory, ProductSubFactory, ProductCondition};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = ProductCategory::query()->get()->keyBy('code');
            $subCategories = ProductSubCategory::query()->get()->keyBy('code');
            $units = ProductUnit::query()->get()->keyBy('code');
            $factories = ProductFactory::query()->get()->keyBy('code');
            $subFactories = ProductSubFactory::query()->get()->keyBy('code');
            $conditions = ProductCondition::query()->get()->keyBy('name');

            $condBaru = $conditions->get('Baru');

            $get = function (array|Collection $map, string $key): ?string {
                $item = $map instanceof Collection ? ($map->get($key) ?? null) : ($map[$key] ?? null);
                return $item ? (string) $item->id : null;
            };

            $machCat = $categories->get('MAC');

            $parentMesinYanmar = Product::query()->updateOrCreate(
                ['sku' => 'ENG-YANMAR'],
                [
                    'name' => 'Mesin Diesel Yanmar',
                    'description' => 'Mesin diesel Yanmar untuk aplikasi pertanian dan industri.',
                    'image' => null,
                    'weight' => 45.0,
                    'is_active' => true,
                    'product_category_id' => $machCat ? (string) $machCat->id : null,
                    'product_sub_category_id' => $get($subCategories, 'DSL'),
                    'product_unit_id' => $get($units, 'UNT'),
                    'product_factory_id' => $get($factories, 'YMR'),
                    'product_sub_factory_id' => null,
                    'product_condition_id' => $condBaru ? (string) $condBaru->id : null,
                    'product_type' => 'ready',
                    'product_variant_type' => 'parent',
                    'parent_product_id' => null,
                    'min_stock' => 1,
                    'max_stock' => 10,
                ]
            );

            $rows = [
                [
                    'sku' => 'ENG-YMR-L48',
                    'name' => 'Yanmar L48',
                    'description' => 'Mesin diesel Yanmar tipe L48, 4.8 HP.',
                    'weight' => 32.0,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'DSL',
                    'unit_code' => 'UNT',
                    'factory_code' => 'YMR',
                    'sub_factory_code' => 'L48',
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'variant',
                    'parent_id' => (string) $parentMesinYanmar->id,
                    'min_stock' => 2,
                    'max_stock' => 15,
                ],
                [
                    'sku' => 'ENG-YMR-L70',
                    'name' => 'Yanmar L70',
                    'description' => 'Mesin diesel Yanmar tipe L70, 6.8 HP.',
                    'weight' => 35.0,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'DSL',
                    'unit_code' => 'UNT',
                    'factory_code' => 'YMR',
                    'sub_factory_code' => 'L70',
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'variant',
                    'parent_id' => (string) $parentMesinYanmar->id,
                    'min_stock' => 2,
                    'max_stock' => 15,
                ],
                [
                    'sku' => 'PMP-SMZ-128',
                    'name' => 'Pompa Air Shimizu PS 128 BIT',
                    'description' => 'Pompa air Shimizu PS 128 BIT untuk irigasi dan kebutuhan rumah tangga.',
                    'weight' => 9.0,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'PMP',
                    'unit_code' => 'UNT',
                    'factory_code' => 'SMZ',
                    'sub_factory_code' => '128',
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 5,
                    'max_stock' => 50,
                ],
                [
                    'sku' => 'GEN-KUB-5KVA',
                    'name' => 'Genset Diesel 5 kVA Kubota',
                    'description' => 'Genset diesel 5 kVA berbasis mesin Kubota D1105.',
                    'weight' => 120.0,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'GEN',
                    'unit_code' => 'UNT',
                    'factory_code' => 'KBT',
                    'sub_factory_code' => 'D11',
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 1,
                    'max_stock' => 8,
                ],
                [
                    'sku' => 'SPT-QCK-G10',
                    'name' => 'Sparepart Traktor Quick G100',
                    'description' => 'Sparepart traktor Quick seri G100.',
                    'weight' => 2.5,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'SPT',
                    'unit_code' => 'PCS',
                    'factory_code' => 'QCK',
                    'sub_factory_code' => 'G10',
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 10,
                    'max_stock' => 100,
                ],
                [
                    'sku' => 'SED-PDI-5KG',
                    'name' => 'Benih Padi Ciherang 5 kg',
                    'description' => 'Benih padi varietas Ciherang kemasan 5 kg.',
                    'weight' => 5.0,
                    'category_code' => 'AGR',
                    'sub_category_code' => 'BPR',
                    'unit_code' => 'KGM',
                    'factory_code' => null,
                    'sub_factory_code' => null,
                    'condition_name' => 'Baru',
                    'type' => 'raw',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 20,
                    'max_stock' => 200,
                ],
                [
                    'sku' => 'SED-JGG-1KG',
                    'name' => 'Benih Jagung Hibrida 1 kg',
                    'description' => 'Benih jagung hibrida kemasan 1 kg.',
                    'weight' => 1.0,
                    'category_code' => 'AGR',
                    'sub_category_code' => 'BJG',
                    'unit_code' => 'KGM',
                    'factory_code' => null,
                    'sub_factory_code' => null,
                    'condition_name' => 'Baru',
                    'type' => 'raw',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 30,
                    'max_stock' => 300,
                ],
                [
                    'sku' => 'FRT-URE-50KG',
                    'name' => 'Pupuk Urea 50 kg',
                    'description' => 'Pupuk Urea 46% kemasan karung 50 kg.',
                    'weight' => 50.0,
                    'category_code' => 'AGR',
                    'sub_category_code' => 'URE',
                    'unit_code' => 'KRG',
                    'factory_code' => 'KUJ',
                    'sub_factory_code' => 'U46',
                    'condition_name' => 'Baru',
                    'type' => 'raw',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 10,
                    'max_stock' => 150,
                ],
                [
                    'sku' => 'FRT-NPK-50KG',
                    'name' => 'Pupuk NPK 16-16-16 50 kg',
                    'description' => 'Pupuk NPK 16-16-16 kemasan karung 50 kg.',
                    'weight' => 50.0,
                    'category_code' => 'AGR',
                    'sub_category_code' => 'NPK',
                    'unit_code' => 'KRG',
                    'factory_code' => 'PTK',
                    'sub_factory_code' => 'N16',
                    'condition_name' => 'Baru',
                    'type' => 'raw',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 10,
                    'max_stock' => 150,
                ],
                [
                    'sku' => 'PEST-INSEK-1L',
                    'name' => 'Pestisida Insektisida 1 L',
                    'description' => 'Pestisida insektisida cair kemasan 1 liter.',
                    'weight' => 1.1,
                    'category_code' => 'AGR',
                    'sub_category_code' => 'PES',
                    'unit_code' => 'LTR',
                    'factory_code' => null,
                    'sub_factory_code' => null,
                    'condition_name' => 'Baru',
                    'type' => 'raw',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 40,
                    'max_stock' => 400,
                ],
                [
                    'sku' => 'IRG-2IN-50M',
                    'name' => 'Selang Irigasi 2 inci 50 m',
                    'description' => 'Selang irigasi diameter 2 inci panjang 50 meter.',
                    'weight' => 12.0,
                    'category_code' => 'AGR',
                    'sub_category_code' => 'IRR',
                    'unit_code' => 'ROL',
                    'factory_code' => null,
                    'sub_factory_code' => null,
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 10,
                    'max_stock' => 80,
                ],
                [
                    'sku' => 'SPR-EL-16L',
                    'name' => 'Sprayer Elektrik 16 L',
                    'description' => 'Sprayer elektrik kapasitas 16 liter untuk penyemprotan tanaman.',
                    'weight' => 6.0,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'SPR',
                    'unit_code' => 'UNT',
                    'factory_code' => null,
                    'sub_factory_code' => null,
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 10,
                    'max_stock' => 100,
                ],
                [
                    'sku' => 'SPR-MAN-16L',
                    'name' => 'Sprayer Manual 16 L',
                    'description' => 'Sprayer manual kapasitas 16 liter.',
                    'weight' => 5.5,
                    'category_code' => 'MAC',
                    'sub_category_code' => 'SPR',
                    'unit_code' => 'UNT',
                    'factory_code' => null,
                    'sub_factory_code' => null,
                    'condition_name' => 'Baru',
                    'type' => 'ready',
                    'variant_type' => 'standalone',
                    'parent_id' => null,
                    'min_stock' => 12,
                    'max_stock' => 120,
                ],
            ];

            foreach ($rows as $r) {
                $categoryId = null;
                if ($r['category_code'] && isset($categories[$r['category_code']])) {
                    $categoryId = (string) $categories[$r['category_code']]->id;
                }
                $subCategoryId = null;
                if ($r['sub_category_code'] && isset($subCategories[$r['sub_category_code']])) {
                    $subCategoryId = (string) $subCategories[$r['sub_category_code']]->id;
                }
                $unitId = null;
                if ($r['unit_code'] && isset($units[$r['unit_code']])) {
                    $unitId = (string) $units[$r['unit_code']]->id;
                }
                $factoryId = null;
                if ($r['factory_code'] && isset($factories[$r['factory_code']])) {
                    $factoryId = (string) $factories[$r['factory_code']]->id;
                }
                $subFactoryId = null;
                if ($r['sub_factory_code'] && isset($subFactories[$r['sub_factory_code']])) {
                    $subFactoryId = (string) $subFactories[$r['sub_factory_code']]->id;
                }
                $conditionId = null;
                if ($r['condition_name'] && isset($conditions[$r['condition_name']])) {
                    $conditionId = (string) $conditions[$r['condition_name']]->id;
                }

                Product::query()->updateOrCreate(
                    ['sku' => Str::upper($r['sku'])],
                    [
                        'name' => $r['name'],
                        'description' => $r['description'],
                        'image' => null,
                        'weight' => $r['weight'],
                        'is_active' => true,
                        'product_category_id' => $categoryId,
                        'product_sub_category_id' => $subCategoryId,
                        'product_unit_id' => $unitId,
                        'product_factory_id' => $factoryId,
                        'product_sub_factory_id' => $subFactoryId,
                        'product_condition_id' => $conditionId,
                        'product_type' => $r['type'],
                        'product_variant_type' => $r['variant_type'],
                        'parent_product_id' => $r['parent_id'],
                        'min_stock' => (int) $r['min_stock'],
                        'max_stock' => (int) $r['max_stock'],
                    ]
                );
            }
        });
    }
}
