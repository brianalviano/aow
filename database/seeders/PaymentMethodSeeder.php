<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rows = [
                [
                    'name' => 'Tunai',
                    'description' => 'Pembayaran tunai',
                    'image_url' => null,
                    'mdr_percentage' => '0.00',
                    'is_active' => true,
                ],
                [
                    'name' => 'Transfer Bank',
                    'description' => 'Pembayaran via transfer bank',
                    'image_url' => null,
                    'mdr_percentage' => '0.00',
                    'is_active' => true,
                ],
                [
                    'name' => 'QRIS',
                    'description' => 'Pembayaran QRIS',
                    'image_url' => null,
                    'mdr_percentage' => '0.70',
                    'is_active' => true,
                ],
                [
                    'name' => 'Kartu Debit',
                    'description' => 'Pembayaran kartu debit',
                    'image_url' => null,
                    'mdr_percentage' => '1.50',
                    'is_active' => true,
                ],
                [
                    'name' => 'Kartu Kredit',
                    'description' => 'Pembayaran kartu kredit',
                    'image_url' => null,
                    'mdr_percentage' => '2.50',
                    'is_active' => true,
                ],
            ];

            foreach ($rows as $data) {
                PaymentMethod::query()->updateOrCreate(
                    ['name' => $data['name']],
                    $data
                );
            }
        });
    }
}
