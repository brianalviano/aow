<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ValueAddedTax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ValueAddedTaxSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rows = [
                ['percentage' => '11.00', 'is_active' => true],
                ['percentage' => '12.00', 'is_active' => false],
            ];

            foreach ($rows as $data) {
                ValueAddedTax::query()->updateOrCreate(
                    ['percentage' => $data['percentage']],
                    ['is_active' => $data['is_active']]
                );
            }
        });
    }
}
