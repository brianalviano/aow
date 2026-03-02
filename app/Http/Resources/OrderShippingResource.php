<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk OrderShipping.
 *
 * Serializes per-chef shipping data untuk ditampilkan di frontend.
 */
class OrderShippingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'chef_id'               => $this->chef_id,
            'courier_company'       => $this->courier_company,
            'courier_type'          => $this->courier_type,
            'courier_name'          => $this->courier_name,
            'shipping_fee'          => $this->shipping_fee,
            'origin_address'        => $this->origin_address,
            'origin_latitude'       => $this->origin_latitude,
            'origin_longitude'      => $this->origin_longitude,
            'destination_latitude'  => $this->destination_latitude,
            'destination_longitude' => $this->destination_longitude,
            'biteship_order_id'     => $this->biteship_order_id,
            'biteship_tracking_id'  => $this->biteship_tracking_id,
            'biteship_waybill_id'   => $this->biteship_waybill_id,
            'biteship_status'       => $this->biteship_status,
            'chef'                  => $this->whenLoaded('chef', fn() => [
                'id'   => $this->chef->id,
                'name' => $this->chef->name,
            ]),
        ];
    }
}
