<?php
declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\ApiLoginRequest;

/**
 * DTO Login untuk API Marketing.
 *
 * @author
 * @package DTOs\Auth
 *
 * @property-read string $login
 * @property-read string $password
 */
class LoginData
{
    public function __construct(
        public string $login,
        public string $password,
    ) {}

    public static function fromRequest(ApiLoginRequest $request): self
    {
        $p = $request->validated();
        return new self(
            login: (string) $p['login'],
            password: (string) $p['password'],
        );
    }
}
