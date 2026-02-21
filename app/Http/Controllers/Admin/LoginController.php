<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Show the login form.
     * 
     * @return \Inertia\Response
     * 
     */
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Auth/Admin/Login');
    }

    /**
     * Handle an authentication attempt.
     * 
     * @param Request $request The request.
     * 
     * @return \Illuminate\Http\RedirectResponse
     * 
     * @throws ValidationException
     * 
     */
    public function authenticate(Request $request)
    {
        $p = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $login = (string) $p['login'];
        $password = (string) $p['password'];
        $remember = (bool) ($p['remember'] ?? $request->boolean('remember'));

        $attempt = function (array $fields) use ($password, $remember): bool {
            return Auth::attempt(array_merge($fields, ['password' => $password]), $remember);
        };

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if ($attempt(['email' => $login])) {
                $request->session()->regenerate();
                Inertia::flash('toast', [
                    'message' => 'Berhasil login',
                    'type' => 'success',
                ]);
                return redirect()->route('admin.dashboard');
            }
        }

        if ($attempt(['username' => $login])) {
            $request->session()->regenerate();
            Inertia::flash('toast', [
                'message' => 'Berhasil login',
                'type' => 'success',
            ]);
            return redirect()->route('dashboard');
        }

        $normalizedPhone = $this->normalizePhone($login);
        if ($normalizedPhone !== '' && $attempt(['phone_number' => $normalizedPhone])) {
            $request->session()->regenerate();
            Inertia::flash('toast', [
                'message' => 'Berhasil login',
                'type' => 'success',
            ]);
            return redirect()->route('dashboard');
        }

        Inertia::flash('toast', [
            'message' => 'Identitas atau kata sandi salah',
            'type' => 'error',
        ]);
        throw ValidationException::withMessages([
            'login' => 'Identitas atau kata sandi salah',
        ]);
    }

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

    /**
     * Log the user out of the application.
     * 
     * @param Request $request The request.
     * 
     * @return \Illuminate\Http\RedirectResponse
     * 
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', [
            'message' => 'Berhasil logout',
            'type' => 'success',
        ]);
        return redirect()->route('admin.login');
    }
}
