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
        $qrisGuideId = \App\Models\PaymentGuide::where('name', 'QRIS')->first()?->id;

        $methods = [
            [
                'name' => 'Cash',
                'category' => \App\Enums\PaymentMethodCategory::CASH,
                'type' => \App\Enums\PaymentMethodType::MANUAL,
                'is_active' => true,
            ],
            [
                'name' => 'Transfer Bank BCA',
                'category' => \App\Enums\PaymentMethodCategory::BANK_TRANSFER,
                'type' => \App\Enums\PaymentMethodType::MANUAL,
                'is_active' => true,
                'account_number' => '7240081851',
                'account_name' => 'NUNING RAHMAWATI SE',
                'payment_guide_id' => $manualGuideId,
            ],
            [
                'name' => 'QRIS',
                'category' => \App\Enums\PaymentMethodCategory::E_WALLET,
                'type' => \App\Enums\PaymentMethodType::GATEWAY,
                'code' => 'qris',
                'is_active' => true,
                'payment_guide_id' => $qrisGuideId,
            ],
            [
                'name' => 'OVO',
                'code' => 'ovo',
                'category' => \App\Enums\PaymentMethodCategory::E_WALLET,
                'type' => \App\Enums\PaymentMethodType::MANUAL,
                'is_active' => true,
                'payment_guide_id' => $eWalletGuideId,
            ],
            [
                'name' => 'ShopeePay',
                'code' => 'shopeepay',
                'category' => \App\Enums\PaymentMethodCategory::E_WALLET,
                'type' => \App\Enums\PaymentMethodType::MANUAL,
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
