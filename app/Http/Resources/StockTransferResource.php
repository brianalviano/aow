<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\StockTransferStatus;

/**
 * Resource untuk transformasi StockTransfer ke bentuk JSON untuk frontend.
 *
 * @author PJD
 *
 * @return array<string, mixed>
 */
class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\StockTransfer $tr */
        $tr = $this->resource;

        return [
            'id' => (string) $tr->id,
            'number' => (string) ($tr->number ?? ''),
            'from_warehouse' => $tr->fromWarehouse ? [
                'id' => (string) $tr->fromWarehouse->id,
                'name' => (string) $tr->fromWarehouse->name,
                'address' => $tr->fromWarehouse->address ? (string) $tr->fromWarehouse->address : null,
                'whatsapp' => $tr->fromWarehouse->phone ? (string) $tr->fromWarehouse->phone : null,
            ] : ['id' => null, 'name' => null, 'address' => null, 'whatsapp' => null],
            'to_warehouse' => $tr->toWarehouse ? [
                'id' => (string) $tr->toWarehouse->id,
                'name' => (string) $tr->toWarehouse->name,
                'address' => $tr->toWarehouse->address ? (string) $tr->toWarehouse->address : null,
                'whatsapp' => $tr->toWarehouse->phone ? (string) $tr->toWarehouse->phone : null,
            ] : ['id' => null, 'name' => null, 'address' => null, 'whatsapp' => null],
            'to_owner_user' => $tr->toOwnerUser ? [
                'id' => (string) $tr->toOwnerUser->id,
                'name' => (string) $tr->toOwnerUser->name,
                'address' => $tr->toOwnerUser->address ? (string) $tr->toOwnerUser->address : null,
                'whatsapp' => $tr->toOwnerUser->phone_number ? (string) $tr->toOwnerUser->phone_number : null,
            ] : ['id' => null, 'name' => null, 'address' => null, 'whatsapp' => null],
            'transfer_date' => $tr->transfer_date ? $tr->transfer_date->toDateString() : null,
            'notes' => $tr->notes ? (string) $tr->notes : null,
            'status' => $tr->status instanceof StockTransferStatus ? (string) $tr->status->value : (string) $tr->status,
            'status_label' => $tr->status instanceof StockTransferStatus ? $tr->status->label() : ($tr->status ? StockTransferStatus::from((string) $tr->status)->label() : null),
            'created_at' => $tr->created_at ? $tr->created_at->toDateTimeString() : null,
            'updated_at' => $tr->updated_at ? $tr->updated_at->toDateTimeString() : null,
            'items' => $tr->items ? $tr->items->map(function ($it) {
                return [
                    'id' => (string) $it->id,
                    'product' => $it->product ? [
                        'id' => (string) $it->product->id,
                        'name' => (string) $it->product->name,
                    ] : ['id' => null, 'name' => null],
                    'quantity' => (int) $it->quantity,
                ];
            })->toArray() : [],
        ];
    }
}
