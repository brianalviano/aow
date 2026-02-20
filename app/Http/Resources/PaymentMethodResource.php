<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileHelperTrait;

final class PaymentMethodResource extends JsonResource
{
    use FileHelperTrait;

    public function toArray($request): array
    {
        $pm = $this->resource;
        return [
            'id' => (string) $pm->id,
            'name' => (string) $pm->name,
            'description' => $pm->description ? (string) $pm->description : null,
            'image_url' => $this->getFileUrl($pm->image_url),
            'mdr_percentage' => (string) $pm->mdr_percentage,
            'is_active' => (bool) $pm->is_active,
            'created_at' => $pm->created_at?->toISOString(),
            'updated_at' => $pm->updated_at?->toISOString(),
        ];
    }
}
