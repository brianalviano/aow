<?php

namespace App\Http\Resources;

use App\Enums\{LeaveRequestStatus, LeaveRequestType};
use App\Models\LeaveRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileHelperTrait;

class LeaveRequestResource extends JsonResource
{
    use FileHelperTrait;

    public function toArray($request): array
    {
        /** @var LeaveRequest $lr */
        $lr = $this->resource;
        $user = $lr->user;
        return [
            'id' => (string) $lr->id,
            'user' => $user ? [
                'id' => (string) $user->id,
                'name' => (string) $user->name,
            ] : null,
            'start_date' => $lr->start_date?->toDateString(),
            'end_date' => $lr->end_date?->toDateString(),
            'type' => [
                'value' => (string) $lr->type->value,
                'label' => LeaveRequestType::from((string) $lr->type->value)->label(),
            ],
            'reason' => $lr->reason !== null ? (string) $lr->reason : null,
            'status' => [
                'value' => (string) $lr->status->value,
                'label' => LeaveRequestStatus::from((string) $lr->status->value)->label(),
            ],
            'approved_by' => $lr->approved_by !== null ? (string) $lr->approved_by : null,
            'created_at' => $lr->created_at?->toDateTimeString(),
            'updated_at' => $lr->updated_at?->toDateTimeString(),
            'formatted_period' => $lr->start_date && $lr->end_date
                ? ($lr->start_date->isSameDay($lr->end_date)
                    ? $lr->start_date->translatedFormat('d M Y')
                    : $lr->start_date->translatedFormat('d M Y') . ' - ' . $lr->end_date->translatedFormat('d M Y'))
                : null,
        ];
    }
}
