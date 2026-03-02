<?php

declare(strict_types=1);

namespace App\DTOs\Setting;

use App\Models\OrderSetting;
use Illuminate\Support\Facades\Cache;

class OrderSettingsDTO
{
    public function __construct(
        public string $orderCutoffTime,
        public int $orderMinDaysAhead,
        public string $instantOrderStartTime,
        public string $instantOrderEndTime,

        public string $deliveryFeeMode,
        public int $deliveryFeeFlat,

        public int $freeCourierMinOrder,

        public bool $adminFeeEnabled,
        public string $adminFeeType,
        public int $adminFeeValue,

        public int $paymentExpiredDuration,

        public bool $telegramEnabled,
        public ?string $telegramBotToken,
        public ?string $telegramAdminChatId,
        public bool $telegramNotifyOrderCreated,
        public bool $telegramNotifyOrderPaid,
        public bool $telegramNotifyOrderCancelled,

        public bool $whatsappEnabled,
        public ?string $whatsappAccessToken,
        public ?string $whatsappPhoneId,
        public bool $whatsappNotifyOrderCreated,
        public bool $whatsappNotifyOrderConfirmed,
        public bool $whatsappNotifyOrderDelivered,

        public bool $taxEnabled,
        public int $taxPercentage,
    ) {}

    public static function load(): self
    {
        return Cache::rememberForever('settings:order_settings_dto', function () {
            $settings = OrderSetting::pluck('value', 'key')->toArray();

            return new self(
                orderCutoffTime: $settings['order_cutoff_time'] ?? '20:00',
                orderMinDaysAhead: (int) ($settings['order_min_days_ahead'] ?? 1),
                instantOrderStartTime: $settings['instant_order_start_time'] ?? '08:00',
                instantOrderEndTime: $settings['instant_order_end_time'] ?? '21:00',

                deliveryFeeMode: $settings['delivery_fee_mode'] ?? 'per_drop_point',
                deliveryFeeFlat: (int) ($settings['delivery_fee_flat'] ?? 0),

                freeCourierMinOrder: (int) ($settings['free_courier_min_order'] ?? 0),

                adminFeeEnabled: ($settings['admin_fee_enabled'] ?? 'false') === 'true',
                adminFeeType: $settings['admin_fee_type'] ?? 'fixed',
                adminFeeValue: (int) ($settings['admin_fee_value'] ?? 0),

                paymentExpiredDuration: (int) ($settings['payment_expired_duration'] ?? 60),

                telegramEnabled: ($settings['telegram_enabled'] ?? 'false') === 'true',
                telegramBotToken: $settings['telegram_bot_token'] ?? null,
                telegramAdminChatId: $settings['telegram_admin_chat_id'] ?? null,
                telegramNotifyOrderCreated: ($settings['telegram_notify_order_created'] ?? 'false') === 'true',
                telegramNotifyOrderPaid: ($settings['telegram_notify_order_paid'] ?? 'false') === 'true',
                telegramNotifyOrderCancelled: ($settings['telegram_notify_order_cancelled'] ?? 'false') === 'true',

                whatsappEnabled: ($settings['whatsapp_enabled'] ?? 'false') === 'true',
                whatsappAccessToken: $settings['whatsapp_access_token'] ?? null,
                whatsappPhoneId: $settings['whatsapp_phone_id'] ?? null,
                whatsappNotifyOrderCreated: ($settings['whatsapp_notify_order_created'] ?? 'false') === 'true',
                whatsappNotifyOrderConfirmed: ($settings['whatsapp_notify_order_confirmed'] ?? 'false') === 'true',
                whatsappNotifyOrderDelivered: ($settings['whatsapp_notify_order_delivered'] ?? 'false') === 'true',

                taxEnabled: ($settings['tax_enabled'] ?? 'false') === 'true',
                taxPercentage: (int) ($settings['tax_percentage'] ?? 0),
            );
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('settings:order_settings_dto');
    }
}
