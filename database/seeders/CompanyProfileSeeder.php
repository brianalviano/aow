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
        CompanyProfile::updateOrCreate([
            'name'      => 'AOWenak',
        ], [
            'email'     => 'aowenak.official@gmail.com',
            'phone'     => '081330704059',
            'whatsapp'  => '6281330704059',
            'address'   => 'Jl. Medokan Asri Utara IX No.25, Medokan Ayu, Kec. Rungkut, Surabaya, Jawa Timur 60295',
            'instagram' => 'https://instagram.com/aow.enak',
            'facebook'  => null,
            'tiktok'    => 'https://tiktok.com/@aow.enak',
        ]);
    }
}
