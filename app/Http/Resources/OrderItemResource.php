<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'discount_id' => $this->discount_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'discount_amount' => $this->discount_amount,
            'final_price' => $this->final_price,
            'subtotal' => $this->subtotal,
            'note' => $this->note,
            'chef_id' => $this->chef_id,
            'chef_status' => $this->chef_status,
            'chef_confirmed_at' => $this->chef_confirmed_at?->toIso8601String(),
            'can_give_testimonial' => $this->canBeTestimonialed(),
            'testimonial' => $this->whenLoaded('testimonial', fn() => (new TestimonialResource($this->testimonial))->resolve()),
            'product' => $this->whenLoaded('product', fn() => (new ProductResource($this->product))->resolve()),
            'chef' => $this->whenLoaded('chef', fn() => (new ChefResource($this->chef))->resolve()),
            'options' => $this->whenLoaded('options', fn() => OrderItemOptionResource::collection($this->options)->resolve()),
        ];
    }
}
