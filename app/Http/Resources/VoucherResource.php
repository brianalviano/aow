<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class VoucherResource extends JsonResource
{
    public function toArray($request): array
    {
        $v = $this->resource;

        return [
            'id' => (string) $v->id,
            'code' => (string) $v->code,
            'name' => (string) $v->name,
            'description' => $v->description !== null ? (string) $v->description : null,
            'value_type' => (string) $v->value_type,
            'value' => $v->value !== null ? (string) $v->value : null,
            'min_order_amount' => $v->min_order_amount !== null ? (string) $v->min_order_amount : null,
            'usage_limit' => $v->usage_limit !== null ? (int) $v->usage_limit : null,
            'used_count' => $v->used_count !== null ? (int) $v->used_count : null,
            'max_uses_per_customer' => $v->max_uses_per_customer !== null ? (int) $v->max_uses_per_customer : null,
            'start_at' => $v->start_at?->toIsoString(),
            'end_at' => $v->end_at?->toIsoString(),
            'is_active' => (bool) $v->is_active,
            'created_at' => $v->created_at?->toIsoString(),
            'updated_at' => $v->updated_at?->toIsoString(),
        ];
    }
}

