<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

readonly class UpdateProfileDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $drop_point_id,
        public ?string $username = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $school_class = null,
    ) {}
}
