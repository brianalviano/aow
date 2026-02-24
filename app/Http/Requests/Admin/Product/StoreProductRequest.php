<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'product_category_id' => ['required', 'string', 'uuid', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'stock_limit' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.is_required' => ['sometimes', 'boolean'],
            'options.*.is_multiple' => ['sometimes', 'boolean'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.items' => ['required', 'array', 'min:1'],
            'options.*.items.*.name' => ['required', 'string', 'max:255'],
            'options.*.items.*.extra_price' => ['nullable', 'integer', 'min:0'],
            'options.*.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
