<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PaymentMethod;

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(PaymentMethodCategory::class)],
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'code' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'payment_guide_id' => ['nullable', 'exists:payment_guides,id'],
        ];
    }
}
