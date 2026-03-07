<?php

declare(strict_types=1);

namespace App\DTOs\Chef;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for Chef login operations.
 *
 * @property string $login Email or Phone of the chef
 * @property string $password Chef password
 * @property bool $remember Whether to persist session
 */
class LoginChefDTO extends Data
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
