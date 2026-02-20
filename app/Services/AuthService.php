<?php
declare(strict_types=1);

namespace App\Services;

use App\DTOs\Auth\LoginData;
use App\Enums\RoleName;
use App\Models\User;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{DB, Hash, Log};
use Illuminate\Validation\ValidationException;

/**
 * AuthService.
 *
 * Layanan otentikasi untuk aplikasi mobile Marketing.
 *
 * @author
 * @package Services
 */
class AuthService
{
    use RetryableTransactionsTrait;

    /**
     * Login khusus pengguna dengan Role Marketing.
     *
     * @param LoginData $data Kredensial login.
     * @return array{token: string, user: User}
     *
     * @throws ValidationException Bila kredensial tidak valid.
     * @throws AuthorizationException Bila role tidak diizinkan.
     */
    public function loginMarketing(LoginData $data): array
    {
        $user = $this->findByIdentifier($data->login);
        if (!$user || !Hash::check($data->password, (string) $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Email/Username/WhatsApp atau password salah',
            ]);
        }
        $roleName = $user->role?->name;
        if ($roleName !== RoleName::Marketing->value) {
            throw new AuthorizationException('Akun bukan Marketing');
        }

        return $this->runWithRetry(function () use ($user, $data) {
            try {
                return DB::transaction(function () use ($user, $data) {
                    $token = $user->createToken('mobile-marketing')->plainTextToken;
                    return ['token' => $token, 'user' => $user];
                }, 3);
            } catch (\Throwable $e) {
                Log::error('marketing_login_failed', [
                    'service' => self::class,
                    'action' => 'create_token',
                    'user_id' => (string) $user->getKey(),
                    'identifier' => (string) $data->login,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * @param string $identifier
     * @return User|null
     */
    private function findByIdentifier(string $identifier): ?User
    {
        $login = trim($identifier);
        $normalized = mb_strtolower($login);

        return User::query()
            ->with(['role'])
            ->where(function ($q) use ($login, $normalized) {
                $q->where('email', $login)
                    ->orWhereRaw('LOWER(email) = ?', [$normalized])
                    ->orWhere('phone_number', $login)
                    ->orWhereRaw('LOWER(phone_number) = ?', [$normalized])
                    ->orWhere('name', $login)
                    ->orWhereRaw('LOWER(name) = ?', [$normalized]);
            })
            ->first();
    }
}
