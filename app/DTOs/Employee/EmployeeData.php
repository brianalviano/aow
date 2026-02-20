<?php

namespace App\DTOs\Employee;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UpdateMyProfileRequest;
use Illuminate\Http\UploadedFile;

class EmployeeData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $username,
        public ?string $password,
        public ?string $pin,
        public ?string $roleId,
        public ?string $joinDate,
        public ?string $phoneNumber,
        public ?string $address,
        public ?string $birthDate,
        public ?string $gender,
        public ?UploadedFile $ktpPhoto,
    ) {}

    public static function fromStoreRequest(StoreEmployeeRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            username: (string) $p['username'],
            password: (string) $p['password'],
            pin: (string) $p['pin'],
            roleId: $p['role_id'] ?? null,
            joinDate: $p['join_date'] ?? null,
            phoneNumber: $p['phone_number'] ?? null,
            address: $p['address'] ?? null,
            birthDate: $p['birth_date'] ?? null,
            gender: $p['gender'] ?? null,
            ktpPhoto: $request->file('photo'),
        );
    }

    public static function fromUpdateRequest(UpdateEmployeeRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            username: (string) $p['username'],
            password: !empty($p['password']) ? (string) $p['password'] : null,
            pin: !empty($p['pin']) ? (string) $p['pin'] : null,
            roleId: $p['role_id'] ?? null,
            joinDate: $p['join_date'] ?? null,
            phoneNumber: $p['phone_number'] ?? null,
            address: $p['address'] ?? null,
            birthDate: $p['birth_date'] ?? null,
            gender: $p['gender'] ?? null,
            ktpPhoto: $request->file('photo'),
        );
    }

    public static function fromMyProfileRequest(UpdateMyProfileRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            username: (string) $p['username'],
            password: !empty($p['password']) ? (string) $p['password'] : null,
            pin: null,
            roleId: null,
            joinDate: null,
            phoneNumber: $p['phone_number'] ?? null,
            address: $p['address'] ?? null,
            birthDate: $p['birth_date'] ?? null,
            gender: $p['gender'] ?? null,
            ktpPhoto: null,
        );
    }

    public static function fromArrayMyProfile(array $p): self
    {
        return new self(
            name: (string) ($p['name'] ?? ''),
            email: (string) ($p['email'] ?? ''),
            username: (string) ($p['username'] ?? ''),
            password: array_key_exists('password', $p) && $p['password'] !== null && $p['password'] !== '' ? (string) $p['password'] : null,
            pin: null,
            roleId: null,
            joinDate: null,
            phoneNumber: array_key_exists('phone_number', $p) ? ($p['phone_number'] !== null && $p['phone_number'] !== '' ? (string) $p['phone_number'] : null) : null,
            address: array_key_exists('address', $p) ? ($p['address'] !== null && $p['address'] !== '' ? (string) $p['address'] : null) : null,
            birthDate: array_key_exists('birth_date', $p) ? ($p['birth_date'] !== null && $p['birth_date'] !== '' ? (string) $p['birth_date'] : null) : null,
            gender: array_key_exists('gender', $p) ? ($p['gender'] !== null && $p['gender'] !== '' ? (string) $p['gender'] : null) : null,
            ktpPhoto: null,
        );
    }
}
