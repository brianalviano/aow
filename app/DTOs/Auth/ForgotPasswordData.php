<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use Spatie\LaravelData\{Attributes\Validation\Rule, Data};

/**
 * DTO for forgot password (send reset link) request.
 */
class ForgotPasswordData extends Data
{
    public function __construct(
        #[Rule(['required', 'email'])]
        public readonly string $email,
    ) {}
}
