<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for ChefTransfer model.
 *
 * Exposes transfer details including snapshot fee data and proof URL.
 */
class ChefTransferResource extends JsonResource
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
            'chef_id'         => $this->chef_id,
            'amount'          => $this->amount,
            'fee_percentage'  => $this->fee_percentage,
            'fee_amount'      => $this->fee_amount,
            'gross_amount'    => $this->gross_amount,
            'note'            => $this->note,
            'transfer_proof'  => $this->transfer_proof,
            'transferred_at'  => $this->transferred_at,
            'created_at'      => $this->created_at,
        ];
    }
}
