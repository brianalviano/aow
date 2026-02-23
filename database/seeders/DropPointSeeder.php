<?php

namespace Database\Seeders;

use App\Models\DropPoint;
use Illuminate\Database\Seeder;

class DropPointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dropPoints = [
            [
                'name' => 'SMP Negeri 9 Surabaya',
                'address' => 'Jl. Taman Putro Agung No.1, Rangkah, Kec. Tambaksari, Surabaya, Jawa Timur 60135',
                'phone' => null,
                'latitude' => -7.25,
                'longitude' => 112.7653,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
            [
                'name' => 'Burningroom Technology',
                'address' => 'Jl. Nginden Semolo No.42 Blok B-23, Nginden Jangkungan, Kec. Sukolilo, Surabaya, Jawa Timur 60118',
                'phone' => null,
                'latitude' => -7.300405356390868,
                'longitude' => 112.76629877511692,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
            [
                'name' => 'SMP Negeri 23 Surabaya',
                'address' => 'Jl. Kedung Baruk Bar. No.1, Kedung Baruk, Kec. Rungkut, Surabaya, Jawa Timur 60298',
                'phone' => null,
                'latitude' => -7.3199,
                'longitude' => 112.7778,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 5000,
            ],
            [
                'name' => 'SMK Negeri 1 Surabaya',
                'address' => 'Jl. Smea No.4, Wonokromo, Kec. Wonokromo, Surabaya, Jawa Timur 60243',
                'phone' => null,
                'latitude' => -7.305282,
                'longitude' => 112.7340030,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 5000,
            ],
            [
                'name' => 'SMK Negeri 10 Surabaya',
                'address' => 'Jl. Keputih Tegal No.54, Keputih, Kec. Sukolilo, Surabaya, Jawa Timur 60111',
                'phone' => null,
                'latitude' => -7.30751,
                'longitude' => 112.79447,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 5000,
            ],
            [
                'name' => 'SMK Negeri 2 Surabaya',
                'address' => 'Jl. Tentara Genie Pelajar No.26, Petemon, Kec. Sawahan, Surabaya, Jawa Timur 60252',
                'phone' => null,
                'latitude' => -7.25807,
                'longitude' => 112.72523,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
            [
                'name' => 'SMK Negeri 5 Surabaya',
                'address' => 'Jl. Prof. DR. Moestopo No.167-169, Mojo, Kec. Gubeng, Surabaya, Jawa Timur 60285',
                'phone' => null,
                'latitude' => -7.266546317748208,
                'longitude' => 112.76848125835005,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
            [
                'name' => 'Universitas 17 Agustus 1945 Surabaya',
                'address' => 'Jl. Semolowaru No.45, Menur Pumpungan, Kec. Sukolilo, Surabaya, Jawa Timur 60118',
                'phone' => null,
                'latitude' => -7.298992,
                'longitude' => 112.766742,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
            [
                'name' => 'Universitas Dr. Soetomo',
                'address' => 'Jl. Semolowaru No.84, Menur Pumpungan, Kec. Sukolilo, Surabaya, Jawa Timur 60118',
                'phone' => null,
                'latitude' => -7.298476482573491,
                'longitude' => 112.76575986548858,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
            [
                'name' => 'SMP Negeri 35 Surabaya',
                'address' => 'Jl. Rungkut Asri No.22, Rungkut Kidul, Kec. Rungkut, Surabaya, Jawa Timur 60293',
                'phone' => null,
                'latitude' => -7.3275,
                'longitude' => 112.7728,
                'pic_name' => null,
                'pic_phone' => null,
                'is_active' => true,
                'delivery_fee' => 0,
            ],
        ];

        foreach ($dropPoints as $dropPoint) {
            DropPoint::create($dropPoint);
        }
    }
}
