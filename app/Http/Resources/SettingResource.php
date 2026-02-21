<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // the resource is an array with ['company_profile' => Model, 'order_settings' => Collection|array]
        $companyProfile = $this->resource['company_profile'];
        $orderSettings = collect($this->resource['order_settings']);

        $settings = [];

        // Company Profile Fields
        if ($companyProfile) {
            $settings['name'] = $companyProfile->name;
            $settings['email'] = $companyProfile->email;
            $settings['phone'] = $companyProfile->phone;
            $settings['whatsapp'] = $companyProfile->whatsapp;
            $settings['address'] = $companyProfile->address;
            $settings['instagram'] = $companyProfile->instagram;
            $settings['facebook'] = $companyProfile->facebook;
            $settings['tiktok'] = $companyProfile->tiktok;
        }

        // Order Settings Fields
        foreach ($orderSettings as $key => $value) {
            // Convert 'true'/'false' strings to boolean for Svelte bindings
            if ($value === 'true') {
                $settings[$key] = true;
            } elseif ($value === 'false') {
                $settings[$key] = false;
            } else {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }
}
