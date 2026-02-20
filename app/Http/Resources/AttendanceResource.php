<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileHelperTrait;

/**
 * Resource untuk menampilkan data absensi ke frontend.
 *
 * @property-read Attendance $resource
 */
class AttendanceResource extends JsonResource
{
    use FileHelperTrait;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $a = $this->resource;
        $schedule = $a->schedule;
        $shift = $schedule?->shift;

        $lateInfo = null;
        if ($a->status->value === 'late' && $a->check_in_at && $schedule?->date && $shift?->start_time) {
            $shiftStart = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $shift->start_time);
            if ($a->check_in_at->greaterThan($shiftStart)) {
                $diff = $a->check_in_at->diff($shiftStart);
                $parts = [];
                if ($diff->h > 0) $parts[] = $diff->h . ' jam';
                if ($diff->i > 0) $parts[] = $diff->i . ' menit';
                if ($diff->s > 0) $parts[] = $diff->s . ' detik';
                $lateInfo = implode(' ', $parts);
            }
        }

        return [
            'id' => (string) $a->id,
            'check_in_at' => self::formatDateTime($a->check_in_at),
            'check_out_at' => self::formatDateTime($a->check_out_at),
            'status' => [
                'value' => (string) $a->status->value,
                'label' => $a->status->label(),
            ],
            'late_duration' => (int) ($a->late_duration ?? 0),
            'late_info' => $lateInfo,
            'check_in_notes' => $a->check_in_notes !== null ? (string) $a->check_in_notes : null,
            'check_out_notes' => $a->check_out_notes !== null ? (string) $a->check_out_notes : null,
            'check_in' => [
                'lat' => $a->check_in_lat !== null ? (float) $a->check_in_lat : null,
                'long' => $a->check_in_long !== null ? (float) $a->check_in_long : null,
                'photo_url' => $this->getFileUrl($a->check_in_photo),
            ],
            'check_out' => [
                'lat' => $a->check_out_lat !== null ? (float) $a->check_out_lat : null,
                'long' => $a->check_out_long !== null ? (float) $a->check_out_long : null,
                'photo_url' => $this->getFileUrl($a->check_out_photo),
            ],
            'schedule' => $schedule ? [
                'id' => (string) $schedule->id,
                'date' => $schedule->date?->toDateString(),
                'shift' => $shift ? [
                    'id' => (string) $shift->id,
                    'name' => (string) $shift->name,
                    'start_time' => $shift->start_time ? substr((string) $shift->start_time, 0, 5) : null,
                    'end_time' => $shift->end_time ? substr((string) $shift->end_time, 0, 5) : null,
                    'is_overnight' => (bool) $shift->is_overnight,
                    'is_off' => (bool) $shift->is_off,
                ] : null,
            ] : null,
        ];
    }

    /**
     * Format nilai tanggal-waktu campuran menjadi string 'Y-m-d H:i:s'.
     *
     * @param mixed $value
     * @return string|null
     */
    private static function formatDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_int($value)) {
            return Carbon::createFromTimestamp($value)
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString();
        }

        if (is_string($value)) {
            $v = trim($value);
            if ($v === '') {
                return null;
            }
            if (ctype_digit($v)) {
                return Carbon::createFromTimestamp((int) $v)
                    ->setTimezone(config('app.timezone'))
                    ->toDateTimeString();
            }
            // Parse as standard date string in app timezone
            return Carbon::parse($v)
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString();
        }

        return null;
    }
}
