<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'description' => ['required', 'string'],
            'sku' => ['required', 'string', 'max:13', 'unique:products,sku'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'product_category_id' => ['nullable', 'uuid', 'exists:product_categories,id'],
            'product_sub_category_id' => ['nullable', 'uuid', 'exists:product_sub_categories,id'],
            'product_unit_id' => ['nullable', 'uuid', 'exists:product_units,id'],
            'product_factory_id' => ['nullable', 'uuid', 'exists:product_factories,id'],
            'product_sub_factory_id' => ['nullable', 'uuid', 'exists:product_sub_factories,id'],
            'product_condition_id' => ['nullable', 'uuid', 'exists:product_conditions,id'],
            'product_type' => ['nullable', 'in:raw,ready'],
            'product_variant_type' => ['nullable', 'in:standalone,parent,variant'],
            'parent_product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
