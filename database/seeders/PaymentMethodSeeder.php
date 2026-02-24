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
                'category' => null,
                'type' => 'cash',
                'is_active' => true,
            ],
            [
                'name' => 'Transfer Bank',
                'category' => 'bank-transfer',
                'type' => 'online',
                'is_active' => true,
                'account_number' => '7240081851',
                'account_name' => 'NUNING RAHMAWATI SE',
            ],
            [
                'name' => 'Virtual Account',
                'category' => 'virtual-account',
                'type' => 'online',
                'is_active' => true,
            ],
            [
                'name' => 'E-Wallet',
                'category' => 'e-wallet',
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
