<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use Illuminate\Validation\Rule as ValidationRule;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Customer profile update.
 *
 * Contains dynamic unique validation rules that depend on the authenticated customer ID.
 *
 * @property string $name Customer's full name
 * @property string $phone Customer's phone number
 * @property string|null $username Optional username
 * @property string|null $email Optional email
 * @property string|null $password Optional new password
 * @property string|null $schoolClass Optional class/school description
 */
class UpdateProfileDTO extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        public readonly string $phone,

        public readonly ?string $username = null,

        public readonly ?string $email = null,

        #[Rule('nullable', 'string', 'min:8', 'confirmed')]
        public readonly ?string $password = null,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $schoolClass = null,
    ) {}

    /**
     * Dynamic validation rules for fields requiring unique constraints.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $customerId = auth('customer')->id();

        return [
            'username' => ['nullable', 'string', 'max:255', ValidationRule::unique('customers', 'username')->ignore($customerId)],
            'email' => ['required', 'email', 'max:255', ValidationRule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['required', 'string', 'max:20', ValidationRule::unique('customers', 'phone')->ignore($customerId)],
        ];
    }
}
