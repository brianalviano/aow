<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileHelperTrait;

class SupplierResource extends JsonResource
{
    use FileHelperTrait;

    public function toArray(Request $request): array
    {
        /** @var \App\Models\Supplier $supplier */
        $supplier = $this->resource;

        return [
            'id' => (string) $supplier->id,
            'name' => (string) $supplier->name,
            'email' => (string) $supplier->email,
            'phone' => $supplier->phone ? (string) $supplier->phone : null,
            'address' => $supplier->address ? (string) $supplier->address : null,
            'birth_date' => $supplier->birth_date ? $supplier->birth_date->toDateString() : null,
            'gender' => $supplier->gender ?? null,
            'is_active' => (bool) $supplier->is_active,
            'photo_url' => $this->getFileUrl($supplier->photo),
            'created_at' => $supplier->created_at ? $supplier->created_at->toDateTimeString() : null,
            'updated_at' => $supplier->updated_at ? $supplier->updated_at->toDateTimeString() : null,
        ];
    }
}
