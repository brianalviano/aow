<?php

declare(strict_types=1);

namespace App\Http\Requests\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Traits\FileHelperTrait;

final class UpdatePaymentMethodRequest extends FormRequest
{
    use FileHelperTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentMethod = $this->route('payment_method');
        $id = $paymentMethod instanceof PaymentMethod ? $paymentMethod->getKey() : null;

        $imageRules = $this->getFileValidationRules(false, [
            'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'],
        ]);

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('payment_methods', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => $imageRules,
            'mdr_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
