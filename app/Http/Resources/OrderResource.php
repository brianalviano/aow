<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'id'                => $this->id,
            'number'            => $this->number,
            'drop_point_id'     => $this->drop_point_id,
            'pick_up_point_id'  => $this->pick_up_point_id,
            'customer_id'       => $this->customer_id,
            'delivery_date'     => $this->delivery_date?->format('Y-m-d'),
            'delivery_time'     => $this->delivery_time?->format('H:i'),
            'payment_method_id' => $this->payment_method_id,
            'barcode'           => $this->barcode,
            'tracking_number'   => $this->tracking_number,
            'shipping_method'   => $this->shipping_method,
            'payment_status'    => $this->payment_status,
            'order_status'      => $this->order_status,
            'note'               => $this->note,
            'cancellation_note'  => $this->cancellation_note,
            'payment_proof_url' => $this->payment_proof,
            'discount_amount'          => $this->discount_amount,
            'total_amount'             => $this->total_amount,
            'delivery_fee'             => $this->delivery_fee,
            'delivery_discount_amount' => $this->delivery_discount_amount,
            'final_delivery_fee'       => $this->final_delivery_fee,
            'admin_fee'                => $this->admin_fee,
            'tax_amount'               => $this->tax_amount,
            'payment_expired_at'       => $this->payment_expired_at?->toIso8601String(),
            'delivered_at'             => $this->delivered_at?->toIso8601String(),
            'created_at'               => $this->created_at?->toIso8601String(),
            'updated_at'               => $this->updated_at?->toIso8601String(),
            'chef_status_summary'      => $this->chef_status_summary,

            // Relationships
            'items'          => $this->whenLoaded('items', fn() => OrderItemResource::collection($this->items)->resolve()),
            'shippings'      => $this->whenLoaded('shippings', fn() => OrderShippingResource::collection($this->shippings)->resolve()),
            'drop_point'     => $this->whenLoaded('dropPoint', fn() => (new DropPointResource($this->dropPoint))->resolve()),
            'pick_up_point'  => $this->whenLoaded('pickUpPoint', fn() => $this->pickUpPoint ? [
                'id' => $this->pickUpPoint->id,
                'name' => $this->pickUpPoint->name,
                'address' => $this->pickUpPoint->address,
                'latitude' => $this->pickUpPoint->latitude,
                'longitude' => $this->pickUpPoint->longitude,
            ] : null),
            'customer_address' => $this->whenLoaded('customerAddress', fn() => (new CustomerAddressResource($this->customerAddress))->resolve()),
            'customer'       => $this->whenLoaded('customer', fn() => (new CustomerResource($this->customer))->resolve()),
            'payment_method' => $this->whenLoaded('paymentMethod', fn() => (new PaymentMethodResource($this->paymentMethod))->resolve()),
        ];
    }
}
