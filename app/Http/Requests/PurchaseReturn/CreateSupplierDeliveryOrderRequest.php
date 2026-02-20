<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PurchaseReturn;

class CreateSupplierDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('purchase_return');
        $supplierId = $routeParam instanceof PurchaseReturn
            ? (string) ($routeParam->supplier_id ?? '')
            : '';
        $numberUniqueRule = Rule::unique('supplier_delivery_orders', 'number');
        if ($supplierId !== '') {
            $numberUniqueRule = $numberUniqueRule->where(fn($q) => $q->where('supplier_id', $supplierId));
        }

        return [
            'delivery_date' => ['required', 'date'],
            'number' => ['required', 'string', 'min:3', 'max:50', $numberUniqueRule],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

