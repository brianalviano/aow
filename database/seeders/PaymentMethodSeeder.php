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
        $manualGuideId = \App\Models\PaymentGuide::where('name', 'Transfer Bank (Manual)')->first()?->id;
        $eWalletGuideId = \App\Models\PaymentGuide::where('name', 'E-Wallet (OVO/ShopeePay)')->first()?->id;

        $methods = [
            [
                'name' => 'Cash',
                'category' => 'cash',
                'is_active' => true,
            ],
            [
                'name' => 'Transfer Bank BCA',
                'category' => 'bank-transfer',
                'is_active' => true,
                'account_number' => '7240081851',
                'account_name' => 'NUNING RAHMAWATI SE',
                'payment_guide_id' => $manualGuideId,
            ],
            [
                'name' => 'OVO',
                'category' => 'e-wallet',
                'is_active' => true,
                'payment_guide_id' => $eWalletGuideId,
            ],
            [
                'name' => 'ShopeePay',
                'category' => 'e-wallet',
                'is_active' => true,
                'payment_guide_id' => $eWalletGuideId,
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
