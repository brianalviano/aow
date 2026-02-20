<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $service = new ShiftService();

        $offName = $service->generateName(null, null, true);
        $off = Shift::updateOrCreate(
            ['name' => $offName, 'is_off' => true],
            ['is_overnight' => false, 'start_time' => null, 'end_time' => null, 'color' => null]
        );
        $service->update($off, new \App\DTOs\Shift\ShiftData(null, null, false, true));

        $pairs = [
            ['07:00:00', '21:00:00'],
        ];

        $seen = [];
        foreach ($pairs as [$start, $end]) {
            $key = $start . '-' . $end;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            [$sh, $sm] = explode(':', $start);
            [$eh, $em] = explode(':', $end);
            $isOvernight = ((int) $eh * 60 + (int) $em) < ((int) $sh * 60 + (int) $sm);
            $name = $service->generateName($start, $end, false);
            $model = Shift::updateOrCreate(
                ['name' => $name, 'is_off' => false],
                ['start_time' => $start, 'end_time' => $end, 'is_overnight' => $isOvernight, 'color' => null]
            );
            $service->update($model, new \App\DTOs\Shift\ShiftData($start, $end, $isOvernight, false));
        }
    }
}
