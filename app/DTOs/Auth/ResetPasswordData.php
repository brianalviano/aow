<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use Spatie\LaravelData\{Attributes\Validation\Rule, Data};

/**
 * DTO for password reset request.
 */
class ResetPasswordData extends Data
{
    public function __construct(
        #[Rule(['required', 'string'])]
        public readonly string $token,

        #[Rule(['required', 'email'])]
        public readonly string $email,

        #[Rule(['required', 'string', 'min:8', 'confirmed'])]
        public readonly string $password,

        public readonly ?string $passwordConfirmation = null,
    ) {}
}
