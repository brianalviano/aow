<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pic;

use App\Http\Controllers\Controller;
use App\DTOs\Pic\LoginPicDTO;
use App\Services\PicAuthService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\{Inertia, Response};

/**
 * Controller for PIC (Pickup Point Officer) authentication.
 */
class LoginController extends Controller
{
    /**
     * @param PicAuthService $authService
     */
    public function __construct(
        private readonly PicAuthService $authService,
    ) {}

    /**
     * Show the login form.
     *
     * @return Response|RedirectResponse
     */
    public function show(): Response|RedirectResponse
    {
        if (Auth::guard('pickup_officer')->check()) {
            return redirect()->route('pic.dashboard');
        }

        return Inertia::render('Domains/Pic/Auth/Login');
    }

    /**
     * Handle an authentication attempt.
     *
     * @param LoginPicDTO $dto
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function login(LoginPicDTO $dto): RedirectResponse
    {
        if ($this->authService->login($dto)) {
            request()->session()->regenerate();

            Inertia::flash('toast', [
                'message' => 'Berhasil login',
                'type' => 'success',
            ]);

            return redirect()->intended(route('pic.dashboard'));
        }

        Inertia::flash('toast', [
            'message' => 'Identitas atau kata sandi salah',
            'type' => 'error',
        ]);

        throw ValidationException::withMessages([
            'login' => 'Identitas atau kata sandi salah',
        ]);
    }

    /**
     * Log the PIC out of the application.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('pickup_officer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', [
            'message' => 'Berhasil logout',
            'type' => 'success',
        ]);

        return redirect()->route('pic.login');
    }
}
