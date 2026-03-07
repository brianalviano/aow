<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PickUpPoint;
use Illuminate\Database\Seeder;

/**
 * Class PickUpPointSeeder
 * 
 * Seeds the database with initial pick up points.
 */
class PickUpPointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pickUpPoints = [
            [
                'name' => 'AOW Head Office',
                'address' => 'Jl. Medokan Asri Utara IX No.25, Medokan Ayu, Kec. Rungkut, Surabaya, Jawa Timur 60295',
                'latitude' => -7.322206775241284,
                'longitude' => 112.79465848983943,
                'is_active' => true,
            ],
            [
                'name' => 'Dapur Cabang 1 (Pick Up A)',
                'address' => 'Jl. Cabang No. 2, Jakarta',
                'description' => 'Pick up point untuk area dapur cabang.',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'is_active' => true,
            ],
        ];

        foreach ($pickUpPoints as $point) {
            PickUpPoint::create($point);
        }
    }
}
