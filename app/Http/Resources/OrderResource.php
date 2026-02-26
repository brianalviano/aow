<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'drop_point_id' => $this->drop_point_id,
            'customer_id' => $this->customer_id,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'delivery_time' => $this->delivery_time?->format('H:i'),
            'payment_method_id' => $this->payment_method_id,
            'barcode' => $this->barcode,
            'tracking_number' => $this->tracking_number,
            'delivery_detail' => $this->delivery_detail,
            'shipping_method' => $this->shipping_method,
            'payment_status' => $this->payment_status,
            'order_status' => $this->order_status,
            'note' => $this->note,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'delivery_fee' => $this->delivery_fee,
            'delivery_discount_amount' => $this->delivery_discount_amount,
            'final_delivery_fee' => $this->final_delivery_fee,
            'admin_fee' => $this->admin_fee,
            'tax_amount' => $this->tax_amount,
            'payment_expired_at' => $this->payment_expired_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'drop_point' => new DropPointResource($this->whenLoaded('dropPoint')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
        ];
    }
}
