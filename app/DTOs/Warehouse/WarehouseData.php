<?php
declare(strict_types=1);

namespace App\DTOs\Warehouse;

use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;

class WarehouseData
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $address,
        public bool $isCentral,
        public ?string $phone,
        public bool $isActive,
    ) {}

    public static function fromStoreRequest(StoreWarehouseRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            code: (string) $p['code'],
            address: $p['address'] ?? null,
            isCentral: isset($p['is_central']) ? (bool) $p['is_central'] : false,
            phone: $p['phone'] ?? null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
        );
    }

    public static function fromUpdateRequest(UpdateWarehouseRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            code: (string) $p['code'],
            address: $p['address'] ?? null,
            isCentral: isset($p['is_central']) ? (bool) $p['is_central'] : false,
            phone: $p['phone'] ?? null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
        );
    }
}

