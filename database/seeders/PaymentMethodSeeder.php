<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Cash',
                'category' => 'manual',
                'type' => 'cash',
                'is_active' => true,
            ],
            [
                'name' => 'Transfer BCA',
                'category' => 'manual',
                'type' => 'online',
                'is_active' => true,
                'account_number' => '7240081851',
                'account_name' => 'NUNING RAHMAWATI SE',
            ],
            [
                'name' => 'QRIS',
                'category' => 'gateway',
                'type' => 'online',
                'is_active' => true,
            ],
            [
                'name' => 'ShopeePay',
                'category' => 'gateway',
                'type' => 'online',
                'is_active' => true,
            ],
            [
                'name' => 'OVO',
                'category' => 'gateway',
                'type' => 'online',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
