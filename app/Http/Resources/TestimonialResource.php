<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TestimonialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'customer_id' => $this->customer_id,
            'order_id'    => $this->order_id,
            'rating'      => $this->rating,
            'content'     => $this->content,
            'photo_url'   => $this->photo,
            'is_approved' => $this->is_approved,
            'customer'    => $this->whenLoaded('customer', function () {
                return [
                    'name' => $this->customer?->name,
                ];
            }),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
