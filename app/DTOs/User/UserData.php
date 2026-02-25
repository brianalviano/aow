<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;

/**
 * Data Transfer Object for User information.
 */
class UserData
{
    public function __construct(
        public string $name,
        public string $username,
        public string $email,
        public string $roleId,
        public ?string $password = null,
        public ?string $phone = null,
    ) {}

    /**
     * Create UserData from store request.
     *
     * @param StoreUserRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreUserRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: (string) $validated['name'],
            username: (string) $validated['username'],
            email: (string) $validated['email'],
            roleId: (string) $validated['role_id'],
            password: (string) $validated['password'],
            phone: $validated['phone'] ?? null,
        );
    }

    /**
     * Create UserData from update request.
     *
     * @param UpdateUserRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateUserRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: (string) $validated['name'],
            username: (string) $validated['username'],
            email: (string) $validated['email'],
            roleId: (string) $validated['role_id'],
            password: !empty($validated['password']) ? (string) $validated['password'] : null,
            phone: $validated['phone'] ?? null,
        );
    }
}
