<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\PurchaseOrderStatus;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\PurchaseOrder $po */
        $po = $this->resource;

        return [
            'id' => (string) $po->id,
            'number' => (string) $po->number,
            'supplier_source' => $po->supplier_source?->value ?? null,
            'supplier' => $po->supplier ? [
                'id' => (string) $po->supplier->id,
                'name' => (string) $po->supplier->name,
                'phone' => (string) ($po->supplier->phone ?? ''),
                'email' => (string) ($po->supplier->email ?? ''),
                'address' => (string) ($po->supplier->address ?? ''),
            ] : [
                'id' => null,
                'name' => $po->supplier_name ? (string) $po->supplier_name : null,
                'phone' => $po->supplier_phone ? (string) $po->supplier_phone : null,
                'email' => $po->supplier_email ? (string) $po->supplier_email : null,
                'address' => $po->supplier_address ? (string) $po->supplier_address : null,
            ],
            'warehouse' => $po->warehouse ? [
                'id' => (string) $po->warehouse->id,
                'name' => (string) $po->warehouse->name,
                'address' => $po->warehouse->address ? (string) $po->warehouse->address : null,
                'phone' => $po->warehouse->phone ? (string) $po->warehouse->phone : null,
            ] : ['id' => null, 'name' => null, 'address' => null, 'phone' => null],
            'order_date' => $po->order_date ? $po->order_date->toDateString() : null,
            'due_date' => $po->due_date ? $po->due_date->toDateString() : null,
            'notes' => $po->notes ? (string) $po->notes : null,
            'status' => $po->status?->value ?? null,
            'status_label' => $po->status instanceof PurchaseOrderStatus ? $po->status->label() : null,
            'subtotal' => (int) $po->subtotal,
            'delivery_cost' => (int) $po->delivery_cost,
            'total_before_discount' => (int) $po->total_before_discount,
            'discount_percentage' => $po->discount_percentage ? (string) $po->discount_percentage : null,
            'discount_amount' => (int) $po->discount_amount,
            'total_after_discount' => (int) $po->total_after_discount,
            'value_added_tax_included' => (bool) $po->value_added_tax_included,
            'is_value_added_tax_enabled' => (bool) $po->is_value_added_tax_enabled,
            'value_added_tax_id' => $po->value_added_tax_id ? (string) $po->value_added_tax_id : null,
            'value_added_tax_percentage' => $po->value_added_tax_percentage ? (string) $po->value_added_tax_percentage : null,
            'value_added_tax_amount' => (int) $po->value_added_tax_amount,
            'total_after_value_added_tax' => (int) $po->total_after_value_added_tax,
            'is_income_tax_enabled' => (bool) $po->is_income_tax_enabled,
            'income_tax_id' => $po->income_tax_id ? (string) $po->income_tax_id : null,
            'income_tax_percentage' => $po->income_tax_percentage ? (string) $po->income_tax_percentage : null,
            'income_tax_amount' => (int) $po->income_tax_amount,
            'total_after_income_tax' => (int) $po->total_after_income_tax,
            'grand_total' => (int) $po->grand_total,
            'supplier_invoice_number' => $po->supplier_invoice_number ? (string) $po->supplier_invoice_number : null,
            'supplier_invoice_file' => $po->supplier_invoice_file ? (string) $po->supplier_invoice_file : null,
            'supplier_invoice_date' => $po->supplier_invoice_date ? $po->supplier_invoice_date->toDateString() : null,
            'created_at' => $po->created_at ? $po->created_at->toDateTimeString() : null,
            'updated_at' => $po->updated_at ? $po->updated_at->toDateTimeString() : null,
            'items' => $po->items ? $po->items->map(function ($it) {
                return [
                    'id' => (string) $it->id,
                    'purchase_request_item_id' => $it->purchase_request_item_id ? (string) $it->purchase_request_item_id : null,
                    'product' => $it->product ? [
                        'id' => (string) $it->product->id,
                        'name' => (string) $it->product->name,
                    ] : ['id' => null, 'name' => null],
                    'quantity' => (int) $it->quantity,
                    'price' => (int) $it->price,
                    'subtotal' => (int) $it->subtotal,
                    'notes' => $it->notes ? (string) $it->notes : null,
                ];
            })->toArray() : [],
        ];
    }
}
