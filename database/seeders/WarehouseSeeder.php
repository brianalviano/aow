<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $warehouses = [
                [
                    'name' => 'Gudang Pusat',
                    'code' => 'CTR',
                    'address' => 'Jl. Gatot Subroto No. 100, Jakarta',
                    'is_central' => true,
                    'phone' => '0211234501',
                    'is_active' => true,
                ],
                [
                    'name' => 'Gudang Barat',
                    'code' => 'WST',
                    'address' => 'Jl. Pasteur No. 50, Bandung',
                    'is_central' => false,
                    'phone' => '0221234502',
                    'is_active' => true,
                ],
                [
                    'name' => 'Gudang Timur',
                    'code' => 'EST',
                    'address' => 'Jl. Ahmad Yani No. 75, Surabaya',
                    'is_central' => false,
                    'phone' => '0311234503',
                    'is_active' => true,
                ],
            ];

            foreach ($warehouses as $data) {
                Warehouse::query()->updateOrCreate(
                    ['code' => $data['code']],
                    $data
                );
            }
        });
    }
}

