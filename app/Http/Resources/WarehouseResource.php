<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Warehouse $w */
        $w = $this->resource;
        return [
            'id' => (string) $w->id,
            'name' => (string) $w->name,
            'code' => (string) $w->code,
            'address' => $w->address ? (string) $w->address : null,
            'is_central' => (bool) $w->is_central,
            'phone' => $w->phone ? (string) $w->phone : null,
            'is_active' => (bool) $w->is_active,
            'created_at' => $w->created_at ? $w->created_at->toDateTimeString() : null,
            'updated_at' => $w->updated_at ? $w->updated_at->toDateTimeString() : null,
        ];
    }
}

