<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyProfile::create([
            'name'      => 'AOW',
            'email'     => 'aow@example.com',
            'phone'     => '02112345678',
            'whatsapp'  => '6281234567890',
            'address'   => 'Jl. Contoh No. 1, Jakarta, Indonesia',
            'instagram' => 'https://instagram.com/aow',
            'facebook'  => 'https://facebook.com/aow',
            'tiktok'    => 'https://tiktok.com/@aow',
        ]);
    }
}
