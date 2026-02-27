<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\LoginCustomerRequest;
use App\Http\Requests\Customer\RegisterCustomerRequest;
use App\Services\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
    ) {}

    /**
     * Show the login form.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('home');
        }

        return Inertia::render('Domains/Customer/Auth/Login/Index');
    }

    /**
     * Handle an authentication attempt.
     *
     * @param LoginCustomerRequest $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function login(LoginCustomerRequest $request): RedirectResponse
    {
        $dto = $request->toDTO();

        if ($this->authService->login($dto)) {
            $request->session()->regenerate();

            Inertia::flash('toast', [
                'message' => 'Berhasil login',
                'type' => 'success',
            ]);

            return redirect()->intended(route('home'));
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
     * Show the registration form.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function showRegister(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('home');
        }

        return Inertia::render('Domains/Customer/Auth/Register/Index');
    }

    /**
     * Handle a registration request.
     *
     * @param RegisterCustomerRequest $request
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function register(RegisterCustomerRequest $request): RedirectResponse
    {
        $dto = $request->toDTO();

        $customer = $this->authService->register($dto);

        // Auto-login after successful registration
        Auth::guard('customer')->login($customer);

        $request->session()->regenerate();

        Inertia::flash('toast', [
            'message' => 'Pendaftaran berhasil',
            'type' => 'success',
        ]);

        return redirect()->route('home');
    }

    /**
     * Log the customer out of the application.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', [
            'message' => 'Berhasil logout',
            'type' => 'success',
        ]);

        return redirect()->route('home');
    }
}
