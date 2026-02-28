<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Chef;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a new Chef.
 */
class StoreChefRequest extends FormRequest
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
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:chefs,email'],
            'password'       => ['required', 'string', 'min:8'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'bank_name'      => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_name'   => ['nullable', 'string', 'max:255'],
            'note'           => ['nullable', 'string'],
            'fee_percentage' => ['required', 'numeric', 'between:0,100'],
            'address'        => ['nullable', 'string'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'is_active'      => ['sometimes', 'boolean'],
            'order_type'     => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\ChefOrderType::values())],
            'product_ids'    => ['nullable', 'array'],
            'product_ids.*'  => ['uuid', 'exists:products,id'],
        ];
    }
}
