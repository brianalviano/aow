<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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
                'description' => 'Batas waktu order H-1 (format HH:MM)',
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
            // Konfigurasi Kurir
            // -------------------------------------------------------
            [
                'key'         => 'free_courier_min_order',
                'value'       => '50000',
                'description' => 'Minimal total order untuk mendapatkan gratis ongkir',
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

            // -------------------------------------------------------
            // Notifikasi Telegram (Admin)
            // -------------------------------------------------------
            [
                'key'         => 'telegram_enabled',
                'value'       => 'true',
                'description' => 'Aktifkan notifikasi Telegram ke admin: true | false',
            ],
            [
                'key'         => 'telegram_bot_token',
                'value'       => '8394429134:AAGP310DKb8ciYpL2TX_J9p5dxHUzVQLNM0',
                'description' => 'Token bot Telegram dari @BotFather',
            ],
            [
                'key'         => 'telegram_admin_chat_id',
                'value'       => '483432151',
                'description' => 'Chat ID Telegram admin (didapat dari getUpdates)',
            ],
            [
                'key'         => 'telegram_notify_order_created',
                'value'       => 'true',
                'description' => 'Kirim notif Telegram saat ada order baru masuk',
            ],
            [
                'key'         => 'telegram_notify_order_paid',
                'value'       => 'true',
                'description' => 'Kirim notif Telegram saat order berhasil dibayar',
            ],
            [
                'key'         => 'telegram_notify_order_cancelled',
                'value'       => 'true',
                'description' => 'Kirim notif Telegram saat order dibatalkan',
            ],

            // -------------------------------------------------------
            // Notifikasi WhatsApp Cloud API (Customer)
            // -------------------------------------------------------
            [
                'key'         => 'whatsapp_enabled',
                'value'       => 'true',
                'description' => 'Aktifkan notifikasi WhatsApp ke customer: true | false',
            ],
            [
                'key'         => 'whatsapp_access_token',
                'value'       => 'rS6tsScAezVHJzyQFGpc',
                'description' => 'Access token WhatsApp Cloud API dari Meta',
            ],
            [
                'key'         => 'whatsapp_phone_number_id',
                'value'       => null,
                'description' => 'Phone Number ID dari Meta Business Dashboard',
            ],
            [
                'key'         => 'whatsapp_notify_order_created',
                'value'       => 'true',
                'description' => 'Kirim notif WA ke customer saat order berhasil dibuat',
            ],
            [
                'key'         => 'whatsapp_notify_order_confirmed',
                'value'       => 'true',
                'description' => 'Kirim notif WA ke customer saat order dikonfirmasi admin',
            ],
            [
                'key'         => 'whatsapp_notify_order_delivered',
                'value'       => 'true',
                'description' => 'Kirim notif WA ke customer saat order dikirim/delivered',
            ],
        ];

        foreach ($settings as $setting) {
            OrderSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value'       => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
