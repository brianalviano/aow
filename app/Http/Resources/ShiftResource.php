<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Shift $shift */
        $shift = $this->resource;

        $formatTime = static function (?string $t): ?string {
            if ($t === null || $t === '') {
                return null;
            }
            return substr($t, 0, 5);
        };

        return [
            'id' => (string) $shift->id,
            'name' => (string) $shift->name,
            'start_time' => $formatTime($shift->start_time),
            'end_time' => $formatTime($shift->end_time),
            'is_overnight' => (bool) $shift->is_overnight,
            'is_off' => (bool) $shift->is_off,
            'color' => $shift->color ? (string) $shift->color : null,
            'created_at' => $shift->created_at ? $shift->created_at->toDateTimeString() : null,
            'updated_at' => $shift->updated_at ? $shift->updated_at->toDateTimeString() : null,
        ];
    }
}
