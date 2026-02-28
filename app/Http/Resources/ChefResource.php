<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Chef model.
 *
 * Exposes chef data along with computed sales/balance metrics
 * when those values are loaded (via `whenLoaded` / `when`).
 */
class ChefResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'phone'           => $this->phone,
            'bank_name'       => $this->bank_name,
            'account_number'  => $this->account_number,
            'account_name'    => $this->account_name,
            'note'            => $this->note,
            'fee_percentage'  => $this->fee_percentage,
            'address'         => $this->address,
            'longitude'       => $this->longitude,
            'latitude'        => $this->latitude,
            'email'           => $this->email,
            'is_active'       => $this->is_active,
            'order_types'      => $this->order_types,
            'products'        => ProductResource::collection($this->whenLoaded('products')),
            'transfers'       => ChefTransferResource::collection($this->whenLoaded('transfers')),

            // Computed sales data — set by ChefService::enrichWithSalesData()
            'total_sales'         => $this->when(isset($this->resource->total_sales), $this->resource->total_sales ?? 0),
            'total_fee_amount'    => $this->when(isset($this->resource->total_fee_amount), $this->resource->total_fee_amount ?? 0),
            'net_sales'           => $this->when(isset($this->resource->net_sales), $this->resource->net_sales ?? 0),
            'total_transferred'   => $this->when(isset($this->resource->total_transferred), $this->resource->total_transferred ?? 0),
            'outstanding_balance' => $this->when(
                isset($this->resource->outstanding_balance),
                $this->resource->outstanding_balance ?? 0,
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
