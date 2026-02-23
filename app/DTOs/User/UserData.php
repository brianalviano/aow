<?php

namespace App\DTOs\User;

use App\Http\Requests\User\UpdateUserRequest;

class UserData
{
    public function __construct(
        public string $email,
        public ?string $password,
        public ?string $phoneNumber,
    ) {}

    public static function fromUpdateRequest(UpdateUserRequest $request): self
    {
        $p = $request->validated();
        return new self(
            email: (string) $p['email'],
            password: !empty($p['password']) ? (string) $p['password'] : null,
            phoneNumber: $p['phone'] ?? null,
        );
    }
}
