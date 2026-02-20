<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'site_name' => 'Prima Jaya Diesel',
            'contact_email' => 'primajayadiesel@gmail.com',
            'whatsapp_number' => '085385656513',
            'address' => 'Jl. Raya Kediri No. 01, Sentono, Patihan, Loceret, Kabupaten Nganjuk, Jawa Timur 64471',
            'latitude' => '-7.6805712',
            'longitude' => '111.9239886',
            'bank_name' => 'Bank BCA',
            'bank_account_name' => 'Prima Jaya Disel',
            'bank_account_number' => '9999999999',
        ]);
    }
}
