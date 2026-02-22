<?php

declare(strict_types=1);

namespace App\DTOs\DropPoint;

use App\Http\Requests\Admin\DropPoint\StoreDropPointRequest;
use App\Http\Requests\Admin\DropPoint\UpdateDropPointRequest;

/**
 * Data Transfer Object for DropPoint.
 */
class DropPointData
{
    public function __construct(
        public readonly string $name,
        public readonly string $address,
        public readonly ?string $phone,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $picName,
        public readonly ?string $picPhone,
        public readonly bool $isActive = true,
        public readonly int $deliveryFee = 0,
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromStoreRequest(StoreDropPointRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            address: (string) $request->validated('address'),
            phone: $request->validated('phone') === null ? null : (string) $request->validated('phone'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            picName: $request->validated('pic_name') === null ? null : (string) $request->validated('pic_name'),
            picPhone: $request->validated('pic_phone') === null ? null : (string) $request->validated('pic_phone'),
            isActive: (bool) $request->validated('is_active', true),
            deliveryFee: (int) $request->validated('delivery_fee', 0),
        );
    }

    /**
     * Create DTO from Update Form Request.
     */
    public static function fromUpdateRequest(UpdateDropPointRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            address: (string) $request->validated('address'),
            phone: $request->validated('phone') === null ? null : (string) $request->validated('phone'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            picName: $request->validated('pic_name') === null ? null : (string) $request->validated('pic_name'),
            picPhone: $request->validated('pic_phone') === null ? null : (string) $request->validated('pic_phone'),
            isActive: (bool) $request->validated('is_active', true),
            deliveryFee: (int) $request->validated('delivery_fee', 0),
        );
    }
}
