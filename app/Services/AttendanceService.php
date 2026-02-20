<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Attendance\CheckInData;
use App\DTOs\Attendance\CheckOutData;
use App\Enums\AttendanceStatus;
use App\Enums\RoleName;
use App\Models\{Attendance, Schedule, Setting, User};
use App\Traits\{RetryableTransactionsTrait, FileHelperTrait};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Cache};

class AttendanceService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    public function checkIn(Schedule $schedule, CheckInData $data): Attendance
    {
        $isFlexible = $this->isFlexibleUser((string) $schedule->user_id);
        if (!$isFlexible) {
            $this->assertInsideGeofence($data->lat, $data->long);
        }

        $now = Carbon::now();
        $shiftStart = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . (string) $schedule->shift?->start_time);

        $lateDuration = 0;
        $status = AttendanceStatus::Present->value;
        if (!$isFlexible) {
            if ($shiftStart && $now->greaterThan($shiftStart)) {
                $lateDuration = (int) $now->diffInMinutes($shiftStart);
                if ($lateDuration < 0) {
                    $lateDuration = 0;
                }
                $status = AttendanceStatus::Late->value;
            }
        }

        return $this->runWithRetry(function () use ($schedule, $data, $now, $lateDuration, $status) {
            return DB::transaction(function () use ($schedule, $data, $now, $lateDuration, $status) {
                $openOther = Attendance::query()
                    ->where('user_id', (string) $schedule->user_id)
                    ->whereNotNull('check_in_at')
                    ->whereNull('check_out_at')
                    ->latest()
                    ->first();
                if ($openOther && (string) $openOther->schedule_id !== (string) $schedule->id) {
                    throw new \DomainException('Tidak dapat check-in sebelum menyelesaikan checkout shift sebelumnya');
                }

                if ($schedule->attendance()->exists()) {
                    $existing = $schedule->attendance()->first();
                    if ($existing && $existing->check_in_at !== null) {
                        return $existing;
                    }
                }

                $attendance = new Attendance();
                $attendance->user_id = (string) $schedule->user_id;
                $attendance->schedule_id = (string) $schedule->id;
                $attendance->check_in_at = $now;
                $attendance->check_in_lat = $data->lat;
                $attendance->check_in_long = $data->long;
                $stored = $this->handleFileUpload($data->photo, null, 'attendance_photos');
                $attendance->check_in_photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                $attendance->check_in_notes = $data->notes;
                $attendance->status = $status;
                $attendance->late_duration = (int) max(0, $lateDuration);
                $attendance->save();

                return $attendance;
            }, 3);
        }, 3);
    }

    public function checkOut(string $userId, CheckOutData $data): ?Attendance
    {
        $isFlexible = $this->isFlexibleUser($userId);
        if (!$isFlexible) {
            $this->assertInsideGeofence($data->lat, $data->long);
        }

        return $this->runWithRetry(function () use ($userId, $data) {
            return DB::transaction(function () use ($userId, $data) {
                $attendance = Attendance::query()
                    ->where('user_id', $userId)
                    ->whereNotNull('check_in_at')
                    ->whereNull('check_out_at')
                    ->latest()
                    ->first();

                if (!$attendance) {
                    return null;
                }

                $attendance->load(['schedule.shift']);
                $schedule = $attendance->schedule;
                $shift = $schedule?->shift;
                $isFlexible = $this->isFlexibleUser((string) $attendance->user_id);
                if ($schedule && $shift && $shift->end_time && !$isFlexible) {
                    $end = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . (string) $shift->end_time);
                    if ((bool) $shift->is_overnight) {
                        $end->addDay();
                    }
                    $allowedStart = (clone $end)->subMinutes(15);
                    if (Carbon::now()->lessThan($allowedStart)) {
                        throw new \DomainException('Checkout hanya dapat dimulai 15 menit sebelum jam pulang');
                    }
                }

                $attendance->check_out_at = Carbon::now();
                $attendance->check_out_lat = $data->lat;
                $attendance->check_out_long = $data->long;
                $stored = $this->handleFileUpload($data->photo, null, 'attendance_photos');
                $attendance->check_out_photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                $attendance->check_out_notes = $data->notes;
                $attendance->save();

                return $attendance;
            }, 3);
        }, 3);
    }

    /**
     * Auto checkout untuk karyawan yang sudah check-in ketika izin disetujui.
     * Tidak memerlukan validasi geofence atau foto.
     *
     * @param Attendance $attendance Record kehadiran yang sudah check-in
     * @param Schedule $schedule Jadwal terkait
     * @param string $leaveType Tipe izin (untuk notes)
     * @param Carbon $date Tanggal kehadiran
     * @return Attendance
     */
    public function autoCheckoutForPermit(Attendance $attendance, Schedule $schedule, string $leaveType, Carbon $date): Attendance
    {
        return $this->runWithRetry(function () use ($attendance, $schedule, $leaveType, $date) {
            return DB::transaction(function () use ($attendance, $schedule, $leaveType, $date) {
                // Tentukan waktu checkout (gunakan shift end_time atau waktu sekarang)
                $endTime = $schedule->shift?->end_time ? (string) $schedule->shift->end_time : null;
                $checkOut = $endTime
                    ? Carbon::parse($date->format('Y-m-d') . ' ' . $endTime)
                    : Carbon::now();

                // Update attendance menjadi Permit dengan auto checkout
                $attendance->status = AttendanceStatus::Permit->value;
                $attendance->check_out_at = $checkOut;
                $attendance->check_out_notes = 'Izin ' . $leaveType . ' - Auto checkout';
                $attendance->save();

                return $attendance;
            }, 3);
        }, 3);
    }

    /**
     * Override status keterlambatan menjadi hadir tepat waktu.
     *
     * @param Attendance $attendance
     * @return Attendance
     */
    public function overrideLateToPresent(Attendance $attendance): Attendance
    {
        return $this->runWithRetry(function () use ($attendance) {
            return DB::transaction(function () use ($attendance) {
                if ($attendance->status !== AttendanceStatus::Late) {
                    return $attendance;
                }
                $attendance->status = AttendanceStatus::Present->value;
                $attendance->late_duration = 0;
                $attendance->save();
                return $attendance;
            }, 3);
        }, 3);
    }

    private function assertInsideGeofence(float $lat, float $long): void
    {
        return;

        // if (!app()->environment('production')) {
        //     return;
        // }

        // [$centerLat, $centerLong, $radiusM] = $this->geofence();

        // if ($centerLat === 0.0 && $centerLong === 0.0) {
        //     return;
        // }

        // $effectiveRadius = min($radiusM, 500);
        // $distanceM = $this->distanceInMeters($centerLat, $centerLong, $lat, $long);
        // if ($distanceM > $effectiveRadius) {
        //     throw new \DomainException('Lokasi di luar area geofence');
        // }
    }

    private function isFlexibleUser(string $userId): bool
    {
        $u = User::query()
            ->with(['role'])
            ->find($userId);
        if (!$u) {
            return false;
        }
        $roleName = $u->role?->name;
        if ($roleName === null) {
            return false;
        }
        return (string) $roleName === RoleName::ManagerHR->value;
    }

    /**
     * @return array{0: float, 1: float, 2: int}
     */
    private function geofence(): array
    {
        $gf = (array) config('tomtom.geofence', []);
        $fallbackLat = (float) ($gf['center_lat'] ?? 0);
        $fallbackLong = (float) ($gf['center_long'] ?? 0);
        $radiusM = (int) ($gf['radius_m'] ?? 100);
        $center = Cache::remember('settings:geofence-center', 600, function () {
            return Setting::query()->select(['latitude', 'longitude'])->first();
        });
        $lat = $center?->latitude !== null ? (float) $center->latitude : $fallbackLat;
        $long = $center?->longitude !== null ? (float) $center->longitude : $fallbackLong;
        return [$lat, $long, $radiusM];
    }

    /**
     * Hitung jarak permukaan bumi (Haversine) dalam meter.
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadiusKm * $c * 1000.0;
    }
}
