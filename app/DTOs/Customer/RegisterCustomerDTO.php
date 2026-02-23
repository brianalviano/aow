<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

readonly class RegisterCustomerDTO
{
    public function __construct(
        public string $name,
        public ?string $username,
        public string $phone,
        public ?string $address,
        public ?string $email,
        public string $password,
        public string $drop_point_id,
        public ?string $school_class = null,
    ) {}
}
