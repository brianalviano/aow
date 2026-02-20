<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk model Discount.
 *
 * Menyajikan data diskon beserta target item bila relasi dimuat.
 */
final class DiscountResource extends JsonResource
{
    /**
     * Konversi model Discount ke bentuk array untuk API/props Inertia.
     *
     * @param mixed $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $d = $this->resource;

        return [
            'id' => (string) $d->id,
            'name' => (string) $d->name,
            'type' => (string) $d->type,
            'scope' => (string) $d->scope,
            'value_type' => $d->value_type !== null ? (string) $d->value_type : null,
            'value' => $d->value !== null ? (string) $d->value : null,
            'start_at' => $d->start_at?->toIsoString(),
            'end_at' => $d->end_at?->toIsoString(),
            'is_active' => (bool) $d->is_active,
            'created_at' => $d->created_at?->toIsoString(),
            'updated_at' => $d->updated_at?->toIsoString(),
            'items' => $d->relationLoaded('items')
                ? array_map(static function ($it): array {
                    $target = null;
                    $type = (string) ($it->itemable_type ?? '');
                    if ($it->relationLoaded('itemable') && $it->itemable) {
                        $target = [
                            'id' => (string) $it->itemable->id,
                            'name' => (string) ($it->itemable->name ?? ''),
                            'type' => $type,
                        ];
                    }
                    return [
                        'id' => (string) $it->id,
                        'min_qty_buy' => (int) ($it->min_qty_buy ?? 0),
                        'is_multiple' => (bool) ($it->is_multiple ?? false),
                        'free_product_id' => $it->free_product_id !== null ? (string) $it->free_product_id : null,
                        'free_qty_get' => (int) ($it->free_qty_get ?? 0),
                        'custom_value' => $it->custom_value !== null ? (string) $it->custom_value : null,
                        'itemable_type' => $type,
                        'itemable' => $target,
                    ];
                }, $d->items->all())
                : null,
        ];
    }
}
