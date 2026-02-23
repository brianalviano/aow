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
            'name'      => 'AOW Order',
            'email'     => 'aow-order@gmail.com',
            'phone'     => '082335032821',
            'whatsapp'  => '6282335032821',
            'address'   => 'Jl. Medokan Asri Utara IX No.25, Medokan Ayu, Kec. Rungkut, Surabaya, Jawa Timur 60295',
            'instagram' => 'https://instagram.com/aow',
            'facebook'  => 'https://facebook.com/aow',
            'tiktok'    => 'https://tiktok.com/@aow',
        ]);
    }
}
