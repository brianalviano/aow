<?php

declare(strict_types=1);

namespace App\Http\Requests\ProductPrice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi untuk penyimpanan batch harga produk.
 */
class StoreProductPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.type' => ['required', Rule::in(['purchase', 'selling', 'supplier'])],
            'entries.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'entries.*.selling_price_level_id' => [
                'nullable',
                'uuid',
                'exists:selling_price_levels,id',
            ],
            'entries.*.supplier_id' => [
                'nullable',
                'uuid',
                'exists:suppliers,id',
                'required_if:entries.*.type,supplier',
            ],
            'entries.*.price' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
