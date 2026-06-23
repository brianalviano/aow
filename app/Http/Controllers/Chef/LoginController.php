<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\DTOs\Chef\LoginChefDTO;
use App\Http\Controllers\Controller;
use App\Services\ChefAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for chef authentication.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly ChefAuthService $authService,
    ) {}

    /**
     * Show the login form.
     */
    public function show(): Response|RedirectResponse
    {
        if (Auth::guard('chef')->check()) {
            return redirect()->route('chef.dashboard');
        }

        return Inertia::render('Domains/Chef/Auth/Login');
    }

    /**
     * Handle an authentication attempt.
     *
     * @throws ValidationException
     */
    public function login(LoginChefDTO $dto): RedirectResponse
    {
        if ($this->authService->login($dto)) {
            request()->session()->regenerate();

            Inertia::flash('toast', [
                'message' => 'Berhasil login',
                'type' => 'success',
            ]);

            return redirect()->intended(route('chef.dashboard'));
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
     * Log the chef out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('chef')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', [
            'message' => 'Berhasil logout',
            'type' => 'success',
        ]);

        return redirect()->route('chef.login');
    }
}
