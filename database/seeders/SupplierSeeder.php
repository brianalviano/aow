<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $suppliers = [
                [
                    'name' => 'PT Sumber Makmur',
                    'email' => 'contact@sumbermakmur.co.id',
                    'phone' => '0215551001',
                    'address' => 'Jl. Pangeran Antasari No. 8, Jakarta',
                    'photo' => null,
                    'birth_date' => null,
                    'gender' => null,
                    'is_active' => true,
                ],
                [
                    'name' => 'CV Maju Bersama',
                    'email' => 'admin@majubersama.id',
                    'phone' => '0227772002',
                    'address' => 'Jl. Asia Afrika No. 12, Bandung',
                    'photo' => null,
                    'birth_date' => null,
                    'gender' => null,
                    'is_active' => true,
                ],
                [
                    'name' => 'PT Sejahtera Abadi',
                    'email' => 'halo@sejahteraabadi.co',
                    'phone' => '0318883003',
                    'address' => 'Jl. Tunjungan No. 1, Surabaya',
                    'photo' => null,
                    'birth_date' => null,
                    'gender' => null,
                    'is_active' => true,
                ],
            ];

            foreach ($suppliers as $data) {
                Supplier::query()->updateOrCreate(
                    $data
                );
            }
        });
    }
}
