<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PickUpPoint;
use App\Models\PickUpPointOfficer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PickUpPointOfficerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pickUpPoints = PickUpPoint::all();

        if ($pickUpPoints->isEmpty()) {
            return;
        }

        $officers = [
            [
                'name' => 'Nuning Rahmawati, SE',
                'phone' => '081330704059',
                'email' => 'nuning@aow.com',
                'password' => Hash::make('12345678'),
            ],
            [
                'name' => 'Andi Wijaya',
                'phone' => '081987654321',
                'email' => 'andi@aow.com',
                'password' => Hash::make('12345678'),
            ]
        ];

        foreach ($officers as $index => $officerData) {
            $pickUpPoint = $pickUpPoints->get($index);
            if ($pickUpPoint) {
                $officerData['pick_up_point_id'] = $pickUpPoint->id;
                PickUpPointOfficer::create($officerData);
            }
        }
    }
}
