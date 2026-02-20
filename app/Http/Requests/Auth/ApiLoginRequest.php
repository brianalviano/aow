<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

/**
 * API Login Request untuk aplikasi mobile Marketing.
 *
 * @author
 * @package Http\Requests\Auth
 *
 * @property-read string $login
 * @property-read string $password
 */
class ApiLoginRequest extends BaseApiRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
