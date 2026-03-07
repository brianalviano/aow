<?php

declare(strict_types=1);

namespace App\DTOs\CustomerAddress;

use Spatie\LaravelData\{Attributes\Validation\Rule, Data};
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * DTO for customer address creation/update.
 *
 * Handles both authenticated and guest checkout scenarios.
 * Guest users must provide registration details alongside address data.
 */
class CustomerAddressData extends Data
{
    public function __construct(
        #[Rule(['required', 'string', 'max:255'])]
        public readonly string $name,

        #[Rule(['required', 'string'])]
        public readonly string $address,

        #[Rule(['required', 'string', 'max:20'])]
        public readonly string $phone,

        #[Rule(['nullable', 'numeric'])]
        public readonly ?float $latitude = null,

        #[Rule(['nullable', 'numeric'])]
        public readonly ?float $longitude = null,

        #[Rule(['nullable', 'string', 'max:500'])]
        public readonly ?string $note = null,

        public readonly ?string $registerName = null,
        public readonly ?string $registerPhone = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $passwordConfirmation = null,
    ) {}

    /**
     * Dynamic rules: guest checkout requires registration fields.
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $rules = [];

        if (!auth()->guard('customer')->check()) {
            $rules['register_name'] = ['required', 'string', 'max:255'];
            $rules['register_phone'] = ['required', 'string', 'max:20'];
            $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:customers,email'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }
}
