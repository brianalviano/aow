<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\{PurchaseOrderSupplierSource, PurchaseOrderStatus};

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(PurchaseOrderStatus::values())],
            'delivery_cost' => ['nullable', 'integer', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_value_added_tax_enabled' => ['nullable', 'boolean'],
            'value_added_tax_included' => ['nullable', 'boolean'],
            'value_added_tax_id' => ['nullable', 'uuid', 'exists:value_added_taxes,id'],
            'value_added_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_income_tax_enabled' => ['nullable', 'boolean'],
            'income_tax_id' => ['nullable', 'uuid', 'exists:income_taxes,id'],
            'income_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:255'],
            'supplier_invoice_file' => ['nullable', 'string', 'max:255'],
            'supplier_invoice_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_request_item_id' => ['nullable', 'uuid', 'exists:purchase_request_items,id'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
