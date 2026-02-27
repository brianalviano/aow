<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            OrderSettingSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            CompanyProfileSeeder::class,
            DropPointSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            ChefSeeder::class,
            CustomerSeeder::class,
            PaymentGuideSeeder::class,
            PaymentMethodSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
