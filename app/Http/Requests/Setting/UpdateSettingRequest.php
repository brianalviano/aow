<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Company Profile
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'instagram' => ['nullable', 'string', 'max:255'], // could be URL or username
            'facebook' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],

            // Order Settings
            'order_cutoff_time' => ['nullable', 'string', 'max:5'], // 20:00
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

            // Tax (PPN)
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
