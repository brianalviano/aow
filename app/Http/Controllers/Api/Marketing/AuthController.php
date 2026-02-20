<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\DTOs\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Http\Requests\Auth\{ApiLoginRequest, ForgotPasswordRequest};
use App\Http\Resources\EmployeeResource;
use App\Services\AuthService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use App\Models\User;

/**
 * AuthController.
 *
 * Endpoint login untuk aplikasi mobile Marketing.
 *
 * @author
 * @package Http\Controllers\Api
 */
class AuthController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    /**
     * @param ApiLoginRequest $request
     * @param AuthService $service
     * @return JsonResponse
     */
    public function login(ApiLoginRequest $request, AuthService $service, NotificationService $notificationService): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service, $notificationService) {
            $result = $service->loginMarketing(LoginData::fromRequest($request));
            $stats = $notificationService->getStatsForUser($result['user']);
            return $this->apiResponse('Login berhasil', [
                'token' => (string) $result['token'],
                'token_type' => 'Bearer',
                'user' => EmployeeResource::make($result['user'])->toArray($request),
                'notifications_count' => (int) $stats['unread'],
            ]);
        }, $request, [
            'login' => (string) $request->input('login'),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $email = mb_strtolower(trim((string) $request->input('email')));
            $exists = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists();
            if (!$exists) {
                return $this->apiResponse('Email tidak terdaftar', [
                    'email' => (string) $request->input('email'),
                ], false);
            }
            $status = Password::sendResetLink($request->only('email'));
            if ($status === Password::RESET_LINK_SENT) {
                return $this->apiResponse((string) trans($status), []);
            }
            return $this->apiResponse((string) trans($status), [
                'email' => (string) $request->input('email'),
            ], false);
        }, $request, [
            'email' => (string) $request->input('email'),
        ]);
    }
}
