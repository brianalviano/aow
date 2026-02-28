<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chef\LoginChefRequest;
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
    /**
     * @param ChefAuthService $authService
     */
    public function __construct(
        private readonly ChefAuthService $authService,
    ) {}

    /**
     * Show the login form.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
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
     * @param LoginChefRequest $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function login(LoginChefRequest $request): RedirectResponse
    {
        $dto = $request->toDTO();

        if ($this->authService->login($dto)) {
            $request->session()->regenerate();

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
     *
     * @param Request $request
     * @return RedirectResponse
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
