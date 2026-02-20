<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StockType;
use App\Enums\RoleName;
use App\Services\GoodsComeService;
use App\Models\{Warehouse, Product, Stock, ProductPurchasePrice, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Schema};

class StockSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('stocks') || !Schema::hasTable('warehouses') || !Schema::hasTable('products')) {
            return;
        }

        DB::transaction(function (): void {
            $warehouseIds = Warehouse::query()->pluck('id')->all();
            $productIds = Product::query()->pluck('id')->all();

            if (empty($warehouseIds) || empty($productIds)) {
                return;
            }

            $existing = Stock::query()
                ->whereIn('warehouse_id', $warehouseIds)
                ->whereIn('product_id', $productIds)
                ->whereNull('product_division_id')
                ->whereNull('product_rack_id')
                ->whereNull('bucket')
                ->get(['warehouse_id', 'product_id']);

            $existsKey = [];
            foreach ($existing as $row) {
                $existsKey[$row->warehouse_id . '|' . $row->product_id] = true;
            }

            $now = now();
            foreach ($warehouseIds as $wid) {
                foreach ($productIds as $pid) {
                    $key = $wid . '|' . $pid;
                    if (isset($existsKey[$key])) {
                        continue;
                    }
                    Stock::create([
                        'warehouse_id' => (string) $wid,
                        'product_id' => (string) $pid,
                        'product_division_id' => null,
                        'product_rack_id' => null,
                        'quantity' => 0,
                        'type' => StockType::Main->value,
                        'locked_by_stock_opname_id' => null,
                        'bucket' => null,
                    ]);
                }
            }
        });

        $products = Product::query()->get(['id', 'min_stock']);
        $productIdsForPrices = $products->pluck('id')->map(fn($id) => (string) $id)->all();
        $purchaseRows = !empty($productIdsForPrices)
            ? ProductPurchasePrice::query()
            ->whereIn('product_id', $productIdsForPrices)
            ->get(['product_id', 'price'])
            : collect();
        $purchasePriceMap = [];
        foreach ($purchaseRows as $row) {
            $purchasePriceMap[(string) $row->product_id] = (int) $row->price;
        }
        $seedUserId = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->orderBy('id')
            ->value('id');
        if (!$seedUserId) {
            $seedUserId = User::query()->where('email', 'superadmin@gmail.com')->value('id');
        }
        if (!$seedUserId) {
            $seedUserId = User::query()->orderBy('id')->value('id');
        }
        if (!$seedUserId) {
            return;
        }
        $warehouseIds = Warehouse::query()->pluck('id')->all();
        foreach ($warehouseIds as $wid) {
            foreach ($products as $p) {
                $min = (int) ($p->min_stock ?? 0);
                if ($min <= 0) {
                    continue;
                }
                $buckets = [
                    ['name' => 'non_vat', 'flag' => false, 'note' => 'Initial min_stock non_vat via seeder'],
                    ['name' => 'vat', 'flag' => true, 'note' => 'Initial min_stock vat via seeder'],
                ];
                foreach ($buckets as $b) {
                    $existsQty = (int) Stock::query()
                        ->where('warehouse_id', (string) $wid)
                        ->where('product_id', (string) $p->id)
                        ->where('bucket', $b['name'])
                        ->sum('quantity');
                    if ($existsQty > 0) {
                        continue;
                    }
                    $unitCost = (int) ($purchasePriceMap[(string) $p->id] ?? 0);
                    app(GoodsComeService::class)->receiveGoods([
                        'warehouse_id' => (string) $wid,
                        'product_id' => (string) $p->id,
                        'quantity' => $min,
                        'unit_cost' => $unitCost,
                        'is_value_added_tax_enabled' => $b['flag'],
                        'created_by_id' => (string) $seedUserId,
                        'notes' => $b['note'],
                        'sender_name' => 'Seeder',
                        'vehicle_plate_number' => 'SEED-PLATE',
                    ]);
                }
            }
        }
    }
}
