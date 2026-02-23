<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

readonly class LoginCustomerDTO
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember = false,
    ) {}
}
