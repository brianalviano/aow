<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCondition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductConditionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $rows = [
                ['name' => 'Baru', 'is_active' => true],
                ['name' => 'Bekas', 'is_active' => true],
            ];

            foreach ($rows as $data) {
                ProductCondition::query()->updateOrCreate(
                    ['name' => $data['name']],
                    $data
                );
            }
        });
    }
}

