<?php

namespace App\Services;

use App\DTOs\Setting\SettingData;
use App\Models\CompanyProfile;
use App\Models\OrderSetting;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{DB, Cache, Log};
use Throwable;

class SettingService
{
    use RetryableTransactionsTrait;

    public function update(SettingData $data): void
    {
        $this->runWithRetry(function () use ($data) {
            try {
                DB::transaction(function () use ($data) {
                    // 1. Update Company Profile
                    $cp = CompanyProfile::query()->first() ?? new CompanyProfile();
                    $cp->fill($data->companyProfile);
                    $cp->save();

                    // 2. Update Order Settings
                    foreach ($data->orderSettings as $key => $value) {
                        if ($value !== null) {
                            OrderSetting::updateOrCreate(
                                ['key' => $key],
                                ['value' => $value]
                            );
                        }
                    }

                    // Flush cache
                    \App\DTOs\Setting\OrderSettingsDTO::clearCache();
                    Cache::forget('settings:shared');
                    Cache::forget('settings:geofence-center');
                }, 5);
            } catch (Throwable $e) {
                Log::error('SettingService update failed', [
                    'error' => $e->getMessage(),
                    'data' => [
                        'companyProfile' => $data->companyProfile,
                        'orderSettings' => $data->orderSettings,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
