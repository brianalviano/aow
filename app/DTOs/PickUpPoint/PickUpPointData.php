<?php

declare(strict_types=1);

namespace App\DTOs\PickUpPoint;

use App\Http\Requests\Admin\PickUpPoint\StorePickUpPointRequest;
use App\Http\Requests\Admin\PickUpPoint\UpdatePickUpPointRequest;

/**
 * Data Transfer Object for PickUpPoint.
 */
class PickUpPointData
{
    public function __construct(
        public readonly string $name,
        public readonly string $address,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $description,
        public readonly bool $isActive = true,
        public readonly array $officerIds = [],
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromStoreRequest(StorePickUpPointRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            address: (string) $request->validated('address'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            description: $request->validated('description') === null ? null : (string) $request->validated('description'),
            isActive: (bool) $request->validated('is_active', true),
            officerIds: (array) $request->validated('officer_ids', []),
        );
    }

    /**
     * Create DTO from Update Form Request.
     */
    public static function fromUpdateRequest(UpdatePickUpPointRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            address: (string) $request->validated('address'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            description: $request->validated('description') === null ? null : (string) $request->validated('description'),
            isActive: (bool) $request->validated('is_active', true),
            officerIds: (array) $request->validated('officer_ids', []),
        );
    }
}
