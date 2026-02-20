<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Shift\ShiftData;
use App\Models\Shift;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    use RetryableTransactionsTrait;

    public function create(ShiftData $data): Shift
    {
        return $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $shift = new Shift();
                $shift->name = $this->generateName($data->startTime, $data->endTime, $data->isOff);
                if ($data->isOff) {
                    $shift->start_time = null;
                    $shift->end_time = null;
                    $shift->is_overnight = false;
                    $shift->color = null;
                } else {
                    $shift->start_time = $data->startTime;
                    $shift->end_time = $data->endTime;
                    $shift->is_overnight = $this->isOvernight($data->startTime, $data->endTime);
                    $shift->color = $this->autoColorFromStart($data->startTime);
                }
                $shift->is_off = $data->isOff;
                $shift->save();
                return $shift;
            }, 5);
        }, 3);
    }

    public function update(Shift $shift, ShiftData $data): Shift
    {
        return $this->runWithRetry(function () use ($shift, $data) {
            return DB::transaction(function () use ($shift, $data) {
                $shift->name = $this->generateName($data->startTime, $data->endTime, $data->isOff);
                if ($data->isOff) {
                    $shift->start_time = null;
                    $shift->end_time = null;
                    $shift->is_overnight = false;
                    $shift->color = null;
                } else {
                    $shift->start_time = $data->startTime;
                    $shift->end_time = $data->endTime;
                    $shift->is_overnight = $this->isOvernight($data->startTime, $data->endTime);
                    $shift->color = $this->autoColorFromStart($data->startTime);
                }
                $shift->is_off = $data->isOff;
                $shift->save();
                return $shift;
            }, 5);
        }, 3);
    }

    public function delete(Shift $shift): void
    {
        $this->runWithRetry(function () use ($shift) {
            return DB::transaction(function () use ($shift) {
                $shift->delete();
                return null;
            }, 5);
        }, 3);
    }

    private function isOvernight(?string $startTime, ?string $endTime): bool
    {
        if ($startTime === null || $endTime === null) {
            return false;
        }
        [$sh, $sm] = explode(':', $startTime);
        [$eh, $em] = explode(':', $endTime);
        $start = ((int) $sh) * 60 + (int) $sm;
        $end = ((int) $eh) * 60 + (int) $em;
        return $end < $start;
    }

    public function generateName(?string $startTime, ?string $endTime, bool $isOff): string
    {
        if ($isOff) {
            return 'Libur';
        }
        if ($startTime === null) {
            return 'Shift';
        }
        [$sh] = explode(':', $startTime);
        $isOvernight = $this->isOvernight($startTime, $endTime);
        $base = $this->buildBaseName((int) $sh, $isOvernight);
        $exists = Shift::query()->where('name', $base)->exists();
        if (!$exists) {
            return $base;
        }
        $names = Shift::query()
            ->where('name', 'like', $base . '%')
            ->pluck('name')
            ->all();
        $max = 1;
        foreach ($names as $n) {
            $n = (string) $n;
            if ($n === $base) {
                $max = max($max, 1);
                continue;
            }
            $pfx = $base . ' ';
            if (str_starts_with($n, $pfx)) {
                $suffix = substr($n, strlen($pfx));
                $num = (int) $suffix;
                if ($num > 0) {
                    $max = max($max, $num);
                }
            }
        }
        return $base . ' ' . ($max + 1);
    }

    private function buildBaseName(int $startHour, bool $isOvernight): string
    {
        $period = $this->periodFromHour($startHour);
        $name = 'Shift ' . $period;
        if ($isOvernight) {
            $name .= ' Lintas Hari';
        }
        return $name;
    }

    private function periodFromHour(int $h): string
    {
        if ($h >= 0 && $h <= 4) {
            return 'Dini Hari';
        }
        if ($h >= 5 && $h <= 10) {
            return 'Pagi';
        }
        if ($h >= 11 && $h <= 14) {
            return 'Siang';
        }
        if ($h >= 15 && $h <= 17) {
            return 'Sore';
        }
        return 'Malam';
    }

    private function autoColorFromStart(?string $startTime): string
    {
        if ($startTime === null || $startTime === '') {
            return 'green';
        }
        [$sh] = explode(':', $startTime);
        $h = (int) $sh;
        if ($h >= 0 && $h <= 4) {
            return 'purple';
        }
        if ($h >= 5 && $h <= 10) {
            return 'green';
        }
        if ($h >= 11 && $h <= 14) {
            return 'yellow';
        }
        if ($h >= 15 && $h <= 17) {
            return 'orange';
        }
        return 'indigo';
    }
}
