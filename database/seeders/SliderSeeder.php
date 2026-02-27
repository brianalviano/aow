<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sliders = [
            [
                'name' => 'Tentukan makan siang mu besok mulai dari sekarang',
                'photo' => 'sliders/promo_preorder.png',
            ],
            [
                'name' => 'Harga hemat, rasa nikmat',
                'photo' => 'sliders/promo_hemat.png',
            ],
            [
                'name' => 'Order barengan lebih nyaman',
                'photo' => 'sliders/promo_barengan.png',
            ],
            [
                'name' => 'Hidangan tepat di acara istimewamu',
                'photo' => 'sliders/promo_istimewa.png',
            ],
        ];

        foreach ($sliders as $slider) {
            Slider::updateOrCreate(
                ['name' => $slider['name']],
                ['photo' => $slider['photo']]
            );
        }
    }
}
