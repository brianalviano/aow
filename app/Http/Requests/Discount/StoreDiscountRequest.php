<?php

declare(strict_types=1);

namespace App\Http\Requests\Discount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\{Product, ProductCategory};

final class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:discounts,name'],
            'type' => ['required', Rule::in(['basic', 'bogo'])],
            'scope' => ['required', Rule::in(['global', 'specific'])],
            'value_type' => ['nullable', Rule::in(['percentage', 'nominal']), 'required_if:type,basic', 'prohibited_if:type,bogo'],
            'value' => ['nullable', 'numeric', 'min:0', 'required_if:type,basic'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'is_active' => ['required', 'boolean'],
            'items' => ['nullable', 'array', 'min:1', 'required_if:scope,specific'],
            'items.*.itemable_type' => ['nullable', Rule::in([Product::class, ProductCategory::class]), 'required_if:scope,specific'],
            'items.*.itemable_id' => ['nullable', 'uuid', 'required_if:scope,specific'],
            'items.*.min_qty_buy' => ['nullable', 'integer', 'min:1'],
            'items.*.is_multiple' => ['nullable', 'boolean'],
            'items.*.free_product_id' => ['nullable', 'uuid', 'exists:products,id', 'required_if:type,bogo'],
            'items.*.free_qty_get' => ['nullable', 'integer', 'min:0', 'required_if:type,bogo'],
            'items.*.custom_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        if ($validator instanceof Validator) {
            $validator->after(function (Validator $v) {
                $scope = (string) ($this->input('scope') ?? '');
                if ($scope !== 'specific') {
                    return;
                }
                $items = (array) ($this->input('items') ?? []);
                foreach ($items as $idx => $row) {
                    $type = (string) ($row['itemable_type'] ?? '');
                    $id = (string) ($row['itemable_id'] ?? '');
                    if ($type === Product::class) {
                        $exists = Product::query()->whereKey($id)->exists();
                        if (!$exists) {
                            $v->errors()->add("items.$idx.itemable_id", 'Produk tidak ditemukan.');
                        }
                    } elseif ($type === ProductCategory::class) {
                        $exists = ProductCategory::query()->whereKey($id)->exists();
                        if (!$exists) {
                            $v->errors()->add("items.$idx.itemable_id", 'Kategori produk tidak ditemukan.');
                        }
                    } else {
                        $v->errors()->add("items.$idx.itemable_type", 'Tipe item tidak valid.');
                    }
                }
            });
        }
    }
}
