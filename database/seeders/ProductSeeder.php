<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Product\ProductData;
use App\DTOs\Product\ProductOptionData;
use App\DTOs\Product\ProductOptionItemData;
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
            'Makanan Utama' => [
                [
                    'name' => 'Salad Mentai',
                    'description' => 'Kombinasi sayuran dan buah segar dengan dressing saus mentai gurih dan creamy.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'image' => 'products/salad-mentai.jpg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Tingkat Pedas Saus',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Tidak Pedas', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Sedang', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Pedas', extraPrice: 0, sortOrder: 3),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Tambahan Topping',
                            isRequired: false,
                            isMultiple: true,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Telur Rebus', extraPrice: 3000, sortOrder: 1),
                                new ProductOptionItemData(name: 'Keju Parut', extraPrice: 4000, sortOrder: 2),
                                new ProductOptionItemData(name: 'Crab Stick', extraPrice: 5000, sortOrder: 3),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Steak Mentai',
                    'description' => 'Potongan daging ayam panggang empuk dengan siraman saus mentai spesial yang ditorch hingga harum.',
                    'price' => 25000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 2,
                    'image' => 'products/steak-mentai.jpeg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Tingkat Pematangan',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Well Done', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Medium Well', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Tingkat Pedas Saus',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Original', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Spicy', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Dimsum Mentai',
                    'description' => 'Dimsum ayam halal berbalut saus mentai bakar yang lumer di mulut dan gurih maksimal.',
                    'price' => 10000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 3,
                    'image' => 'products/dimsum-mentai.jpeg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Tingkat Pedas Saus',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Original', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Pedas', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Sushi Mentai',
                    'description' => 'Sushi roll a la Jepang yang padat dengan isian crab stick dan extra lelehan saus mentai bakar.',
                    'price' => 25000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 4,
                    'image' => 'products/sushi-mentai.jpeg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Pilihan Topping Tambahan',
                            isRequired: false,
                            isMultiple: true,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Keju Mozzarella', extraPrice: 5000, sortOrder: 1),
                                new ProductOptionItemData(name: 'Kani Mambo', extraPrice: 4000, sortOrder: 2),
                                new ProductOptionItemData(name: 'Tobiko', extraPrice: 6000, sortOrder: 3),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Lontong Kikil',
                    'description' => 'Potongan kikil empuk dengan kuah santan bumbu kuning yang kaya rempah jinten, disajikan dengan lontong.',
                    'price' => 30000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 5,
                    'image' => 'products/lontong-kikil.webp',
                    'options' => [
                        new ProductOptionData(
                            name: 'Level Pedas Sambal',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Pisah Sambal', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Sedang', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Extra Pedas', extraPrice: 0, sortOrder: 3),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Tambahan',
                            isRequired: false,
                            isMultiple: true,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Kerupuk Udang', extraPrice: 2000, sortOrder: 1),
                                new ProductOptionItemData(name: 'Telur Asin', extraPrice: 6000, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Bakso',
                    'description' => 'Bakso daging sapi asli dengan kuah kaldu sapi pekat tulang rangu segar, dilengkapi mi, bihun, dan poyah bawang goreng.',
                    'price' => 25000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 6,
                    'image' => 'products/bakso.jpeg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Pilihan Karbohidrat',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Mie & Bihun', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Mie Saja', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Bihun Saja', extraPrice: 0, sortOrder: 3),
                                new ProductOptionItemData(name: 'Tanpa Mie/Bihun (Full Sayur)', extraPrice: 0, sortOrder: 4),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Tambahan Kenikmatan',
                            isRequired: false,
                            isMultiple: true,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Tahu Bakso', extraPrice: 3000, sortOrder: 1),
                                new ProductOptionItemData(name: 'Pangsit Goreng', extraPrice: 2000, sortOrder: 2),
                                new ProductOptionItemData(name: 'Nasi Putih', extraPrice: 5000, sortOrder: 3),
                            ]
                        ),
                    ],
                ],
            ],
            'Minuman' => [
                [
                    'name' => 'Smoothies Strawberry',
                    'description' => 'Minuman blend stroberi segar dengan susu yang manis, creamy, dan dijamin dingin menyegarkan.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'image' => 'products/smoothies-strawberry.jpg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Tingkat Kemanisan',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Normal Sugar', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Less Sugar', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Tambahan Topping',
                            isRequired: false,
                            isMultiple: true,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Jelly', extraPrice: 3000, sortOrder: 1),
                                new ProductOptionItemData(name: 'Nata de Coco', extraPrice: 3000, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Es Sirsak',
                    'description' => 'Kesegaran daging buah sirsak utuh dan tebal yang diblend dengan manisnya susu kental manis.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 2,
                    'image' => 'products/es-sirsak.jpg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Tingkat Kemanisan',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Normal Sugar', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Less Sugar', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Es Nangka',
                    'description' => 'Potongan buah nangka manis dengan kuah santan dan sirup andalan yang harum merona.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 3,
                    'image' => 'products/es-nangka.webp',
                    'options' => [
                        new ProductOptionData(
                            name: 'Es',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Normal Ice', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Less Ice', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Es Teller',
                    'description' => 'Perpaduan klasik alpukat, nangka, kelapa muda dan mutiara disiram susu kental manis berlimpah.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 4,
                    'image' => 'products/es-teller.jpg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Tingkat Kemanisan',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Normal Sugar', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Less Sugar', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Es',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Normal Ice', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Less Ice', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                    ],
                ],
            ],
            'Cemilan' => [
                [
                    'name' => 'Mini Pizza Bites',
                    'description' => 'Potongan pizza ukuran gigitan (bite-size) dengan taburan sosis, saus tomat pilihan, dan keju lumer.',
                    'price' => 20000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'image' => 'products/mini-pizza-bites.jpg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Pilihan Saus Cocol',
                            isRequired: false,
                            isMultiple: true,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Saus Tomat Ekstra', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Saus Sambal Ekstra', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Mayonnaise', extraPrice: 0, sortOrder: 3),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Pentol Kriwil',
                    'description' => 'Pentol daging sapi bertekstur keriting kenyal dengan saus kacang pedas manis yang bikin nagih.',
                    'price' => 15000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 2,
                    'image' => 'products/pentol-kriwil.jpg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Pilihan Saus',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Saus Kacang', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Saus Sambal & Kecap', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Bumbu Tabur Gurih', extraPrice: 0, sortOrder: 3),
                            ]
                        ),
                        new ProductOptionData(
                            name: 'Level Pedas',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 2,
                            items: [
                                new ProductOptionItemData(name: 'Tidak Pedas', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Sedang', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Pedas Mantap', extraPrice: 0, sortOrder: 3),
                            ]
                        ),
                    ],
                ],
            ],
            'Makanan Penutup' => [
                [
                    'name' => 'Putri Salju',
                    'description' => 'Kue kering klasik berbentuk bulan sabit dengan taburan taburan gula halus spesial yang langsung lumer di mulut.',
                    'price' => 5000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'image' => 'products/putri-salju.jpg',
                    'options' => [],
                ],
                [
                    'name' => 'Puding Fla',
                    'description' => 'Puding susu lembut dan kenyal, disajikan sangat dingin dengan siraman fla manis legit.',
                    'price' => 5000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 2,
                    'image' => 'products/puding-fla.webp',
                    'options' => [
                        new ProductOptionData(
                            name: 'Pilihan Rasa Fla (Saus)',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Vanilla Classic', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Coklat Belgia', extraPrice: 0, sortOrder: 2),
                                new ProductOptionItemData(name: 'Stroberi Murni', extraPrice: 0, sortOrder: 3),
                            ]
                        ),
                    ],
                ],
                [
                    'name' => 'Picnic Roll',
                    'description' => 'Pastry renyah berlapis nan wangi butter dengan isian daging sapi cincang, dan telur rebus yang gurih padat.',
                    'price' => 25000,
                    'stock_limit' => null,
                    'is_active' => true,
                    'sort_order' => 3,
                    'image' => 'products/picnic-roll.jpeg',
                    'options' => [
                        new ProductOptionData(
                            name: 'Penyajian',
                            isRequired: true,
                            isMultiple: false,
                            sortOrder: 1,
                            items: [
                                new ProductOptionItemData(name: 'Hangat (Toasted)', extraPrice: 0, sortOrder: 1),
                                new ProductOptionItemData(name: 'Suhu Ruang', extraPrice: 0, sortOrder: 2),
                            ]
                        ),
                    ],
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
                        options: $product['options'] ?? [],
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
