<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\OrderSetting;

class OrderSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // -------------------------------------------------------
            // Konfigurasi Pre-Order
            // -------------------------------------------------------
            [
                'key'         => 'order_cutoff_time',
                'value'       => '20:00',
                'description' => 'Batas waktu order H-1',
            ],
            [
                'key'         => 'order_min_days_ahead',
                'value'       => '1',
                'description' => 'Minimal H berapa sebelum delivery date (1 = H-1)',
            ],

            // -------------------------------------------------------
            // Konfigurasi Ongkir
            // -------------------------------------------------------
            [
                'key'         => 'delivery_fee_mode',
                'value'       => 'per_drop_point',
                'description' => 'Mode ongkir: per_drop_point | flat | free',
            ],
            [
                'key'         => 'delivery_fee_flat',
                'value'       => '0',
                'description' => 'Nominal ongkir flat (dipakai jika delivery_fee_mode = flat)',
            ],

            // -------------------------------------------------------
            // Konfigurasi Biaya Admin
            // -------------------------------------------------------
            [
                'key'         => 'admin_fee_enabled',
                'value'       => 'false',
                'description' => 'Aktifkan biaya admin: true | false',
            ],
            [
                'key'         => 'admin_fee_type',
                'value'       => 'fixed',
                'description' => 'Tipe biaya admin: fixed | percentage',
            ],
            [
                'key'         => 'admin_fee_value',
                'value'       => '0',
                'description' => 'Nilai biaya admin (nominal Rp atau persentase %)',
            ],

            // -------------------------------------------------------
            // Konfigurasi Pembayaran
            // -------------------------------------------------------
            [
                'key'         => 'payment_expired_duration',
                'value'       => '60',
                'description' => 'Durasi kedaluwarsa pembayaran dalam menit (Midtrans)',
            ],
        ];

        foreach ($settings as $setting) {
            OrderSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'id'          => Str::uuid(),
                    'value'       => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
