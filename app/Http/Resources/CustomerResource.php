<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drop_point_id' => $this->drop_point_id,
            'name' => $this->name,
            'username' => $this->username,
            'phone' => $this->phone,
            'address' => $this->address,
            'email' => $this->email,
            'school_class' => $this->school_class,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'drop_point' => new DropPointResource($this->whenLoaded('dropPoint')),
        ];
    }
}
