<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TestimonialTemplate;
use Illuminate\Database\Seeder;

class ManipulationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'customer_name' => 'Budi Santoso',
                'rating' => 5,
                'content' => 'Makanannya enak banget, porsinya juga pas. Anak-anak suka sekali!',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Siti Aminah',
                'rating' => 5,
                'content' => 'Pengiriman cepat dan masih hangat. Rasa bintang 5 harga kaki lima.',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Agus darmawan',
                'rating' => 4,
                'content' => 'Rasanya mantap, bumbunya meresap sampai ke dalam. Recommended!',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Dewi Lestari',
                'rating' => 5,
                'content' => 'Packaging rapi dan aman. Penjualnya ramah. Bakal pesan lagi sih ini.',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Rizky Pratama',
                'rating' => 5,
                'content' => 'Baru pertama coba dan langsung ketagihan. Mantul bosku!',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Ani Wijaya',
                'rating' => 4,
                'content' => 'Enak, tapi kalau bisa sambalnya dibanyakin lagi ya hehe.',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Hendra Kurniawan',
                'rating' => 5,
                'content' => 'Cita rasa otentik. Mengingatkan masakan ibu di rumah.',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Maya Sari',
                'rating' => 5,
                'content' => 'Sangat puas dengan pelayanannya. Makanannya juga tidak pernah mengecewakan.',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Doni Saputra',
                'rating' => 5,
                'content' => 'Top markotop! Kualitas bahan sangat segar.',
                'is_active' => true,
            ],
            [
                'customer_name' => 'Linda Rahmah',
                'rating' => 4,
                'content' => 'Enak dan bersih. Cocok buat makan siang kantor.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            TestimonialTemplate::create($template);
        }
    }
}
