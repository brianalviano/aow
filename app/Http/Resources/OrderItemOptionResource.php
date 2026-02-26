<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemOptionResource extends JsonResource
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
            'order_item_id' => $this->order_item_id,
            'product_option_id' => $this->product_option_id,
            'product_option_item_id' => $this->product_option_item_id,
            'extra_price' => $this->extra_price,
            'product_option' => new ProductOptionResource($this->whenLoaded('productOption')),
            'product_option_item' => new ProductOptionItemResource($this->whenLoaded('productOptionItem')),
        ];
    }
}
