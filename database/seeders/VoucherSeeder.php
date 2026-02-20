<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Schema};

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('vouchers')) {
            return;
        }

        DB::transaction(function (): void {
            $rows = [
                [
                    'code' => 'PROMO10',
                    'name' => 'Promo 10% Semua Produk',
                    'description' => 'Diskon 10% untuk semua produk. Minimal transaksi Rp 100.000.',
                    'value_type' => 'percentage',
                    'value' => '10.00',
                    'min_order_amount' => '100000',
                    'usage_limit' => 500,
                    'used_count' => 0,
                    'max_uses_per_customer' => 1,
                    'start_at' => now()->subDay(),
                    'end_at' => now()->addDays(30),
                    'is_active' => true,
                ],
                [
                    'code' => 'HEMAT50K',
                    'name' => 'Hemat Rp 50.000',
                    'description' => 'Diskon nominal Rp 50.000 untuk pembelian minimal Rp 250.000.',
                    'value_type' => 'amount',
                    'value' => '50000.00',
                    'min_order_amount' => '250000',
                    'usage_limit' => 300,
                    'used_count' => 0,
                    'max_uses_per_customer' => 2,
                    'start_at' => now(),
                    'end_at' => now()->addDays(45),
                    'is_active' => true,
                ],
                [
                    'code' => 'WEEKEND15',
                    'name' => 'Weekend Sale 15%',
                    'description' => 'Diskon 15% periode promo terbatas.',
                    'value_type' => 'percentage',
                    'value' => '15.00',
                    'min_order_amount' => '0',
                    'usage_limit' => 1000,
                    'used_count' => 0,
                    'max_uses_per_customer' => 3,
                    'start_at' => now()->addDay(),
                    'end_at' => now()->addDays(14),
                    'is_active' => true,
                ],
                [
                    'code' => 'NEWCUST25',
                    'name' => 'Diskon Customer Baru 25%',
                    'description' => 'Diskon 25% untuk transaksi pertama customer. Minimal transaksi Rp 100.000.',
                    'value_type' => 'percentage',
                    'value' => '25.00',
                    'min_order_amount' => '100000',
                    'usage_limit' => 100,
                    'used_count' => 0,
                    'max_uses_per_customer' => 1,
                    'start_at' => now()->subDays(2),
                    'end_at' => now()->addDays(60),
                    'is_active' => true,
                ],
            ];

            foreach ($rows as $data) {
                Voucher::query()->updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'value_type' => $data['value_type'],
                        'value' => $data['value'],
                        'min_order_amount' => $data['min_order_amount'],
                        'usage_limit' => $data['usage_limit'],
                        'used_count' => $data['used_count'],
                        'max_uses_per_customer' => $data['max_uses_per_customer'],
                        'start_at' => $data['start_at'],
                        'end_at' => $data['end_at'],
                        'is_active' => $data['is_active'],
                    ]
                );
            }
        });
    }
}
