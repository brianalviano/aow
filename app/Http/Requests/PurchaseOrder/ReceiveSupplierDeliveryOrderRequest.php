<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\{PurchaseReturnReason, PurchaseReturnResolution};

class ReceiveSupplierDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sdo_id' => ['required', 'string', 'exists:supplier_delivery_orders,id'],
            'sender_name' => ['required', 'string', 'max:255'],
            'vehicle_plate_number' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:255'],
            'supplier_invoice_date' => ['nullable', 'date'],
            'supplier_invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'exceptions' => ['nullable', 'array'],
            'exceptions.*.product_id' => ['required_with:exceptions', 'string', 'exists:products,id'],
            'exceptions.*.quantity' => ['required_with:exceptions', 'integer', 'min:1'],
            'exceptions.*.reason' => [
                'required_with:exceptions',
                'string',
                Rule::in(PurchaseReturnReason::values()),
            ],
            'exceptions.*.resolution' => [
                'required_with:exceptions',
                'string',
                Rule::in(PurchaseReturnResolution::values()),
            ],
            'exceptions.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
