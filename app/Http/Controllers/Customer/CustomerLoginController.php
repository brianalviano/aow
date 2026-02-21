<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles customer authentication (login / logout) via the `customer` guard.
 *
 * Login supports both email and username as the identifier.
 */
class CustomerLoginController extends Controller
{
    /**
     * Show the customer login form.
     *
     * @return RedirectResponse|Response
     */
    public function show(): RedirectResponse|Response
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        return Inertia::render('Auth/Customer/Login');
    }

    /**
     * Handle a customer authentication attempt.
     *
     * Accepts email or username as the login identifier.
     *
     * @param  Request $request
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $login    = (string) $validated['login'];
        $password = (string) $validated['password'];
        $remember = (bool) ($validated['remember'] ?? false);

        $attempt = fn(array $credentials): bool => Auth::guard('customer')->attempt(
            array_merge($credentials, ['password' => $password, 'is_active' => true]),
            $remember,
        );

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if ($attempt(['email' => $login])) {
                return $this->loginSuccess($request);
            }
        }

        if ($attempt(['username' => $login])) {
            return $this->loginSuccess($request);
        }

        Inertia::flash('toast', [
            'message' => 'Identitas atau kata sandi salah',
            'type'    => 'error',
        ]);

        throw ValidationException::withMessages([
            'login' => 'Identitas atau kata sandi salah',
        ]);
    }

    /**
     * Log the customer out of the application.
     *
     * @param  Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', [
            'message' => 'Berhasil logout',
            'type'    => 'success',
        ]);

        return redirect()->route('customer.login');
    }

    /**
     * Regenerate session and redirect to customer dashboard after successful login.
     *
     * @param  Request $request
     * @return RedirectResponse
     */
    private function loginSuccess(Request $request): RedirectResponse
    {
        $request->session()->regenerate();

        Inertia::flash('toast', [
            'message' => 'Berhasil login',
            'type'    => 'success',
        ]);

        return redirect()->route('customer.dashboard');
    }
}
