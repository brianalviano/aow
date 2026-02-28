<?php

declare(strict_types=1);

namespace App\DTOs\Chef;

/**
 * Data Transfer Object for Chef login operations.
 */
class LoginChefDTO
{
    /**
     * @param string $login Email or Phone of the chef
     * @param string $password
     * @param bool $remember
     */
    public function __construct(
        public readonly string $login,
        public readonly string $password,
        public readonly bool $remember = false,
    ) {}
}
