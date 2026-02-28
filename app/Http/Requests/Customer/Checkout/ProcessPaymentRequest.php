<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\Checkout;

use App\Enums\DropPointCategory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for processing payment and creating an order.
 * 
 * Validates customer information, payment method, and delivery schedule.
 */
class ProcessPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        $dropPoint = session('checkout_drop_point');
        $isSchool = $dropPoint && ($dropPoint['category'] ?? '') === DropPointCategory::SCHOOL->value;

        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'school_class' => ($isSchool ? 'required' : 'nullable') . '|string|max:255',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'delivery_time' => 'nullable|date_format:H:i',
        ];
    }
}
