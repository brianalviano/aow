<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Pic\LoginPicDTO;
use Illuminate\Support\Facades\Auth;

/**
 * Service handling PickUp Point Officer (PIC) authentication.
 *
 * Supports login via email or phone number with automatic normalization.
 */
class PicAuthService
{
    /**
     * Attempt to login a PIC officer.
     *
     * @param LoginPicDTO $dto
     * @return bool
     */
    public function login(LoginPicDTO $dto): bool
    {
        $login = $dto->login;
        $password = $dto->password;
        $remember = $dto->remember;

        $attempt = function (array $fields) use ($password, $remember): bool {
            return Auth::guard('pickup_officer')->attempt(array_merge($fields, ['password' => $password]), $remember);
        };

        // Try login by email
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if ($attempt(['email' => $login, 'is_active' => true])) {
                return true;
            }
        }

        // Try login by phone
        $normalizedPhone = $this->normalizePhone($login);
        if ($normalizedPhone !== '' && $attempt(['phone' => $normalizedPhone, 'is_active' => true])) {
            return true;
        }

        return false;
    }

    /**
     * Normalize phone number to start with '0'.
     *
     * @param string $input
     * @return string
     */
    private function normalizePhone(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === null || $digits === '') {
            return '';
        }
        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            return $digits;
        }
        return '0' . $digits;
    }
}
