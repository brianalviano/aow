<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileHelperTrait;

class SettingResource extends JsonResource
{
    use FileHelperTrait;

    public function toArray(Request $request): array
    {
        /** @var \App\Models\Setting $s */
        $s = $this->resource;

        return [
            'name' => (string) $s->name,
            'contact_email' => $s->contact_email ? (string) $s->contact_email : null,
            'whatsapp_number' => $s->whatsapp_number ? (string) $s->whatsapp_number : null,
            // 'logo_url' => $this->getFileUrl($s->logo),
            'address' => $s->address ? (string) $s->address : null,
            'latitude' => $s->latitude ? (string) $s->latitude : null,
            'longitude' => $s->longitude ? (string) $s->longitude : null,
            'bank_name' => $s->bank_name ? (string) $s->bank_name : null,
            'bank_account_name' => $s->bank_account_name ? (string) $s->bank_account_name : null,
            'bank_account_number' => $s->bank_account_number ? (string) $s->bank_account_number : null,
        ];
    }
}
