<?php

declare(strict_types=1);

namespace App\DTOs\Setting;

use App\Models\OrderSetting;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for order-related system settings.
 *
 * Loaded from the order_settings table and cached indefinitely.
 * The cache is busted manually when settings are updated.
 *
 * @see \App\Services\SettingService::update()
 */
class OrderSettingsDTO extends Data
{
    public function __construct(
        public readonly string $orderCutoffTime,
        public readonly int $orderMinDaysAhead,
        public readonly string $instantOrderStartTime,
        public readonly string $instantOrderEndTime,

        public readonly string $deliveryFeeMode,
        public readonly int $deliveryFeeFlat,

        public readonly int $freeCourierMinOrder,

        public readonly bool $adminFeeEnabled,
        public readonly string $adminFeeType,
        public readonly int $adminFeeValue,

        public readonly int $paymentExpiredDuration,

        public readonly bool $telegramEnabled,
        public readonly ?string $telegramBotToken,
        public readonly ?string $telegramAdminChatId,
        public readonly bool $telegramNotifyOrderCreated,
        public readonly bool $telegramNotifyOrderPaid,
        public readonly bool $telegramNotifyOrderCancelled,

        public readonly bool $whatsappEnabled,
        public readonly ?string $whatsappAccessToken,
        public readonly ?string $whatsappPhoneId,
        public readonly bool $whatsappNotifyOrderCreated,
        public readonly bool $whatsappNotifyOrderConfirmed,
        public readonly bool $whatsappNotifyOrderDelivered,

        public readonly bool $taxEnabled,
        public readonly int $taxPercentage,
    ) {}

    /**
     * Load order settings from cache (or DB on cache miss).
     *
     * @return self
     */
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

    /**
     * Bust the cached settings.
     */
    public static function clearCache(): void
    {
        Cache::forget('settings:order_settings_dto');
    }
}
