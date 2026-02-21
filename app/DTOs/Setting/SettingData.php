<?php

namespace App\DTOs\Setting;

use App\Http\Requests\Setting\UpdateSettingRequest;

class SettingData
{
    public function __construct(
        public array $companyProfile,
        public array $orderSettings,
    ) {}

    public static function fromUpdateRequest(UpdateSettingRequest $request): self
    {
        $p = $request->validated();

        return new self(
            companyProfile: [
                'name' => $p['name'] ?? '',
                'email' => $p['email'] ?? null,
                'phone' => $p['phone'] ?? null,
                'whatsapp' => $p['whatsapp'] ?? null,
                'address' => $p['address'] ?? null,
                'instagram' => $p['instagram'] ?? null,
                'facebook' => $p['facebook'] ?? null,
                'tiktok' => $p['tiktok'] ?? null,
            ],
            orderSettings: [
                'order_cutoff_time' => $p['order_cutoff_time'] ?? null,
                'order_min_days_ahead' => $p['order_min_days_ahead'] ?? null,

                'delivery_fee_mode' => $p['delivery_fee_mode'] ?? null,
                'delivery_fee_flat' => $p['delivery_fee_flat'] ?? null,
                'free_courier_min_order' => $p['free_courier_min_order'] ?? null,

                'admin_fee_enabled' => isset($p['admin_fee_enabled']) ? ($p['admin_fee_enabled'] ? 'true' : 'false') : 'false',
                'admin_fee_type' => $p['admin_fee_type'] ?? null,
                'admin_fee_value' => $p['admin_fee_value'] ?? null,

                'payment_expired_duration' => $p['payment_expired_duration'] ?? null,

                'telegram_enabled' => isset($p['telegram_enabled']) ? ($p['telegram_enabled'] ? 'true' : 'false') : 'false',
                'telegram_bot_token' => $p['telegram_bot_token'] ?? null,
                'telegram_admin_chat_id' => $p['telegram_admin_chat_id'] ?? null,
                'telegram_notify_order_created' => isset($p['telegram_notify_order_created']) ? ($p['telegram_notify_order_created'] ? 'true' : 'false') : 'false',
                'telegram_notify_order_paid' => isset($p['telegram_notify_order_paid']) ? ($p['telegram_notify_order_paid'] ? 'true' : 'false') : 'false',
                'telegram_notify_order_cancelled' => isset($p['telegram_notify_order_cancelled']) ? ($p['telegram_notify_order_cancelled'] ? 'true' : 'false') : 'false',

                'whatsapp_enabled' => isset($p['whatsapp_enabled']) ? ($p['whatsapp_enabled'] ? 'true' : 'false') : 'false',
                'whatsapp_access_token' => $p['whatsapp_access_token'] ?? null,
                'whatsapp_phone_number_id' => $p['whatsapp_phone_number_id'] ?? null,
                'whatsapp_notify_order_created' => isset($p['whatsapp_notify_order_created']) ? ($p['whatsapp_notify_order_created'] ? 'true' : 'false') : 'false',
                'whatsapp_notify_order_confirmed' => isset($p['whatsapp_notify_order_confirmed']) ? ($p['whatsapp_notify_order_confirmed'] ? 'true' : 'false') : 'false',
                'whatsapp_notify_order_delivered' => isset($p['whatsapp_notify_order_delivered']) ? ($p['whatsapp_notify_order_delivered'] ? 'true' : 'false') : 'false',
            ],
        );
    }
}
