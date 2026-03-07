<?php

declare(strict_types=1);

namespace App\DTOs\Setting;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for application settings (company profile + order settings).
 *
 * Contains complex array reshaping logic in fromUpdateRequest() to transform
 * flat request fields into structured companyProfile/orderSettings arrays
 * matching the settings storage format.
 *
 * @property array<string, mixed> $companyProfile Company profile settings
 * @property array<string, mixed> $orderSettings Order-related settings
 */
class SettingData extends Data
{
    public function __construct(
        public readonly array $companyProfile,
        public readonly array $orderSettings,
    ) {}

    /**
     * Validation rules for all setting fields (flat structure from form).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // Company Profile
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],

            // Order Settings
            'order_cutoff_time' => ['nullable', 'string', 'max:5'],
            'order_min_days_ahead' => ['nullable', 'integer', 'min:0'],
            'instant_order_start_time' => ['nullable', 'string', 'max:5'],
            'instant_order_end_time' => ['nullable', 'string', 'max:5'],

            'delivery_fee_mode' => ['nullable', 'string', 'in:per_drop_point,flat,free'],
            'delivery_fee_flat' => ['nullable', 'numeric', 'min:0'],
            'free_courier_min_order' => ['nullable', 'numeric', 'min:0'],

            'admin_fee_enabled' => ['nullable', 'boolean'],
            'admin_fee_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'admin_fee_value' => ['nullable', 'numeric', 'min:0'],

            'payment_expired_duration' => ['nullable', 'integer', 'min:1'],

            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string'],
            'telegram_admin_chat_id' => ['nullable', 'string'],
            'telegram_notify_order_created' => ['nullable', 'boolean'],
            'telegram_notify_order_paid' => ['nullable', 'boolean'],
            'telegram_notify_order_cancelled' => ['nullable', 'boolean'],

            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_access_token' => ['nullable', 'string'],
            'whatsapp_phone_id' => ['nullable', 'string'],
            'whatsapp_notify_order_created' => ['nullable', 'boolean'],
            'whatsapp_notify_order_confirmed' => ['nullable', 'boolean'],
            'whatsapp_notify_order_delivered' => ['nullable', 'boolean'],

            'tax_enabled' => ['nullable', 'boolean'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Create DTO from validated request data with array reshaping.
     *
     * Transforms flat form fields into structured companyProfile/orderSettings arrays.
     * This factory method is retained because the DTO structure (nested arrays)
     * differs from the flat form input structure.
     *
     * @param array<string, mixed> $validated Validated request data
     * @return self
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            companyProfile: [
                'name' => $validated['name'] ?? '',
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'address' => $validated['address'] ?? null,
                'instagram' => $validated['instagram'] ?? null,
                'facebook' => $validated['facebook'] ?? null,
                'tiktok' => $validated['tiktok'] ?? null,
            ],
            orderSettings: [
                'order_cutoff_time' => $validated['order_cutoff_time'] ?? null,
                'order_min_days_ahead' => $validated['order_min_days_ahead'] ?? null,

                'delivery_fee_mode' => $validated['delivery_fee_mode'] ?? null,
                'delivery_fee_flat' => $validated['delivery_fee_flat'] ?? null,
                'free_courier_min_order' => $validated['free_courier_min_order'] ?? null,

                'admin_fee_enabled' => isset($validated['admin_fee_enabled'])
                    ? ($validated['admin_fee_enabled'] ? 'true' : 'false') : 'false',
                'admin_fee_type' => $validated['admin_fee_type'] ?? null,
                'admin_fee_value' => $validated['admin_fee_value'] ?? null,

                'payment_expired_duration' => $validated['payment_expired_duration'] ?? null,

                'telegram_enabled' => isset($validated['telegram_enabled'])
                    ? ($validated['telegram_enabled'] ? 'true' : 'false') : 'false',
                'telegram_bot_token' => $validated['telegram_bot_token'] ?? null,
                'telegram_admin_chat_id' => $validated['telegram_admin_chat_id'] ?? null,
                'telegram_notify_order_created' => isset($validated['telegram_notify_order_created'])
                    ? ($validated['telegram_notify_order_created'] ? 'true' : 'false') : 'false',
                'telegram_notify_order_paid' => isset($validated['telegram_notify_order_paid'])
                    ? ($validated['telegram_notify_order_paid'] ? 'true' : 'false') : 'false',
                'telegram_notify_order_cancelled' => isset($validated['telegram_notify_order_cancelled'])
                    ? ($validated['telegram_notify_order_cancelled'] ? 'true' : 'false') : 'false',

                'whatsapp_enabled' => isset($validated['whatsapp_enabled'])
                    ? ($validated['whatsapp_enabled'] ? 'true' : 'false') : 'false',
                'whatsapp_access_token' => $validated['whatsapp_access_token'] ?? null,
                'whatsapp_phone_id' => $validated['whatsapp_phone_id'] ?? null,
                'whatsapp_notify_order_created' => isset($validated['whatsapp_notify_order_created'])
                    ? ($validated['whatsapp_notify_order_created'] ? 'true' : 'false') : 'false',
                'whatsapp_notify_order_confirmed' => isset($validated['whatsapp_notify_order_confirmed'])
                    ? ($validated['whatsapp_notify_order_confirmed'] ? 'true' : 'false') : 'false',
                'whatsapp_notify_order_delivered' => isset($validated['whatsapp_notify_order_delivered'])
                    ? ($validated['whatsapp_notify_order_delivered'] ? 'true' : 'false') : 'false',

                'tax_enabled' => isset($validated['tax_enabled'])
                    ? ($validated['tax_enabled'] ? 'true' : 'false') : 'false',
                'tax_percentage' => $validated['tax_percentage'] ?? '12',
            ],
        );
    }
}
