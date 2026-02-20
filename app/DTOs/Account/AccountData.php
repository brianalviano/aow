<?php

namespace App\DTOs\Account;

use App\Http\Requests\Account\UpdateAccountRequest;
use Illuminate\Http\UploadedFile;

class AccountData
{
    public function __construct(
        public string $email,
        public ?string $password,
        public ?string $pin,
        public ?string $phoneNumber,
        public ?string $address,
        public ?string $birthDate,
        public ?string $gender,
        public ?UploadedFile $ktpPhoto,
    ) {}

    public static function fromUpdateRequest(UpdateAccountRequest $request): self
    {
        $p = $request->validated();
        return new self(
            email: (string) $p['email'],
            password: !empty($p['password']) ? (string) $p['password'] : null,
            pin: !empty($p['pin']) ? (string) $p['pin'] : null,
            phoneNumber: $p['phone_number'] ?? null,
            address: $p['address'] ?? null,
            birthDate: $p['birth_date'] ?? null,
            gender: $p['gender'] ?? null,
            ktpPhoto: $request->file('photo'),
        );
    }
}
