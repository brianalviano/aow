<?php

namespace App\DTOs\Setting;

use App\Http\Requests\Setting\UpdateSettingRequest;
use Illuminate\Http\UploadedFile;

class SettingData
{
    public function __construct(
        public string $siteName,
        public ?string $contactEmail,
        public ?string $whatsappNumber,
        public ?string $address,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $bankName,
        public ?string $bankAccountName,
        public ?string $bankAccountNumber,
        // public ?UploadedFile $logo,
    ) {}

    public static function fromUpdateRequest(UpdateSettingRequest $request): self
    {
        $p = $request->validated();
        return new self(
            siteName: (string) $p['site_name'],
            contactEmail: $p['contact_email'] ?? null,
            whatsappNumber: $p['whatsapp_number'] ?? null,
            address: $p['address'] ?? null,
            latitude: $p['latitude'] ?? null,
            longitude: $p['longitude'] ?? null,
            bankName: $p['bank_name'] ?? null,
            bankAccountName: $p['bank_account_name'] ?? null,
            bankAccountNumber: $p['bank_account_number'] ?? null,
            // logo: $request->file('logo'),
        );
    }
}
