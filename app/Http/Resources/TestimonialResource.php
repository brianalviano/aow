<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TestimonialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both objects (Eloquent models) and arrays (for fake testimonials)
        $data = is_array($this->resource) ? (object) $this->resource : $this->resource;

        return [
            'id'          => $data->id ?? null,
            'customer_id' => $data->customer_id ?? null,
            'order_id'    => $data->order_id ?? null,
            'rating'      => $data->rating ?? null,
            'content'     => $data->content ?? null,
            'photo_url'   => $data->photo ?? ($data->photo_url ?? null),
            'is_approved' => $data->is_approved ?? true,
            'is_fake'     => $data->is_fake ?? false,
            'customer'    => [
                'name' => $data->customer_name ?? ($data->customer?->name ?? 'User'),
            ],
            'created_at'  => isset($data->created_at)
                ? ($data->created_at instanceof \Carbon\Carbon
                    ? $data->created_at->toIso8601String()
                    : (\Carbon\Carbon::parse($data->created_at)->toIso8601String()))
                : now()->toIso8601String(),
        ];
    }
}
