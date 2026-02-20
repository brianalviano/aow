<?php

declare(strict_types=1);

namespace App\DTOs\Supplier;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use Illuminate\Http\UploadedFile;

class SupplierData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $birthDate,
        public ?string $gender,
        public bool $isActive,
        public ?UploadedFile $photo,
    ) {}

    public static function fromStoreRequest(StoreSupplierRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            phone: $p['phone'] ?? null,
            address: $p['address'] ?? null,
            birthDate: $p['birth_date'] ?? null,
            gender: $p['gender'] ?? null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
            photo: $request->file('photo'),
        );
    }

    public static function fromUpdateRequest(UpdateSupplierRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            phone: $p['phone'] ?? null,
            address: $p['address'] ?? null,
            birthDate: $p['birth_date'] ?? null,
            gender: $p['gender'] ?? null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
            photo: $request->file('photo'),
        );
    }
}
