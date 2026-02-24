<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for PaymentMethod model.
 *
 * @property \App\Models\PaymentMethod $resource
 */
class PaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'category' => $this->resource->category,
            'photo' => $this->resource->photo ? asset('storage/' . $this->resource->photo) : null,
            'is_active' => $this->resource->is_active,
            'type' => $this->resource->type,
            'account_number' => $this->resource->account_number,
            'account_name' => $this->resource->account_name,
            'created_at' => $this->resource->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->resource->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
