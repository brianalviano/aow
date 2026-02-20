<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Customer $c */
        $c = $this->resource;
        $mLat = null;
        $mLong = null;
        $latParam = $request->input('lat', $request->input('latitude'));
        $longParam = $request->input('long', $request->input('longitude'));
        if ($latParam !== null && $longParam !== null) {
            $mLat = (float) $latParam;
            $mLong = (float) $longParam;
        } elseif ($request->user()) {
            $mLat = $request->user()->latitude !== null ? (float) $request->user()->latitude : null;
            $mLong = $request->user()->longitude !== null ? (float) $request->user()->longitude : null;
        }
        $distanceLabel = null;
        if ($c->latitude !== null && $c->longitude !== null && $mLat !== null && $mLong !== null) {
            $lat1 = (float) $c->latitude;
            $lon1 = (float) $c->longitude;
            $lat2 = (float) $mLat;
            $lon2 = (float) $mLong;
            $p = 0.017453292519943295;
            $a = 0.5 - cos(($lat2 - $lat1) * $p) / 2 + cos($lat1 * $p) * cos($lat2 * $p) * (1 - cos(($lon2 - $lon1) * $p)) / 2;
            $km = 12742 * asin(sqrt($a));
            $m = $km * 1000;
            $distanceLabel = $m < 1000 ? (string) round($m) . ' M' : (string) round($km, 2) . ' KM';
        }

        return [
            'id' => (string) $c->id,
            'name' => (string) $c->name,
            'email' => (string) $c->email,
            'phone' => $c->phone ? (string) $c->phone : null,
            'address' => $c->address ? (string) $c->address : null,
            'latitude' => $c->latitude !== null ? (float) $c->latitude : null,
            'longitude' => $c->longitude !== null ? (float) $c->longitude : null,
            'distance' => $distanceLabel,
            'is_active' => (bool) $c->is_active,
            'is_visible_in_pos' => (bool) $c->is_visible_in_pos,
            'is_visible_in_marketing' => (bool) $c->is_visible_in_marketing,
            'source' => $c->source instanceof \App\Enums\CustomerSource ? $c->source->value : (string) $c->source,
            'source_label' => \App\Enums\CustomerSource::tryFrom(
                $c->source instanceof \App\Enums\CustomerSource ? $c->source->value : (string) $c->source
            )?->label(),
            'created_by_id' => $c->created_by_id ? (string) $c->created_by_id : null,
            'created_by_name' => $c->relationLoaded('createdBy') && $c->createdBy ? (string) $c->createdBy->name : null,
            'marketer_ids' => $c->relationLoaded('marketers') ? array_map(
                fn($u) => (string) $u->id,
                $c->marketers->all()
            ) : null,
            'marketers' => $c->relationLoaded('marketers') ? array_map(
                fn($u) => ['id' => (string) $u->id, 'name' => (string) $u->name],
                $c->marketers->all()
            ) : null,
            'last_transaction_at' => $c->last_transaction_at ? $c->last_transaction_at->toDateTimeString() : null,
            'created_at' => $c->created_at ? $c->created_at->toDateTimeString() : null,
            'updated_at' => $c->updated_at ? $c->updated_at->toDateTimeString() : null,
        ];
    }
}
