<?php

declare(strict_types=1);

namespace App\DTOs\Pic;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for PIC (Pickup Point Officer) login operations.
 *
 * @property string $login Email or Phone of the officer
 * @property string $password Officer password
 * @property bool $remember Whether to persist session
 */
class LoginPicDTO extends Data
{
    public function __construct(
        #[Rule('required', 'string')]
        public readonly string $login,

        #[Rule('required', 'string')]
        public readonly string $password,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $remember = false,
    ) {}
}
