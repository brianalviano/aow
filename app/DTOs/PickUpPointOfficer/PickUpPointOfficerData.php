<?php

declare(strict_types=1);

namespace App\DTOs\PickUpPointOfficer;

use App\Http\Requests\Admin\PickUpPointOfficer\StorePickUpPointOfficerRequest;
use App\Http\Requests\Admin\PickUpPointOfficer\UpdatePickUpPointOfficerRequest;

/**
 * Data Transfer Object untuk Pick Up Point Officer.
 */
class PickUpPointOfficerData
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly ?string $password,
        public readonly ?string $pickUpPointId,
        public readonly bool $isActive = true,
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromStoreRequest(StorePickUpPointOfficerRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            phone: (string) $request->validated('phone'),
            email: (string) $request->validated('email'),
            password: (string) $request->validated('password'),
            pickUpPointId: $request->validated('pick_up_point_id') === null ? null : (string) $request->validated('pick_up_point_id'),
            isActive: (bool) $request->validated('is_active', true),
        );
    }

    /**
     * Create DTO from Update Form Request.
     */
    public static function fromUpdateRequest(UpdatePickUpPointOfficerRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            phone: (string) $request->validated('phone'),
            email: (string) $request->validated('email'),
            password: $request->validated('password') === null ? null : (string) $request->validated('password'),
            pickUpPointId: $request->validated('pick_up_point_id') === null ? null : (string) $request->validated('pick_up_point_id'),
            isActive: (bool) $request->validated('is_active', true),
        );
    }
}
