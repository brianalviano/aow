<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DropPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $dropPoints = DropPoint::all();

        if ($dropPoints->isEmpty()) {
            $this->command->warn('No drop points found. Skipping CustomerSeeder.');
            return;
        }

        $password = Hash::make('password');

        for ($i = 0; $i < 20; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $fullName = $firstName . ' ' . $lastName;
            $username = strtolower($firstName) . $faker->randomNumber(3, true);

            Customer::create([
                'drop_point_id' => $dropPoints->random()->id,
                'name'          => $fullName,
                'username'      => $username,
                'phone'         => $faker->phoneNumber(),
                'address'       => $faker->address(),
                'email'         => $faker->unique()->safeEmail(),
                'password'      => $password,
                'school_class'  => $faker->randomElement(['X', 'XI', 'XII']) . ' ' . $faker->randomElement(['RPL', 'TKJ', 'Multimedia', 'Akuntansi']),
                'is_active'     => $faker->boolean(90), // 90% chance of being active
            ]);
        }
    }
}
