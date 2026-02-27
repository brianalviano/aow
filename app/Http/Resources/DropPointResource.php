<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Traits\FileHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DropPointResource extends JsonResource
{
    use FileHelperTrait;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'photo_url' => $this->getFileUrl($this->photo),
            'address' => $this->address,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'phone' => $this->phone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'pic_name' => $this->pic_name,
            'pic_phone' => $this->pic_phone,
            'is_active' => $this->is_active,
            'delivery_fee' => $this->delivery_fee,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
