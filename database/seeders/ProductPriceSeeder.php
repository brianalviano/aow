<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\ProductPrice\{ProductPriceBatchData, ProductPriceEntryData};
use App\Models\{Product, SellingPriceLevel};
use App\Services\ProductPriceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Schema};

class ProductPriceSeeder extends Seeder
{
    public function run(): void
    {
        if (
            !Schema::hasTable('products') ||
            !Schema::hasTable('product_purchase_prices') ||
            !Schema::hasTable('product_selling_prices') ||
            !Schema::hasTable('selling_price_levels')
        ) {
            return;
        }

        DB::transaction(function (): void {
            $retail = SellingPriceLevel::query()->firstOrCreate(['name' => 'Retail']);
            $grosir = SellingPriceLevel::query()->firstOrCreate(['name' => 'Grosir']);

            $products = Product::query()->select(['id', 'sku'])->get();
            if ($products->isEmpty()) {
                return;
            }

            $entries = [];
            foreach ($products as $p) {
                $sku = (string) ($p->sku ?? (string) $p->id);
                $purchase = $this->computePurchasePrice($sku);
                $sellingMain = (int) round($purchase * 1.15);

                $entries[] = new ProductPriceEntryData(
                    type: 'purchase',
                    productId: (string) $p->id,
                    levelId: null,
                    supplierId: null,
                    price: $purchase
                );
                $entries[] = new ProductPriceEntryData(
                    type: 'selling',
                    productId: (string) $p->id,
                    levelId: null,
                    supplierId: null,
                    price: $sellingMain
                );
            }

            $service = app(ProductPriceService::class);
            $service->saveBatch(new ProductPriceBatchData(q: null, entries: $entries));

            $service->adjustSellingLevelPricesFromMain((string) $retail->id, 10.0);
            $service->adjustSellingLevelPricesFromMain((string) $grosir->id, -5.0);
        });
    }

    private function computePurchasePrice(string $key): int
    {
        $hash = abs(crc32($key));
        $bucket = ($hash % 400) + 100; // 100..499
        return $bucket * 10000; // Rp 1.000.000 .. Rp 4.990.000
    }
}
