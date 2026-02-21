<?php

namespace App\Services;

use App\DTOs\Setting\SettingData;
use App\Models\Setting;
use App\Traits\{RetryableTransactionsTrait, FileHelperTrait};
use Illuminate\Support\Facades\{DB, Cache};

class SettingService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    public function update(SettingData $data): Setting
    {
        return $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $s = CompanyProfile::query()->first() ?? new Setting();
                $s->site_name = $data->siteName;
                $s->contact_email = $data->contactEmail;
                $s->whatsapp_number = $data->whatsappNumber;
                $s->address = $data->address;
                $s->latitude = $data->latitude;
                $s->longitude = $data->longitude;
                $s->bank_name = $data->bankName;
                $s->bank_account_name = $data->bankAccountName;
                $s->bank_account_number = $data->bankAccountNumber;

                // if ($data->logo) {
                //     $existingPublicPath = $s->logo ? '/storage/' . ltrim((string) $s->logo, '/') : null;
                //     $stored = $this->handleFileUpload($data->logo, $existingPublicPath, 'logos');
                //     $s->logo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                // }

                $s->save();

                Cache::forget('settings:shared');
                Cache::forget('settings:geofence-center');

                return $s;
            }, 5);
        }, 3);
    }
}
