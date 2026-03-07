<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for Customer registration.
 *
 * @property string $name Customer's full name
 * @property string|null $username Optional username (unique)
 * @property string $phone Customer's phone number (unique)
 * @property string|null $address Optional address
 * @property string|null $email Optional email (unique)
 * @property string $password Password (min 8, must be confirmed)
 * @property string|null $schoolClass Optional class/school description
 */
class RegisterCustomerDTO extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('nullable', 'string', 'max:255', 'unique:customers,username')]
        public readonly ?string $username,

        #[Rule('required', 'string', 'max:20', 'unique:customers,phone')]
        public readonly string $phone,

        #[Rule('nullable', 'string')]
        public readonly ?string $address,

        #[Rule('nullable', 'string', 'email', 'max:255', 'unique:customers,email')]
        public readonly ?string $email,

        #[Rule('required', 'string', 'min:8', 'confirmed')]
        public readonly string $password,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $schoolClass = null,
    ) {}
}
