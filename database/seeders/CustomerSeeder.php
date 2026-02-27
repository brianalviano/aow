<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash, Log};
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws \Throwable
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        try {
            DB::transaction(function () use ($faker): void {
                $password = Hash::make('password');

                // Seed specific test customers
                $testCustomers = [
                    [
                        'name' => 'Budi Santoso',
                        'username' => 'budi_santoso',
                        'email' => 'budi@example.com',
                        'phone' => '081234567890',
                        'school_class' => 'XII RPL 1',
                    ],
                    [
                        'name' => 'Siti Aminah',
                        'username' => 'siti_aminah',
                        'email' => 'siti@example.com',
                        'phone' => '081234567891',
                        'school_class' => 'XI TKJ 2',
                    ],
                ];

                foreach ($testCustomers as $data) {
                    Customer::query()->updateOrCreate(
                        ['phone' => $data['phone']],
                        array_merge($data, [
                            'password' => $password,
                            'address' => $faker->address(),
                            'is_active' => true,
                        ])
                    );
                }

                // Seed random customers
                for ($i = 0; $i < 20; $i++) {
                    $firstName = $faker->firstName();
                    $lastName = $faker->lastName();
                    $email = $faker->unique()->safeEmail();
                    $phone = $faker->unique()->numerify('08##########');

                    Customer::query()->updateOrCreate(
                        ['phone' => $phone],
                        [
                            'name' => $firstName . ' ' . $lastName,
                            'username' => strtolower($firstName) . $faker->randomNumber(3, true),
                            'email' => $email,
                            'password' => $password,
                            'address' => $faker->address(),
                            'school_class' => $faker->randomElement(['X', 'XI', 'XII']) . ' ' .
                                $faker->randomElement(['RPL', 'TKJ', 'Multimedia', 'Akuntansi']),
                            'is_active' => $faker->boolean(90),
                        ]
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('CustomerSeeder failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
