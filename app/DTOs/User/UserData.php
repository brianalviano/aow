<?php

declare(strict_types=1);

namespace App\DTOs\User;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for admin User create/update operations.
 *
 * Dynamic unique validation for email/username via rules() override.
 * Authorization is handled by the Controller (not the DTO).
 *
 * @property string $name Nama user
 * @property string $username Username (unique)
 * @property string $email Email (unique)
 * @property string $roleId ID role
 * @property string|null $password Password (required on create, nullable on update)
 * @property string|null $phone Nomor telepon
 */
class UserData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        public readonly string $username,

        public readonly string $email,

        #[Rule('required', 'exists:roles,id')]
        public readonly string $roleId,

        public readonly ?string $password = null,

        #[Rule('nullable', 'string', 'max:20')]
        public readonly ?string $phone = null,
    ) {}

    /**
     * Dynamic rules for unique email/username (ignore current user on update)
     * and password strength validation.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $user = request()->route('user');
        $userId = is_object($user) ? $user->id : $user;

        return [
            'username' => [
                'required',
                'string',
                'max:255',
                $userId
                    ? 'unique:users,username,' . $userId
                    : 'unique:users,username',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $userId
                    ? 'unique:users,email,' . $userId
                    : 'unique:users,email',
            ],
            'password' => [
                $userId ? 'nullable' : 'required',
                'string',
                Password::defaults(),
            ],
        ];
    }
}
