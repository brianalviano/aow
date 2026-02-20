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
            RoleSeeder::class,
            ShiftSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            AccountingSeeder::class,
            ValueAddedTaxSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            PaymentMethodSeeder::class,
            WarehouseSeeder::class,
            ProductCategorySeeder::class,
            ProductSubCategorySeeder::class,
            ProductUnitSeeder::class,
            ProductFactorySeeder::class,
            ProductSubFactorySeeder::class,
            ProductConditionSeeder::class,
            ProductSeeder::class,
            ProductPriceSeeder::class,
            StockSeeder::class,
            // PurchaseOrderSeeder::class,
            // StockTransferSeeder::class,
            // StockOpnameSeeder::class,
            DiscountSeeder::class,
            VoucherSeeder::class,
            SalesSeeder::class,
        ]);
    }
}
