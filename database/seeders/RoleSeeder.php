<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roleNames = RoleName::values();

            // 1) Roles: updateOrCreate per nama
            foreach ($roleNames as $name) {
                Role::query()->updateOrCreate(
                    ['name' => $name],   // kondisi pencarian
                    ['name' => $name]    // data yang diupdate/diinsert
                );
            }
        });
    }
}
