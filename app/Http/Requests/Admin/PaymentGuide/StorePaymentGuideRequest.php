<?php

namespace App\Http\Requests\Admin\PaymentGuide;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentGuideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'content' => ['required', 'array'],
            'content.*.title' => ['required', 'string', 'max:255'],
            'content.*.items' => ['required', 'array'],
            'content.*.items.*' => ['required', 'string'],
        ];
    }
}
