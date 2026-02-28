<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ChefOrderType;
use App\Models\{Chef, Product};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash, Log};
use Faker\Factory as Faker;

/**
 * Seeds chef mitra data and assigns products to each chef.
 */
class ChefSeeder extends Seeder
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
                $products = Product::all();

                if ($products->isEmpty()) {
                    $this->command->warn('No products found. Skipping ChefSeeder.');
                    return;
                }

                $password = Hash::make('password');
                $banks = ['BCA', 'BRI', 'BNI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Bank Jago'];

                // Seed specific test chefs
                $testChefs = [
                    [
                        'name'          => 'Chef Rani Kusuma',
                        'business_name' => 'Dapur Rani',
                        'email'         => 'rani@example.com',
                        'phone'         => '081300000001',
                    ],
                    [
                        'name'          => 'Chef Dimas Pratama',
                        'business_name' => 'Dimas Catering',
                        'email'         => 'dimas@example.com',
                        'phone'         => '081300000002',
                    ],
                    [
                        'name'          => 'Chef Lestari',
                        'business_name' => 'RM Lestari',
                        'email'         => 'lestari@example.com',
                        'phone'         => '081300000003',
                    ],
                ];

                foreach ($testChefs as $data) {
                    $chef = Chef::query()->updateOrCreate(
                        ['email' => $data['email']],
                        array_merge($data, [
                            'password'       => $password,
                            'bank_name'      => $faker->randomElement($banks),
                            'account_number' => $faker->numerify('##########'),
                            'account_name'   => $data['name'],
                            'fee_percentage' => $faker->randomElement([5, 10, 15]),
                            'address'        => $faker->address(),
                            'longitude'      => $faker->longitude(95, 141),
                            'latitude'       => $faker->latitude(-11, 6),
                            'note'           => null,
                            'is_active'      => true,
                            'order_types'    => [ChefOrderType::INSTANT, ChefOrderType::PREORDER],
                        ])
                    );

                    // Assign 2-4 random products to each test chef
                    $assignCount = min($faker->numberBetween(2, 4), $products->count());
                    $chef->products()->syncWithoutDetaching(
                        $products->random($assignCount)->pluck('id')->toArray()
                    );
                }

                // Seed random chefs
                for ($i = 0; $i < 5; $i++) {
                    $chefName = 'Chef ' . $faker->firstName() . ' ' . $faker->lastName();
                    $email = $faker->unique()->safeEmail();

                    $chef = Chef::query()->updateOrCreate(
                        ['email' => $email],
                        [
                            'name'           => $chefName,
                            'business_name'  => $faker->company(),
                            'phone'          => $faker->unique()->numerify('0813########'),
                            'password'       => $password,
                            'bank_name'      => $faker->randomElement($banks),
                            'account_number' => $faker->numerify('##########'),
                            'account_name'   => $chefName,
                            'fee_percentage' => $faker->randomElement([5, 8, 10, 12, 15, 20]),
                            'address'        => $faker->address(),
                            'longitude'      => $faker->longitude(95, 141),
                            'latitude'       => $faker->latitude(-11, 6),
                            'note'           => $faker->boolean(30) ? $faker->sentence() : null,
                            'is_active'      => $faker->boolean(85),
                            'order_types'    => $faker->randomElement([
                                [ChefOrderType::INSTANT, ChefOrderType::PREORDER],
                                [ChefOrderType::INSTANT],
                                [ChefOrderType::PREORDER],
                            ]),
                        ]
                    );

                    $assignCount = min($faker->numberBetween(1, 3), $products->count());
                    $chef->products()->syncWithoutDetaching(
                        $products->random($assignCount)->pluck('id')->toArray()
                    );
                }

                $this->command->info('ChefSeeder: seeded ' . Chef::count() . ' chefs with product assignments.');
            });
        } catch (\Throwable $e) {
            Log::error('ChefSeeder failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
